<?php

namespace Tests\Feature;

use App\Jobs\ProcessMetaWebhookJob;
use App\Jobs\SendWhatsAppNotificationJob;
use App\Models\ImportBatch;
use App\Models\InspectionCenter;
use App\Models\InspectionRecord;
use App\Models\MessagingConsent;
use App\Models\NotificationLog;
use App\Models\NotificationSchedule;
use App\Models\NotificationTemplate;
use App\Models\User;
use App\Services\Notification\DueNotificationDispatcher;
use App\Services\Notification\MessagePersonalizer;
use App\Services\Notification\MessagingConsentService;
use Carbon\Carbon;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WhatsAppNotificationTest extends TestCase
{
    use RefreshDatabase;

    private InspectionCenter $center;

    private User $admin;

    private ImportBatch $batch;

    private InspectionRecord $record;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        Carbon::setTestNow(Carbon::parse('2026-10-15 10:00:00', 'Africa/Douala'));

        config([
            'app.timezone' => 'Africa/Douala',
            'services.whatsapp.token' => 'test-token',
            'services.whatsapp.phone_number_id' => '1234567890',
            'services.whatsapp.verify_token' => 'verify-me',
            'services.whatsapp.api_version' => 'v21.0',
            'notifications.sending_enabled' => true,
            'notifications.sms.sending_enabled' => false,
            'notifications.whatsapp.default_template_name' => 'visite_expiry_reminder',
            'notifications.whatsapp.default_language_code' => 'fr',
        ]);

        $this->center = InspectionCenter::create([
            'name' => 'Centre de Visite Technique Douala',
            'status' => 'active',
            'whatsapp_enabled' => true,
            'sms_enabled' => true,
        ]);

        $this->admin = User::factory()->create([
            'center_id' => $this->center->id,
            'role' => 'admin',
        ]);
        $this->admin->assignRole('admin');

        $this->batch = ImportBatch::create([
            'center_id' => $this->center->id,
            'uploaded_by' => $this->admin->id,
            'filename' => 'imports/test.csv',
            'original_filename' => 'test.csv',
            'status' => 'completed',
        ]);

        $this->record = InspectionRecord::create([
            'center_id' => $this->center->id,
            'import_batch_id' => $this->batch->id,
            'expiration_date' => '2026-11-14',
            'licence_plate' => 'LT 164 HP',
            'customer_name' => 'Jean Dupont',
            'phone_number' => '677123456',
            'normalized_phone_number' => '+237677123456',
            'status' => 'Inspection acceptée et activée',
            'record_hash' => hash('sha256', 'wa-record-1'),
        ]);

        NotificationTemplate::create([
            'center_id' => $this->center->id,
            'channel' => 'whatsapp',
            'language' => 'fr',
            'title' => 'Rappel WA',
            'content' => "Bonjour {customer_name},\n\nVotre visite technique pour le véhicule {licence_plate} expire le {expiration_date}.\n\nNous vous invitons à vous rendre à {center_name} pour effectuer votre renouvellement.\n\nMerci de votre confiance.",
            'meta_template_name' => 'visite_expiry_reminder',
            'meta_language_code' => 'fr',
            'status' => 'active',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_personalizer_builds_named_meta_parameters(): void
    {
        $params = app(MessagePersonalizer::class)->metaBodyParameters($this->record);

        $this->assertSame([
            ['name' => 'customer_name', 'text' => 'Jean Dupont'],
            ['name' => 'licence_plate', 'text' => 'LT164HP'],
            ['name' => 'expiration_date', 'text' => '14/11/2026'],
            ['name' => 'center_name', 'text' => 'Centre de Visite Technique Douala'],
        ], $params);
    }

    public function test_sends_approved_template_via_meta_cloud_api(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'messages' => [['id' => 'wamid.TEST123']],
            ], 200),
        ]);

        $schedule = $this->makeSchedule('2026-10-15');

        (new SendWhatsAppNotificationJob($schedule->id))->handle(
            app(\App\Services\WhatsApp\MetaWhatsAppService::class),
            app(MessagePersonalizer::class),
            app(MessagingConsentService::class),
        );

        Http::assertSent(function ($request) {
            $data = $request->data();

            return $request->url() === 'https://graph.facebook.com/v21.0/1234567890/messages'
                && $data['type'] === 'template'
                && $data['template']['name'] === 'visite_expiry_reminder'
                && $data['to'] === '237677123456'
                && $data['template']['components'][0]['parameters'][0]['parameter_name'] === 'customer_name'
                && $data['template']['components'][0]['parameters'][0]['text'] === 'Jean Dupont'
                && $data['template']['components'][0]['parameters'][3]['parameter_name'] === 'center_name'
                && $data['template']['components'][0]['parameters'][3]['text'] === 'Centre de Visite Technique Douala';
        });

        $this->assertSame('sent', $schedule->fresh()->status);
        $this->assertDatabaseHas('notification_logs', [
            'notification_schedule_id' => $schedule->id,
            'provider' => 'meta_whatsapp',
            'provider_message_id' => 'wamid.TEST123',
            'delivery_status' => 'sent',
        ]);
    }

    public function test_does_not_send_twice_for_same_schedule(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'messages' => [['id' => 'wamid.ONCE']],
            ], 200),
        ]);

        $schedule = $this->makeSchedule('2026-10-15');
        $job = new SendWhatsAppNotificationJob($schedule->id);

        $job->handle(
            app(\App\Services\WhatsApp\MetaWhatsAppService::class),
            app(MessagePersonalizer::class),
            app(MessagingConsentService::class),
        );
        $job->handle(
            app(\App\Services\WhatsApp\MetaWhatsAppService::class),
            app(MessagePersonalizer::class),
            app(MessagingConsentService::class),
        );

        Http::assertSentCount(1);
        $this->assertSame(1, NotificationLog::where('notification_schedule_id', $schedule->id)->where('delivery_status', 'sent')->count());
    }

    public function test_skips_customers_who_opted_out(): void
    {
        Http::fake();
        app(MessagingConsentService::class)->revoke($this->center->id, '+237677123456');

        $schedule = $this->makeSchedule('2026-10-15');

        (new SendWhatsAppNotificationJob($schedule->id))->handle(
            app(\App\Services\WhatsApp\MetaWhatsAppService::class),
            app(MessagePersonalizer::class),
            app(MessagingConsentService::class),
        );

        Http::assertNothingSent();
        $this->assertSame('cancelled', $schedule->fresh()->status);
        $this->assertDatabaseHas('notification_logs', [
            'notification_schedule_id' => $schedule->id,
            'delivery_status' => 'skipped',
        ]);
    }

    public function test_dispatcher_queues_whatsapp_but_not_sms_when_sms_disabled(): void
    {
        Queue::fake();

        $wa = $this->makeSchedule('2026-10-15', 'whatsapp');
        $sms = $this->makeSchedule('2026-10-15', 'sms');

        $queued = app(DueNotificationDispatcher::class)->dispatchDueToday();

        $this->assertSame(1, $queued);
        $this->assertSame('queued', $wa->fresh()->status);
        $this->assertSame('pending', $sms->fresh()->status);
        Queue::assertPushed(SendWhatsAppNotificationJob::class, 1);
    }

    public function test_webhook_verification(): void
    {
        $this->get('/webhooks/meta/whatsapp?'.http_build_query([
            'hub_mode' => 'subscribe',
            'hub_verify_token' => 'verify-me',
            'hub_challenge' => '12345',
        ]))->assertOk()->assertSee('12345');

        $this->get('/webhooks/meta/whatsapp?'.http_build_query([
            'hub_mode' => 'subscribe',
            'hub_verify_token' => 'wrong',
            'hub_challenge' => '12345',
        ]))->assertForbidden();
    }

    public function test_webhook_updates_delivery_and_cost(): void
    {
        Queue::fake();

        $schedule = $this->makeSchedule('2026-10-15');
        $log = NotificationLog::create([
            'center_id' => $this->center->id,
            'inspection_record_id' => $this->record->id,
            'notification_schedule_id' => $schedule->id,
            'channel' => 'whatsapp',
            'provider' => 'meta_whatsapp',
            'phone_number' => '+237677123456',
            'message' => 'preview',
            'provider_message_id' => 'wamid.ABC',
            'delivery_status' => 'sent',
            'sent_at' => now(),
        ]);

        $payload = [
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'statuses' => [[
                            'id' => 'wamid.ABC',
                            'status' => 'delivered',
                            'timestamp' => now()->timestamp,
                            'recipient_id' => '237677123456',
                            'pricing' => [
                                'billable' => true,
                                'pricing_model' => 'PMP',
                                'category' => 'utility',
                            ],
                        ]],
                    ],
                ]],
            ]],
        ];

        $this->postJson('/webhooks/meta/whatsapp', $payload)
            ->assertOk()
            ->assertSee('EVENT_RECEIVED');

        Queue::assertPushed(ProcessMetaWebhookJob::class);

        (new ProcessMetaWebhookJob($payload))->handle(
            app(\App\Services\WhatsApp\MetaWhatsAppService::class),
            app(MessagingConsentService::class),
        );

        $log->refresh();
        $this->assertSame('delivered', $log->delivery_status);
        $this->assertNotNull($log->delivered_at);
        $this->assertTrue($log->billable);
        $this->assertSame('utility', $log->conversation_category);
        $this->assertSame('0.0040', $log->estimated_cost);
    }

    public function test_inbound_stop_message_revokes_consent(): void
    {
        app(MessagingConsentService::class)->grant($this->center->id, '+237677123456');

        NotificationLog::create([
            'center_id' => $this->center->id,
            'inspection_record_id' => $this->record->id,
            'channel' => 'whatsapp',
            'provider' => 'meta_whatsapp',
            'phone_number' => '+237677123456',
            'message' => 'preview',
            'provider_message_id' => 'wamid.PREV',
            'delivery_status' => 'delivered',
        ]);

        $payload = [
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'messages' => [[
                            'from' => '237677123456',
                            'id' => 'wamid.IN',
                            'timestamp' => (string) now()->timestamp,
                            'text' => ['body' => 'STOP'],
                            'type' => 'text',
                        ]],
                    ],
                ]],
            ]],
        ];

        (new ProcessMetaWebhookJob($payload))->handle(
            app(\App\Services\WhatsApp\MetaWhatsAppService::class),
            app(MessagingConsentService::class),
        );

        $this->assertFalse(
            app(MessagingConsentService::class)->isAllowed($this->center->id, '+237677123456')
        );
        $this->assertNotNull(
            MessagingConsent::where('phone_number', '+237677123456')->value('opted_out_at')
        );
    }

    public function test_admin_can_toggle_consent_on_record_page(): void
    {
        $this->actingAs($this->admin)
            ->get(route('records.show', $this->record))
            ->assertOk()
            ->assertSee(__('WhatsApp consent'));

        $this->actingAs($this->admin)
            ->put(route('records.whatsapp-consent', $this->record), ['consented' => 0])
            ->assertRedirect(route('records.show', $this->record));

        $this->assertFalse(
            app(MessagingConsentService::class)->isAllowed($this->center->id, '+237677123456')
        );
    }

    public function test_whatsapp_template_stores_meta_fields(): void
    {
        $this->actingAs($this->admin)
            ->post(route('templates.store'), [
                'channel' => 'whatsapp',
                'language' => 'en',
                'title' => 'Expiry EN',
                'content' => 'Hello {customer_name}',
                'meta_template_name' => 'visite_expiry_reminder',
                'meta_language_code' => 'en',
            ])
            ->assertRedirect(route('templates.index'));

        $this->assertDatabaseHas('notification_templates', [
            'center_id' => $this->center->id,
            'channel' => 'whatsapp',
            'language' => 'en',
            'meta_template_name' => 'visite_expiry_reminder',
            'meta_language_code' => 'en',
        ]);
    }

    private function makeSchedule(string $date, string $channel = 'whatsapp'): NotificationSchedule
    {
        return NotificationSchedule::create([
            'center_id' => $this->center->id,
            'inspection_record_id' => $this->record->id,
            'channel' => $channel,
            'scheduled_date' => $date,
            'days_before' => 30,
            'status' => NotificationSchedule::STATUS_PENDING,
        ]);
    }
}
