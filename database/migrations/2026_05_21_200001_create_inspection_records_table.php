<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inspection_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id')->constrained('inspection_centers')->cascadeOnDelete();
            $table->foreignId('import_batch_id')->constrained('import_batches')->cascadeOnDelete();
            $table->date('registration_date')->nullable();
            $table->date('inspection_date')->nullable();
            $table->date('expiration_date');
            $table->string('vehicle_class', 50)->nullable();
            $table->string('inspection_type', 100)->nullable();
            $table->string('licence_plate', 30);
            $table->string('vehicle_category', 100)->nullable();
            $table->string('customer_name');
            $table->string('phone_number', 30);
            $table->string('normalized_phone_number', 20);
            $table->string('status', 100);
            $table->string('record_hash', 64)->unique();
            $table->timestamps();

            $table->index('licence_plate');
            $table->index('expiration_date');
            $table->index('normalized_phone_number');
            $table->index('customer_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspection_records');
    }
};
