<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messaging_consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id')->constrained('inspection_centers')->cascadeOnDelete();
            $table->string('phone_number', 20);
            $table->string('channel', 20);
            $table->boolean('consented')->default(true);
            $table->timestamp('opted_out_at')->nullable();
            $table->string('source', 40)->default('import');
            $table->string('notes', 255)->nullable();
            $table->timestamps();

            $table->unique(['center_id', 'phone_number', 'channel'], 'idx_messaging_consents_unique');
            $table->index(['center_id', 'channel', 'consented']);
        });

        Schema::table('notification_templates', function (Blueprint $table) {
            $table->string('meta_template_name', 100)->nullable()->after('content');
            $table->string('meta_language_code', 20)->nullable()->after('meta_template_name');
        });

        Schema::table('notification_logs', function (Blueprint $table) {
            $table->timestamp('delivered_at')->nullable()->after('sent_at');
            $table->timestamp('read_at')->nullable()->after('delivered_at');
            $table->timestamp('failed_at')->nullable()->after('read_at');
            $table->boolean('billable')->nullable()->after('failed_at');
            $table->string('pricing_model', 40)->nullable()->after('billable');
            $table->string('conversation_category', 40)->nullable()->after('pricing_model');
            $table->decimal('estimated_cost', 10, 4)->nullable()->after('conversation_category');
            $table->string('cost_currency', 10)->nullable()->after('estimated_cost');
        });
    }

    public function down(): void
    {
        Schema::table('notification_logs', function (Blueprint $table) {
            $table->dropColumn([
                'delivered_at',
                'read_at',
                'failed_at',
                'billable',
                'pricing_model',
                'conversation_category',
                'estimated_cost',
                'cost_currency',
            ]);
        });

        Schema::table('notification_templates', function (Blueprint $table) {
            $table->dropColumn(['meta_template_name', 'meta_language_code']);
        });

        Schema::dropIfExists('messaging_consents');
    }
};
