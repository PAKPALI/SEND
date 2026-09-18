<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('country_code', 2)->default('TG')->after('email');
            $table->unsignedInteger('sms_credits')->default(0)->after('country_code');
            $table->unsignedInteger('whatsapp_credits')->default(0)->after('sms_credits');
        });

        Schema::create('contacts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('phone', 30);
            $table->string('country_code', 2)->default('TG');
            $table->string('email')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->unique(['user_id', 'phone']);
            $table->index(['user_id', 'name']);
        });

        Schema::create('contact_groups', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('color', 7)->default('#7c6cff');
            $table->timestamps();
            $table->unique(['user_id', 'name']);
        });

        Schema::create('contact_group_contact', function (Blueprint $table): void {
            $table->foreignId('contact_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->primary(['contact_group_id', 'contact_id']);
        });

        Schema::create('campaigns', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_group_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('channel', 20);
            $table->string('title')->nullable();
            $table->text('message');
            $table->string('status')->default('draft')->index();
            $table->unsignedInteger('total_recipients')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->unsignedInteger('pending_count')->default(0);
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('campaign_recipients', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained()->restrictOnDelete();
            $table->string('status')->default('pending')->index();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->string('provider_message_id', 180)->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->unique(['campaign_id', 'contact_id']);
            $table->index(['campaign_id', 'status']);
        });

        Schema::create('quota_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('transaction_id')->unique();
            $table->string('idempotency_key')->unique();
            $table->string('kpp_reference')->nullable()->index();
            $table->string('event_id')->nullable()->unique();
            $table->unsignedInteger('sms_quantity')->default(0);
            $table->unsignedInteger('whatsapp_quantity')->default(0);
            $table->unsignedBigInteger('amount');
            $table->string('currency', 3)->default('XOF');
            $table->string('status')->default('created')->index();
            $table->text('checkout_url')->nullable();
            $table->string('failure_reason')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quota_payments');
        Schema::dropIfExists('campaign_recipients');
        Schema::dropIfExists('campaigns');
        Schema::dropIfExists('contact_group_contact');
        Schema::dropIfExists('contact_groups');
        Schema::dropIfExists('contacts');
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['country_code', 'sms_credits', 'whatsapp_credits']);
        });
    }
};
