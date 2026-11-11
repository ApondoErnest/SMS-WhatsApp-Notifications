<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\InspectionCenter;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    private InspectionCenter $center;

    private User $admin;

    private User $operator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->center = InspectionCenter::create([
            'name' => 'Centre Douala',
            'status' => 'active',
        ]);

        $this->admin = User::factory()->create([
            'center_id' => $this->center->id,
            'role' => 'admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);
        $this->admin->assignRole('admin');

        $this->operator = User::factory()->create([
            'center_id' => $this->center->id,
            'role' => 'operator',
            'email' => 'operator@example.com',
            'status' => 'active',
        ]);
        $this->operator->assignRole('operator');
    }

    public function test_login_is_rate_limited_and_audited(): void
    {
        RateLimiter::clear(strtolower('admin@example.com').'|127.0.0.1');

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login'), [
                'email' => 'admin@example.com',
                'password' => 'wrong-password',
            ])->assertSessionHasErrors('email');
        }

        $this->assertTrue(
            AuditLog::query()->where('action', 'login_failed')->count() >= 5
        );

        $this->post(route('login'), [
            'email' => 'admin@example.com',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->assertStringContainsString(
            '59',
            session('errors')->first('email')
        );
        $this->assertTrue(
            str_contains(session('errors')->first('email'), 'Trop de tentatives')
            || str_contains(session('errors')->first('email'), 'Too many login attempts')
        );
    }

    public function test_successful_login_writes_audit_log(): void
    {
        $this->post(route('login'), [
            'email' => 'admin@example.com',
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'login_success',
            'user_id' => $this->admin->id,
        ]);
    }

    public function test_admin_can_view_audit_logs_operator_cannot(): void
    {
        AuditLog::create([
            'center_id' => $this->center->id,
            'user_id' => $this->admin->id,
            'action' => 'login_success',
            'description' => 'test',
            'ip_address' => '127.0.0.1',
        ]);

        $this->actingAs($this->admin)
            ->get(route('audit-logs.index'))
            ->assertOk()
            ->assertSee('login_success');

        $this->actingAs($this->operator)
            ->get(route('audit-logs.index'))
            ->assertForbidden();
    }

    public function test_meta_webhook_rejects_invalid_signature_when_secret_configured(): void
    {
        config(['services.whatsapp.app_secret' => 'meta-secret']);

        $payload = json_encode(['entry' => []], JSON_THROW_ON_ERROR);

        $this->call(
            'POST',
            '/webhooks/meta/whatsapp',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_HUB_SIGNATURE_256' => 'sha256=deadbeef',
            ],
            $payload,
        )->assertForbidden();

        $valid = 'sha256='.hash_hmac('sha256', $payload, 'meta-secret');

        $this->call(
            'POST',
            '/webhooks/meta/whatsapp',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_HUB_SIGNATURE_256' => $valid,
            ],
            $payload,
        )->assertOk()->assertSee('EVENT_RECEIVED');
    }

    public function test_nexah_webhook_rejects_missing_token_when_configured(): void
    {
        config(['services.nexah.webhook_token' => 'nexah-secret']);

        $this->postJson('/webhooks/nexah/sms', ['dlrlist' => []])
            ->assertForbidden();

        $this->postJson('/webhooks/nexah/sms?token=nexah-secret', ['dlrlist' => []])
            ->assertOk();

        $this->withHeader('X-Nexah-Token', 'nexah-secret')
            ->postJson('/webhooks/nexah/sms', ['dlrlist' => []])
            ->assertOk();
    }

    public function test_database_backup_command_creates_sqlite_copy(): void
    {
        Storage::fake('local');

        $sqliteFile = storage_path('framework/testing/phase9-backup.sqlite');
        if (! is_dir(dirname($sqliteFile))) {
            mkdir(dirname($sqliteFile), 0777, true);
        }
        if (is_file($sqliteFile)) {
            unlink($sqliteFile);
        }
        touch($sqliteFile);
        file_put_contents($sqliteFile, 'SQLite format 3');

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => $sqliteFile,
            'security.backups.enabled' => true,
            'security.backups.disk' => 'local',
            'security.backups.path' => 'backups/database',
            'security.backups.retain' => 5,
        ]);

        $this->assertSame(0, Artisan::call('backup:database'));

        $files = Storage::disk('local')->files('backups/database');
        $this->assertNotEmpty($files);
        $this->assertTrue(str_ends_with($files[0], '.sqlite'));

        @unlink($sqliteFile);
    }

    public function test_user_create_is_audited(): void
    {
        $this->actingAs($this->admin)
            ->post(route('users.store'), [
                'name' => 'New Operator',
                'email' => 'new-op@example.com',
                'phone' => null,
                'password' => 'Password1!',
                'role' => 'operator',
            ])
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'user_created',
            'user_id' => $this->admin->id,
        ]);
    }
}
