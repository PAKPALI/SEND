<?php

namespace App\Http\Controllers;

use App\Jobs\SendCampaignMessageJob;
use App\Models\CampaignRecipient;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HistoryController extends Controller
{
    public function index(Request $request)
    {
        $query = CampaignRecipient::whereHas('campaign', fn ($q) => $q->where('user_id', $request->user()->id))
            ->with(['contact', 'campaign'])->latest();
        if ($request->filled('status')) $query->where('status', $request->string('status'));
        if ($request->filled('channel')) $query->whereHas('campaign', fn ($q) => $q->where('channel', $request->string('channel')));
        if ($request->filled('q')) $query->whereHas('contact', fn ($q) => $q->where('name', 'like', '%'.$request->string('q').'%')->orWhere('phone', 'like', '%'.$request->string('q').'%'));
        if ($request->filled('campaign')) $query->where('campaign_id', $request->integer('campaign'));
        $deliveries = $query->paginate($this->perPage($request))->withQueryString();
        $campaigns = $request->user()->campaigns()->latest()->get(['id', 'name']);
        return view('history.index', compact('deliveries', 'campaigns'));
    }

    public function retry(Request $request, CampaignRecipient $recipient)
    {
        abort_unless($recipient->campaign()->where('user_id', $request->user()->id)->exists(), 403);
        if ($recipient->status !== 'failed') return back()->withErrors(['recipient' => 'Seuls les messages échoués peuvent être renvoyés.']);
        $recipient->update(['status' => 'pending', 'error_message' => null]);
        SendCampaignMessageJob::dispatch($recipient->id);
        $recipient->campaign->update(['status' => 'queued', 'completed_at' => null]);
        return back()->with('success', 'Message remis en file.');
    }
}
