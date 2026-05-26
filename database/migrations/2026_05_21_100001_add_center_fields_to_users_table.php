<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('center_id')->nullable()->after('id')->constrained('inspection_centers')->nullOnDelete();
            $table->string('phone', 20)->nullable()->after('email');
            $table->string('role', 30)->default('operator')->after('phone');
            $table->string('status', 20)->default('active')->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['center_id']);
            $table->dropColumn(['center_id', 'phone', 'role', 'status']);
        });
    }
};
