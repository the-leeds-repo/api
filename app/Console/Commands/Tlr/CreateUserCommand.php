<?php

namespace App\Console\Commands\Tlr;

use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use App\RoleManagement\RoleManagerInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateUserCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tlr:create-user 
        {first_name : The user\'s first name} 
        {last_name : The user\' last name} 
        {email : The user\'s email} 
        {phone : The user\'s phone number}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a new user with Super Admin privileges';

    /**
     * Execute the console command.
     *
     * @throws \Throwable
     * @return mixed
     */
    public function handle()
    {
        return DB::transaction(function () {
            // Cache the password to display.
            $password = Str::random();

            $user = $this->createUser($password);
            $this->makeSuperAdmin($user);

            // Output message.
            $this->info('User created successfully.');
            $this->warn("Password: $password");

            return true;
        });
    }

    /**
     * @param string $password
     *
     * @return \App\Models\User
     */
    protected function createUser(string $password): User
    {
        return User::create([
            'first_name' => $this->argument('first_name'),
            'last_name' => $this->argument('last_name'),
            'email' => $this->argument('email'),
            'phone' => $this->argument('phone'),
            'password' => bcrypt($password),
        ]);
    }

    /**
     * @param \App\Models\User $user
     * @return \App\Models\User
     */
    protected function makeSuperAdmin(User $user): User
    {
        /** @var \App\RoleManagement\RoleManagerInterface $roleManager */
        $roleManager = app()->make(RoleManagerInterface::class, [
            'user' => $user,
        ]);

        return $roleManager->updateRoles([
            new UserRole(['role_id' => Role::superAdmin()->id]),
        ]);
    }
}
