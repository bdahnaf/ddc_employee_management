<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/**
 * Creates the first HR account (or promotes an existing one).
 *
 *   php artisan hr:create
 *   php artisan hr:create hr@ddclbd.com --name="HR Admin"
 */
class CreateHrUser extends Command
{
    protected $signature = 'hr:create {email? : Email address} {--name= : Full name}';

    protected $description = 'Create an HR account, or make an existing account HR';

    public function handle(): int
    {
        $email = $this->argument('email') ?? $this->ask('Email address');

        if ($existing = User::where('email', $email)->first()) {
            $existing->update(['role' => Role::Hr, 'is_active' => true]);
            ActivityLog::record('user.updated', "Made {$email} HR from the command line", $existing, $existing);
            $this->info("{$email} is now an active HR account.");

            return self::SUCCESS;
        }

        $name = $this->option('name') ?? $this->ask('Full name');
        $password = $this->secret('Password (at least 8 characters, letters and numbers)');
        $confirm = $this->secret('Confirm password');

        $validator = Validator::make(
            ['email' => $email, 'name' => $name, 'password' => $password, 'password_confirmation' => $confirm],
            [
                'email' => ['required', 'email', 'unique:users,email'],
                'name' => ['required', 'string', 'max:120'],
                'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'role' => Role::Hr,
            'is_active' => true,
        ]);
        ActivityLog::record('user.created', "Created HR account {$email} from the command line", $user, $user);

        $this->info("HR account created for {$name} <{$email}>. You can now sign in at /login.");

        return self::SUCCESS;
    }
}
