<?php

namespace App\Http\Controllers;

use App\Jobs\SendCampaignMessageJob;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CampaignController extends Controller
{
    public function index(Request $request)
    {
        $query = $request->user()->campaigns()->with('group')->latest();
        if ($request->filled('status')) $query->where('status', $request->string('status'));
        if ($request->filled('channel')) $query->where('channel', $request->string('channel'));
        $campaigns = $query->paginate($this->perPage($request))->withQueryString();
        return view('campaigns.index', compact('campaigns'));
    }

    public function create(Request $request)
    {
        $groups = $request->user()->groups()->withCount('contacts')->orderBy('name')->get();
        $pendingCredits = [
            'sms' => (int) $request->user()->quotaPayments()->where('status', 'awaiting_approval')->sum('sms_quantity'),
            'whatsapp' => (int) $request->user()->quotaPayments()->where('status', 'awaiting_approval')->sum('whatsapp_quantity'),
        ];
        return view('campaigns.create', compact('groups', 'pendingCredits'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'contact_group_id' => ['required', Rule::exists('contact_groups', 'id')->where(fn ($query) => $query->where('user_id', $request->user()->id))],
            'channel' => ['required', Rule::in(['sms', 'whatsapp'])],
            'title' => ['nullable', 'string', 'max:120'],
            'message' => ['required', 'string', 'max:4096'],
        ]);
        if ($data['channel'] === 'sms' && mb_strlen($data['message']) > 160) {
            return back()->withErrors(['message' => 'Un SMS ne peut pas dépasser 160 caractères.'])->withInput();
        }
        $group = $request->user()->groups()->with('contacts')->findOrFail($data['contact_group_id']);
        $contacts = $group->contacts()->where('status', 'active')->get();
        if ($contacts->isEmpty()) return back()->withErrors(['contact_group_id' => 'Ce groupe ne contient aucun contact actif.'])->withInput();
        $quotaField = $data['channel'] === 'sms' ? 'sms_credits' : 'whatsapp_credits';
        if ((int) $request->user()->{$quotaField} < $contacts->count()) {
            $pendingQuantity = (int) $request->user()->quotaPayments()->where('status', 'awaiting_approval')->sum($data['channel'].'_quantity');
            $message = $pendingQuantity > 0
                ? 'Votre quota est en attente d’approbation par l’administrateur. Le bouton d’envoi restera bloqué jusqu’à sa validation.'
                : 'Quota insuffisant pour ce groupe. Achetez puis faites approuver votre quota avant de lancer l’envoi.';
            return back()->withErrors(['channel' => $message])->withInput();
        }

        $campaign = $request->user()->campaigns()->create(array_merge($data, [
            'status' => 'queued', 'total_recipients' => $contacts->count(), 'pending_count' => $contacts->count(), 'queued_at' => now(),
        ]));
        $campaign->recipients()->createMany($contacts->map(fn ($contact) => ['contact_id' => $contact->id, 'status' => 'pending'])->all());
        $campaign->recipients()->each(fn ($recipient) => SendCampaignMessageJob::dispatch($recipient->id));
        return redirect()->route('campaigns.show', $campaign)->with('success', 'Campagne mise en file. Les messages sont envoyés en arrière-plan.');
    }

    public function show(Request $request, Campaign $campaign)
    {
        abort_unless($campaign->user_id === $request->user()->id, 403);
        $campaign->load('group');
        $recipients = $campaign->recipients()->with('contact')->latest()->paginate($this->perPage($request))->withQueryString();
        return view('campaigns.show', compact('campaign', 'recipients'));
    }

    public function retry(Request $request, Campaign $campaign)
    {
        abort_unless($campaign->user_id === $request->user()->id, 403);
        $query = $campaign->recipients()->where('status', 'failed');
        if ($request->filled('recipient_id')) $query->whereKey($request->integer('recipient_id'));
        $recipients = $query->get();
        foreach ($recipients as $recipient) {
            $recipient->update(['status' => 'pending', 'error_message' => null]);
            SendCampaignMessageJob::dispatch($recipient->id);
        }
        $campaign->update(['status' => $recipients->isNotEmpty() ? 'queued' : $campaign->status, 'completed_at' => null]);
        $campaign->refreshCounters();
        return back()->with('success', $recipients->count().' message(s) remis en file.');
    }
}
