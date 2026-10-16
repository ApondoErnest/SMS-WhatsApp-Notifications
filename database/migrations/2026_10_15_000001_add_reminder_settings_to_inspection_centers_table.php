<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inspection_centers', function (Blueprint $table) {
            $table->boolean('sms_enabled')->default(true)->after('status');
            $table->boolean('whatsapp_enabled')->default(true)->after('sms_enabled');
            $table->json('reminder_days')->nullable()->after('whatsapp_enabled');
        });

        Schema::table('notification_schedules', function (Blueprint $table) {
            $table->unsignedSmallInteger('days_before')->nullable()->after('scheduled_date');
        });
    }

    public function down(): void
    {
        Schema::table('notification_schedules', function (Blueprint $table) {
            $table->dropColumn('days_before');
        });

        Schema::table('inspection_centers', function (Blueprint $table) {
            $table->dropColumn(['sms_enabled', 'whatsapp_enabled', 'reminder_days']);
        });
    }
};
