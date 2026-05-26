<?php

namespace Database\Seeders;

use App\Models\InspectionCenter;
use App\Models\NotificationTemplate;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $center = InspectionCenter::firstOrCreate(
            ['name' => 'Centre de Visite Technique Douala'],
            [
                'phone' => '+237677000000',
                'email' => 'contact@visite-douala.cm',
                'address' => 'Douala, Cameroun',
                'status' => 'active',
            ]
        );

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

        NotificationTemplate::firstOrCreate(
            ['center_id' => $center->id, 'channel' => 'sms', 'language' => 'fr'],
            [
                'title' => 'Rappel visite technique SMS',
                'content' => 'Cher client, votre visite technique pour le véhicule {licence_plate} expire le {expiration_date}. Veuillez passer au centre pour le renouvellement.',
                'status' => 'active',
            ]
        );

        NotificationTemplate::firstOrCreate(
            ['center_id' => $center->id, 'channel' => 'whatsapp', 'language' => 'fr'],
            [
                'title' => 'Rappel visite technique WhatsApp',
                'content' => 'Bonjour {customer_name}, votre visite technique pour le véhicule {licence_plate} expire le {expiration_date}. Merci de passer au centre pour le renouvellement.',
                'status' => 'active',
            ]
        );
    }
}
