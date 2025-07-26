<?php

namespace OpenBackend\LaravelPermission;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\Compilers\BladeCompiler;
use OpenBackend\LaravelPermission\Models\Permission;
use OpenBackend\LaravelPermission\Commands\CreatePermissionCommand;
use OpenBackend\LaravelPermission\Commands\CreateRoleCommand;
use OpenBackend\LaravelPermission\Commands\AssignRoleCommand;
use OpenBackend\LaravelPermission\Commands\ShowUserPermissionsCommand;
use OpenBackend\LaravelPermission\Commands\ImportPermissionsCommand;
use OpenBackend\LaravelPermission\Commands\ExportPermissionsCommand;
use OpenBackend\LaravelPermission\Commands\CachePermissionsCommand;
use OpenBackend\LaravelPermission\Commands\ClearPermissionCacheCommand;
use OpenBackend\LaravelPermission\Commands\ApplyTemplateCommand;
use OpenBackend\LaravelPermission\Commands\DetectConflictsCommand;
use OpenBackend\LaravelPermission\Commands\SuggestPermissionsCommand;
use OpenBackend\LaravelPermission\Middleware\PermissionMiddleware;
use OpenBackend\LaravelPermission\Middleware\RoleMiddleware;
use OpenBackend\LaravelPermission\Middleware\RoleOrPermissionMiddleware;

class PermissionServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/permission.php', 'permission');

        $this->app->singleton(PermissionRegistrar::class, function ($app) {
            return new PermissionRegistrar($app['cache.store']);
        });

        $this->app->alias(PermissionRegistrar::class, 'permission-registrar');

        $this->registerBladeExtensions();
    }

    public function boot()
    {
        $this->registerPublishables();
        $this->registerCommands();
        $this->registerMiddleware();
        $this->registerGates();
        $this->registerMacros();

        // Only register permissions if tables exist
        if ($this->hasPermissionTables()) {
            $this->app->make(PermissionRegistrar::class)->registerPermissions();
        }
    }

    protected function registerPublishables()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/permission.php' => config_path('permission.php'),
            ], 'config');

            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'migrations');

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/laravel-permission'),
            ], 'views');
        }
    }

    protected function registerCommands()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                CreatePermissionCommand::class,
                CreateRoleCommand::class,
                AssignRoleCommand::class,
                ShowUserPermissionsCommand::class,
                ImportPermissionsCommand::class,
                ExportPermissionsCommand::class,
                CachePermissionsCommand::class,
                ClearPermissionCacheCommand::class,
                ApplyTemplateCommand::class,
                DetectConflictsCommand::class,
                SuggestPermissionsCommand::class,
            ]);
        }
    }

    protected function registerMiddleware()
    {
        $router = $this->app['router'];

        $router->aliasMiddleware('permission', PermissionMiddleware::class);
        $router->aliasMiddleware('role', RoleMiddleware::class);
        $router->aliasMiddleware('role_or_permission', RoleOrPermissionMiddleware::class);
    }

    protected function registerGates()
    {
        Gate::before(function ($user, $ability) {
            if (method_exists($user, 'hasPermissionTo')) {
                return $user->hasPermissionTo($ability) ?: null;
            }
        });
    }

    protected function registerMacros()
    {
        // Register query builder macros for easier permission queries
        \Illuminate\Database\Eloquent\Builder::macro('whereHasPermission', function ($permission) {
            return $this->whereHas('permissions', function ($query) use ($permission) {
                $query->where('name', $permission);
            });
        });

        \Illuminate\Database\Eloquent\Builder::macro('whereHasRole', function ($role) {
            return $this->whereHas('roles', function ($query) use ($role) {
                $query->where('name', $role);
            });
        });
    }

    protected function registerBladeExtensions()
    {
        $this->callAfterResolving('blade.compiler', function (BladeCompiler $bladeCompiler) {
            // @role directive
            $bladeCompiler->directive('role', function ($arguments) {
                list($role, $guard) = explode(',', $arguments.',');

                return "<?php if(auth({$guard})->check() && auth({$guard})->user()->hasRole({$role})): ?>";
            });

            $bladeCompiler->directive('endrole', function () {
                return '<?php endif; ?>';
            });

            // @hasrole directive
            $bladeCompiler->directive('hasrole', function ($arguments) {
                list($role, $guard) = explode(',', $arguments.',');

                return "<?php if(auth({$guard})->check() && auth({$guard})->user()->hasRole({$role})): ?>";
            });

            $bladeCompiler->directive('endhasrole', function () {
                return '<?php endif; ?>';
            });

            // @hasanyrole directive
            $bladeCompiler->directive('hasanyrole', function ($arguments) {
                list($roles, $guard) = explode(',', $arguments.',');

                return "<?php if(auth({$guard})->check() && auth({$guard})->user()->hasAnyRole({$roles})): ?>";
            });

            $bladeCompiler->directive('endhasanyrole', function () {
                return '<?php endif; ?>';
            });

            // @hasallroles directive
            $bladeCompiler->directive('hasallroles', function ($arguments) {
                list($roles, $guard) = explode(',', $arguments.',');

                return "<?php if(auth({$guard})->check() && auth({$guard})->user()->hasAllRoles({$roles})): ?>";
            });

            $bladeCompiler->directive('endhasallroles', function () {
                return '<?php endif; ?>';
            });

            // @unlessrole directive
            $bladeCompiler->directive('unlessrole', function ($arguments) {
                list($role, $guard) = explode(',', $arguments.',');

                return "<?php if(!auth({$guard})->check() || !auth({$guard})->user()->hasRole({$role})): ?>";
            });

            $bladeCompiler->directive('endunlessrole', function () {
                return '<?php endif; ?>';
            });

            // @hasanypermission directive
            $bladeCompiler->directive('hasanypermission', function ($arguments) {
                list($permissions, $guard) = explode(',', $arguments.',');

                return "<?php if(auth({$guard})->check() && auth({$guard})->user()->hasAnyPermission({$permissions})): ?>";
            });

            $bladeCompiler->directive('endhasanypermission', function () {
                return '<?php endif; ?>';
            });

            // @hasallpermissions directive
            $bladeCompiler->directive('hasallpermissions', function ($arguments) {
                list($permissions, $guard) = explode(',', $arguments.',');

                return "<?php if(auth({$guard})->check() && auth({$guard})->user()->hasAllPermissions({$permissions})): ?>";
            });

            $bladeCompiler->directive('endhasallpermissions', function () {
                return '<?php endif; ?>';
            });
        });
    }

    protected function hasPermissionTables(): bool
    {
        try {
            return \Schema::hasTable('permissions') && \Schema::hasTable('roles');
        } catch (\Exception $e) {
            return false;
        }
    }
}
