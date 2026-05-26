<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id')->constrained('inspection_centers')->cascadeOnDelete();
            $table->string('channel', 20);
            $table->string('language', 10)->default('fr');
            $table->string('title');
            $table->text('content');
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->unique(['center_id', 'channel', 'language'], 'idx_templates_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
    }
};
