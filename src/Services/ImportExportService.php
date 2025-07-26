<?php

namespace OpenBackend\LaravelPermission\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use OpenBackend\LaravelPermission\Models\Role;
use OpenBackend\LaravelPermission\Models\Permission;

class ImportExportService
{
    /**
     * Export permissions and roles to JSON.
     */
    public function exportToJson(): array
    {
        return [
            'permissions' => Permission::with('roles')->get()->map(function ($permission) {
                return [
                    'name' => $permission->name,
                    'guard_name' => $permission->guard_name,
                    'description' => $permission->description,
                    'group' => $permission->group,
                    'resource_type' => $permission->resource_type,
                    'resource_id' => $permission->resource_id,
                    'expires_at' => $permission->expires_at,
                    'meta' => $permission->meta,
                    'roles' => $permission->roles->pluck('name')->toArray()
                ];
            })->toArray(),
            'roles' => Role::with(['permissions', 'parent'])->get()->map(function ($role) {
                return [
                    'name' => $role->name,
                    'guard_name' => $role->guard_name,
                    'description' => $role->description,
                    'parent' => $role->parent?->name,
                    'level' => $role->level,
                    'meta' => $role->meta,
                    'permissions' => $role->permissions->pluck('name')->toArray()
                ];
            })->toArray()
        ];
    }

    /**
     * Export permissions and roles to CSV.
     */
    public function exportToCSV(): array
    {
        $permissions = Permission::with('roles')->get()->map(function ($permission) {
            return [
                'type' => 'permission',
                'name' => $permission->name,
                'guard_name' => $permission->guard_name,
                'description' => $permission->description,
                'group' => $permission->group,
                'roles' => $permission->roles->pluck('name')->implode(';')
            ];
        });

        $roles = Role::with(['permissions', 'parent'])->get()->map(function ($role) {
            return [
                'type' => 'role',
                'name' => $role->name,
                'guard_name' => $role->guard_name,
                'description' => $role->description,
                'parent' => $role->parent?->name,
                'permissions' => $role->permissions->pluck('name')->implode(';')
            ];
        });

        return $permissions->merge($roles)->toArray();
    }

    /**
     * Import from JSON.
     */
    public function importFromJson(array $data): array
    {
        $results = ['permissions' => [], 'roles' => []];

        DB::transaction(function () use ($data, &$results) {
            // Import permissions first
            foreach ($data['permissions'] ?? [] as $permissionData) {
                try {
                    $permission = Permission::firstOrCreate([
                        'name' => $permissionData['name'],
                        'guard_name' => $permissionData['guard_name']
                    ], $permissionData);
                    
                    $results['permissions'][] = "Created/Updated: {$permission->name}";
                } catch (\Exception $e) {
                    $results['permissions'][] = "Error: {$permissionData['name']} - {$e->getMessage()}";
                }
            }

            // Import roles with hierarchy
            $rolesWithParents = [];
            foreach ($data['roles'] ?? [] as $roleData) {
                try {
                    $role = Role::firstOrCreate([
                        'name' => $roleData['name'],
                        'guard_name' => $roleData['guard_name']
                    ], collect($roleData)->except(['parent', 'permissions'])->toArray());

                    if (!empty($roleData['parent'])) {
                        $rolesWithParents[] = ['role' => $role, 'parent' => $roleData['parent']];
                    }

                    // Assign permissions
                    if (!empty($roleData['permissions'])) {
                        $role->syncPermissions($roleData['permissions']);
                    }

                    $results['roles'][] = "Created/Updated: {$role->name}";
                } catch (\Exception $e) {
                    $results['roles'][] = "Error: {$roleData['name']} - {$e->getMessage()}";
                }
            }

            // Set up role hierarchy
            foreach ($rolesWithParents as $item) {
                try {
                    $parent = Role::findByName($item['parent'], $item['role']->guard_name);
                    $item['role']->setParent($parent);
                } catch (\Exception $e) {
                    $results['roles'][] = "Parent Error: {$item['role']->name} - {$e->getMessage()}";
                }
            }
        });

        return $results;
    }

    /**
     * Import from CSV.
     */
    public function importFromCSV(array $csvData): array
    {
        $permissions = [];
        $roles = [];

        foreach ($csvData as $row) {
            if ($row['type'] === 'permission') {
                $permissions[] = [
                    'name' => $row['name'],
                    'guard_name' => $row['guard_name'],
                    'description' => $row['description'] ?? null,
                    'group' => $row['group'] ?? null,
                    'roles' => !empty($row['roles']) ? explode(';', $row['roles']) : []
                ];
            } elseif ($row['type'] === 'role') {
                $roles[] = [
                    'name' => $row['name'],
                    'guard_name' => $row['guard_name'],
                    'description' => $row['description'] ?? null,
                    'parent' => $row['parent'] ?? null,
                    'permissions' => !empty($row['permissions']) ? explode(';', $row['permissions']) : []
                ];
            }
        }

        return $this->importFromJson(['permissions' => $permissions, 'roles' => $roles]);
    }
}
