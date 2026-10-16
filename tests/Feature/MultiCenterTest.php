<?php

namespace Tests\Feature;

use App\Models\ImportBatch;
use App\Models\InspectionCenter;
use App\Models\InspectionRecord;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiCenterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_center_admin_cannot_view_another_centers_record(): void
    {
        $centerA = InspectionCenter::create(['name' => 'Center A', 'status' => 'active']);
        $centerB = InspectionCenter::create(['name' => 'Center B', 'status' => 'active']);

        $admin = User::factory()->create([
            'center_id' => $centerA->id,
            'role' => 'admin',
        ]);
        $admin->assignRole('admin');

        $record = $this->makeRecord($centerB, $admin);

        $this->actingAs($admin)
            ->get(route('records.show', $record))
            ->assertForbidden();
    }

    public function test_super_admin_only_sees_the_selected_center(): void
    {
        $centerA = InspectionCenter::create(['name' => 'Center A', 'status' => 'active']);
        $centerB = InspectionCenter::create(['name' => 'Center B', 'status' => 'active']);

        $super = User::factory()->create([
            'center_id' => null,
            'role' => 'super-admin',
        ]);
        $super->assignRole('super-admin');

        $recordA = $this->makeRecord($centerA, $super);
        $recordB = $this->makeRecord($centerB, $super);

        $this->actingAs($super)
            ->withSession(['current_center_id' => $centerB->id])
            ->get(route('records.show', $recordB))
            ->assertOk();

        $this->actingAs($super)
            ->withSession(['current_center_id' => $centerB->id])
            ->get(route('records.show', $recordA))
            ->assertForbidden();
    }

    public function test_operator_cannot_manage_centers(): void
    {
        $center = InspectionCenter::create(['name' => 'Center A', 'status' => 'active']);
        $operator = User::factory()->create([
            'center_id' => $center->id,
            'role' => 'operator',
        ]);
        $operator->assignRole('operator');

        $this->actingAs($operator)
            ->get(route('centers.index'))
            ->assertForbidden();
    }

    public function test_super_admin_can_create_a_center_and_switch_to_it(): void
    {
        $super = User::factory()->create([
            'center_id' => null,
            'role' => 'super-admin',
        ]);
        $super->assignRole('super-admin');

        $response = $this->actingAs($super)
            ->post(route('centers.store'), [
                'name' => 'Centre Bafoussam',
                'code' => 'baf',
                'city' => 'Bafoussam',
                'status' => 'active',
            ]);

        $center = InspectionCenter::where('name', 'Centre Bafoussam')->first();
        $this->assertNotNull($center);
        $this->assertSame('BAF', $center->code);
        $response->assertRedirect(route('centers.index'));
        $response->assertSessionHas('current_center_id', $center->id);
    }

    public function test_super_admin_can_load_every_page(): void
    {
        $center = InspectionCenter::create(['name' => 'Center A', 'code' => 'AAA', 'city' => 'Douala', 'status' => 'active']);
        $super = $this->makeUser(null, 'super-admin');
        $record = $this->makeRecord($center, $super);

        $pages = [
            route('centers.index'),
            route('centers.create'),
            route('centers.edit', $center),
            route('dashboard'),
            route('imports.create'),
            route('import-history.index'),
            route('import-history.show', $record->import_batch_id),
            route('records.index'),
            route('records.show', $record),
            route('schedules.index'),
            route('notifications.index'),
            route('templates.index'),
            route('users.index'),
            route('center.settings.edit'),
            route('account.password.edit'),
        ];

        foreach ($pages as $url) {
            $this->actingAs($super)
                ->withSession(['current_center_id' => $center->id])
                ->get($url)
                ->assertOk();
        }

        $this->actingAs($super)
            ->withSession(['current_center_id' => $center->id])
            ->get(route('centers.index'))
            ->assertSee('Center A')
            ->assertSee('AAA');
    }

    public function test_admin_and_operator_pages_load(): void
    {
        $center = InspectionCenter::create(['name' => 'Center A', 'status' => 'active']);
        $admin = $this->makeUser($center, 'admin');
        $operator = $this->makeUser($center, 'operator');

        foreach (['dashboard', 'records.index', 'templates.index', 'users.index', 'center.settings.edit'] as $name) {
            $this->actingAs($admin)->get(route($name))->assertOk();
        }

        foreach (['dashboard', 'imports.create', 'import-history.index', 'schedules.index', 'notifications.index'] as $name) {
            $this->actingAs($operator)->get(route($name))->assertOk();
        }

        $this->actingAs($admin)->get(route('centers.index'))->assertForbidden();
        $this->actingAs($operator)->get(route('center.settings.edit'))->assertForbidden();
    }

    public function test_super_admin_without_a_selected_center_is_sent_to_centers(): void
    {
        $super = $this->makeUser(null, 'super-admin');

        $this->actingAs($super)
            ->get(route('dashboard'))
            ->assertRedirect(route('centers.index'));

        $this->actingAs($super)
            ->get(route('centers.index'))
            ->assertOk();
    }

    public function test_super_admin_can_update_and_switch_centers(): void
    {
        $centerA = InspectionCenter::create(['name' => 'Center A', 'status' => 'active']);
        $centerB = InspectionCenter::create(['name' => 'Center B', 'status' => 'active']);
        $super = $this->makeUser(null, 'super-admin');

        $this->actingAs($super)
            ->withSession(['current_center_id' => $centerA->id])
            ->put(route('centers.update', $centerB), [
                'name' => 'Center B Renamed',
                'code' => 'bbb',
                'city' => 'Yaoundé',
                'status' => 'inactive',
            ])
            ->assertRedirect(route('centers.index'));

        $centerB->refresh();
        $this->assertSame('Center B Renamed', $centerB->name);
        $this->assertSame('BBB', $centerB->code);
        $this->assertSame('inactive', $centerB->status);

        $this->actingAs($super)
            ->withSession(['current_center_id' => $centerA->id])
            ->post(route('centers.switch'), ['center_id' => $centerB->id])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('current_center_id', $centerB->id);
    }

    public function test_center_code_must_be_unique(): void
    {
        InspectionCenter::create(['name' => 'Center A', 'code' => 'AAA', 'status' => 'active']);
        $super = $this->makeUser(null, 'super-admin');

        $this->actingAs($super)
            ->post(route('centers.store'), [
                'name' => 'Center Duplicate',
                'code' => 'aaa',
                'status' => 'active',
            ])
            ->assertSessionHasErrors('code');
    }

    public function test_users_of_an_inactive_center_cannot_log_in(): void
    {
        $center = InspectionCenter::create(['name' => 'Center A', 'status' => 'inactive']);
        $admin = $this->makeUser($center, 'admin');

        $this->post(route('login'), [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_super_admin_can_delete_a_center_and_its_users(): void
    {
        $centerA = InspectionCenter::create(['name' => 'Center A', 'status' => 'active']);
        $centerB = InspectionCenter::create(['name' => 'Center B', 'status' => 'active']);
        $super = $this->makeUser(null, 'super-admin');
        $admin = $this->makeUser($centerB, 'admin');
        $record = $this->makeRecord($centerB, $admin);

        $this->actingAs($super)
            ->withSession(['current_center_id' => $centerB->id])
            ->delete(route('centers.destroy', $centerB))
            ->assertRedirect(route('centers.index'));

        $this->assertDatabaseMissing('inspection_centers', ['id' => $centerB->id]);
        $this->assertDatabaseMissing('users', ['id' => $admin->id]);
        $this->assertDatabaseMissing('inspection_records', ['id' => $record->id]);
        $this->assertDatabaseHas('inspection_centers', ['id' => $centerA->id]);
        $this->assertSame($centerA->id, session('current_center_id'));
    }

    public function test_super_admin_can_delete_multiple_centers(): void
    {
        $keep = InspectionCenter::create(['name' => 'Keep', 'status' => 'active']);
        $a = InspectionCenter::create(['name' => 'Delete A', 'status' => 'active']);
        $b = InspectionCenter::create(['name' => 'Delete B', 'status' => 'active']);
        $super = $this->makeUser(null, 'super-admin');

        $this->actingAs($super)
            ->withSession(['current_center_id' => $keep->id])
            ->delete(route('centers.destroy-many'), ['center_ids' => [$a->id, $b->id]])
            ->assertRedirect(route('centers.index'));

        $this->assertDatabaseMissing('inspection_centers', ['id' => $a->id]);
        $this->assertDatabaseMissing('inspection_centers', ['id' => $b->id]);
        $this->assertDatabaseHas('inspection_centers', ['id' => $keep->id]);
    }

    public function test_cannot_delete_the_last_center(): void
    {
        $only = InspectionCenter::create(['name' => 'Only Center', 'status' => 'active']);
        $super = $this->makeUser(null, 'super-admin');

        $this->actingAs($super)
            ->withSession(['current_center_id' => $only->id])
            ->from(route('centers.index'))
            ->delete(route('centers.destroy', $only))
            ->assertRedirect(route('centers.index'))
            ->assertSessionHasErrors('center_ids');

        $this->assertDatabaseHas('inspection_centers', ['id' => $only->id]);
    }

    public function test_admin_cannot_delete_centers(): void
    {
        $center = InspectionCenter::create(['name' => 'Center A', 'status' => 'active']);
        InspectionCenter::create(['name' => 'Center B', 'status' => 'active']);
        $admin = $this->makeUser($center, 'admin');

        $this->actingAs($admin)
            ->delete(route('centers.destroy', $center))
            ->assertForbidden();
    }

    private function makeUser(?InspectionCenter $center, string $role): User
    {
        $user = User::factory()->create([
            'center_id' => $center?->id,
            'role' => $role,
        ]);
        $user->assignRole($role);

        return $user;
    }

    private function makeRecord(InspectionCenter $center, User $uploader): InspectionRecord
    {
        $batch = ImportBatch::create([
            'center_id' => $center->id,
            'uploaded_by' => $uploader->id,
            'filename' => 'imports/test.csv',
            'original_filename' => 'test.csv',
            'status' => 'completed',
        ]);

        return InspectionRecord::create([
            'center_id' => $center->id,
            'import_batch_id' => $batch->id,
            'expiration_date' => '2027-01-20',
            'licence_plate' => 'LT '.$center->id.' AA',
            'customer_name' => 'Client '.$center->id,
            'phone_number' => '677123456',
            'normalized_phone_number' => '+237677123456',
            'status' => 'APTE',
            'record_hash' => hash('sha256', 'record-'.$center->id.'-'.$batch->id),
        ]);
    }
}
