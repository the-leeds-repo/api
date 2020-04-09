<?php

namespace App\Console\Commands\Tlr;

use App\Models\User;
use App\RoleManagement\RoleManagerInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncUserRolesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tlr:sync-user-roles';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Syncs all user roles';

    /**
     * Execute the console command.
     *
     * @throws \Throwable
     * @return mixed
     */
    public function handle()
    {
        $this->line('Starting role sync for all users...');

        DB::transaction(function () {
            User::query()
                ->with('userRoles')
                ->get()
                ->each(function (User $user): void {
                    $this->line("Syncing roles for user [{$user->id}]...");

                    /** @var \App\RoleManagement\RoleManagerInterface $roleManager */
                    $roleManager = app()->make(RoleManagerInterface::class, [
                        'user' => $user,
                    ]);

                    $roleManager->updateRoles($user->userRoles->all());

                    $this->info("Completed role sync for user [{$user->id}].");
                });
        });

        $this->info('Complete role sync for all users.');
    }
}
