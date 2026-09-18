<?php

use App\Http\Controllers\KprimePayWebhookController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

Route::post('/kprimepay/webhook', KprimePayWebhookController::class)->name('api.kprimepay.webhook');

Route::post('/sms/callback', function (Request $request) {
    $secret = (string) config('services.kprimesms.callback_secret');
    $signature = (string) $request->header('X-KPrime-Signature', '');
    if ($secret !== '' && ($signature === '' || !hash_equals(hash_hmac('sha256', $request->getContent(), $secret), $signature))) {
        return response()->json(['message' => 'Signature invalide.'], 401);
    }
    Log::info('KPrimeSMS callback received', ['status' => $request->string('status')->toString()]);
    return response()->json(['status' => 'accepted']);
})->name('api.sms.callback');
