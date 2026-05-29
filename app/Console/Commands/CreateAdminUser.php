<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

#[Signature('users:create-admin')]
#[Description('Create the initial admin user from env vars (idempotent).')]
class CreateAdminUser extends Command
{
    public function handle(): int
    {
        $email = (string) env('ADMIN_EMAIL');
        $password = (string) env('ADMIN_PASSWORD');

        if ($email === '' || $password === '') {
            $this->components->error(
                'ADMIN_EMAIL and ADMIN_PASSWORD must be set in the environment.',
            );

            return self::FAILURE;
        }

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => (string) env('ADMIN_NAME', 'Admin'),
                'password' => Hash::make($password),
            ],
        );

        if ($user->wasRecentlyCreated) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        $this->components->info(
            $user->wasRecentlyCreated
                ? "Admin user created: {$email}"
                : "Admin user already exists: {$email}",
        );

        return self::SUCCESS;
    }
}
