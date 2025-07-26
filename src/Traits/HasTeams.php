<?php

namespace OpenBackend\LaravelPermission\Traits;

trait HasTeams
{
    /**
     * Get the team attribute for this model.
     */
    public function getTeamId()
    {
        $teamsConfig = config('permission.teams');

        if (! $teamsConfig['enabled']) {
            return null;
        }

        return $this->getAttribute(config('permission.column_names.team_foreign_key'));
    }

    /**
     * Scope the query to a specific team.
     */
    public function scopeForTeam($query, $teamId)
    {
        $teamsConfig = config('permission.teams');

        if (! $teamsConfig['enabled']) {
            return $query;
        }

        return $query->where(config('permission.column_names.team_foreign_key'), $teamId);
    }

    /**
     * Check if teams feature is enabled.
     */
    public function teamsEnabled(): bool
    {
        return config('permission.teams.enabled', false);
    }
}
