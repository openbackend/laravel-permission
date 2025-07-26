<?php

namespace OpenBackend\LaravelPermission\Traits;

use OpenBackend\LaravelPermission\PermissionRegistrar;

trait RefreshesPermissionCache
{
    protected static function bootRefreshesPermissionCache()
    {
        static::saved(function () {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        });

        static::deleted(function () {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        });
    }
}
