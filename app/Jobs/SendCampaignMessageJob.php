<?php

namespace App\Jobs;

use App\Models\CampaignRecipient;
use App\Services\KprimeMessagingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class SendCampaignMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;
    public bool $failOnTimeout = true;

    public function __construct(public int $recipientId) { $this->onQueue('campaigns'); }
    public function backoff(): array { return [60, 300]; }

    public function handle(KprimeMessagingService $messaging): void
    {
        $recipient = CampaignRecipient::with(['campaign.user', 'contact'])->find($this->recipientId);
        if (!$recipient || $recipient->status === 'sent') return;

        DB::transaction(function () use ($recipient): void {
            $locked = CampaignRecipient::whereKey($recipient->id)->lockForUpdate()->first();
            if (!$locked || $locked->status === 'sent') return;
            $locked->update(['status' => 'processing', 'attempts' => $locked->attempts + 1, 'last_attempt_at' => now()]);
        }, 3);

        $recipient->refresh()->load(['campaign.user', 'contact']);
        if ($recipient->status === 'sent') return;
        $campaign = $recipient->campaign;
        $contact = $recipient->contact;
        $user = $campaign->user;
        $quotaField = $campaign->channel === 'sms' ? 'sms_credits' : 'whatsapp_credits';
        if ((int) $user->{$quotaField} < 1) {
            $this->markFailed($recipient, 'Quota '.$campaign->channel.' épuisé.');
            return;
        }

        $response = $messaging->send($campaign->channel, $contact->phone, $campaign->message, $contact->country_code, $campaign->title ?: $campaign->name);
        if (($response['status'] ?? false) !== true) {
            $this->markFailed($recipient, $response['message'] ?? 'Message refusé par le fournisseur.');
            throw new RuntimeException($response['message'] ?? 'Envoi refusé par KPrime.');
        }

        DB::transaction(function () use ($recipient, $campaign, $quotaField, $response): void {
            $locked = CampaignRecipient::whereKey($recipient->id)->lockForUpdate()->firstOrFail();
            if ($locked->status === 'sent') return;
            $consumed = DB::table('users')->where('id', $campaign->user_id)->where($quotaField, '>', 0)->decrement($quotaField);
            if ($consumed !== 1) {
                $locked->update(['status' => 'failed', 'error_message' => 'Quota devenu insuffisant avant la confirmation.']);
                return;
            }
            $locked->update(['status' => 'sent', 'provider_message_id' => $response['message_id'] ?? null, 'error_message' => null, 'sent_at' => now()]);
        }, 3);

        $campaign->refreshCounters();
    }

    public function failed(Throwable $exception): void
    {
        $recipient = CampaignRecipient::with('campaign')->find($this->recipientId);
        if ($recipient && $recipient->status !== 'sent') $this->markFailed($recipient, $exception->getMessage());
    }

    private function markFailed(CampaignRecipient $recipient, string $message): void
    {
        $recipient->update(['status' => 'failed', 'error_message' => mb_substr($message, 0, 1000)]);
        $recipient->campaign?->refreshCounters();
        Log::warning('Campaign recipient failed', ['recipient_id' => $recipient->id, 'error' => $message]);
    }
}
