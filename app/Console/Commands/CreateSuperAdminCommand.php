<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateSuperAdminCommand extends Command
{
    protected $signature = 'visite:create-super-admin
        {email : Super-admin email}
        {--name=Super Admin : Display name}
        {--password= : Password (prompted if omitted)}';

    protected $description = 'Create or update a super-admin user for production bootstrap';

    public function handle(): int
    {
        $email = strtolower(trim((string) $this->argument('email')));
        $name = trim((string) $this->option('name'));
        $password = $this->option('password') ?: $this->secret('Password');

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Invalid email.');

            return self::FAILURE;
        }

        if (! is_string($password) || strlen($password) < 8) {
            $this->error('Password must be at least 8 characters.');

            return self::FAILURE;
        }

        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name !== '' ? $name : 'Super Admin',
                'password' => Hash::make($password),
                'role' => 'super-admin',
                'status' => 'active',
                'center_id' => null,
            ]
        );

        $user->syncRoles(['super-admin']);

        $this->info('Super-admin ready: '.$user->email);

        return self::SUCCESS;
    }
}
