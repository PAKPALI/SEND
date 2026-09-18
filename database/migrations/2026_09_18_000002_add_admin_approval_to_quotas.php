<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('is_admin')->default(false)->after('password');
        });

        Schema::table('quota_payments', function (Blueprint $table): void {
            $table->timestamp('approved_at')->nullable()->after('paid_at');
            $table->foreignId('approved_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
        });

        $adminEmail = strtolower((string) config('services.send.admin_email', 'pakpalididier@gmail.com'));
        DB::table('users')->whereRaw('LOWER(email) = ?', [$adminEmail])->update(['is_admin' => true]);
    }

    public function down(): void
    {
        Schema::table('quota_payments', function (Blueprint $table): void {
            $table->dropForeign(['approved_by']);
            $table->dropColumn(['approved_at', 'approved_by']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('is_admin');
        });
    }
};
