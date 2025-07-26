<?php

namespace OpenBackend\LaravelPermission\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use OpenBackend\LaravelPermission\PermissionServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase();
    }

    protected function getPackageProviders($app)
    {
        return [
            PermissionServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
        config()->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        config()->set('permission.models.permission', \OpenBackend\LaravelPermission\Models\Permission::class);
        config()->set('permission.models.role', \OpenBackend\LaravelPermission\Models\Role::class);

        config()->set('permission.table_names.roles', 'roles');
        config()->set('permission.table_names.permissions', 'permissions');
        config()->set('permission.table_names.model_has_permissions', 'model_has_permissions');
        config()->set('permission.table_names.model_has_roles', 'model_has_roles');
        config()->set('permission.table_names.role_has_permissions', 'role_has_permissions');

        config()->set('permission.column_names.model_morph_key', 'model_id');
        config()->set('permission.column_names.team_foreign_key', 'team_id');

        config()->set('auth.providers.users.model', User::class);
    }

    protected function setUpDatabase()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Create users table for testing
        $this->app['db']->connection()->getSchemaBuilder()->create('users', function ($table) {
            $table->increments('id');
            $table->string('email');
            $table->string('name');
            $table->timestamps();
        });
    }
}

class User extends \Illuminate\Foundation\Auth\User
{
    use \OpenBackend\LaravelPermission\Traits\HasRolesAndPermissions;

    protected $fillable = ['name', 'email'];
}
