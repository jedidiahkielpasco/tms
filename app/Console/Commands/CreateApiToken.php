<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CreateApiToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:create-token {email? : The email of the user} {--name=Test User : The name of the user if creating new}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create an API token for a user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $name = $this->option('name');

        if (!$email) {
            $email = 'test@example.com';
            $this->info("No email provided, using default: $email");
        }

        $user = \App\Models\User::firstOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => \Illuminate\Support\Facades\Hash::make('password')]
        );

        $token = $user->createToken('cli-token')->plainTextToken;

        $this->info("User: {$user->email}");
        $this->info("Token: $token");
        $this->info("\nUse this token in the Authorization header:");
        $this->line("Authorization: Bearer $token");
    }
}
