<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id')->constrained('inspection_centers')->cascadeOnDelete();
            $table->foreignId('inspection_record_id')->constrained('inspection_records')->cascadeOnDelete();
            $table->foreignId('notification_schedule_id')->nullable()->constrained('notification_schedules')->nullOnDelete();
            $table->string('channel', 20);
            $table->string('provider', 30);
            $table->string('phone_number', 20);
            $table->text('message');
            $table->string('provider_message_id', 100)->nullable();
            $table->string('delivery_status', 30)->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index('provider_message_id');
            $table->index(['center_id', 'delivery_status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
