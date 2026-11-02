<?php

namespace Tests\Feature;

use App\Jobs\GenerateNotificationSchedulesJob;
use App\Jobs\SendSmsNotificationJob;
use App\Jobs\SendWhatsAppNotificationJob;
use App\Models\ImportBatch;
use App\Models\InspectionCenter;
use App\Models\InspectionRecord;
use App\Models\NotificationSchedule;
use App\Models\User;
use App\Services\Notification\NotificationSchedulerService;
use Carbon\Carbon;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class NotificationSchedulingTest extends TestCase
{
    use RefreshDatabase;

    private InspectionCenter $center;

    private User $admin;

    private ImportBatch $batch;

    private NotificationSchedulerService $scheduler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        Carbon::setTestNow(Carbon::parse('2026-10-15 16:00:00'));

        $this->center = InspectionCenter::create(['name' => 'Center A', 'status' => 'active']);
        $this->admin = User::factory()->create(['center_id' => $this->center->id, 'role' => 'admin']);
        $this->admin->assignRole('admin');
        $this->batch = ImportBatch::create([
            'center_id' => $this->center->id,
            'uploaded_by' => $this->admin->id,
            'filename' => 'imports/test.csv',
            'original_filename' => 'test.csv',
            'status' => 'completed',
        ]);
        $this->scheduler = app(NotificationSchedulerService::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_creates_reminders_for_default_days_on_both_channels(): void
    {
        $record = $this->makeRecord('2026-12-15');

        $this->assertSame(8, $this->scheduler->generateForRecord($record));

        $this->assertEqualsCanonicalizing(
            ['2026-11-15', '2026-12-01', '2026-12-08', '2026-12-14'],
            $record->notificationSchedules()->where('channel', 'sms')->get()->map(fn ($s) => $s->scheduled_date->toDateString())->all()
        );
        $this->assertSame(30, (int) $record->notificationSchedules()->whereDate('scheduled_date', '2026-11-15')->value('days_before'));

        $this->assertSame(0, $this->scheduler->generateForRecord($record));
    }

    public function test_reminder_due_today_is_kept(): void
    {
        $record = $this->makeRecord('2026-10-16');

        $this->scheduler->generateForRecord($record);

        $this->assertSame(2, $record->notificationSchedules()->whereDate('scheduled_date', '2026-10-15')->count());
    }

    public function test_only_future_reminders_are_created_without_catch_up(): void
    {
        $record = $this->makeRecord('2026-10-20');

        $this->scheduler->generateForRecord($record);

        $this->assertEqualsCanonicalizing(
            [1],
            $record->notificationSchedules()->pluck('days_before')->unique()->values()->all()
        );
    }

    public function test_refused_inspections_get_no_reminders(): void
    {
        foreach (['Inspection refusée et enregistrée', 'INAPTE', 'Inspection rejected'] as $i => $status) {
            $record = $this->makeRecord('2026-12-15', $status, 'LT 00'.$i.' AA');
            $this->assertSame(0, $this->scheduler->generateForRecord($record), $status);
        }

        $accepted = $this->makeRecord('2026-12-15', 'Inspection acceptée et activée', 'LT 999 ZZ');
        $this->assertSame(8, $this->scheduler->generateForRecord($accepted));
    }

    public function test_respects_center_channels_and_days(): void
    {
        $this->center->update(['whatsapp_enabled' => false, 'reminder_days' => [14, 3]]);
        $record = $this->makeRecord('2026-12-15');

        $this->scheduler->generateForRecord($record->fresh());

        $schedules = $record->notificationSchedules()->get();
        $this->assertCount(2, $schedules);
        $this->assertSame(['sms'], $schedules->pluck('channel')->unique()->values()->all());
        $this->assertEqualsCanonicalizing([14, 3], $schedules->pluck('days_before')->all());
    }

    public function test_renewal_cancels_older_reminders_for_same_plate(): void
    {
        $old = $this->makeRecord('2026-12-15', plate: 'LT 164 HP');
        $this->scheduler->generateForRecord($old);

        $renewed = $this->makeRecord('2027-12-15', plate: 'lt164hp');
        $this->scheduler->generateForRecord($renewed);

        $this->assertSame(0, $old->notificationSchedules()->where('status', 'pending')->count());
        $this->assertSame(8, $old->notificationSchedules()->where('status', 'cancelled')->count());
        $this->assertSame(8, $renewed->notificationSchedules()->where('status', 'pending')->count());
    }

    public function test_older_record_imported_after_renewal_gets_no_reminders(): void
    {
        $renewed = $this->makeRecord('2027-12-15', plate: 'LT 164 HP');
        $this->scheduler->generateForRecord($renewed);

        $old = $this->makeRecord('2026-12-15', plate: 'LT 164 HP');
        $this->assertSame(0, $this->scheduler->generateForRecord($old));
    }

    public function test_refused_newer_record_does_not_cancel_accepted_reminders(): void
    {
        $accepted = $this->makeRecord('2026-12-15', plate: 'CH 141885');
        $this->scheduler->generateForRecord($accepted);

        $refused = $this->makeRecord('2027-05-27', 'Inspection refusée et enregistrée', 'CH 141885');
        $this->scheduler->generateForRecord($refused);

        $this->assertSame(8, $accepted->notificationSchedules()->where('status', 'pending')->count());
    }

    public function test_rebuild_applies_new_settings_and_keeps_history(): void
    {
        $record = $this->makeRecord('2026-12-15');
        $this->scheduler->generateForRecord($record);
        NotificationSchedule::create([
            'center_id' => $this->center->id,
            'inspection_record_id' => $record->id,
            'channel' => 'sms',
            'scheduled_date' => '2026-10-01',
            'days_before' => 75,
            'status' => 'sent',
        ]);

        $this->center->update(['whatsapp_enabled' => false, 'reminder_days' => [7]]);

        $this->artisan('notifications:rebuild-schedules', ['--center' => $this->center->id])->assertSuccessful();

        $pending = $record->notificationSchedules()->where('status', 'pending')->get();
        $this->assertCount(1, $pending);
        $this->assertSame('sms', $pending->first()->channel);
        $this->assertSame('2026-12-08', $pending->first()->scheduled_date->toDateString());
        $this->assertSame(1, $record->notificationSchedules()->where('status', 'sent')->count());
    }

    public function test_dispatch_marks_missed_and_leaves_due_pending_while_sending_disabled(): void
    {
        Queue::fake();
        config(['notifications.sending_enabled' => false]);

        $record = $this->makeRecord('2026-12-15');
        $past = $this->makeSchedule($record, 'sms', '2026-10-10');
        $today = $this->makeSchedule($record, 'sms', '2026-10-15');

        $this->artisan('notifications:dispatch-due')->assertSuccessful();

        $this->assertSame('missed', $past->fresh()->status);
        $this->assertSame('pending', $today->fresh()->status);
        Queue::assertNothingPushed();
    }

    public function test_dispatch_queues_due_reminders_for_enabled_channels_only(): void
    {
        Queue::fake();
        config([
            'notifications.sending_enabled' => true,
            'notifications.sms.sending_enabled' => true,
            'services.nexah.user' => 'nexah-user',
            'services.nexah.password' => 'nexah-pass',
            'services.nexah.sender_id' => 'VISITE',
            'services.whatsapp.token' => 'token',
            'services.whatsapp.phone_number_id' => '123',
        ]);
        $this->center->update(['whatsapp_enabled' => false]);

        $record = $this->makeRecord('2026-12-15');
        $sms = $this->makeSchedule($record, 'sms', '2026-10-15');
        $whatsapp = $this->makeSchedule($record, 'whatsapp', '2026-10-15');
        $tomorrow = $this->makeSchedule($record, 'sms', '2026-10-16');

        $this->artisan('notifications:dispatch-due')->assertSuccessful();

        $this->assertSame('queued', $sms->fresh()->status);
        $this->assertSame('pending', $whatsapp->fresh()->status);
        $this->assertSame('pending', $tomorrow->fresh()->status);
        Queue::assertPushed(SendSmsNotificationJob::class, 1);
        Queue::assertNotPushed(SendWhatsAppNotificationJob::class);

        $this->artisan('notifications:dispatch-due')->assertSuccessful();
        Queue::assertPushed(SendSmsNotificationJob::class, 1);
    }

    public function test_dispatch_waits_for_send_window(): void
    {
        Queue::fake();
        config(['notifications.sending_enabled' => true]);
        Carbon::setTestNow(Carbon::parse('2026-10-15 06:30:00'));

        $record = $this->makeRecord('2026-12-15');
        $sms = $this->makeSchedule($record, 'sms', '2026-10-15');

        $this->artisan('notifications:dispatch-due')->assertSuccessful();

        $this->assertSame('pending', $sms->fresh()->status);
        Queue::assertNothingPushed();
    }

    public function test_dispatch_skips_inactive_centers(): void
    {
        Queue::fake();
        config(['notifications.sending_enabled' => true]);
        $this->center->update(['status' => 'inactive']);

        $record = $this->makeRecord('2026-12-15');
        $sms = $this->makeSchedule($record, 'sms', '2026-10-15');

        $this->artisan('notifications:dispatch-due')->assertSuccessful();

        $this->assertSame('pending', $sms->fresh()->status);
        Queue::assertNothingPushed();
    }

    public function test_admin_can_update_reminder_settings(): void
    {
        Queue::fake();

        $this->actingAs($this->admin)
            ->get(route('center.settings.edit'))
            ->assertOk()
            ->assertSee(__('Save reminder settings'));

        $this->actingAs($this->admin)
            ->put(route('center.reminders.update'), [
                'reminders_form' => 1,
                'sms_enabled' => 1,
                'whatsapp_enabled' => 0,
                'reminder_days' => [7, 30, 1],
            ])
            ->assertRedirect(route('center.settings.edit'));

        $center = $this->center->fresh();
        $this->assertTrue($center->sms_enabled);
        $this->assertFalse($center->whatsapp_enabled);
        $this->assertSame([30, 7, 1], $center->reminder_days);
        Queue::assertPushed(GenerateNotificationSchedulesJob::class, fn ($job) => $job->centerId === $this->center->id);
    }

    public function test_reminder_settings_validation(): void
    {
        $this->actingAs($this->admin)
            ->put(route('center.reminders.update'), [
                'sms_enabled' => 1,
                'whatsapp_enabled' => 1,
            ])
            ->assertSessionHasErrors('reminder_days');

        $this->actingAs($this->admin)
            ->put(route('center.reminders.update'), [
                'sms_enabled' => 1,
                'reminder_days' => [999],
            ])
            ->assertSessionHasErrors('reminder_days.0');
    }

    public function test_operator_cannot_change_reminder_settings(): void
    {
        $operator = User::factory()->create(['center_id' => $this->center->id, 'role' => 'operator']);
        $operator->assignRole('operator');

        $this->actingAs($operator)
            ->put(route('center.reminders.update'), ['reminder_days' => [7]])
            ->assertForbidden();
    }

    public function test_schedules_page_shows_new_statuses(): void
    {
        $record = $this->makeRecord('2026-12-15');
        $this->makeSchedule($record, 'sms', '2026-10-10', 'missed');

        $this->actingAs($this->admin)
            ->get(route('schedules.index'))
            ->assertOk()
            ->assertSee(__('Missed'));
    }

    private function makeRecord(string $expiry, string $status = 'Inspection acceptée et activée', string $plate = 'LT 164 HP'): InspectionRecord
    {
        static $n = 0;
        $n++;

        return InspectionRecord::create([
            'center_id' => $this->center->id,
            'import_batch_id' => $this->batch->id,
            'expiration_date' => $expiry,
            'licence_plate' => $plate,
            'customer_name' => 'Client '.$n,
            'phone_number' => '677123456',
            'normalized_phone_number' => '+237677123456',
            'status' => $status,
            'record_hash' => hash('sha256', 'record-'.$n.'-'.$expiry.'-'.$plate.'-'.$status),
        ]);
    }

    private function makeSchedule(InspectionRecord $record, string $channel, string $date, string $status = 'pending'): NotificationSchedule
    {
        return NotificationSchedule::create([
            'center_id' => $record->center_id,
            'inspection_record_id' => $record->id,
            'channel' => $channel,
            'scheduled_date' => $date,
            'status' => $status,
        ]);
    }
}
