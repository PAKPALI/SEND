<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuotaPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'transaction_id', 'idempotency_key', 'kpp_reference', 'event_id', 'sms_quantity',
        'whatsapp_quantity', 'amount', 'currency', 'status', 'checkout_url', 'failure_reason',
        'expires_at', 'paid_at', 'failed_at', 'approved_at', 'approved_by',
    ];

    protected $casts = ['expires_at' => 'datetime', 'paid_at' => 'datetime', 'failed_at' => 'datetime', 'approved_at' => 'datetime'];
    public function user() { return $this->belongsTo(User::class); }
    public function approver() { return $this->belongsTo(User::class, 'approved_by'); }
}
