<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $teams = config('permission.teams');

        if (empty($tableNames)) {
            throw new \Exception('Error: config/permission.php not loaded. Run [php artisan config:clear] and try again.');
        }

        if (Schema::hasTable($tableNames['permissions'])) {
            return;
        }

        // Permission Groups Table
        Schema::create($tableNames['permission_groups'] ?? 'permission_groups', function (Blueprint $table) use ($teams) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('icon')->nullable();
            $table->integer('sort_order')->default(0);
            
            if ($teams['enabled'] ?? false) {
                $table->unsignedBigInteger($teams['team_foreign_key'] ?? 'team_id')->nullable();
                $table->index($teams['team_foreign_key'] ?? 'team_id', 'permission_groups_team_foreign_key_index');
            }
            
            $table->timestamps();
            
            $table->unique(['name', $teams['enabled'] ? ($teams['team_foreign_key'] ?? 'team_id') : 'name']);
        });

        // Permissions Table
        Schema::create($tableNames['permissions'], function (Blueprint $table) use ($teams) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->string('description')->nullable();
            $table->string('group')->nullable();
            $table->unsignedBigInteger('group_id')->nullable();
            
            // Resource-based permissions
            $table->string('resource_type')->nullable();
            $table->unsignedBigInteger('resource_id')->nullable();
            
            // Time-based permissions
            $table->timestamp('expires_at')->nullable();
            
            // Additional metadata
            $table->json('meta')->nullable();
            
            if ($teams['enabled'] ?? false) {
                $table->unsignedBigInteger($teams['team_foreign_key'] ?? 'team_id')->nullable();
                $table->index($teams['team_foreign_key'] ?? 'team_id', 'permissions_team_foreign_key_index');
            }
            
            $table->timestamps();
            
            $table->unique(['name', 'guard_name', $teams['enabled'] ? ($teams['team_foreign_key'] ?? 'team_id') : 'guard_name']);
            $table->index(['resource_type', 'resource_id']);
            $table->index('expires_at');
            $table->index('group');
            
            $table->foreign('group_id')->references('id')->on($tableNames['permission_groups'] ?? 'permission_groups')->onDelete('set null');
        });

        // Roles Table
        Schema::create($tableNames['roles'], function (Blueprint $table) use ($teams) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->string('description')->nullable();
            
            // Hierarchical roles
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->integer('level')->default(0);
            
            // Additional metadata
            $table->json('meta')->nullable();
            
            if ($teams['enabled'] ?? false) {
                $table->unsignedBigInteger($teams['team_foreign_key'] ?? 'team_id')->nullable();
                $table->index($teams['team_foreign_key'] ?? 'team_id', 'roles_team_foreign_key_index');
            }
            
            $table->timestamps();
            
            $table->unique(['name', 'guard_name', $teams['enabled'] ? ($teams['team_foreign_key'] ?? 'team_id') : 'guard_name']);
            $table->index('parent_id');
            $table->index('level');
            
            $table->foreign('parent_id')->references('id')->on($tableNames['roles'])->onDelete('cascade');
        });

        // Model Has Permissions Table
        Schema::create($tableNames['model_has_permissions'], function (Blueprint $table) use ($tableNames, $columnNames, $teams) {
            $table->unsignedBigInteger($columnNames['permission_pivot_key'] ?? 'permission_id');
            
            $table->string('model_type');
            $table->unsignedBigInteger($columnNames['model_morph_key'] ?? 'model_id');
            $table->index([$columnNames['model_morph_key'] ?? 'model_id', 'model_type'], 'model_has_permissions_model_id_model_type_index');
            
            // Time-based permissions
            $table->timestamp('expires_at')->nullable();
            
            // Resource-based permissions
            $table->string('resource_type')->nullable();
            $table->unsignedBigInteger('resource_id')->nullable();
            
            // Additional metadata
            $table->json('meta')->nullable();
            
            if ($teams['enabled'] ?? false) {
                $table->unsignedBigInteger($teams['team_foreign_key'] ?? 'team_id')->nullable();
                $table->index($teams['team_foreign_key'] ?? 'team_id', 'model_has_permissions_team_foreign_key_index');
            }
            
            $table->foreign($columnNames['permission_pivot_key'] ?? 'permission_id')
                ->references('id')
                ->on($tableNames['permissions'])
                ->onDelete('cascade');
                
            $table->primary([
                $columnNames['permission_pivot_key'] ?? 'permission_id',
                $columnNames['model_morph_key'] ?? 'model_id',
                'model_type'
            ], 'model_has_permissions_permission_model_type_primary');
            
            $table->index(['resource_type', 'resource_id']);
            $table->index('expires_at');
        });

        // Model Has Roles Table
        Schema::create($tableNames['model_has_roles'], function (Blueprint $table) use ($tableNames, $columnNames, $teams) {
            $table->unsignedBigInteger($columnNames['role_pivot_key'] ?? 'role_id');
            
            $table->string('model_type');
            $table->unsignedBigInteger($columnNames['model_morph_key'] ?? 'model_id');
            $table->index([$columnNames['model_morph_key'] ?? 'model_id', 'model_type'], 'model_has_roles_model_id_model_type_index');
            
            if ($teams['enabled'] ?? false) {
                $table->unsignedBigInteger($teams['team_foreign_key'] ?? 'team_id')->nullable();
                $table->index($teams['team_foreign_key'] ?? 'team_id', 'model_has_roles_team_foreign_key_index');
            }
            
            $table->foreign($columnNames['role_pivot_key'] ?? 'role_id')
                ->references('id')
                ->on($tableNames['roles'])
                ->onDelete('cascade');
                
            $table->primary([
                $columnNames['role_pivot_key'] ?? 'role_id',
                $columnNames['model_morph_key'] ?? 'model_id',
                'model_type'
            ], 'model_has_roles_role_model_type_primary');
        });

        // Role Has Permissions Table
        Schema::create($tableNames['role_has_permissions'], function (Blueprint $table) use ($tableNames, $columnNames) {
            $table->unsignedBigInteger($columnNames['permission_pivot_key'] ?? 'permission_id');
            $table->unsignedBigInteger($columnNames['role_pivot_key'] ?? 'role_id');
            
            $table->foreign($columnNames['permission_pivot_key'] ?? 'permission_id')
                ->references('id')
                ->on($tableNames['permissions'])
                ->onDelete('cascade');
                
            $table->foreign($columnNames['role_pivot_key'] ?? 'role_id')
                ->references('id')
                ->on($tableNames['roles'])
                ->onDelete('cascade');
                
            $table->primary([
                $columnNames['permission_pivot_key'] ?? 'permission_id',
                $columnNames['role_pivot_key'] ?? 'role_id'
            ], 'role_has_permissions_permission_id_role_id_primary');
        });

        // Permission Audits Table
        Schema::create($tableNames['permission_audits'] ?? 'permission_audits', function (Blueprint $table) use ($teams) {
            $table->id();
            $table->string('event');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->string('permission_name')->nullable();
            $table->string('role_name')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            
            if ($teams['enabled'] ?? false) {
                $table->unsignedBigInteger($teams['team_foreign_key'] ?? 'team_id')->nullable();
                $table->index($teams['team_foreign_key'] ?? 'team_id', 'permission_audits_team_foreign_key_index');
            }
            
            $table->timestamps();
            
            $table->index(['model_type', 'model_id']);
            $table->index('event');
            $table->index('user_id');
            $table->index('created_at');
        });

        app('cache')
            ->store(config('permission.cache.store') != 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableNames = config('permission.table_names');

        if (empty($tableNames)) {
            throw new \Exception('Error: config/permission.php not found and defaults could not be merged. Please publish the package configuration before proceeding, or drop the tables manually.');
        }

        Schema::dropIfExists($tableNames['role_has_permissions']);
        Schema::dropIfExists($tableNames['model_has_roles']);
        Schema::dropIfExists($tableNames['model_has_permissions']);
        Schema::dropIfExists($tableNames['roles']);
        Schema::dropIfExists($tableNames['permissions']);
        Schema::dropIfExists($tableNames['permission_groups'] ?? 'permission_groups');
        Schema::dropIfExists($tableNames['permission_audits'] ?? 'permission_audits');
    }
};
