<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id')->constrained('inspection_centers')->cascadeOnDelete();
            $table->foreignId('inspection_record_id')->constrained('inspection_records')->cascadeOnDelete();
            $table->string('channel', 20);
            $table->date('scheduled_date');
            $table->string('status', 20)->default('pending');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamps();

            $table->unique(['inspection_record_id', 'channel', 'scheduled_date'], 'idx_schedules_dedup');
            $table->index(['status', 'scheduled_date'], 'idx_schedules_due');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_schedules');
    }
};
