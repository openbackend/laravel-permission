# Examples

This file contains practical examples of using the OpenBackend Laravel Permission package.

## Basic Examples

### Creating Permissions and Roles

```php
use OpenBackend\LaravelPermission\Models\Permission;
use OpenBackend\LaravelPermission\Models\Role;

// Create permissions
$editPosts = Permission::create(['name' => 'edit posts', 'group' => 'posts']);
$deletePosts = Permission::create(['name' => 'delete posts', 'group' => 'posts']);
$manageUsers = Permission::create(['name' => 'manage users', 'group' => 'users']);

// Create roles
$admin = Role::create(['name' => 'admin', 'description' => 'Administrator with full access']);
$editor = Role::create(['name' => 'editor', 'description' => 'Content editor']);

// Assign permissions to roles
$admin->givePermissionTo([$editPosts, $deletePosts, $manageUsers]);
$editor->givePermissionTo([$editPosts]);
```

### User Permission Management

```php
use App\Models\User;

$user = User::find(1);

// Assign roles
$user->assignRole('admin');
$user->assignRole(['admin', 'editor']); // Multiple roles

// Give direct permissions
$user->givePermissionTo('edit posts');
$user->givePermissionTo(['edit posts', 'delete posts']);

// Check permissions
if ($user->can('edit posts')) {
    // User can edit posts
}

if ($user->hasRole('admin')) {
    // User is an admin
}

// Get all user permissions
$permissions = $user->getAllPermissions();
$roleNames = $user->getRoleNames();
```

## Advanced Examples

### Hierarchical Roles

```php
// Create parent-child role relationship
$admin = Role::create(['name' => 'admin']);
$moderator = Role::create(['name' => 'moderator']);
$user = Role::create(['name' => 'user']);

// Set up hierarchy: admin > moderator > user
$moderator->setParent($admin);
$user->setParent($moderator);

// Give permissions to parent
$admin->givePermissionTo('manage everything');

// Child roles inherit parent permissions
$moderator->hasPermissionTo('manage everything'); // true (inherited)
$user->hasPermissionTo('manage everything'); // true (inherited)

// Add specific permissions to child roles
$moderator->givePermissionTo('moderate content');
$user->givePermissionTo('create posts');
```

### Time-based Permissions

```php
// Grant temporary permission
$user->givePermissionTo('access premium features', [
    'expires_at' => now()->addDays(30)
]);

// Check if permission is still valid
if ($user->hasValidPermission('access premium features')) {
    // Permission is active
}

// Create time-limited role assignment
$premiumRole = Role::create(['name' => 'premium']);
$user->assignRole($premiumRole, ['expires_at' => now()->addMonth()]);
```

### Resource-based Permissions

```php
use App\Models\Post;

$post = Post::find(1);
$user = User::find(1);

// Create resource-specific permission
$permission = Permission::createForResource(
    'edit',
    Post::class,
    $post->id
);

// Assign to user
$user->givePermissionTo($permission);

// Check resource permission
if ($user->can('edit', $post)) {
    // User can edit this specific post
}

// Bulk resource permissions
$posts = Post::where('author_id', $user->id)->get();
foreach ($posts as $post) {
    $user->givePermissionTo(
        Permission::createForResource('edit', Post::class, $post->id)
    );
}
```

### Team-based Permissions (Multi-tenancy)

```php
// Enable teams in config/permission.php first
// 'teams' => ['enabled' => true]

use App\Models\Team;

$team = Team::create(['name' => 'Development Team']);
$user = User::find(1);

// Create team-specific role
$teamLead = Role::create([
    'name' => 'team-lead',
    'team_id' => $team->id
]);

// Create team-specific permission
$manageTeam = Permission::create([
    'name' => 'manage team',
    'team_id' => $team->id
]);

$teamLead->givePermissionTo($manageTeam);
$user->assignRole($teamLead);

// Check team-specific permissions
if ($user->hasPermissionTo('manage team', $team->id)) {
    // User can manage this specific team
}
```

## Middleware Examples

