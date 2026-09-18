<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CampaignRecipient extends Model
{
    use HasFactory;

    protected $fillable = ['campaign_id', 'contact_id', 'status', 'attempts', 'provider_message_id', 'error_message', 'last_attempt_at', 'sent_at'];
    protected $casts = ['last_attempt_at' => 'datetime', 'sent_at' => 'datetime'];

    public function campaign() { return $this->belongsTo(Campaign::class); }
    public function contact() { return $this->belongsTo(Contact::class); }
}
