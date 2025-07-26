<?php

namespace OpenBackend\LaravelPermission\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PermissionAudit extends Model
{
    protected $fillable = [
        'event',
        'model_type',
        'model_id',
        'permission_name',
        'role_name',
        'user_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that performed the action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'));
    }

    /**
     * Get the model that was affected.
     */
    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the table name from config.
     */
    public function getTable(): string
    {
        return config('permission.table_names.permission_audits', 'permission_audits');
    }
}
