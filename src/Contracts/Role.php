<?php

namespace OpenBackend\LaravelPermission\Contracts;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

interface Role
{
    /**
     * Find a role by its name.
     */
    public static function findByName(string $name, ?string $guardName = null): Role;

    /**
     * Find a role by its id.
     */
    public static function findById(int $id, ?string $guardName = null): Role;

    /**
     * Find or create role by its name and guard name.
     */
    public static function findOrCreate(string $name, ?string $guardName = null): Role;

    /**
     * A role may be given various permissions.
     */
    public function permissions(): BelongsToMany;

    /**
     * A role may have a parent role.
     */
    public function parent();

    /**
     * A role may have child roles.
     */
    public function children();
}
