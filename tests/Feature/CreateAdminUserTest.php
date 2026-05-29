<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

function setAdminEnv(?string $email, ?string $password, ?string $name = null): void
{
    foreach (['ADMIN_EMAIL' => $email, 'ADMIN_PASSWORD' => $password, 'ADMIN_NAME' => $name] as $key => $value) {
        if ($value === null) {
            unset($_SERVER[$key], $_ENV[$key]);
            putenv($key);
        } else {
            $_SERVER[$key] = $value;
            $_ENV[$key] = $value;
            putenv("{$key}={$value}");
        }
    }
}

afterEach(function () {
    setAdminEnv(null, null, null);
});

test('command fails when env vars are missing', function () {
    setAdminEnv(null, null, null);

    $this->artisan('users:create-admin')->assertFailed();

    expect(User::count())->toBe(0);
});

test('command creates an admin user from env vars', function () {
    setAdminEnv('admin@example.com', 'secret-pass', 'Sentinel Admin');

    $this->artisan('users:create-admin')->assertSuccessful();

    $user = User::firstWhere('email', 'admin@example.com');
    expect($user)->not->toBeNull()
        ->and($user->name)->toBe('Sentinel Admin')
        ->and(Hash::check('secret-pass', $user->password))->toBeTrue()
        ->and($user->email_verified_at)->not->toBeNull();
});

test('command is idempotent when user already exists', function () {
    setAdminEnv('admin@example.com', 'secret-pass');

    User::factory()->create(['email' => 'admin@example.com', 'name' => 'Pre-existing']);

    $this->artisan('users:create-admin')->assertSuccessful();

    expect(User::where('email', 'admin@example.com')->count())->toBe(1)
        ->and(User::firstWhere('email', 'admin@example.com')->name)->toBe('Pre-existing');
});
