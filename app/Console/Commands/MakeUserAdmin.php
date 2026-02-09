<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class MakeUserAdmin extends Command
{
    protected $signature = 'user:make-admin {email}';

    protected $description = 'Grant admin privileges to a user by email';

    public function handle(): int
    {
        $email = $this->argument('email');
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User with email {$email} not found.");

            return Command::FAILURE;
        }

        if ($user->is_admin) {
            $this->info("{$user->name} is already an admin.");

            return Command::SUCCESS;
        }

        $user->update(['is_admin' => true]);
        $this->info("Admin privileges granted to {$user->name}.");

        return Command::SUCCESS;
    }
}
