<?php

namespace OpenBackend\LaravelPermission\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use OpenBackend\LaravelPermission\Contracts\Permission as PermissionContract;
use OpenBackend\LaravelPermission\Traits\HasTeams;
use OpenBackend\LaravelPermission\Traits\RefreshesPermissionCache;
use OpenBackend\LaravelPermission\Exceptions\PermissionDoesNotExist;

class Permission extends Model implements PermissionContract
{
    use HasTeams, RefreshesPermissionCache;

    protected $guarded = [];

    protected $casts = [
        'expires_at' => 'datetime',
        'meta' => 'array',
    ];

    public function __construct(array $attributes = [])
    {
        $attributes['guard_name'] = $attributes['guard_name'] ?? config('auth.defaults.guard');

        parent::__construct($attributes);

        $this->setTable(config('permission.table_names.permissions'));
    }

    public static function create(array $attributes = [])
    {
        $attributes['guard_name'] = $attributes['guard_name'] ?? config('auth.defaults.guard');

        $permission = static::getPermissions(['name' => $attributes['name']])->first();

        if ($permission) {
            throw new \Exception("Permission `{$attributes['name']}` already exists");
        }

        return static::query()->create($attributes);
    }

    /**
     * Get the table associated with the model.
     */
    public function getTable()
    {
        return config('permission.table_names.permissions', parent::getTable());
    }

    /**
     * A permission can belong to a group for organization
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(PermissionGroup::class, 'group_id');
    }

    /**
     * A permission can be assigned to roles
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            config('permission.models.role'),
            config('permission.table_names.role_has_permissions'),
            config('permission.column_names.permission_pivot_key') ?: 'permission_id',
            config('permission.column_names.role_pivot_key') ?: 'role_id'
        );
    }

    /**
     * A permission can be assigned to any model that uses the HasRolesAndPermissions trait
     */
    public function users(): MorphToMany
    {
        return $this->morphedByMany(
            getModelForGuard($this->attributes['guard_name'] ?? config('auth.defaults.guard')),
            'model',
            config('permission.table_names.model_has_permissions'),
            config('permission.column_names.permission_pivot_key') ?: 'permission_id',
            config('permission.column_names.model_morph_key')
        )->withPivot(['expires_at', 'resource_type', 'resource_id', 'meta']);
    }

    /**
     * Find a permission by its name and guard name
     */
    public static function findByName(string $name, ?string $guardName = null): PermissionContract
    {
        $guardName = $guardName ?? config('auth.defaults.guard');

        $permission = static::getPermissions(['name' => $name, 'guard_name' => $guardName])->first();

        if (! $permission) {
            throw PermissionDoesNotExist::named($name);
        }

        return $permission;
    }

    /**
     * Find a permission by its id
     */
    public static function findById(int $id, ?string $guardName = null): PermissionContract
    {
        $guardName = $guardName ?? config('auth.defaults.guard');

        $permission = static::getPermissions(['guard_name' => $guardName])->where('id', $id)->first();

        if (! $permission) {
            throw PermissionDoesNotExist::withId($id);
        }

        return $permission;
    }

    /**
     * Find or create permission by its name and guard name
     */
    public static function findOrCreate(string $name, ?string $guardName = null): PermissionContract
    {
        $guardName = $guardName ?? config('auth.defaults.guard');

        $permission = static::getPermissions(['name' => $name, 'guard_name' => $guardName])->first();

        if (! $permission) {
            return static::query()->create(['name' => $name, 'guard_name' => $guardName]);
        }

        return $permission;
    }

    /**
     * Get the current cached permissions
     */
    protected static function getPermissions(array $params = []): Collection
    {
        return app(PermissionRegistrar::class)
            ->setPermissionClass(static::class)
            ->getPermissions($params);
    }

    /**
     * Check if permission is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if permission is valid (not expired)
     */
    public function isValid(): bool
    {
        return ! $this->isExpired();
    }

    /**
     * Scope to only include permissions that are not expired
     */
    public function scopeValid(Builder $query): Builder
    {
        return $query->where(function ($query) {
            $query->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Scope to only include permissions that are expired
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('expires_at', '<=', now());
    }

    /**
     * Scope to filter by group
     */
    public function scopeInGroup(Builder $query, string $group): Builder
    {
        return $query->where('group', $group);
    }

    /**
     * Scope to filter by resource type
     */
    public function scopeForResourceType(Builder $query, string $resourceType): Builder
    {
        return $query->where('resource_type', $resourceType);
    }

    /**
     * Scope to filter by resource id
     */
    public function scopeForResource(Builder $query, string $resourceType, int $resourceId): Builder
    {
        return $query->where('resource_type', $resourceType)
                     ->where('resource_id', $resourceId);
    }

    /**
     * Create a resource-specific permission
     */
    public static function createForResource(string $name, string $resourceType, int $resourceId, array $attributes = []): PermissionContract
    {
        $attributes = array_merge($attributes, [
            'name' => $name,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
        ]);

        return static::create($attributes);
    }

    /**
     * Check if this is a resource-specific permission
     */
    public function isResourcePermission(): bool
    {
        return ! empty($this->resource_type) && ! empty($this->resource_id);
    }

    /**
     * Get the resource model instance
     */
    public function getResource()
    {
        if (! $this->isResourcePermission()) {
            return null;
        }

        return $this->resource_type::find($this->resource_id);
    }

    /**
     * Bulk create permissions
     */
    public static function bulkCreate(array $permissions): Collection
    {
        $created = collect();

        foreach ($permissions as $permission) {
            if (is_string($permission)) {
                $permission = ['name' => $permission];
            }

            $created->push(static::create($permission));
        }

        return $created;
    }

    /**
     * Get permissions by group
     */
    public static function getByGroup(string $group): Collection
    {
        return static::where('group', $group)->get();
    }

    /**
     * Get all permission groups
     */
    public static function getAllGroups(): Collection
    {
        return static::whereNotNull('group')
                     ->distinct()
                     ->pluck('group');
    }
}
