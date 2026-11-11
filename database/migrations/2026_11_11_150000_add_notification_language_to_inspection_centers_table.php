<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inspection_centers', function (Blueprint $table) {
            $table->string('notification_language', 5)->default('fr')->after('reminder_days');
        });
    }

    public function down(): void
    {
        Schema::table('inspection_centers', function (Blueprint $table) {
            $table->dropColumn('notification_language');
        });
    }
};