### Route Protection

```php
// routes/web.php

// Single permission
Route::get('/admin', [AdminController::class, 'index'])
    ->middleware('permission:manage users');

// Multiple permissions (any)
Route::get('/posts', [PostController::class, 'index'])
    ->middleware('permission:edit posts|delete posts');

// Multiple permissions (all)
Route::get('/posts/create', [PostController::class, 'create'])
    ->middleware('permission:create posts,publish posts');

// Role-based protection
Route::group(['middleware' => ['role:admin']], function () {
    Route::get('/admin/users', [AdminController::class, 'users']);
    Route::get('/admin/settings', [AdminController::class, 'settings']);
});

// Complex middleware combinations
Route::group(['middleware' => ['role_or_permission:admin|manage users']], function () {
    Route::resource('users', UserController::class);
});
```

### API Route Protection

```php
// routes/api.php

Route::middleware(['auth:sanctum', 'permission:api access'])->group(function () {
    Route::get('/posts', [ApiController::class, 'posts']);
    
    Route::middleware('permission:create posts')->group(function () {
        Route::post('/posts', [ApiController::class, 'store']);
    });
    
    Route::middleware('permission:edit posts')->group(function () {
        Route::put('/posts/{post}', [ApiController::class, 'update']);
    });
});
```

## Blade Template Examples

### Basic Permission Checks

```blade
{{-- Check single permission --}}
@can('edit posts')
    <a href="{{ route('posts.edit', $post) }}" class="btn btn-primary">Edit</a>
@endcan

{{-- Check role --}}
@role('admin')
    <a href="{{ route('admin.dashboard') }}" class="btn btn-success">Admin Panel</a>
@endrole

{{-- Check multiple permissions (any) --}}
@hasanypermission('edit posts|delete posts')
    <div class="post-actions">
        @can('edit posts')
            <button class="btn btn-sm btn-primary">Edit</button>
        @endcan
        
        @can('delete posts')
            <button class="btn btn-sm btn-danger">Delete</button>
        @endcan
    </div>
@endhasanypermission

{{-- Check all permissions --}}
@hasallpermissions('edit posts,delete posts')
    <button class="btn btn-warning">Full Post Control</button>
@endhasallpermissions
```

### Navigation Menu Example

```blade
<nav class="navbar">
    <ul class="nav">
        <li><a href="{{ route('home') }}">Home</a></li>
        
        @role('admin|moderator')
            <li class="dropdown">
                <a href="#" class="dropdown-toggle">Management</a>
                <ul class="dropdown-menu">
                    @can('manage users')
                        <li><a href="{{ route('users.index') }}">Users</a></li>
                    @endcan
                    
                    @can('manage posts')
                        <li><a href="{{ route('posts.index') }}">Posts</a></li>
                    @endcan
                    
                    @role('admin')
                        <li><a href="{{ route('admin.settings') }}">Settings</a></li>
                    @endrole
                </ul>
            </li>
        @endrole
        
        @can('create posts')
            <li><a href="{{ route('posts.create') }}">Create Post</a></li>
        @endcan
    </ul>
</nav>
```

## Controller Examples

### Basic Permission Checks

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use OpenBackend\LaravelPermission\Exceptions\UnauthorizedException;

class PostController extends Controller
{
    public function index()
    {
        // Check if user can view posts
        if (!auth()->user()->can('view posts')) {
            throw UnauthorizedException::forPermissions(['view posts']);
        }
        
        return view('posts.index');
    }
    
    public function store(Request $request)
    {
        $this->authorize('create posts');
        
        // Create post logic
    }
    
    public function edit(Post $post)
    {
        // Check resource-specific permission
        $this->authorize('edit', $post);
        
        return view('posts.edit', compact('post'));
    }
}
```

### Advanced Controller with Multiple Checks

```php
<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;

class AdminPostController extends Controller
{
    public function __construct()
    {
        // Apply middleware to all methods
        $this->middleware('role:admin|moderator');
        
        // Apply specific permissions to specific methods
        $this->middleware('permission:create posts')->only(['create', 'store']);
        $this->middleware('permission:edit posts')->only(['edit', 'update']);
        $this->middleware('permission:delete posts')->only(['destroy']);
    }
    
