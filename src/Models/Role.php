<?php

namespace OpenBackend\LaravelPermission\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Collection;
use OpenBackend\LaravelPermission\Contracts\Role as RoleContract;
use OpenBackend\LaravelPermission\Traits\HasTeams;
use OpenBackend\LaravelPermission\Traits\RefreshesPermissionCache;
use OpenBackend\LaravelPermission\Exceptions\RoleDoesNotExist;
use OpenBackend\LaravelPermission\PermissionRegistrar;

class Role extends Model implements RoleContract
{
    use HasTeams, RefreshesPermissionCache;

    protected $guarded = [];

    protected $casts = [
        'meta' => 'array',
    ];

    public function __construct(array $attributes = [])
    {
        $attributes['guard_name'] = $attributes['guard_name'] ?? config('auth.defaults.guard');

        parent::__construct($attributes);

        $this->setTable(config('permission.table_names.roles'));
    }

    public static function create(array $attributes = [])
    {
        $attributes['guard_name'] = $attributes['guard_name'] ?? config('auth.defaults.guard');

        $role = static::getRoles(['name' => $attributes['name']])->first();

        if ($role) {
            throw new \Exception("Role `{$attributes['name']}` already exists");
        }

        return static::query()->create($attributes);
    }

    /**
     * Get the table associated with the model.
     */
    public function getTable()
    {
        return config('permission.table_names.roles', parent::getTable());
    }

    /**
     * A role may be given various permissions.
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            config('permission.models.permission'),
            config('permission.table_names.role_has_permissions'),
            config('permission.column_names.role_pivot_key') ?: 'role_id',
            config('permission.column_names.permission_pivot_key') ?: 'permission_id'
        );
    }

    /**
     * A role belongs to some users of the model associated with its guard.
     */
    public function users(): MorphToMany
    {
        return $this->morphedByMany(
            getModelForGuard($this->attributes['guard_name'] ?? config('auth.defaults.guard')),
            'model',
            config('permission.table_names.model_has_roles'),
            config('permission.column_names.role_pivot_key') ?: 'role_id',
            config('permission.column_names.model_morph_key')
        );
    }

    /**
     * A role may have a parent role (hierarchical roles).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(static::class, 'parent_id');
    }

    /**
     * A role may have child roles.
     */
    public function children(): HasMany
    {
        return $this->hasMany(static::class, 'parent_id');
    }

    /**
     * Get all descendant roles (recursive).
     */
    public function descendants(): Collection
    {
        $descendants = collect();
        
        foreach ($this->children as $child) {
            $descendants->push($child);
            $descendants = $descendants->merge($child->descendants());
        }
        
        return $descendants;
    }

    /**
     * Get all ancestor roles (recursive).
     */
    public function ancestors(): Collection
    {
        $ancestors = collect();
        
        if ($this->parent) {
            $ancestors->push($this->parent);
            $ancestors = $ancestors->merge($this->parent->ancestors());
        }
        
        return $ancestors;
    }

    /**
     * Get all permissions including inherited ones.
     */
    public function getAllPermissions(): Collection
    {
        $permissions = $this->permissions;
        
        if (config('permission.hierarchical_roles.enabled') && $this->parent) {
            $permissions = $permissions->merge($this->parent->getAllPermissions());
        }
        
        return $permissions->unique('id');
    }

    /**
     * Find a role by its name and guard name.
     */
    public static function findByName(string $name, ?string $guardName = null): RoleContract
    {
        $guardName = $guardName ?? config('auth.defaults.guard');

        $role = static::getRoles(['name' => $name, 'guard_name' => $guardName])->first();

        if (! $role) {
            throw RoleDoesNotExist::named($name);
        }

        return $role;
    }

    /**
     * Find a role by its id.
     */
    public static function findById(int $id, ?string $guardName = null): RoleContract
    {
        $guardName = $guardName ?? config('auth.defaults.guard');

        $role = static::getRoles(['guard_name' => $guardName])->where('id', $id)->first();

        if (! $role) {
            throw RoleDoesNotExist::withId($id);
        }

        return $role;
    }

