<?php

namespace OpenBackend\LaravelPermission\Commands;

use Illuminate\Console\Command;
use OpenBackend\LaravelPermission\PermissionRegistrar;

class ClearPermissionCacheCommand extends Command
{
    protected $signature = 'permission:clear-cache';
    protected $description = 'Clear the permission cache';

    public function handle()
    {
        try {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
            
            $this->info('Permission cache cleared successfully!');

        } catch (\Exception $e) {
            $this->error("Error clearing permission cache: {$e->getMessage()}");
            return 1;
        }

        return 0;
    }
}
