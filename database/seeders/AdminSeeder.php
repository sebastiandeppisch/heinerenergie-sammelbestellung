<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Creates the system admin from config/seed.php, like the registration: admin of the first group, if there is one.
 */
class AdminSeeder extends Seeder
{
    public function run(): void
    {
        /** @var array{first_name: string|null, last_name: string|null, email: string|null, password: string|null} $admin */
        $admin = config('seed.admin');

        if (blank($admin['email']) || blank($admin['password'])) {
            $this->command?->warn('Kein Systemadmin angelegt: SEED_ADMIN_EMAIL und SEED_ADMIN_PASSWORD fehlen.');

            return;
        }

        $user = User::updateOrCreate(
            ['email' => $admin['email']],
            [
                'first_name' => $admin['first_name'] ?? 'Admin',
                'last_name' => $admin['last_name'] ?? 'Admin',
                'password' => $admin['password'],
                'email_verified_at' => now(),
            ],
        );
        $user->forceFill(['is_admin' => true])->save();

        $group = Group::first();

        if ($group instanceof Group) {
            $user->groups()->syncWithoutDetaching([$group->id => ['is_admin' => true]]);
        }

        $this->command?->info('Systemadmin '.$user->email.' angelegt.');
    }
}
