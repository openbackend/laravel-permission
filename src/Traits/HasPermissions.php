<?php

namespace OpenBackend\LaravelPermission\Traits;

use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Collection;

trait HasPermissions
{
    /**
     * A model may have multiple direct permissions.
     */
    public function permissions(): MorphToMany
    {
        return $this->morphToMany(
            config('permission.models.permission'),
            'model',
            config('permission.table_names.model_has_permissions'),
            config('permission.column_names.model_morph_key'),
            config('permission.column_names.permission_pivot_key') ?: 'permission_id'
        )->withPivot(['expires_at', 'resource_type', 'resource_id', 'meta']);
    }

    /**
     * Scope the model query to certain permissions only.
     */
    public function scopePermission($query, $permissions)
    {
        $permissions = \is_array($permissions) ? $permissions : [$permissions];

        return $query->whereHas('permissions', function ($query) use ($permissions) {
            $query->where(function ($query) use ($permissions) {
                foreach ($permissions as $permission) {
                    $query->orWhere(config('permission.table_names.permissions').'.name', $permission);
                }
            });
        });
    }
}
