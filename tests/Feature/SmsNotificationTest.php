<?php

namespace Tests\Feature;

use App\Jobs\ProcessSmsDeliveryReportJob;
use App\Jobs\SendSmsNotificationJob;
use App\Jobs\SendWhatsAppNotificationJob;
use App\Models\ImportBatch;
use App\Models\InspectionCenter;
use App\Models\InspectionRecord;
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

class SmsNotificationTest extends TestCase
{
    use RefreshDatabase;

    private InspectionCenter $center;

    private User $admin;

    private User $superAdmin;

    private InspectionRecord $record;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        Carbon::setTestNow(Carbon::parse('2026-10-15 10:00:00', 'Africa/Douala'));

        config([
            'app.timezone' => 'Africa/Douala',
            'services.nexah.user' => 'nexah-user',
            'services.nexah.password' => 'nexah-pass',
            'services.nexah.sender_id' => 'VISITE',
            'services.nexah.base_url' => 'https://smsvas.com/bulk/public/index.php/api/v1',
            'services.whatsapp.token' => 'wa-token',
            'services.whatsapp.phone_number_id' => '123',
            'notifications.sending_enabled' => true,
            'notifications.sms.sending_enabled' => true,
        ]);

        $this->center = InspectionCenter::create([
            'name' => 'Centre Douala',
            'status' => 'active',
            'whatsapp_enabled' => true,
            'sms_enabled' => true,
        ]);

        $this->admin = User::factory()->create([
            'center_id' => $this->center->id,
            'role' => 'admin',
        ]);
        $this->admin->assignRole('admin');

        $this->superAdmin = User::factory()->create([
            'center_id' => null,
            'role' => 'super-admin',
        ]);
        $this->superAdmin->assignRole('super-admin');

        $batch = ImportBatch::create([
            'center_id' => $this->center->id,
            'uploaded_by' => $this->admin->id,
            'filename' => 'imports/test.csv',
            'original_filename' => 'test.csv',
            'status' => 'completed',
        ]);

        $this->record = InspectionRecord::create([
            'center_id' => $this->center->id,
            'import_batch_id' => $batch->id,
            'expiration_date' => '2026-11-14',
            'licence_plate' => 'LT 164 HP',
            'customer_name' => 'Jean Dupont',
            'phone_number' => '677123456',
            'normalized_phone_number' => '+237677123456',
            'status' => 'Inspection acceptée et activée',
            'record_hash' => hash('sha256', 'sms-record-1'),
        ]);

