<?php

namespace OpenBackend\LaravelPermission\Commands;

use Illuminate\Console\Command;
use OpenBackend\LaravelPermission\Models\Role;
use OpenBackend\LaravelPermission\Models\Permission;

class CreateRoleCommand extends Command
{
    protected $signature = 'role:create 
                            {name : The name of the role}
                            {--description= : The description of the role}
                            {--guard= : The guard for the role}
                            {--parent= : The parent role name for hierarchical roles}
                            {--permissions=* : Permissions to assign to the role}';

    protected $description = 'Create a role';

    public function handle()
    {
        $attributes = [
            'name' => $this->argument('name'),
            'guard_name' => $this->option('guard') ?: config('auth.defaults.guard'),
        ];

        if ($this->option('description')) {
            $attributes['description'] = $this->option('description');
        }

        try {
            $role = Role::create($attributes);

            // Set parent role if specified
            if ($this->option('parent')) {
                $parentRole = Role::findByName($this->option('parent'), $attributes['guard_name']);
                $role->setParent($parentRole);
            }

            // Assign permissions if specified
            if ($permissions = $this->option('permissions')) {
                $role->givePermissionTo($permissions);
            }

            $this->info("Role '{$role->name}' created successfully.");

            if ($permissions) {
                $this->info("Assigned permissions: " . implode(', ', $permissions));
            }

        } catch (\Exception $e) {
            $this->error("Error creating role: {$e->getMessage()}");
            return 1;
        }

        return 0;
    }
}
