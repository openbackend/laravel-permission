<?php

namespace OpenBackend\LaravelPermission\Commands;

use Illuminate\Console\Command;
use OpenBackend\LaravelPermission\Models\Permission;

class CreatePermissionCommand extends Command
{
    protected $signature = 'permission:create 
                            {name : The name of the permission}
                            {--group= : The group for the permission}
                            {--description= : The description of the permission}
                            {--guard= : The guard for the permission}
                            {--resource-type= : The resource type for resource-based permission}
                            {--resource-id= : The resource ID for resource-based permission}
                            {--expires-at= : Expiration date for time-based permission}';

    protected $description = 'Create a permission';

    public function handle()
    {
        $attributes = [
            'name' => $this->argument('name'),
            'guard_name' => $this->option('guard') ?: config('auth.defaults.guard'),
        ];

        if ($this->option('group')) {
            $attributes['group'] = $this->option('group');
        }

        if ($this->option('description')) {
            $attributes['description'] = $this->option('description');
        }

        if ($this->option('resource-type')) {
            $attributes['resource_type'] = $this->option('resource-type');
        }

        if ($this->option('resource-id')) {
            $attributes['resource_id'] = $this->option('resource-id');
        }

        if ($this->option('expires-at')) {
            $attributes['expires_at'] = $this->option('expires-at');
        }

        try {
            $permission = Permission::create($attributes);
            $this->info("Permission '{$permission->name}' created successfully.");
        } catch (\Exception $e) {
            $this->error("Error creating permission: {$e->getMessage()}");
            return 1;
        }

        return 0;
    }
}
