<?php

namespace Tests\Unit;

use App\Services\KprimeMessagingService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class KprimeMessagingServiceTest extends TestCase
{
    public function test_sms_payload_uses_the_local_togolese_number(): void
    {
        Config::set('services.kprimesms', [
            'base_url' => 'https://api.example.test/v1',
            'token' => 'token',
            'key' => 'key',
            'sender' => 'SEND',
            'sender_id' => 'send',
            'response_url' => null,
        ]);

        Http::fake([
            'https://api.example.test/*' => Http::response(['status' => true, 'message_id' => 'sms-1'], 200),
        ]);

        $response = app(KprimeMessagingService::class)->send('sms', '+228 90 12 34 56', 'Bonjour', 'TG');

        $this->assertTrue($response['status']);
        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://api.example.test/v1/sms/push'
                && $request['country'] === 'TG'
                && $request['phone_number'] === '90123456';
        });
    }

    public function test_whatsapp_payload_keeps_the_international_number(): void
    {
        Config::set('services.kprimesms', [
            'base_url' => 'https://api.example.test/v1',
            'token' => 'token',
            'key' => 'key',
            'sender' => 'SEND',
            'sender_id' => 'send',
            'response_url' => null,
        ]);

        Http::fake([
            'https://api.example.test/*' => Http::response(['status' => true, 'message_id' => 'wa-1'], 200),
        ]);

        $response = app(KprimeMessagingService::class)->send('whatsapp', '+22890123456', 'Bonjour', 'TG', 'Message');

        $this->assertTrue($response['status']);
        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://api.example.test/v1/whatsapp/template/text-message'
                && $request['phone_number'] === '+22890123456';
        });
    }
}
