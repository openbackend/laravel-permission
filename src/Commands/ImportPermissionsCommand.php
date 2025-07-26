<?php

namespace OpenBackend\LaravelPermission\Commands;

use Illuminate\Console\Command;
use OpenBackend\LaravelPermission\Models\Permission;
use OpenBackend\LaravelPermission\Models\Role;

class ImportPermissionsCommand extends Command
{
    protected $signature = 'permission:import {file : JSON file path}';
    protected $description = 'Import permissions and roles from JSON file';

    public function handle()
    {
        $filePath = $this->argument('file');

        if (!file_exists($filePath)) {
            $this->error("File {$filePath} does not exist.");
            return 1;
        }

        try {
            $data = json_decode(file_get_contents($filePath), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->error("Invalid JSON file: " . json_last_error_msg());
                return 1;
            }

            $this->info('Importing permissions and roles...');

            // Import permissions
            if (isset($data['permissions'])) {
                $this->importPermissions($data['permissions']);
            }

            // Import roles
            if (isset($data['roles'])) {
                $this->importRoles($data['roles']);
            }

            $this->info('Import completed successfully!');

        } catch (\Exception $e) {
            $this->error("Error importing: {$e->getMessage()}");
            return 1;
        }

        return 0;
    }

    protected function importPermissions(array $permissions)
    {
        $this->line('Importing permissions...');
        
        foreach ($permissions as $permissionData) {
            try {
                Permission::findOrCreate($permissionData['name'], $permissionData['guard_name'] ?? null);
                $this->line("  ✓ Permission: {$permissionData['name']}");
            } catch (\Exception $e) {
                $this->line("  ✗ Error importing permission {$permissionData['name']}: {$e->getMessage()}");
            }
        }
    }

    protected function importRoles(array $roles)
    {
        $this->line('Importing roles...');
        
        foreach ($roles as $roleData) {
            try {
                $role = Role::findOrCreate($roleData['name'], $roleData['guard_name'] ?? null);
                
                if (isset($roleData['permissions'])) {
                    $role->syncPermissions($roleData['permissions']);
                }
                
                $this->line("  ✓ Role: {$roleData['name']}");
            } catch (\Exception $e) {
                $this->line("  ✗ Error importing role {$roleData['name']}: {$e->getMessage()}");
            }
        }
    }
}