    /**
     * Find or create role by its name and guard name.
     */
    public static function findOrCreate(string $name, ?string $guardName = null): RoleContract
    {
        $guardName = $guardName ?? config('auth.defaults.guard');

        $role = static::getRoles(['name' => $name, 'guard_name' => $guardName])->first();

        if (! $role) {
            return static::query()->create(['name' => $name, 'guard_name' => $guardName]);
        }

        return $role;
    }

    /**
     * Get the current cached roles.
     */
    protected static function getRoles(array $params = []): Collection
    {
        // Simple implementation without registrar dependency
        return static::with('permissions')->get();
    }

    /**
     * Assign permission to role.
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
                return $permission instanceof \OpenBackend\LaravelPermission\Contracts\Permission;
            })
            ->map->id
            ->all();

        $this->permissions()->sync($permissions, false);
        $this->load('permissions');

        $this->forgetCachedPermissions();

        return $this;
    }

    /**
     * Revoke permission from role.
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
                return $permission instanceof \OpenBackend\LaravelPermission\Contracts\Permission;
            })
            ->map->id
            ->all();

        $this->permissions()->detach($permissions);
        $this->load('permissions');

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
     * Check if role has permission.
     */
    public function hasPermissionTo($permission): bool
    {
        if (is_string($permission)) {
            $permissionClass = config('permission.models.permission');
            $permission = $permissionClass::findByName($permission, $this->guard_name);
        }

        return $this->permissions->contains('id', $permission->id);
    }

    /**
     * Set parent role.
     */
    public function setParent(RoleContract $parent): self
    {
        // Prevent circular references
        if ($parent->isDescendantOf($this)) {
            throw new \Exception('Cannot set parent role as it would create a circular reference');
        }

        $this->parent_id = $parent->id;
        $this->save();

        return $this;
    }

    /**
     * Check if this role is a descendant of another role.
     */
    public function isDescendantOf(RoleContract $role): bool
    {
        return $this->ancestors()->contains('id', $role->id);
    }

    /**
     * Check if this role is an ancestor of another role.
     */
    public function isAncestorOf(RoleContract $role): bool
    {
        return $this->descendants()->contains('id', $role->id);
    }

    /**
     * Get stored permission.
     */
    protected function getStoredPermission($permission)
    {
        $permissionClass = app(PermissionRegistrar::class)->getPermissionClass();

        if (is_numeric($permission)) {
            return $permissionClass::findById($permission, $this->guard_name);
        }

        if (is_string($permission)) {
            return $permissionClass::findByName($permission, $this->guard_name);
        }

        return $permission;
    }

    /**
     * Forget cached permissions.
     */
    public function forgetCachedPermissions(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Bulk create roles.
     */
    public static function bulkCreate(array $roles): Collection
    {
        $created = collect();

        foreach ($roles as $role) {
            if (is_string($role)) {
                $role = ['name' => $role];
            }

            $created->push(static::create($role));
        }

        return $created;
    }

    /**
     * Bulk assign permissions to role.
     */
    public function bulkAssignPermissions(array $permissions): self
    {
        if (config('permission.bulk_operations.enabled')) {
            $permissionIds = collect($permissions)
                ->map(function ($permission) {
                    return $this->getStoredPermission($permission)->id;
                })
                ->toArray();

            if (config('permission.bulk_operations.use_transactions')) {
                \DB::transaction(function () use ($permissionIds) {
                    $this->permissions()->sync($permissionIds, false);
                });
            } else {
                $this->permissions()->sync($permissionIds, false);
            }

            $this->load('permissions');
            $this->forgetCachedPermissions();
        } else {
            $this->givePermissionTo($permissions);
        }

        return $this;
    }

    /**
     * Clone role with all permissions.
     */
    public function clone(string $newName, array $attributes = []): RoleContract
    {
        $newRole = static::create(array_merge([
            'name' => $newName,
            'guard_name' => $this->guard_name,
            'description' => $this->description,
            'meta' => $this->meta,
        ], $attributes));

        $newRole->givePermissionTo($this->permissions);

        return $newRole;
    }
}
