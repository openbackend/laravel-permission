<?php

namespace OpenBackend\LaravelPermission\Exceptions;

use InvalidArgumentException;

class RoleDoesNotExist extends InvalidArgumentException
{
    public static function named(string $roleName, string $guardName = '')
    {
        return new static("There is no role named `{$roleName}` for guard `{$guardName}`.");
    }

    public static function withId(int $roleId, string $guardName = '')
    {
        return new static("There is no role with id `{$roleId}` for guard `{$guardName}`.");
    }

    public static function create(string $roleName, string $guardName = '', $extra = '')
    {
        return new static("There is no role named `{$roleName}` for guard `{$guardName}`. {$extra}");
    }
}
