<?php

namespace Tests\Feature;

use App\Models\ImportBatch;
use App\Models\InspectionCenter;
use App\Models\InspectionRecord;
use App\Models\NotificationLog;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    use RefreshDatabase;

    private InspectionCenter $center;

    private User $admin;

    private User $operator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        Carbon::setTestNow(Carbon::parse('2026-10-15 12:00:00', 'Africa/Douala'));

        $this->center = InspectionCenter::create([
            'name' => 'Centre Douala',
            'status' => 'active',
        ]);

        $this->admin = User::factory()->create([
            'center_id' => $this->center->id,
            'role' => 'admin',
        ]);
        $this->admin->assignRole('admin');

        $this->operator = User::factory()->create([
            'center_id' => $this->center->id,
            'role' => 'operator',
        ]);
        $this->operator->assignRole('operator');

        $batch = ImportBatch::create([
            'center_id' => $this->center->id,
            'uploaded_by' => $this->admin->id,
            'filename' => 'imports/report.csv',
            'original_filename' => 'report.csv',
            'total_rows' => 10,
            'imported_rows' => 8,
            'duplicate_rows' => 1,
            'failed_rows' => 1,
            'status' => 'completed',
            'created_at' => Carbon::parse('2026-10-10 09:00:00'),
        ]);

        $record = InspectionRecord::create([
            'center_id' => $this->center->id,
            'import_batch_id' => $batch->id,
            'expiration_date' => '2026-10-20',
            'inspection_date' => '2025-10-20',
            'licence_plate' => 'LT 100 AA',
            'customer_name' => 'Alice Martin',
            'phone_number' => '677000111',
            'normalized_phone_number' => '+237677000111',
            'status' => 'Inspection acceptée et activée',
            'record_hash' => hash('sha256', 'report-record-1'),
        ]);

        NotificationLog::create([
            'center_id' => $this->center->id,
            'inspection_record_id' => $record->id,
            'channel' => 'sms',
            'provider' => 'nexah',
            'phone_number' => '+237677000111',
            'message' => 'SMS preview',
            'provider_message_id' => 'sms-1',
            'delivery_status' => 'delivered',
            'sent_at' => Carbon::parse('2026-10-12 10:00:00'),
            'delivered_at' => Carbon::parse('2026-10-12 10:01:00'),
            'created_at' => Carbon::parse('2026-10-12 10:00:00'),
        ]);

        NotificationLog::create([
            'center_id' => $this->center->id,
            'inspection_record_id' => $record->id,
            'channel' => 'whatsapp',
            'provider' => 'meta_whatsapp',
            'phone_number' => '+237677000111',
            'message' => 'WA preview',
            'provider_message_id' => 'wa-1',
            'delivery_status' => 'failed',
            'error_message' => 'Template paused',
            'failed_at' => Carbon::parse('2026-10-13 11:00:00'),
            'created_at' => Carbon::parse('2026-10-13 11:00:00'),
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_admin_can_view_reports_overview(): void
    {
        $this->actingAs($this->admin)
            ->get(route('reports.index', ['from' => '2026-10-01', 'to' => '2026-10-15']))
            ->assertOk()
            ->assertSee(__('Reports'))
            ->assertSee(__('Daily activity'))
            ->assertSee(__('SMS delivery'))
            ->assertSee(__('WhatsApp delivery'))
            ->assertSee(__('Failed notifications'));
    }

    public function test_operator_can_view_reports(): void
    {
        $this->actingAs($this->operator)
            ->get(route('reports.index'))
            ->assertOk();
    }

    public function test_user_without_permission_cannot_view_reports(): void
    {
        $user = User::factory()->create([
            'center_id' => $this->center->id,
            'role' => 'admin',
        ]);
        // No Spatie role / permissions assigned.

        $this->actingAs($user)
            ->get(route('reports.index'))
            ->assertForbidden();
    }

    public function test_import_report_lists_batches_in_range(): void
    {
        $this->actingAs($this->admin)
            ->get(route('reports.imports', ['from' => '2026-10-01', 'to' => '2026-10-15']))
            ->assertOk()
            ->assertSee('report.csv')
            ->assertSee('8');
    }

    public function test_expiry_report_lists_expiring_records(): void
    {
        $this->actingAs($this->admin)
            ->get(route('reports.expiry', ['window' => 'month']))
            ->assertOk()
            ->assertSee('LT 100 AA')
            ->assertSee('Alice Martin');
    }

    public function test_notification_report_filters_failed(): void
    {
        $this->actingAs($this->admin)
            ->get(route('reports.notifications', [
                'from' => '2026-10-01',
                'to' => '2026-10-15',
                'status' => 'failed',
            ]))
            ->assertOk()
            ->assertSee('Template paused')
            ->assertDontSee('SMS preview');
    }

    public function test_exports_expiry_and_notifications_csv(): void
    {
        $this->actingAs($this->admin)
            ->get(route('reports.expiry.export', ['window' => 'month']))
            ->assertOk()
            ->assertHeader('content-disposition');

        $this->actingAs($this->admin)
            ->get(route('reports.notifications.export', [
                'from' => '2026-10-01',
                'to' => '2026-10-15',
                'channel' => 'sms',
            ]))
            ->assertOk()
            ->assertHeader('content-disposition');
    }

    public function test_nav_shows_reports_for_operator(): void
    {
        $this->actingAs($this->operator)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(__('Reports'));
    }
}
