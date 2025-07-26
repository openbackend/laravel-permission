<?php

namespace OpenBackend\LaravelPermission;

use Illuminate\Support\Collection;
use Illuminate\Cache\CacheManager;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Cache\Repository as CacheContract;
use OpenBackend\LaravelPermission\Models\Permission;
use OpenBackend\LaravelPermission\Models\Role;

class PermissionRegistrar
{
    protected CacheContract $cache;
    protected string $cacheKey;
    protected Collection $permissions;
    protected \DateInterval $cacheExpirationTime;

    public function __construct(CacheContract $cache)
    {
        $this->cache = $cache;
        $this->cacheKey = config('permission.cache.key');
        $this->cacheExpirationTime = config('permission.cache.expiration_time');
        $this->permissions = collect();
    }

    public function registerPermissions(): bool
    {
        $this->forgetCachedPermissions();

        $this->getPermissions()->each(function ($permission) {
            $this->registerPermission($permission);
        });

        return true;
    }

    protected function registerPermission($permission): void
    {
        app(Gate::class)->define($permission->name, function ($user) use ($permission) {
            return $user->hasPermissionTo($permission);
        });
    }

    public function forgetCachedPermissions(): self
    {
        $this->permissions = collect();
        $this->cache->forget($this->cacheKey);

        return $this;
    }

    public function getPermissions(array $params = []): Collection
    {
        if ($this->permissions->isEmpty()) {
            $this->permissions = $this->cache->remember(
                $this->cacheKey,
                $this->cacheExpirationTime,
                function () {
                    return $this->getPermissionClass()
                        ->with('roles')
                        ->get();
                }
            );
        }

        $permissions = clone $this->permissions;

        $teams = config('permission.teams');
        if ($teams['enabled'] && array_key_exists('team_id', $params)) {
            $permissions = $permissions->where('team_id', $params['team_id']);
        }

        return $permissions;
    }

    public function getPermissionClass(): string
    {
        return config('permission.models.permission');
    }

    public function getRoleClass(): string
    {
        return config('permission.models.role');
    }

    public function getCacheExpirationTime(): \DateInterval
    {
        return $this->cacheExpirationTime;
    }

    public function setCacheExpirationTime(\DateInterval $cacheExpirationTime): self
    {
        $this->cacheExpirationTime = $cacheExpirationTime;

        return $this;
    }

    public function getCacheKey(): string
    {
        return $this->cacheKey;
    }

    public function setCacheKey(string $cacheKey): self
    {
        $this->cacheKey = $cacheKey;

        return $this;
    }

    public function getCacheStore(): CacheContract
    {
        return $this->cache;
    }
}
