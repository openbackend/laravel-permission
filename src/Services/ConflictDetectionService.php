<?php

namespace OpenBackend\LaravelPermission\Services;

use Illuminate\Support\Collection;
use OpenBackend\LaravelPermission\Models\Role;
use OpenBackend\LaravelPermission\Models\Permission;

class ConflictDetectionService
{
    /**
     * Detect permission conflicts.
     */
    public function detectConflicts(): array
    {
        return [
            'circular_role_hierarchies' => $this->detectCircularRoleHierarchies(),
            'conflicting_permissions' => $this->detectConflictingPermissions(),
            'orphaned_roles' => $this->detectOrphanedRoles(),
            'duplicate_permissions' => $this->detectDuplicatePermissions(),
            'invalid_role_hierarchies' => $this->detectInvalidRoleHierarchies()
        ];
    }

    /**
     * Detect circular role hierarchies.
     */
    protected function detectCircularRoleHierarchies(): array
    {
        $conflicts = [];
        $roles = Role::whereNotNull('parent_id')->get();

        foreach ($roles as $role) {
            $visited = [];
            $current = $role;

            while ($current && $current->parent_id) {
                if (in_array($current->id, $visited)) {
                    $conflicts[] = [
                        'type' => 'circular_hierarchy',
                        'role_id' => $role->id,
                        'role_name' => $role->name,
                        'circular_path' => $visited,
                        'severity' => 'high'
                    ];
                    break;
                }

                $visited[] = $current->id;
                $current = $current->parent;
            }
        }

        return $conflicts;
    }

    /**
     * Detect conflicting permissions (mutually exclusive permissions).
     */
    protected function detectConflictingPermissions(): array
    {
        $conflicts = [];
        
        // Define mutually exclusive permission patterns
        $exclusivePatterns = [
            ['create *', 'delete *'],
            ['read only *', 'edit *'],
            ['guest access', 'admin access']
        ];

        $roles = Role::with('permissions')->get();

        foreach ($roles as $role) {
            $rolePermissions = $role->permissions->pluck('name')->toArray();

            foreach ($exclusivePatterns as $pattern) {
                $hasFirst = collect($rolePermissions)->contains(function ($perm) use ($pattern) {
                    return $this->matchesPattern($perm, $pattern[0]);
                });

                $hasSecond = collect($rolePermissions)->contains(function ($perm) use ($pattern) {
                    return $this->matchesPattern($perm, $pattern[1]);
                });

                if ($hasFirst && $hasSecond) {
                    $conflicts[] = [
                        'type' => 'conflicting_permissions',
                        'role_id' => $role->id,
                        'role_name' => $role->name,
                        'pattern' => $pattern,
                        'conflicting_permissions' => $rolePermissions,
                        'severity' => 'medium'
                    ];
                }
            }
        }

        return $conflicts;
    }

    /**
     * Detect orphaned roles (roles with no users and no children).
     */
    protected function detectOrphanedRoles(): array
    {
        $orphaned = [];
        $roles = Role::withCount(['users', 'children'])->get();

        foreach ($roles as $role) {
            if ($role->users_count === 0 && $role->children_count === 0) {
                $orphaned[] = [
                    'type' => 'orphaned_role',
                    'role_id' => $role->id,
                    'role_name' => $role->name,
                    'created_at' => $role->created_at,
                    'severity' => 'low'
                ];
            }
        }

        return $orphaned;
    }

    /**
     * Detect duplicate permissions (same functionality, different names).
     */
    protected function detectDuplicatePermissions(): array
    {
        $duplicates = [];
        $permissions = Permission::all();

        $grouped = $permissions->groupBy(function ($permission) {
            // Group by similar functionality patterns
            $name = strtolower($permission->name);
            $name = preg_replace('/[^a-z\s]/', '', $name);
            $words = explode(' ', $name);
            sort($words);
            return implode(' ', $words);
        });

        foreach ($grouped as $key => $group) {
            if ($group->count() > 1) {
                $duplicates[] = [
                    'type' => 'duplicate_permissions',
                    'pattern' => $key,
                    'permissions' => $group->map(function ($perm) {
                        return [
                            'id' => $perm->id,
                            'name' => $perm->name,
                            'description' => $perm->description
                        ];
                    })->toArray(),
                    'severity' => 'low'
                ];
            }
        }

        return $duplicates;
    }

    /**
     * Detect invalid role hierarchies (depth too deep, etc.).
     */
    protected function detectInvalidRoleHierarchies(): array
    {
        $invalid = [];
        $maxDepth = config('permission.hierarchical_roles.max_depth', 10);
        $roles = Role::all();

        foreach ($roles as $role) {
            $depth = $this->calculateRoleDepth($role);
            
            if ($depth > $maxDepth) {
                $invalid[] = [
                    'type' => 'hierarchy_too_deep',
                    'role_id' => $role->id,
                    'role_name' => $role->name,
                    'depth' => $depth,
                    'max_allowed' => $maxDepth,
                    'severity' => 'medium'
                ];
            }
        }

        return $invalid;
    }

    /**
     * Auto-fix detected conflicts.
     */
    public function autoFixConflicts(array $conflicts): array
    {
        $fixed = [];

        foreach ($conflicts as $category => $categoryConflicts) {
            foreach ($categoryConflicts as $conflict) {
                try {
                    switch ($conflict['type']) {
                        case 'orphaned_role':
                            $this->fixOrphanedRole($conflict);
                            $fixed[] = "Deleted orphaned role: {$conflict['role_name']}";
                            break;

                        case 'duplicate_permissions':
                            $this->fixDuplicatePermissions($conflict);
                            $fixed[] = "Merged duplicate permissions for pattern: {$conflict['pattern']}";
                            break;

                        // Note: Circular hierarchies and conflicting permissions require manual intervention
                        default:
                            $fixed[] = "Manual intervention required for: {$conflict['type']}";
                    }
                } catch (\Exception $e) {
                    $fixed[] = "Failed to fix {$conflict['type']}: {$e->getMessage()}";
                }
            }
        }

        return $fixed;
    }

    /**
     * Fix orphaned role by deleting it.
     */
    protected function fixOrphanedRole(array $conflict): void
    {
        $role = Role::find($conflict['role_id']);
        if ($role) {
            $role->delete();
        }
    }

    /**
     * Fix duplicate permissions by merging them.
     */
    protected function fixDuplicatePermissions(array $conflict): void
    {
        $permissions = $conflict['permissions'];
        if (count($permissions) <= 1) {
            return;
        }

        // Keep the first permission, merge others into it
        $primary = Permission::find($permissions[0]['id']);
        $toMerge = array_slice($permissions, 1);

        foreach ($toMerge as $permData) {
            $permission = Permission::find($permData['id']);
            if ($permission) {
                // Move all role associations to primary permission
                foreach ($permission->roles as $role) {
                    $role->givePermissionTo($primary);
                }
                
                // Delete the duplicate
                $permission->delete();
            }
        }
    }

    /**
     * Calculate role depth in hierarchy.
     */
    protected function calculateRoleDepth(Role $role): int
    {
        $depth = 0;
        $current = $role;

        while ($current && $current->parent_id) {
            $depth++;
            $current = $current->parent;
            
            // Prevent infinite loops
            if ($depth > 100) {
                break;
            }
        }

        return $depth;
    }

    /**
     * Check if permission matches pattern.
     */
    protected function matchesPattern(string $permission, string $pattern): bool
    {
        $pattern = str_replace('*', '.*', preg_quote($pattern, '/'));
        return preg_match("/^{$pattern}$/i", $permission);
    }
}
