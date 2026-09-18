<?php

namespace App\Http\Controllers;

use App\Models\CampaignRecipient;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        $campaigns = $user->campaigns()->with('group')->latest()->take(6)->get();
        $deliveries = CampaignRecipient::whereHas('campaign', fn ($query) => $query->where('user_id', $user->id))
            ->with(['contact', 'campaign'])->latest()->take(8)->get();
        $stats = [
            'contacts' => $user->contacts()->count(),
            'groups' => $user->groups()->count(),
            'campaigns' => $user->campaigns()->count(),
            'sent' => CampaignRecipient::whereHas('campaign', fn ($query) => $query->where('user_id', $user->id))->where('status', 'sent')->count(),
        ];
        return view('dashboard', compact('user', 'campaigns', 'deliveries', 'stats'));
    }
}
