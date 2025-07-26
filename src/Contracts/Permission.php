<?php

namespace OpenBackend\LaravelPermission\Contracts;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

interface Permission
{
    /**
     * Find a permission by its name.
     */
    public static function findByName(string $name, ?string $guardName = null): Permission;

    /**
     * Find a permission by its id.
     */
    public static function findById(int $id, ?string $guardName = null): Permission;

    /**
     * Find or create permission by its name and guard name.
     */
    public static function findOrCreate(string $name, ?string $guardName = null): Permission;

    /**
     * A permission can be applied to roles.
     */
    public function roles(): BelongsToMany;
}