        NotificationTemplate::create([
            'center_id' => $this->center->id,
            'channel' => 'sms',
            'language' => 'fr',
            'title' => 'Rappel SMS',
            'content' => 'Cher client, votre visite technique pour le véhicule {licence_plate} expire le {expiration_date}.',
            'status' => 'active',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_sends_sms_via_nexah_api(): void
    {
        Http::fake([
            'smsvas.com/*' => Http::response([
                'responsecode' => 1,
                'responsedescription' => 'success',
                'responsemessage' => 'success',
                'sms' => [[
                    'messageid' => 'nexah-msg-1',
                    'smsclientid' => 'client-1',
                    'mobileno' => '+237677123456',
                    'status' => 'success',
                    'errorcode' => '',
                    'errordescription' => '',
                ]],
            ], 200),
        ]);

        $schedule = $this->makeSchedule('2026-10-15');

        (new SendSmsNotificationJob($schedule->id))->handle(
            app(\App\Services\Sms\NexahSmsService::class),
            app(MessagePersonalizer::class),
            app(MessagingConsentService::class),
        );

        Http::assertSent(function ($request) {
            $data = $request->data();

            return $request->url() === 'https://smsvas.com/bulk/public/index.php/api/v1/sendsms'
                && $data['user'] === 'nexah-user'
                && $data['password'] === 'nexah-pass'
                && $data['senderid'] === 'VISITE'
                && $data['mobiles'] === '237677123456'
                && str_contains($data['sms'], 'LT164HP')
                && str_contains($data['sms'], '14/11/2026');
        });

        $this->assertSame('sent', $schedule->fresh()->status);
        $this->assertDatabaseHas('notification_logs', [
            'notification_schedule_id' => $schedule->id,
            'provider' => 'nexah',
            'provider_message_id' => 'nexah-msg-1',
            'delivery_status' => 'sent',
            'channel' => 'sms',
        ]);
    }

    public function test_uses_center_notification_language_template(): void
    {
        $this->center->update(['notification_language' => 'en']);

        NotificationTemplate::create([
            'center_id' => $this->center->id,
            'channel' => 'sms',
            'language' => 'en',
            'title' => 'SMS Reminder',
            'content' => 'Dear customer, your inspection for {licence_plate} expires on {expiration_date}.',
            'status' => 'active',
        ]);

        Http::fake([
            'smsvas.com/*' => Http::response([
                'responsecode' => 1,
                'responsedescription' => 'success',
                'responsemessage' => 'success',
                'sms' => [[
                    'messageid' => 'nexah-en-1',
                    'smsclientid' => 'client-en',
                    'mobileno' => '+237677123456',
                    'status' => 'success',
                    'errorcode' => '',
                    'errordescription' => '',
                ]],
            ], 200),
        ]);

        $schedule = $this->makeSchedule('2026-10-15');

        (new SendSmsNotificationJob($schedule->id))->handle(
            app(\App\Services\Sms\NexahSmsService::class),
            app(MessagePersonalizer::class),
            app(MessagingConsentService::class),
        );

        Http::assertSent(fn ($request) => str_contains($request->data()['sms'], 'Dear customer'));
        $this->assertDatabaseHas('notification_logs', [
            'notification_schedule_id' => $schedule->id,
            'message' => 'Dear customer, your inspection for LT164HP expires on 14/11/2026.',
        ]);
    }

    public function test_does_not_send_twice_for_same_schedule(): void
    {
        Http::fake([
            'smsvas.com/*' => Http::response([
                'responsecode' => 1,
                'sms' => [['messageid' => 'nexah-once', 'status' => 'success']],
            ], 200),
        ]);

        $schedule = $this->makeSchedule('2026-10-15');
        $job = new SendSmsNotificationJob($schedule->id);

        $job->handle(
            app(\App\Services\Sms\NexahSmsService::class),
            app(MessagePersonalizer::class),
            app(MessagingConsentService::class),
        );
        $job->handle(
            app(\App\Services\Sms\NexahSmsService::class),
            app(MessagePersonalizer::class),
            app(MessagingConsentService::class),
        );

        Http::assertSentCount(1);
        $this->assertSame(1, NotificationLog::where('notification_schedule_id', $schedule->id)->where('delivery_status', 'sent')->count());
    }

    public function test_marks_permanent_failure_for_invalid_mobile(): void
    {
        Http::fake([
            'smsvas.com/*' => Http::response([
                'responsecode' => 0,
                'responsedescription' => 'error',
                'responsemessage' => 'Invalid Mobile Number',
                'sms' => [[
                    'messageid' => '',
                    'status' => 'error',
                    'errorcode' => -10003,
                    'errordescription' => 'Invalid Mobile Number',
                ]],
            ], 200),
        ]);

        $schedule = $this->makeSchedule('2026-10-15');

        (new SendSmsNotificationJob($schedule->id))->handle(
            app(\App\Services\Sms\NexahSmsService::class),
            app(MessagePersonalizer::class),
            app(MessagingConsentService::class),
        );

        $this->assertSame('failed', $schedule->fresh()->status);
        $this->assertDatabaseHas('notification_logs', [
            'notification_schedule_id' => $schedule->id,
            'delivery_status' => 'failed',
            'provider' => 'nexah',
        ]);
    }

    public function test_dispatcher_queues_sms_when_enabled_and_configured(): void
    {
        Queue::fake();

        $sms = $this->makeSchedule('2026-10-15', 'sms');
        $wa = $this->makeSchedule('2026-10-15', 'whatsapp');

        $queued = app(DueNotificationDispatcher::class)->dispatchDueToday();

        $this->assertSame(2, $queued);
        $this->assertSame('queued', $sms->fresh()->status);
        $this->assertSame('queued', $wa->fresh()->status);
        Queue::assertPushed(SendSmsNotificationJob::class, 1);
        Queue::assertPushed(SendWhatsAppNotificationJob::class, 1);
    }

    public function test_dlr_webhook_acknowledges_and_updates_delivery(): void
    {
        Queue::fake();

        $schedule = $this->makeSchedule('2026-10-15');
        $log = NotificationLog::create([
            'center_id' => $this->center->id,
            'inspection_record_id' => $this->record->id,
            'notification_schedule_id' => $schedule->id,
            'channel' => 'sms',
            'provider' => 'nexah',
            'phone_number' => '+237677123456',
            'message' => 'preview',
            'provider_message_id' => 'nexah-dlr-1',
            'delivery_status' => 'sent',
            'sent_at' => now(),
        ]);

        $payload = [
            'dlrlist' => [[
                'reponsecode' => 1,
                'reponsedescription' => 'success',
                'messageid' => 'nexah-dlr-1',
                'mobileno' => '+237677123456',
                'status' => 'DELIVRD',
                'submittime' => '2026-10-15 10:00:00',
                'senttime' => '2026-10-15 10:00:01',
                'deliverytime' => '2026-10-15 10:00:02',
            ]],
        ];

        $this->postJson('/webhooks/nexah/sms', $payload)
            ->assertOk()
            ->assertJsonPath('dlrlist.0.status', 1)
            ->assertJsonPath('dlrlist.0.messageid', 'nexah-dlr-1');

        Queue::assertPushed(ProcessSmsDeliveryReportJob::class);

        (new ProcessSmsDeliveryReportJob($payload))->handle(
            app(\App\Services\Sms\NexahSmsService::class),
        );

        $log->refresh();
        $this->assertSame('delivered', $log->delivery_status);
        $this->assertNotNull($log->delivered_at);
    }

    public function test_dlr_undelivered_marks_log_failed(): void
    {
        $schedule = $this->makeSchedule('2026-10-15');
        $log = NotificationLog::create([
            'center_id' => $this->center->id,
            'inspection_record_id' => $this->record->id,
            'notification_schedule_id' => $schedule->id,
            'channel' => 'sms',
            'provider' => 'nexah',
            'phone_number' => '+237677123456',
            'message' => 'preview',
            'provider_message_id' => 'nexah-fail-1',
            'delivery_status' => 'sent',
            'sent_at' => now(),
        ]);

        $payload = [
            'dlrlist' => [[
                'messageid' => 'nexah-fail-1',
                'mobileno' => '+237677123456',
                'status' => 'UNDELIV',
                'reponsedescription' => 'not delivered',
            ]],
        ];

        (new ProcessSmsDeliveryReportJob($payload))->handle(
            app(\App\Services\Sms\NexahSmsService::class),
        );

        $log->refresh();
        $this->assertSame('failed', $log->delivery_status);
        $this->assertSame('failed', $schedule->fresh()->status);
    }

    public function test_super_admin_can_refresh_sms_balance_json(): void
    {
        Http::fake([
            'smsvas.com/*/smscredit' => Http::response([
                'accountexpdate' => '26/03/2027',
                'balanceexpdate' => '07/09/2027',
                'credit' => 6124,
            ], 200),
        ]);

        $this->actingAs($this->superAdmin)
            ->getJson(route('sms.balance'))
            ->assertOk()
            ->assertJson([
                'ok' => true,
                'credit' => 6124,
                'account_expires_at' => '26/03/2027',
                'balance_expires_at' => '07/09/2027',
            ]);

        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/smscredit')
            && $request['user'] === 'nexah-user');
    }

    public function test_admin_cannot_view_sms_balance(): void
    {
        $this->actingAs($this->admin)
            ->getJson(route('sms.balance'))
            ->assertForbidden();
    }

    public function test_centers_page_loads_without_blocking_on_nexah_and_shows_balance_widget(): void
    {
        Http::fake([
            'smsvas.com/*/smscredit' => Http::response([
                'accountexpdate' => '26/03/2027',
                'balanceexpdate' => '07/09/2027',
                'credit' => 6124,
            ], 200),
        ]);

        $this->actingAs($this->superAdmin)
            ->get(route('centers.index'))
            ->assertOk()
            ->assertSee(__('SMS balance'))
            ->assertSee(__('Refresh'))
            ->assertSee(__('Loading…'))
            ->assertDontSee(__('Check SMS balance'));

        // Page render must not call Nexah synchronously.
        Http::assertNothingSent();
    }

    public function test_sms_balance_endpoint_uses_cache_unless_fresh(): void
    {
        Http::fake([
            'smsvas.com/*/smscredit' => Http::response([
                'accountexpdate' => '26/03/2027',
                'balanceexpdate' => '07/09/2027',
                'credit' => 6124,
            ], 200),
        ]);

        $this->actingAs($this->superAdmin)->getJson(route('sms.balance'))->assertOk();
        $this->actingAs($this->superAdmin)->getJson(route('sms.balance'))->assertOk();
        Http::assertSentCount(1);

        $this->actingAs($this->superAdmin)
            ->getJson(route('sms.balance', ['fresh' => 1]))
            ->assertOk()
            ->assertJsonPath('credit', 6124);
        Http::assertSentCount(2);
    }

    public function test_sms_balance_falls_back_to_last_known_when_credit_endpoint_fails(): void
    {
        Http::fake([
            'smsvas.com/*/smscredit' => Http::response([
                'accountexpdate' => '26/03/2027',
                'balanceexpdate' => '07/09/2027',
                'credit' => 6124,
            ], 200),
            'smsvas.com/*/sendsms' => Http::response([
                'responsecode' => 1,
                'responsedescription' => 'success',
                'responsemessage' => 'success',
                'sms' => [[
                    'messageid' => 'nexah-bal-1',
                    'mobileno' => '+237677123456',
                    'status' => 'success',
                    'balance' => 5999,
                ]],
            ], 200),
        ]);

        // Prime last-known balance via a successful send response.
        app(\App\Services\Sms\NexahSmsService::class)->send('+237677123456', 'balance prime');

        Http::fake([
            'smsvas.com/*/smscredit' => fn () => throw new \Illuminate\Http\Client\ConnectionException('timeout'),
        ]);

        $this->actingAs($this->superAdmin)
            ->getJson(route('sms.balance', ['fresh' => 1]))
            ->assertOk()
            ->assertJson([
                'ok' => true,
                'credit' => 5999,
                'stale' => true,
            ]);
    }

    private function makeSchedule(string $date, string $channel = 'sms'): NotificationSchedule
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
