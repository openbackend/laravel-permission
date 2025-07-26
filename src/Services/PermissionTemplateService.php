<?php

namespace OpenBackend\LaravelPermission\Services;

use Illuminate\Support\Collection;
use OpenBackend\LaravelPermission\Models\Permission;
use OpenBackend\LaravelPermission\Models\Role;

class PermissionTemplateService
{
    /**
     * Pre-defined permission templates.
     */
    protected array $templates = [
        'blog' => [
            'name' => 'Blog Management',
            'description' => 'Complete blog management permissions',
            'permissions' => [
                'view posts' => 'View blog posts',
                'create posts' => 'Create new blog posts',
                'edit posts' => 'Edit existing blog posts',
                'delete posts' => 'Delete blog posts',
                'publish posts' => 'Publish/unpublish posts',
                'manage categories' => 'Manage blog categories',
                'manage tags' => 'Manage blog tags',
                'moderate comments' => 'Moderate post comments'
            ]
        ],
        'ecommerce' => [
            'name' => 'E-commerce Management',
            'description' => 'Complete e-commerce store permissions',
            'permissions' => [
                'view products' => 'View products',
                'create products' => 'Create new products',
                'edit products' => 'Edit existing products',
                'delete products' => 'Delete products',
                'manage inventory' => 'Manage product inventory',
                'view orders' => 'View customer orders',
                'process orders' => 'Process and fulfill orders',
                'manage customers' => 'Manage customer accounts',
                'view analytics' => 'View sales analytics',
                'manage payments' => 'Manage payment settings'
            ]
        ],
        'cms' => [
            'name' => 'Content Management',
            'description' => 'Content management system permissions',
            'permissions' => [
                'view content' => 'View content',
                'create content' => 'Create new content',
                'edit content' => 'Edit existing content',
                'delete content' => 'Delete content',
                'publish content' => 'Publish/unpublish content',
                'manage media' => 'Manage media library',
                'manage menus' => 'Manage navigation menus',
                'manage themes' => 'Manage website themes',
                'manage plugins' => 'Manage plugins/extensions'
            ]
        ],
        'user_management' => [
            'name' => 'User Management',
            'description' => 'User and role management permissions',
            'permissions' => [
                'view users' => 'View user accounts',
                'create users' => 'Create new user accounts',
                'edit users' => 'Edit user accounts',
                'delete users' => 'Delete user accounts',
                'manage roles' => 'Manage user roles',
                'manage permissions' => 'Manage permissions',
                'view user activity' => 'View user activity logs',
                'manage user sessions' => 'Manage user sessions'
            ]
        ],
        'api' => [
            'name' => 'API Access',
            'description' => 'API access and management permissions',
            'permissions' => [
                'api.read' => 'Read access via API',
                'api.write' => 'Write access via API',
                'api.delete' => 'Delete access via API',
                'api.admin' => 'Administrative API access',
                'manage api keys' => 'Manage API keys',
                'view api logs' => 'View API access logs',
                'manage webhooks' => 'Manage webhooks'
            ]
        ]
    ];

    /**
     * Get all available templates.
     */
    public function getTemplates(): array
    {
        return $this->templates;
    }

    /**
     * Get a specific template.
     */
    public function getTemplate(string $key): ?array
    {
        return $this->templates[$key] ?? null;
    }

    /**
     * Apply a template to create permissions and optionally roles.
     */
    public function applyTemplate(string $templateKey, array $options = []): array
    {
        $template = $this->getTemplate($templateKey);
        
        if (!$template) {
            throw new \Exception("Template '{$templateKey}' not found");
        }

        $guardName = $options['guard_name'] ?? config('auth.defaults.guard');
        $group = $options['group'] ?? $templateKey;
        $createRole = $options['create_role'] ?? true;
        $roleName = $options['role_name'] ?? $templateKey . '_manager';

        $results = [
            'permissions' => [],
            'role' => null,
            'errors' => []
        ];

        try {
            // Create permissions
            foreach ($template['permissions'] as $permissionName => $description) {
                try {
                    $permission = Permission::firstOrCreate([
                        'name' => $permissionName,
                        'guard_name' => $guardName
                    ], [
                        'description' => $description,
                        'group' => $group
                    ]);

                    $results['permissions'][] = $permission;
                } catch (\Exception $e) {
                    $results['errors'][] = "Permission '{$permissionName}': {$e->getMessage()}";
                }
            }

            // Create role if requested
            if ($createRole && !empty($results['permissions'])) {
                try {
                    $role = Role::firstOrCreate([
                        'name' => $roleName,
                        'guard_name' => $guardName
                    ], [
                        'description' => $template['description']
                    ]);

                    $role->syncPermissions(collect($results['permissions'])->pluck('name')->toArray());
                    $results['role'] = $role;
                } catch (\Exception $e) {
                    $results['errors'][] = "Role '{$roleName}': {$e->getMessage()}";
                }
            }

        } catch (\Exception $e) {
            $results['errors'][] = "Template application failed: {$e->getMessage()}";
        }

        return $results;
    }

    /**
     * Create custom template.
     */
    public function createCustomTemplate(string $key, array $template): void
    {
        $this->templates[$key] = $template;
    }

    /**
     * Get permission suggestions based on existing patterns.
     */
    public function getPermissionSuggestions(string $context = ''): array
    {
        $suggestions = [];

        // Common CRUD patterns
        $crudActions = ['view', 'create', 'edit', 'delete'];
        $resources = ['posts', 'users', 'products', 'orders', 'categories'];

        foreach ($resources as $resource) {
            foreach ($crudActions as $action) {
                $suggestions[] = [
                    'name' => "{$action} {$resource}",
                    'description' => ucfirst($action) . " {$resource}",
                    'category' => 'CRUD Operations'
                ];
            }
        }

        // Management permissions
        $managementAreas = ['roles', 'permissions', 'settings', 'reports', 'analytics'];
        foreach ($managementAreas as $area) {
            $suggestions[] = [
                'name' => "manage {$area}",
                'description' => "Manage {$area}",
                'category' => 'Management'
            ];
        }

        // API permissions
        $apiActions = ['api.read', 'api.write', 'api.delete', 'api.admin'];
        foreach ($apiActions as $action) {
            $suggestions[] = [
                'name' => $action,
                'description' => ucfirst(str_replace('api.', '', $action)) . ' API access',
                'category' => 'API Access'
            ];
        }

        // Filter by context if provided
        if ($context) {
            $suggestions = array_filter($suggestions, function ($suggestion) use ($context) {
                return stripos($suggestion['name'], $context) !== false || 
                       stripos($suggestion['description'], $context) !== false;
            });
        }

        return array_values($suggestions);
    }
}
