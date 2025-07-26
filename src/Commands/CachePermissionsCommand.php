<?php

namespace OpenBackend\LaravelPermission\Commands;

use Illuminate\Console\Command;
use OpenBackend\LaravelPermission\PermissionRegistrar;

class CachePermissionsCommand extends Command
{
    protected $signature = 'permission:cache';
    protected $description = 'Cache all permissions for better performance';

    public function handle()
    {
        try {
            app(PermissionRegistrar::class)->registerPermissions();
            
            $this->info('Permissions cached successfully!');

        } catch (\Exception $e) {
            $this->error("Error caching permissions: {$e->getMessage()}");
            return 1;
        }

        return 0;
    }
}
