<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'contact_group_id', 'name', 'channel', 'title', 'message', 'status',
        'total_recipients', 'sent_count', 'failed_count', 'pending_count', 'queued_at', 'completed_at',
    ];

    protected $casts = ['queued_at' => 'datetime', 'completed_at' => 'datetime'];

    public function user() { return $this->belongsTo(User::class); }
    public function group() { return $this->belongsTo(ContactGroup::class, 'contact_group_id'); }
    public function recipients() { return $this->hasMany(CampaignRecipient::class); }

    public function refreshCounters(): void
    {
        $counts = $this->recipients()->selectRaw("status, count(*) as aggregate")->groupBy('status')->pluck('aggregate', 'status');
        $sent = (int) ($counts['sent'] ?? 0);
        $failed = (int) ($counts['failed'] ?? 0);
        $pending = (int) ($counts['pending'] ?? 0) + (int) ($counts['processing'] ?? 0);
        $status = $pending > 0
            ? (($sent + $failed) > 0 ? 'processing' : 'queued')
            : ($failed > 0 ? ($sent > 0 ? 'partial' : 'failed') : 'completed');
        $this->forceFill([
            'total_recipients' => $this->recipients()->count(),
            'sent_count' => $sent,
            'failed_count' => $failed,
            'pending_count' => $pending,
            'status' => $status,
            'completed_at' => $pending === 0 ? ($this->completed_at ?: now()) : null,
        ])->save();
    }
}
