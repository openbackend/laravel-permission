# OpenBackend Laravel Permission

[![Latest Version on Packagist](https://img.shields.io/packagist/v/openbackend/laravel-permission.svg?style=flat-square)](https://packagist.org/packages/openbackend/laravel-permission)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/openbackend/laravel-permission/run-tests?label=tests)](https://github.com/openbackend/laravel-permission/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/openbackend/laravel-permission/Check%20&%20fix%20styling?label=code%20style)](https://github.com/openbackend/laravel-permission/actions?query=workflow%3A"Check+%26+fix+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/openbackend/laravel-permission.svg?style=flat-square)](https://packagist.org/packages/openbackend/laravel-permission)

Advanced Laravel Permission Package with enhanced features beyond traditional role-permission systems.

## Features

### 🚀 Advanced Features
- **Hierarchical Roles** - Parent-child role relationships
- **Dynamic Permissions** - Runtime permission creation and management
- **Resource-based Permissions** - Fine-grained control over specific resources
- **Time-based Permissions** - Temporary permissions with expiration
- **Permission Inheritance** - Automatic permission inheritance through role hierarchy
- **Permission Groups** - Organize permissions into logical groups
- **Audit Trail** - Complete tracking of permission changes
- **Bulk Operations** - Efficient bulk assignment/revocation
- **Cache Management** - Intelligent caching with automatic invalidation
- **Multi-tenancy Support** - Team/organization-based permissions

### 🎯 User-Friendly Features
- **GUI Dashboard** - Web interface for permission management
- **Import/Export** - JSON/CSV import/export functionality
- **Permission Templates** - Pre-defined permission sets
- **Role Cloning** - Duplicate roles with all permissions
- **Permission Suggestions** - AI-powered permission recommendations
- **Conflict Detection** - Automatic detection of permission conflicts

### 🔧 Developer Features
- **Fluent API** - Intuitive method chaining
- **Middleware Support** - Easy route protection
- **Blade Directives** - Template-level permission checks
- **Artisan Commands** - CLI management tools
- **Event System** - Hooks for custom logic
- **Database Agnostic** - Works with any Laravel-supported database

## Requirements

- PHP 8.1+
- Laravel 10.0+

## Installation

Install the package via composer:

```bash
composer require openbackend/laravel-permission
```

Publish and run the migrations:

```bash
php artisan vendor:publish --provider="OpenBackend\LaravelPermission\PermissionServiceProvider" --tag="migrations"
php artisan migrate
```

Publish the config file:

```bash
php artisan vendor:publish --provider="OpenBackend\LaravelPermission\PermissionServiceProvider" --tag="config"
```

## Quick Start

### 1. Add the Trait to Your User Model

```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use OpenBackend\LaravelPermission\Traits\HasRolesAndPermissions;

class User extends Authenticatable
{
    use HasRolesAndPermissions;
    
    // ... your model code
}
```

### 2. Create Roles and Permissions

```php
use OpenBackend\LaravelPermission\Models\Role;
use OpenBackend\LaravelPermission\Models\Permission;

// Create permissions
$editPosts = Permission::create(['name' => 'edit posts', 'group' => 'posts']);
$deletePosts = Permission::create(['name' => 'delete posts', 'group' => 'posts']);

// Create role with permissions
$role = Role::create(['name' => 'writer'])
    ->givePermissionTo($editPosts, $deletePosts);

// Assign role to user
$user->assignRole('writer');
```

### 3. Check Permissions

```php
// Check if user has permission
if ($user->can('edit posts')) {
    // User can edit posts
}

// Check multiple permissions
if ($user->hasAnyPermission(['edit posts', 'delete posts'])) {
    // User has at least one permission
}

// Check all permissions
if ($user->hasAllPermissions(['edit posts', 'delete posts'])) {
    // User has all permissions
}
```

## Advanced Usage

### Hierarchical Roles

```php
// Create parent role
$admin = Role::create(['name' => 'admin']);

// Create child role
$moderator = Role::create(['name' => 'moderator', 'parent_id' => $admin->id]);

// Child roles inherit parent permissions automatically
$admin->givePermissionTo('manage users');
$moderator->hasPermissionTo('manage users'); // true (inherited)
```

### Time-based Permissions

```php
// Give temporary permission
$user->givePermissionTo('access premium', [
    'expires_at' => now()->addDays(30)
]);

// Check if permission is still valid
if ($user->hasValidPermission('access premium')) {
    // Permission is still active
}
```

### Resource-based Permissions

```php
// Create resource-specific permission
$permission = Permission::create([
    'name' => 'edit',
    'resource_type' => 'App\Models\Post',
    'resource_id' => 1
]);

// Assign to user
$user->givePermissionTo($permission);

// Check resource permission
if ($user->can('edit', $post)) {
    // User can edit this specific post
}
```

### Middleware Protection

```php
// In your routes/web.php
Route::group(['middleware' => ['permission:edit posts']], function () {
    Route::get('/posts/{post}/edit', [PostController::class, 'edit']);
});

// Multiple permissions (any)
Route::group(['middleware' => ['permission:edit posts|delete posts']], function () {
    // Routes
});

// Multiple permissions (all)
Route::group(['middleware' => ['permission:edit posts,delete posts']], function () {
    // Routes
});

// Role-based middleware
Route::group(['middleware' => ['role:admin']], function () {
    // Admin only routes
});
```

### Blade Directives

```blade
@can('edit posts')
    <a href="{{ route('posts.edit', $post) }}">Edit</a>
@endcan

@role('admin')
    <a href="{{ route('admin.dashboard') }}">Admin Dashboard</a>
@endrole

@hasanypermission('edit posts|delete posts')
    <div class="post-actions">
        <!-- Action buttons -->
    </div>
@endhasanypermission

@hasallpermissions('edit posts,delete posts')
    <button>Full Post Control</button>
@endhasallpermissions
```

## Artisan Commands

```bash
# Create permission
php artisan permission:create "edit posts" --group=posts

# Create role
php artisan role:create admin --permissions="edit posts,delete posts"

# Assign role to user
php artisan permission:assign-role admin --user=1

# Show user permissions
php artisan permission:show-user 1

# Import permissions from JSON
php artisan permission:import permissions.json

# Export permissions to JSON
php artisan permission:export

# Cache permissions
php artisan permission:cache

# Clear permission cache
php artisan permission:clear-cache
```

## API Reference

### User Methods

```php
// Role assignment
$user->assignRole('admin');
$user->assignRole(['admin', 'editor']);
$user->assignRole($roleObject);

// Role removal
$user->removeRole('admin');
$user->syncRoles(['admin', 'editor']);

// Permission assignment
$user->givePermissionTo('edit posts');
$user->revokePermissionTo('edit posts');
$user->syncPermissions(['edit posts', 'delete posts']);

// Checking permissions
$user->hasPermissionTo('edit posts');
$user->hasAnyPermission(['edit posts', 'delete posts']);
$user->hasAllPermissions(['edit posts', 'delete posts']);

// Checking roles
$user->hasRole('admin');
$user->hasAnyRole(['admin', 'editor']);
$user->hasAllRoles(['admin', 'editor']);

// Getting permissions/roles
$user->getAllPermissions();
$user->getDirectPermissions();
$user->getPermissionsViaRoles();
$user->getRoleNames();
```

### Role Methods

```php
// Permission management
$role->givePermissionTo('edit posts');
$role->revokePermissionTo('edit posts');
$role->syncPermissions(['edit posts', 'delete posts']);

// Hierarchy
$role->setParent($parentRole);
$role->getChildren();
$role->getAncestors();
$role->getDescendants();

// Bulk operations
Role::bulkCreate(['admin', 'editor', 'viewer']);
$role->bulkAssignPermissions(['edit posts', 'delete posts']);
```

## Configuration

The config file allows you to customize:

- Table names
- Model classes
- Cache settings
- Middleware options
- Hierarchy settings
- And more...

## Testing

```bash
composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Rudra Ramesh](https://github.com/rudraramesh)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
