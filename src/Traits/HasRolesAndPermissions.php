<?php

namespace OpenBackend\LaravelPermission\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Collection as SupportCollection;
use OpenBackend\LaravelPermission\Contracts\Permission;
use OpenBackend\LaravelPermission\Contracts\Role;
use OpenBackend\LaravelPermission\PermissionRegistrar;

trait HasRolesAndPermissions
{
    use HasRoles, HasPermissions;

    /**
     * Grant the given permission(s) to a role.
     */
    public function givePermissionTo(...$permissions): self
    {
        $permissions = collect($permissions)
            ->flatten()
            ->map(function ($permission) {
                if (empty($permission)) {
                    return false;
                }

                return $this->getStoredPermission($permission);
            })
            ->filter(function ($permission) {
                return $permission instanceof Permission;
            })
            ->each(function ($permission) {
                $this->ensureModelSharesGuard($permission);
            })
            ->map->id
            ->all();

        $model = $this->getModel();

        if ($model->exists) {
            $this->permissions()->sync($permissions, false);
            $model->load('permissions');
        } else {
            $class = \get_class($model);

            $class::saved(
                function ($object) use ($permissions, $model) {
                    if ($model->getKey() != $object->getKey()) {
                        return;
                    }
                    $model->permissions()->sync($permissions, false);
                    $model->load('permissions');
                }
            );
        }

        $this->forgetCachedPermissions();

        return $this;
    }

    /**
     * Remove all current permissions and set the given ones.
     */
    public function syncPermissions(...$permissions): self
    {
        $this->permissions()->detach();

        return $this->givePermissionTo($permissions);
    }

    /**
     * Revoke the given permission(s).
     */
    public function revokePermissionTo($permissions): self
    {
        $permissions = collect($permissions)
            ->flatten()
            ->map(function ($permission) {
                if (empty($permission)) {
                    return false;
                }

                return $this->getStoredPermission($permission);
            })
            ->filter(function ($permission) {
                return $permission instanceof Permission;
            })
            ->map->id
            ->all();

        $this->permissions()->detach($permissions);

        $this->forgetCachedPermissions();

        $this->load('permissions');

        return $this;
    }

    /**
     * Check if the model has any of the given permissions.
     */
    public function hasAnyPermission(...$permissions): bool
    {
        $permissions = collect($permissions)->flatten();

        foreach ($permissions as $permission) {
            if ($this->checkPermissionTo($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the model has all of the given permissions.
     */
    public function hasAllPermissions(...$permissions): bool
    {
        $permissions = collect($permissions)->flatten();

        foreach ($permissions as $permission) {
            if (! $this->checkPermissionTo($permission)) {
                return false;
            }
        }

        return count($permissions) > 0;
    }

    /**
     * Determine if the model may perform the given permission.
     */
    public function hasPermissionTo($permission, ?string $guardName = null): bool
    {
        if (config('permission.enable_wildcard_permission', false)) {
            return $this->hasWildcardPermission($permission, $guardName);
        }

        $permissionClass = $this->getPermissionClass();

        if (\is_string($permission)) {
            $permission = $permissionClass->findByName(
                $permission,
                $guardName ?? $this->getDefaultGuardName()
            );
        }

        if (\is_int($permission)) {
            $permission = $permissionClass->findById(
                $permission,
                $guardName ?? $this->getDefaultGuardName()
            );
        }

        if (! $this->getGuardNames()->contains($permission->guard_name)) {
            throw new GuardDoesNotMatch($this->getGuardNames(), $permission->guard_name);
        }

        return $this->hasDirectPermission($permission) || $this->hasPermissionViaRole($permission);
    }

    /**
     * Check if model has permission via roles.
     */
    protected function hasPermissionViaRole(Permission $permission): bool
    {
        return $this->hasRole($permission->roles);
    }

    /**
     * Check if model has a direct permission.
     */
    protected function hasDirectPermission(Permission $permission): bool
    {
        return $this->permissions->contains('id', $permission->id);
    }

    /**
     * Get the stored permission.
     */
    protected function getStoredPermission($permissions)
    {
        $permissionClass = $this->getPermissionClass();

        if (\is_numeric($permissions)) {
            return $permissionClass->findById($permissions, $this->getDefaultGuardName());
        }

        if (\is_string($permissions)) {
            return $permissionClass->findByName($permissions, $this->getDefaultGuardName());
        }

        if (\is_array($permissions)) {
            return $permissionClass
                ->whereIn('name', $permissions)
                ->whereIn('guard_name', $this->getGuardNames())
                ->get();
        }

        return $permissions;
    }

    /**
     * Get all permissions for the model.
     */
    public function getAllPermissions(): Collection
    {
        /** @var Collection $permissions */
        $permissions = $this->permissions;

        if ($this->roles) {
            $permissions = $permissions->merge($this->getPermissionsViaRoles());
        }

        return $permissions->sort()->values();
    }

    /**
     * Get permissions via roles.
     */
    public function getPermissionsViaRoles(): Collection
    {
        return $this->loadMissing('roles', 'roles.permissions')
            ->roles->flatMap(function ($role) {
                return $role->permissions;
            })->sort()->values();
    }

    /**
     * Get direct permissions.
     */
    public function getDirectPermissions(): Collection
    {
        return $this->permissions;
    }

    protected function getPermissionClass()
    {
        if (! isset($this->permissionClass)) {
            $this->permissionClass = app(PermissionRegistrar::class)->getPermissionClass();
        }

        return $this->permissionClass;
    }

    protected function getRoleClass()
    {
        if (! isset($this->roleClass)) {
            $this->roleClass = app(PermissionRegistrar::class)->getRoleClass();
        }

        return $this->roleClass;
    }

    /**
     * Forget the cached permissions.
     */
    public function forgetCachedPermissions(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Check the given permission against the model.
     */
    protected function checkPermissionTo($permission): bool
    {
        try {
            return $this->hasPermissionTo($permission);
        } catch (PermissionDoesNotExist $e) {
            return false;
        }
    }

    /**
     * Ensure that the model and permission share the same guard.
     */
    protected function ensureModelSharesGuard($roleOrPermission): void
    {
        if (! $this->getGuardNames()->contains($roleOrPermission->guard_name)) {
            $class = \get_class($roleOrPermission);
            $guard = $roleOrPermission->guard_name;
            $modelGuards = $this->getGuardNames()->implode(', ');

            throw new GuardDoesNotMatch("The given {$class} with guard [{$guard}] does not match the guards [{$modelGuards}] from the given model.");
        }
    }

    protected function getDefaultGuardName(): string
    {
        return $this->guard_name ?? config('auth.defaults.guard');
    }

    protected function getGuardNames(): SupportCollection
    {
        return collect($this->guard_name ?? [config('auth.defaults.guard')]);
    }

    protected function convertToPermissionModels($permissions): SupportCollection
    {
        if ($permissions instanceof Collection) {
            return $permissions;
        }

        $permissions = \is_array($permissions) ? $permissions : [$permissions];

        return collect($permissions)->map(function ($permission) {
            return $this->getStoredPermission($permission);
        });
    }

    protected function convertToRoleModels($roles): SupportCollection
    {
        if ($roles instanceof Collection) {
            return $roles;
        }

        $roles = \is_array($roles) ? $roles : [$roles];

        return collect($roles)->map(function ($role) {
            return $this->getStoredRole($role);
        });
    }
}
