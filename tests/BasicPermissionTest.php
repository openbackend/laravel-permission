<?php

namespace OpenBackend\LaravelPermission\Tests;

use OpenBackend\LaravelPermission\Models\Permission;
use OpenBackend\LaravelPermission\Models\Role;

class BasicPermissionTest extends TestCase
{
    /** @test */
    public function it_can_create_a_permission()
    {
        $permission = Permission::create(['name' => 'edit posts']);

        $this->assertDatabaseHas('permissions', ['name' => 'edit posts']);
        $this->assertEquals('edit posts', $permission->name);
    }

    /** @test */
    public function it_can_create_a_role()
    {
        $role = Role::create(['name' => 'admin']);

        $this->assertDatabaseHas('roles', ['name' => 'admin']);
        $this->assertEquals('admin', $role->name);
    }

    /** @test */
    public function it_can_assign_permission_to_role()
    {
        $permission = Permission::create(['name' => 'edit posts']);
        $role = Role::create(['name' => 'admin']);

        $role->givePermissionTo($permission);

        $this->assertTrue($role->hasPermissionTo('edit posts'));
    }

    /** @test */
    public function it_can_assign_role_to_user()
    {
        $user = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $role = Role::create(['name' => 'admin']);

        $user->assignRole($role);

        $this->assertTrue($user->hasRole('admin'));
    }

    /** @test */
    public function user_can_have_permission_through_role()
    {
        $user = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $permission = Permission::create(['name' => 'edit posts']);
        $role = Role::create(['name' => 'admin']);

        $role->givePermissionTo($permission);
        $user->assignRole($role);

        $this->assertTrue($user->hasPermissionTo('edit posts'));
        $this->assertTrue($user->can('edit posts'));
    }
}
