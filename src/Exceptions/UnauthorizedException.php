<?php

namespace OpenBackend\LaravelPermission\Exceptions;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class UnauthorizedException extends HttpException
{
    private $requiredRoles = [];
    private $requiredPermissions = [];

    public static function forRoles(array $roles): self
    {
        $message = 'User does not have the right roles.';
        
        if (count($roles) === 1) {
            $message = "User does not have the right role: {$roles[0]}.";
        } elseif (count($roles) > 1) {
            $message = 'User does not have any of the necessary roles: ' . implode(', ', $roles) . '.';
        }

        $exception = new static(Response::HTTP_FORBIDDEN, $message, null, []);
        $exception->requiredRoles = $roles;

        return $exception;
    }

    public static function forPermissions(array $permissions): self
    {
        $message = 'User does not have the right permissions.';
        
        if (count($permissions) === 1) {
            $message = "User does not have the right permission: {$permissions[0]}.";
        } elseif (count($permissions) > 1) {
            $message = 'User does not have any of the necessary permissions: ' . implode(', ', $permissions) . '.';
        }

        $exception = new static(Response::HTTP_FORBIDDEN, $message, null, []);
        $exception->requiredPermissions = $permissions;

        return $exception;
    }

    public static function forRolesOrPermissions(array $rolesOrPermissions): self
    {
        $message = 'User does not have any of the necessary access rights.';
        
        $exception = new static(Response::HTTP_FORBIDDEN, $message, null, []);
        $exception->requiredRoles = $rolesOrPermissions;
        $exception->requiredPermissions = $rolesOrPermissions;

        return $exception;
    }

    public static function notLoggedIn(): self
    {
        return new static(Response::HTTP_UNAUTHORIZED, 'User is not logged in.', null, []);
    }

    public function getRequiredRoles(): array
    {
        return $this->requiredRoles;
    }

    public function getRequiredPermissions(): array
    {
        return $this->requiredPermissions;
    }
}
