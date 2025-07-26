<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Permission Models
    |--------------------------------------------------------------------------
    |
    | When using this package, we need to know which Eloquent models you want
    | to use for each of the package's features. The models may be changed
    | to your needs as long as they extend the package's base models.
    |
    */

    'models' => [
        /*
         * When using the "HasPermissions" trait from this package, we need to know which
         * Eloquent model should be used to retrieve your permissions. Of course, it
         * is often just the "Permission" model but you may use whatever you like.
         *
         * The model you want to use as a Permission model needs to implement the
         * `OpenBackend\LaravelPermission\Contracts\Permission` contract.
         */
        'permission' => OpenBackend\LaravelPermission\Models\Permission::class,

        /*
         * When using the "HasRoles" trait from this package, we need to know which
         * Eloquent model should be used to retrieve your roles. Of course, it
         * is often just the "Role" model but you may use whatever you like.
         *
         * The model you want to use as a Role model needs to implement the
         * `OpenBackend\LaravelPermission\Contracts\Role` contract.
         */
        'role' => OpenBackend\LaravelPermission\Models\Role::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Permission Table Names
    |--------------------------------------------------------------------------
    |
    | When using this package, we need to know which table should be used to
    | retrieve your permissions, roles, etc. We have chosen basic defaults
    | but you may easily change them to any table you like.
    |
    */

    'table_names' => [
        /*
         * When using the "HasRoles" trait from this package, we need to know which
         * table should be used to retrieve your roles. We have chosen a basic
         * default value but you may easily change it to any table you like.
         */
        'roles' => 'roles',

        /*
         * When using the "HasPermissions" trait from this package, we need to know which
         * table should be used to retrieve your permissions. We have chosen a basic
         * default value but you may easily change it to any table you like.
         */
        'permissions' => 'permissions',

        /*
         * When using the "HasPermissions" trait from this package, we need to know which
         * table should be used to retrieve your user permissions. We have chosen a
         * basic default value but you may easily change it to any table you like.
         */
        'model_has_permissions' => 'model_has_permissions',

        /*
         * When using the "HasRoles" trait from this package, we need to know which
         * table should be used to retrieve your user roles. We have chosen a
         * basic default value but you may easily change it to any table you like.
         */
        'model_has_roles' => 'model_has_roles',

        /*
         * When using the "HasPermissions" trait from this package, we need to know which
         * table should be used to retrieve your role permissions. We have chosen a
         * basic default value but you may easily change it to any table you like.
         */
        'role_has_permissions' => 'role_has_permissions',

        /*
         * Permission groups table for organizing permissions
         */
        'permission_groups' => 'permission_groups',

        /*
         * Audit trail table for tracking permission changes
         */
        'permission_audits' => 'permission_audits',
    ],

    /*
    |--------------------------------------------------------------------------
    | Permission Column Names
    |--------------------------------------------------------------------------
    |
    | Change to `null` if you want to use the primary key column name instead.
    |
    */

    'column_names' => [
        /*
         * Change this if you want to name the related pivots other than defaults
         */
        'role_pivot_key' => null, //default 'role_id',
        'permission_pivot_key' => null, //default 'permission_id',

        /*
         * Change this if you want to name the related model primary key other than
         * `model_id`.
         *
         * For example, this would be nice if your primary keys are all UUIDs. In
         * that case, name this `model_uuid`.
         */
        'model_morph_key' => 'model_id',

        /*
         * Change this if you want to use the teams feature and your related model's
         * foreign key is other than `team_id`.
         */
        'team_foreign_key' => 'team_id',
    ],

    /*
    |--------------------------------------------------------------------------
    | Teams Feature
    |--------------------------------------------------------------------------
    |
    | This package comes with a teams feature which allows permissions
    | and roles to belong to specific teams. This is useful for multi-tenant
    | applications where users can belong to multiple teams/organizations.
    |
    */

    'teams' => [
        /*
         * Enable teams feature
         */
        'enabled' => env('PERMISSION_TEAMS_ENABLED', false),

        /*
         * The model you want to use as a Team model needs to implement the
         * `OpenBackend\LaravelPermission\Contracts\Team` contract.
         */
        'team_model' => OpenBackend\LaravelPermission\Models\Team::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Hierarchical Roles
    |--------------------------------------------------------------------------
    |
    | Enable hierarchical roles where child roles inherit permissions from
    | their parent roles. This creates a tree-like structure of roles.
    |
    */

    'hierarchical_roles' => [
        /*
         * Enable hierarchical roles feature
         */
        'enabled' => env('PERMISSION_HIERARCHICAL_ROLES_ENABLED', true),

        /*
         * Maximum depth for role hierarchy to prevent infinite loops
         */
        'max_depth' => env('PERMISSION_HIERARCHICAL_ROLES_MAX_DEPTH', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Time-based Permissions
    |--------------------------------------------------------------------------
    |
    | Enable time-based permissions where permissions can have expiration dates
    | and be automatically revoked when they expire.
    |
    */

    'time_based_permissions' => [
        /*
         * Enable time-based permissions feature
         */
        'enabled' => env('PERMISSION_TIME_BASED_ENABLED', true),

        /*
         * Automatically clean up expired permissions
         */
        'auto_cleanup' => env('PERMISSION_TIME_BASED_AUTO_CLEANUP', true),

        /*
         * How often to run the cleanup job (in minutes)
         */
        'cleanup_frequency' => env('PERMISSION_TIME_BASED_CLEANUP_FREQUENCY', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Resource-based Permissions
    |--------------------------------------------------------------------------
    |
    | Enable resource-based permissions for fine-grained control over
    | specific model instances.
    |
    */

    'resource_permissions' => [
        /*
         * Enable resource-based permissions feature
         */
        'enabled' => env('PERMISSION_RESOURCE_ENABLED', true),

        /*
         * Cache resource permissions for better performance
         */
        'cache_enabled' => env('PERMISSION_RESOURCE_CACHE_ENABLED', true),

        /*
         * Cache TTL for resource permissions (in minutes)
         */
        'cache_ttl' => env('PERMISSION_RESOURCE_CACHE_TTL', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Caching
    |--------------------------------------------------------------------------
    |
    | This package can cache permissions for better performance. You can
    | configure the cache settings here.
    |
    */

    'cache' => [
        /*
         * By default all permissions are cached for 24 hours to speed up performance.
         * When permissions or roles are updated the cache is flushed automatically.
         */
        'expiration_time' => \DateInterval::createFromDateString(env('PERMISSION_CACHE_EXPIRATION_HOURS', 24) . ' hours'),

        /*
         * The cache key used to store permissions.
         */
        'key' => env('PERMISSION_CACHE_KEY', 'openbackend.permission.cache'),

        /*
         * You may optionally indicate a specific cache driver to use for permission and
         * role caching using any of the `store` drivers listed in the cache.php config
         * file. Using 'default' here means to use the `default` set in cache.php.
         */
        'store' => env('PERMISSION_CACHE_STORE', 'default'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    |
    | Configure middleware behavior for permission and role checking.
    |
    */

    'middleware' => [
        /*
         * Whether to check permissions/roles against the authenticated user's guard.
         * Set to false to check against all guards.
         */
        'use_authenticated_guard' => env('PERMISSION_USE_AUTHENTICATED_GUARD', true),

        /*
         * Default guard to use when no guard is specified
         */
        'default_guard' => env('PERMISSION_DEFAULT_GUARD', 'web'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Trail
    |--------------------------------------------------------------------------
    |
    | Configure audit trail settings for tracking permission changes.
    |
    */

    'audit' => [
        /*
         * Enable audit trail feature
         */
        'enabled' => env('PERMISSION_AUDIT_ENABLED', true),

        /*
         * Events to track
         */
        'events' => [
            'permission_granted',
            'permission_revoked',
            'role_assigned',
            'role_removed',
            'permission_created',
            'permission_updated',
            'permission_deleted',
            'role_created',
            'role_updated',
            'role_deleted',
        ],

        /*
         * Automatically clean up old audit entries
         */
        'auto_cleanup' => env('PERMISSION_AUDIT_AUTO_CLEANUP', true),

        /*
         * Keep audit entries for this many days
         */
        'retention_days' => env('PERMISSION_AUDIT_RETENTION_DAYS', 365),
    ],

    /*
    |--------------------------------------------------------------------------
    | Bulk Operations
    |--------------------------------------------------------------------------
    |
    | Configure bulk operation settings for better performance when dealing
    | with large numbers of permissions and roles.
    |
    */

    'bulk_operations' => [
        /*
         * Enable bulk operations
         */
        'enabled' => env('PERMISSION_BULK_OPERATIONS_ENABLED', true),

        /*
         * Batch size for bulk operations
         */
        'batch_size' => env('PERMISSION_BULK_BATCH_SIZE', 100),

        /*
         * Use database transactions for bulk operations
         */
        'use_transactions' => env('PERMISSION_BULK_USE_TRANSACTIONS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | API Features
    |--------------------------------------------------------------------------
    |
    | Configure API features for permission management.
    |
    */

    'api' => [
        /*
         * Enable REST API endpoints
         */
        'enabled' => env('PERMISSION_API_ENABLED', true),

        /*
         * API route prefix
         */
        'prefix' => env('PERMISSION_API_PREFIX', 'api/permissions'),

        /*
         * API middleware
         */
        'middleware' => ['api', 'auth:sanctum'],

        /*
         * Rate limiting
         */
        'rate_limit' => env('PERMISSION_API_RATE_LIMIT', '60:1'), // 60 requests per minute
    ],

    /*
    |--------------------------------------------------------------------------
    | GUI Dashboard
    |--------------------------------------------------------------------------
    |
    | Configure the web-based GUI dashboard for permission management.
    |
    */

    'gui' => [
        /*
         * Enable GUI dashboard
         */
        'enabled' => env('PERMISSION_GUI_ENABLED', true),

        /*
         * Dashboard route prefix
         */
        'prefix' => env('PERMISSION_GUI_PREFIX', 'permissions'),

        /*
         * Dashboard middleware
         */
        'middleware' => ['web', 'auth'],

        /*
         * Required permission to access dashboard
         */
        'access_permission' => env('PERMISSION_GUI_ACCESS_PERMISSION', 'manage permissions'),
    ],
];
