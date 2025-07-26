# Installation Guide

## Quick Installation

### 1. Install via Composer

```bash
composer require openbackend/laravel-permission
```

### 2. Publish Configuration and Migrations

```bash
# Publish the migration and config files
php artisan vendor:publish --provider="OpenBackend\LaravelPermission\PermissionServiceProvider"

# Or publish specific files
php artisan vendor:publish --provider="OpenBackend\LaravelPermission\PermissionServiceProvider" --tag="migrations"
php artisan vendor:publish --provider="OpenBackend\LaravelPermission\PermissionServiceProvider" --tag="config"
```

### 3. Run Migrations

```bash
php artisan migrate
```

### 4. Add Trait to User Model

```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use OpenBackend\LaravelPermission\Traits\HasRolesAndPermissions;

class User extends Authenticatable
{
    use HasRolesAndPermissions;
    
    // ... your existing model code
}
```

## Advanced Installation

### Environment-Specific Installation

#### Development Environment

```bash
# Install with dev dependencies
composer require openbackend/laravel-permission --dev

# Publish all assets including views
php artisan vendor:publish --provider="OpenBackend\LaravelPermission\PermissionServiceProvider" --tag="views"
```

#### Production Environment

```bash
# Install for production
composer require openbackend/laravel-permission --no-dev

# Optimize for performance
php artisan permission:cache
php artisan config:cache
php artisan route:cache
```

### Database Configuration

#### Multiple Database Support

```php
// config/permission.php
'connection' => env('PERMISSION_DB_CONNECTION', 'mysql'),

'table_names' => [
    'roles' => 'roles',
    'permissions' => 'permissions',
    // ... other tables
],
```

#### Custom Table Names

```php
// config/permission.php
'table_names' => [
    'roles' => 'custom_roles',
    'permissions' => 'custom_permissions',
    'model_has_permissions' => 'custom_model_has_permissions',
    'model_has_roles' => 'custom_model_has_roles',
    'role_has_permissions' => 'custom_role_has_permissions',
],
```

### Advanced Configuration

#### Enable Teams Feature

```php
// config/permission.php
'teams' => [
    'enabled' => true,
    'team_model' => App\Models\Team::class,
],
```

#### Configure Hierarchical Roles

```php
// config/permission.php
'hierarchical_roles' => [
    'enabled' => true,
    'max_depth' => 10,
],
```

#### Time-based Permissions

```php
// config/permission.php
'time_based_permissions' => [
    'enabled' => true,
    'auto_cleanup' => true,
    'cleanup_frequency' => 60, // minutes
],
```

## Post-Installation Setup

### 1. Create Initial Permissions and Roles

```bash
# Create permissions
php artisan permission:create "manage users" --group=users
php artisan permission:create "edit posts" --group=posts
php artisan permission:create "delete posts" --group=posts

# Create roles
php artisan role:create admin --permissions="manage users,edit posts,delete posts"
php artisan role:create editor --permissions="edit posts"

# Assign role to user
php artisan permission:assign-role admin --user=1
```

### 2. Seed Default Permissions (Optional)

Create a seeder:

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use OpenBackend\LaravelPermission\Models\Permission;
use OpenBackend\LaravelPermission\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run()
    {
        // Create permissions
        $permissions = [
            ['name' => 'manage users', 'group' => 'users'],
            ['name' => 'view users', 'group' => 'users'],
            ['name' => 'create posts', 'group' => 'posts'],
            ['name' => 'edit posts', 'group' => 'posts'],
            ['name' => 'delete posts', 'group' => 'posts'],
            ['name' => 'publish posts', 'group' => 'posts'],
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission['name'])
                ->update(['group' => $permission['group']]);
        }

        // Create roles
        $admin = Role::findOrCreate('admin');
        $admin->givePermissionTo(Permission::all());

        $editor = Role::findOrCreate('editor');
        $editor->givePermissionTo(['create posts', 'edit posts', 'view users']);

        $viewer = Role::findOrCreate('viewer');
        $viewer->givePermissionTo(['view users']);
    }
}
```

Run the seeder:

```bash
php artisan db:seed --class=PermissionSeeder
```

### 3. Configure Middleware (Optional)

Add to `app/Http/Kernel.php`:

```php
protected $routeMiddleware = [
    // ... existing middleware
    'permission' => \OpenBackend\LaravelPermission\Middleware\PermissionMiddleware::class,
    'role' => \OpenBackend\LaravelPermission\Middleware\RoleMiddleware::class,
    'role_or_permission' => \OpenBackend\LaravelPermission\Middleware\RoleOrPermissionMiddleware::class,
];
```

## Verification

### Test Installation

```php
// In tinker or a test route
php artisan tinker

// Test basic functionality
$user = App\Models\User::first();
$role = \OpenBackend\LaravelPermission\Models\Role::create(['name' => 'test']);
$permission = \OpenBackend\LaravelPermission\Models\Permission::create(['name' => 'test permission']);

$role->givePermissionTo($permission);
$user->assignRole($role);

// Check if everything works
$user->hasRole('test'); // should return true
$user->hasPermissionTo('test permission'); // should return true
```

### Check Database Tables

```bash
# List all permission-related tables
php artisan tinker
>>> Schema::getTableListing()
```

You should see these tables:
- `permissions`
- `roles`
- `model_has_permissions`
- `model_has_roles`
- `role_has_permissions`
- `permission_groups`
- `permission_audits`

## Troubleshooting

### Common Issues

#### Migration Conflicts

```bash
# If you have existing permission tables
php artisan migrate:rollback --step=1
php artisan migrate
```

#### Cache Issues

```bash
# Clear all caches
php artisan permission:clear-cache
php artisan config:clear
php artisan cache:clear
```

#### Class Not Found

Make sure you've added the trait to your User model and run:

```bash
composer dump-autoload
```

#### Database Connection Issues

Check your `.env` file:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### Getting Help

- Check the [documentation](https://github.com/openbackend/laravel-permission)
- Search [existing issues](https://github.com/openbackend/laravel-permission/issues)
- Create a [new issue](https://github.com/openbackend/laravel-permission/issues/new)

## Next Steps

After installation, check out:

- [Basic Usage Guide](USAGE.md)
- [Advanced Features](ADVANCED.md)
- [API Documentation](API.md)
- [Examples](examples/)
