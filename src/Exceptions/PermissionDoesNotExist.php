<?php

namespace OpenBackend\LaravelPermission\Exceptions;

use InvalidArgumentException;

class PermissionDoesNotExist extends InvalidArgumentException
{
    public static function named(string $permissionName, string $guardName = '')
    {
        return new static("There is no permission named `{$permissionName}` for guard `{$guardName}`.");
    }

    public static function withId(int $permissionId, string $guardName = '')
    {
        return new static("There is no permission with id `{$permissionId}` for guard `{$guardName}`.");
    }

    public static function create(string $permissionName, string $guardName = '', $extra = '')
    {
        return new static("There is no permission named `{$permissionName}` for guard `{$guardName}`. {$extra}");
    }
}
