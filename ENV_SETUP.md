# Environment Configuration Guide

## Overview

While the OpenBackend Laravel Permission package works out of the box with sensible defaults, you can customize its behavior using environment variables for different environments (development, staging, production).

## Do I Need .env Setup?

**Short Answer:** No, it's optional but recommended for production environments.

**Long Answer:** 
- ✅ **Works without .env setup** - All settings have sensible defaults
- ✅ **Recommended for production** - Environment-specific configuration
- ✅ **Required for customization** - If you want to change default behavior

## Quick Start (No .env needed)

The package works immediately after installation with these defaults:

```php
// Default settings that work out of the box
'hierarchical_roles' => ['enabled' => true],
'time_based_permissions' => ['enabled' => true],
'resource_permissions' => ['enabled' => true],
'audit' => ['enabled' => true],
'api' => ['enabled' => true],
'gui' => ['enabled' => true],
```

## Environment Variables Setup

### 1. Copy Environment Template

Copy the `.env.example` from the package to your Laravel app's `.env` file:

```bash
# Add these lines to your Laravel .env file

# OpenBackend Laravel Permission Configuration
PERMISSION_TEAMS_ENABLED=false
PERMISSION_HIERARCHICAL_ROLES_ENABLED=true
PERMISSION_TIME_BASED_ENABLED=true
PERMISSION_AUDIT_ENABLED=true
PERMISSION_API_ENABLED=true
PERMISSION_GUI_ENABLED=true
```

### 2. Environment-Specific Configurations

#### Development Environment
```env
# .env (Development)
PERMISSION_AUDIT_ENABLED=true
PERMISSION_AUDIT_RETENTION_DAYS=30
PERMISSION_API_ENABLED=true
PERMISSION_GUI_ENABLED=true
PERMISSION_CACHE_STORE=file
```

#### Staging Environment
```env
# .env.staging
PERMISSION_AUDIT_ENABLED=true
PERMISSION_AUDIT_RETENTION_DAYS=90
PERMISSION_API_ENABLED=true
PERMISSION_GUI_ENABLED=true
PERMISSION_CACHE_STORE=redis
PERMISSION_BULK_BATCH_SIZE=50
```

#### Production Environment
```env
# .env.production
PERMISSION_AUDIT_ENABLED=true
PERMISSION_AUDIT_RETENTION_DAYS=365
PERMISSION_API_ENABLED=false  # Disable if not needed
PERMISSION_GUI_ENABLED=false  # Disable in production for security
PERMISSION_CACHE_STORE=redis
PERMISSION_BULK_BATCH_SIZE=200
PERMISSION_API_RATE_LIMIT="30:1"  # Stricter rate limiting
```

## Available Environment Variables

### Core Features
```env
# Teams/Multi-tenancy
PERMISSION_TEAMS_ENABLED=false

# Hierarchical Roles
PERMISSION_HIERARCHICAL_ROLES_ENABLED=true
PERMISSION_HIERARCHICAL_ROLES_MAX_DEPTH=10

# Time-based Permissions
PERMISSION_TIME_BASED_ENABLED=true
PERMISSION_TIME_BASED_AUTO_CLEANUP=true
PERMISSION_TIME_BASED_CLEANUP_FREQUENCY=60

# Resource-based Permissions
PERMISSION_RESOURCE_ENABLED=true
PERMISSION_RESOURCE_CACHE_ENABLED=true
PERMISSION_RESOURCE_CACHE_TTL=60
```

### Performance & Caching
```env
# Cache Configuration
PERMISSION_CACHE_STORE=default
PERMISSION_CACHE_KEY="openbackend.permission.cache"
PERMISSION_CACHE_EXPIRATION_HOURS=24

# Bulk Operations
PERMISSION_BULK_OPERATIONS_ENABLED=true
PERMISSION_BULK_BATCH_SIZE=100
PERMISSION_BULK_USE_TRANSACTIONS=true
```

### Security & Middleware
```env
# Middleware
PERMISSION_USE_AUTHENTICATED_GUARD=true
PERMISSION_DEFAULT_GUARD=web

# API Security
PERMISSION_API_RATE_LIMIT="60:1"
```

