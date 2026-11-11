<?php

namespace Database\Seeders;

use App\Models\InspectionCenter;
use App\Models\User;
use App\Services\Notification\DefaultCenterTemplates;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $center = InspectionCenter::firstOrCreate(
            ['name' => 'Centre de Visite Technique Douala'],
            [
                'code' => 'DLA',
                'phone' => '+237677000000',
                'email' => 'contact@visite-douala.cm',
                'address' => 'Douala, Cameroun',
                'city' => 'Douala',
                'status' => 'active',
            ]
        );
        $center->update([
            'code' => $center->code ?: 'DLA',
            'city' => $center->city ?: 'Douala',
        ]);

        InspectionCenter::firstOrCreate(
            ['name' => 'Centre de Visite Technique Yaoundé'],
            [
                'code' => 'YAO',
                'phone' => '+237699000000',
                'email' => 'contact@visite-yaounde.cm',
                'address' => 'Yaoundé, Cameroun',
                'city' => 'Yaoundé',
                'status' => 'active',
            ]
        );

        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@visite-notify.local'],
            [
                'center_id' => null,
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'role' => 'super-admin',
                'status' => 'active',
            ]
        );
        $superAdmin->syncRoles(['super-admin']);

        $admin = User::firstOrCreate(
            ['email' => 'admin@visite-notify.local'],
            [
                'center_id' => $center->id,
                'name' => 'Administrateur',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'status' => 'active',
            ]
        );
        $admin->syncRoles(['admin']);

        $operator = User::firstOrCreate(
            ['email' => 'operateur@visite-notify.local'],
            [
                'center_id' => $center->id,
                'name' => 'Opérateur',
                'password' => Hash::make('password'),
                'role' => 'operator',
                'status' => 'active',
            ]
        );
        $operator->syncRoles(['operator']);

        app(DefaultCenterTemplates::class)->seedFor($center);

        $yaounde = InspectionCenter::where('name', 'Centre de Visite Technique Yaoundé')->first();
        if ($yaounde) {
            app(DefaultCenterTemplates::class)->seedFor($yaounde);
        }
    }
}
