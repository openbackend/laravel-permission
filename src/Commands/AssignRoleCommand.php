<?php

namespace OpenBackend\LaravelPermission\Commands;

use Illuminate\Console\Command;
use OpenBackend\LaravelPermission\Models\Role;

class AssignRoleCommand extends Command
{
    protected $signature = 'permission:assign-role 
                            {role : The role to assign}
                            {--user= : The user ID to assign the role to}
                            {--guard= : The guard for the role}';

    protected $description = 'Assign a role to a user';

    public function handle()
    {
        $roleName = $this->argument('role');
        $userId = $this->option('user');
        $guardName = $this->option('guard') ?: config('auth.defaults.guard');

        if (!$userId) {
            $this->error('User ID is required. Use --user option.');
            return 1;
        }

        try {
            $role = Role::findByName($roleName, $guardName);
            
            $userModel = config('auth.providers.users.model');
            $user = $userModel::find($userId);

            if (!$user) {
                $this->error("User with ID {$userId} not found.");
                return 1;
            }

            $user->assignRole($role);
            
            $this->info("Role '{$roleName}' assigned to user {$userId} successfully.");

        } catch (\Exception $e) {
            $this->error("Error assigning role: {$e->getMessage()}");
            return 1;
        }

        return 0;
    }
}
