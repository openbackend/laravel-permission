<?php

namespace OpenBackend\LaravelPermission\Traits;

use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

trait HasRoles
{
    /**
     * A model may have multiple roles.
     */
    public function roles(): MorphToMany
    {
        return $this->morphToMany(
            config('permission.models.role'),
            'model',
            config('permission.table_names.model_has_roles'),
            config('permission.column_names.model_morph_key'),
            config('permission.column_names.role_pivot_key') ?: 'role_id'
        );
    }

    /**
     * Scope the model query to certain roles only.
     */
    public function scopeRole($query, $roles)
    {
        $roles = \is_array($roles) ? $roles : [$roles];

        return $query->whereHas('roles', function ($query) use ($roles) {
            $query->where(function ($query) use ($roles) {
                foreach ($roles as $role) {
                    $query->orWhere(config('permission.table_names.roles').'.name', $role);
                }
            });
        });
    }

    /**
     * Assign the given role(s) to the model.
     */
    public function assignRole(...$roles): self
    {
        $roles = collect($roles)
            ->flatten()
            ->map(function ($role) {
                if (empty($role)) {
                    return false;
                }

                return $this->getStoredRole($role);
            })
            ->filter(function ($role) {
                return $role instanceof \OpenBackend\LaravelPermission\Contracts\Role;
            })
            ->each(function ($role) {
                $this->ensureModelSharesGuard($role);
            })
            ->map->id
            ->all();

        $model = $this->getModel();

        if ($model->exists) {
            $this->roles()->sync($roles, false);
            $model->load('roles');
        } else {
            $class = \get_class($model);

            $class::saved(
                function ($object) use ($roles, $model) {
                    if ($model->getKey() != $object->getKey()) {
                        return;
                    }
                    $model->roles()->sync($roles, false);
                    $model->load('roles');
                }
            );
        }

        $this->forgetCachedPermissions();

        return $this;
    }

    /**
     * Revoke the given role(s) from the model.
     */
    public function removeRole($roles): self
    {
        $roles = collect($roles)
            ->flatten()
            ->map(function ($role) {
                if (empty($role)) {
                    return false;
                }

                return $this->getStoredRole($role);
            })
            ->filter(function ($role) {
                return $role instanceof \OpenBackend\LaravelPermission\Contracts\Role;
            })
            ->map->id
            ->all();

        $this->roles()->detach($roles);

        $this->forgetCachedPermissions();

        $this->load('roles');

        return $this;
    }

    /**
     * Remove all current roles and set the given ones.
     */
    public function syncRoles(...$roles): self
    {
        $this->roles()->detach();

        return $this->assignRole($roles);
    }

    /**
     * Determine if the model has (one of) the given role(s).
     */
    public function hasRole($roles): bool
    {
        if (\is_string($roles) && false !== \strpos($roles, '|')) {
            $roles = $this->convertPipeToArray($roles);
        }

        if (\is_string($roles)) {
            return $this->roles->contains('name', $roles);
        }

        if (\is_int($roles)) {
            return $this->roles->contains('id', $roles);
        }

        if ($roles instanceof \OpenBackend\LaravelPermission\Contracts\Role) {
            return $this->roles->contains('id', $roles->id);
        }

        if (\is_array($roles)) {
            foreach ($roles as $role) {
                if ($this->hasRole($role)) {
                    return true;
                }
            }

            return false;
        }

        return $roles->intersect($this->roles)->isNotEmpty();
    }

    /**
     * Determine if the model has any of the given role(s).
     */
    public function hasAnyRole($roles): bool
    {
        return $this->hasRole($roles);
    }

    /**
     * Determine if the model has all of the given role(s).
     */
    public function hasAllRoles($roles): bool
    {
        if (\is_string($roles) && false !== \strpos($roles, '|')) {
            $roles = $this->convertPipeToArray($roles);
        }

        if (\is_string($roles)) {
            return $this->roles->contains('name', $roles);
        }

        if ($roles instanceof Collection) {
            $roles = $roles->all();
        }

        $roles = \is_array($roles) ? $roles : [$roles];

        foreach ($roles as $role) {
            if (! $this->hasRole($role)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get all of the role names associated with the model.
     */
    public function getRoleNames(): SupportCollection
    {
        return $this->roles->pluck('name');
    }

    /**
     * Get the stored role.
     */
    protected function getStoredRole($role)
    {
        $roleClass = $this->getRoleClass();

        if (\is_numeric($role)) {
            return $roleClass::findById($role, $this->getDefaultGuardName());
        }

        if (\is_string($role)) {
            return $roleClass::findByName($role, $this->getDefaultGuardName());
        }

        return $role;
    }

    protected function convertPipeToArray(string $pipeString): array
    {
        $pipeString = trim($pipeString);

        if (\strlen($pipeString) <= 2) {
            return $pipeString;
        }

        $quoteCharacter = \substr($pipeString, 0, 1);
        $endCharacter = \substr($quoteCharacter, -1, 1);

        if ($quoteCharacter !== $endCharacter) {
            return explode('|', $pipeString);
        }

        if (! \in_array($quoteCharacter, ["'", '"'])) {
            return explode('|', $pipeString);
        }

        return explode('|', trim($pipeString, $quoteCharacter));
    }
}
