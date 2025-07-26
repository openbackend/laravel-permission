<?php

namespace OpenBackend\LaravelPermission\Commands;

use Illuminate\Console\Command;

class ShowUserPermissionsCommand extends Command
{
    protected $signature = 'permission:show-user {user : The user ID}';
    protected $description = 'Show all permissions for a user';

    public function handle()
    {
        $userId = $this->argument('user');
        
        try {
            $userModel = config('auth.providers.users.model');
            $user = $userModel::find($userId);

            if (!$user) {
                $this->error("User with ID {$userId} not found.");
                return 1;
            }

            $this->info("Permissions for User ID: {$userId}");
            $this->line('');

            // Show roles
            $roles = $user->getRoleNames();
            if ($roles->isNotEmpty()) {
                $this->info('Roles:');
                foreach ($roles as $role) {
                    $this->line("  - {$role}");
                }
                $this->line('');
            }

            // Show direct permissions
            $directPermissions = $user->getDirectPermissions();
            if ($directPermissions->isNotEmpty()) {
                $this->info('Direct Permissions:');
                foreach ($directPermissions as $permission) {
                    $this->line("  - {$permission->name}");
                }
                $this->line('');
            }

            // Show permissions via roles
            $rolePermissions = $user->getPermissionsViaRoles();
            if ($rolePermissions->isNotEmpty()) {
                $this->info('Permissions via Roles:');
                foreach ($rolePermissions as $permission) {
                    $this->line("  - {$permission->name}");
                }
                $this->line('');
            }

            // Show all permissions
            $allPermissions = $user->getAllPermissions();
            $this->info("Total Permissions: {$allPermissions->count()}");

        } catch (\Exception $e) {
            $this->error("Error: {$e->getMessage()}");
            return 1;
        }

        return 0;
    }
}