### Audit & Compliance
```env
# Audit Trail
PERMISSION_AUDIT_ENABLED=true
PERMISSION_AUDIT_AUTO_CLEANUP=true
PERMISSION_AUDIT_RETENTION_DAYS=365
```

### User Interface
```env
# API Endpoints
PERMISSION_API_ENABLED=true
PERMISSION_API_PREFIX="api/permissions"

# GUI Dashboard
PERMISSION_GUI_ENABLED=true
PERMISSION_GUI_PREFIX="permissions"
PERMISSION_GUI_ACCESS_PERMISSION="manage permissions"
```

## Common Scenarios

### 1. Disable Features You Don't Need

```env
# Minimal setup - only basic roles and permissions
PERMISSION_TIME_BASED_ENABLED=false
PERMISSION_RESOURCE_ENABLED=false
PERMISSION_AUDIT_ENABLED=false
PERMISSION_API_ENABLED=false
PERMISSION_GUI_ENABLED=false
```

### 2. High-Performance Setup

```env
# Optimized for performance
PERMISSION_CACHE_STORE=redis
PERMISSION_CACHE_EXPIRATION_HOURS=48
PERMISSION_RESOURCE_CACHE_ENABLED=true
PERMISSION_RESOURCE_CACHE_TTL=120
PERMISSION_BULK_BATCH_SIZE=500
PERMISSION_BULK_USE_TRANSACTIONS=true
```

### 3. Security-Focused Setup

```env
# Maximum security
PERMISSION_API_ENABLED=false
PERMISSION_GUI_ENABLED=false
PERMISSION_AUDIT_ENABLED=true
PERMISSION_AUDIT_RETENTION_DAYS=2555  # 7 years
PERMISSION_API_RATE_LIMIT="10:1"      # Very strict
```

### 4. Multi-tenant Application

```env
# Enable teams feature
PERMISSION_TEAMS_ENABLED=true
PERMISSION_HIERARCHICAL_ROLES_ENABLED=true
PERMISSION_RESOURCE_ENABLED=true
PERMISSION_AUDIT_ENABLED=true
```

## Custom Table Names (Advanced)

If you need custom table names (optional):

```env
# Custom table names (uncomment if needed)
PERMISSION_TABLE_ROLES=custom_roles
PERMISSION_TABLE_PERMISSIONS=custom_permissions
PERMISSION_TABLE_MODEL_HAS_PERMISSIONS=custom_user_permissions
PERMISSION_TABLE_MODEL_HAS_ROLES=custom_user_roles
PERMISSION_TABLE_ROLE_HAS_PERMISSIONS=custom_role_permissions
```

## Docker Environment

For Docker deployments:

```yaml
# docker-compose.yml
environment:
  - PERMISSION_CACHE_STORE=redis
  - PERMISSION_AUDIT_ENABLED=true
  - PERMISSION_API_ENABLED=false
  - PERMISSION_GUI_ENABLED=false
```

## Environment Validation

Add to your Laravel app's validation:

```php
// config/app.php or custom validator
$requiredEnvVars = [
    'PERMISSION_AUDIT_ENABLED',
    'PERMISSION_CACHE_STORE',
    // ... other critical variables
];

foreach ($requiredEnvVars as $var) {
    if (env($var) === null) {
        throw new Exception("Missing required environment variable: {$var}");
    }
}
```

## Testing Environment

For testing:

```env
# .env.testing
PERMISSION_CACHE_STORE=array
PERMISSION_AUDIT_ENABLED=false
PERMISSION_API_ENABLED=false
PERMISSION_GUI_ENABLED=false
PERMISSION_BULK_BATCH_SIZE=10
```

## Summary

1. **Not Required**: Package works with defaults
2. **Recommended**: Use for production customization
3. **Flexible**: Override only what you need
4. **Environment-Specific**: Different settings per environment
5. **Performance**: Optimize cache and batch settings
6. **Security**: Control API and GUI access

Choose the approach that fits your application's needs!