    public function index()
    {
        $user = auth()->user();
        
        // Show different data based on permissions
        if ($user->hasRole('admin')) {
            $posts = Post::all();
        } elseif ($user->hasPermissionTo('moderate posts')) {
            $posts = Post::where('status', 'pending')->get();
        } else {
            $posts = Post::where('author_id', $user->id)->get();
        }
        
        return view('admin.posts.index', compact('posts'));
    }
    
    public function destroy(Post $post)
    {
        // Additional business logic check
        if (!$post->canBeDeleted()) {
            abort(403, 'This post cannot be deleted.');
        }
        
        // Check if user can delete this specific post
        if (!auth()->user()->can('delete', $post)) {
            abort(403, 'You cannot delete this post.');
        }
        
        $post->delete();
        
        return redirect()->route('admin.posts.index')
            ->with('success', 'Post deleted successfully.');
    }
}
```

## CLI Examples

### Artisan Commands

```bash
# Create permissions
php artisan permission:create "manage products" --group=products --description="Can manage all products"

# Create role with permissions
php artisan role:create "product-manager" --permissions="manage products,view analytics" --description="Product management role"

# Assign role to user
php artisan permission:assign-role "product-manager" --user=5

# Show user permissions
php artisan permission:show-user 5

# Export permissions to JSON
php artisan permission:export --file=production-permissions.json

# Import permissions from JSON
php artisan permission:import staging-permissions.json

# Cache permissions for better performance
php artisan permission:cache

# Clear permission cache
php artisan permission:clear-cache
```

### Bulk Operations Example

```php
// Bulk create permissions
$permissions = [
    ['name' => 'view products', 'group' => 'products'],
    ['name' => 'create products', 'group' => 'products'],
    ['name' => 'edit products', 'group' => 'products'],
    ['name' => 'delete products', 'group' => 'products'],
];

Permission::bulkCreate($permissions);

// Bulk create roles
$roles = ['admin', 'manager', 'editor', 'viewer'];
Role::bulkCreate($roles);

// Bulk assign permissions to role
$role = Role::findByName('manager');
$role->bulkAssignPermissions([
    'view products',
    'create products',
    'edit products'
]);
```

## Integration Examples

### Laravel Gates Integration

```php
// app/Providers/AuthServiceProvider.php

use Illuminate\Support\Facades\Gate;
use OpenBackend\LaravelPermission\Models\Permission;

public function boot()
{
    $this->registerPolicies();
    
    // Automatically register permissions as gates
    Permission::all()->each(function ($permission) {
        Gate::define($permission->name, function ($user) use ($permission) {
            return $user->hasPermissionTo($permission);
        });
    });
    
    // Custom gate with additional logic
    Gate::define('edit-post', function ($user, $post) {
        return $user->hasPermissionTo('edit posts') && 
               ($user->id === $post->author_id || $user->hasRole('admin'));
    });
}
```

### Policy Integration

```php
// app/Policies/PostPolicy.php

use App\Models\Post;
use App\Models\User;

class PostPolicy
{
    public function view(User $user, Post $post)
    {
        return $user->hasPermissionTo('view posts') || 
               $user->id === $post->author_id;
    }
    
    public function update(User $user, Post $post)
    {
        if ($user->hasRole('admin')) {
            return true;
        }
        
        return $user->hasPermissionTo('edit posts') && 
               $user->id === $post->author_id;
    }
    
    public function delete(User $user, Post $post)
    {
        if ($user->hasRole('admin')) {
            return true;
        }
        
        return $user->hasPermissionTo('delete posts') && 
               $user->id === $post->author_id &&
               $post->created_at->diffInDays(now()) <= 1; // Can only delete within 24 hours
    }
}
```

These examples should give you a comprehensive understanding of how to use the OpenBackend Laravel Permission package in various scenarios.
