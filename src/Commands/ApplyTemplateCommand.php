<?php

namespace OpenBackend\LaravelPermission\Commands;

use Illuminate\Console\Command;
use OpenBackend\LaravelPermission\Services\PermissionTemplateService;

class ApplyTemplateCommand extends Command
{
    protected $signature = 'permission:apply-template 
                            {template : Template name (blog, ecommerce, cms, user_management, api)}
                            {--guard= : Guard name}
                            {--group= : Permission group}
                            {--role= : Role name to create}
                            {--no-role : Do not create a role}';

    protected $description = 'Apply a permission template to quickly set up common permission sets';

    public function handle()
    {
        $template = $this->argument('template');
        $service = new PermissionTemplateService();

        // Check if template exists
        if (!$service->getTemplate($template)) {
            $this->error("Template '{$template}' not found.");
            $this->info("Available templates: " . implode(', ', array_keys($service->getTemplates())));
            return 1;
        }

        $options = [
            'guard_name' => $this->option('guard') ?: config('auth.defaults.guard'),
            'group' => $this->option('group') ?: $template,
            'create_role' => !$this->option('no-role'),
            'role_name' => $this->option('role') ?: $template . '_manager'
        ];

        try {
            $results = $service->applyTemplate($template, $options);

            $this->info("Template '{$template}' applied successfully!");
            
            if (!empty($results['permissions'])) {
                $this->info("\nCreated Permissions:");
                foreach ($results['permissions'] as $permission) {
                    $this->line("  ✓ {$permission->name} - {$permission->description}");
                }
            }

            if ($results['role']) {
                $this->info("\nCreated Role:");
                $this->line("  ✓ {$results['role']->name} - {$results['role']->description}");
                $this->line("    Assigned {$results['role']->permissions()->count()} permissions");
            }

            if (!empty($results['errors'])) {
                $this->warn("\nErrors:");
                foreach ($results['errors'] as $error) {
                    $this->line("  ✗ {$error}");
                }
            }

        } catch (\Exception $e) {
            $this->error("Template application failed: {$e->getMessage()}");
            return 1;
        }

        return 0;
    }
}
