<?php

namespace OpenBackend\LaravelPermission\Commands;

use Illuminate\Console\Command;
use OpenBackend\LaravelPermission\Models\Permission;
use OpenBackend\LaravelPermission\Models\Role;

class ExportPermissionsCommand extends Command
{
    protected $signature = 'permission:export {--file= : Output file path}';
    protected $description = 'Export permissions and roles to JSON file';

    public function handle()
    {
        $filePath = $this->option('file') ?: 'permissions_export.json';

        try {
            $data = [
                'permissions' => $this->exportPermissions(),
                'roles' => $this->exportRoles(),
                'exported_at' => now()->toISOString(),
            ];

            file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT));

            $this->info("Permissions and roles exported to: {$filePath}");

        } catch (\Exception $e) {
            $this->error("Error exporting: {$e->getMessage()}");
            return 1;
        }

        return 0;
    }

    protected function exportPermissions()
    {
        return Permission::all()->map(function ($permission) {
            return [
                'name' => $permission->name,
                'guard_name' => $permission->guard_name,
                'description' => $permission->description,
                'group' => $permission->group,
            ];
        })->toArray();
    }

    protected function exportRoles()
    {
        return Role::with('permissions')->get()->map(function ($role) {
            return [
                'name' => $role->name,
                'guard_name' => $role->guard_name,
                'description' => $role->description,
                'permissions' => $role->permissions->pluck('name')->toArray(),
            ];
        })->toArray();
    }
}
