# Security Policy

## Supported Versions

We actively support the following versions of OpenBackend Laravel Permission with security updates:

| Version | Supported          | Laravel Version | PHP Version |
| ------- | ------------------ | --------------- | ----------- |
| 1.1.0     | :white_check_mark: | 10.x, 11.x, 12.x | 8.1+ |
| < 1.0   | :x:                | N/A             | N/A         |

## Reporting a Vulnerability

The OpenBackend team takes security vulnerabilities seriously. We appreciate your efforts to responsibly disclose your findings.

### How to Report

**Please do NOT report security vulnerabilities through public GitHub issues.**

Instead, please report them via one of the following methods:

#### 1. Email (Preferred)
Send details to: **security@openbackend.org**

#### 2. GitHub Security Advisories
Use GitHub's private vulnerability reporting feature:
1. Go to the [Security tab](https://github.com/openbackend/laravel-permission/security)
2. Click "Report a vulnerability"
3. Fill out the security advisory form

#### 3. Direct Contact
Contact the maintainer directly:
- **Rudra Ramesh**: rudraramesh@openbackend.org
- **GitHub**: [@rudraramesh](https://github.com/rudraramesh)

### What to Include

When reporting a vulnerability, please include:

1. **Description**: A clear description of the vulnerability
2. **Impact**: What could an attacker accomplish?
3. **Reproduction Steps**: Step-by-step instructions to reproduce the issue
4. **Proof of Concept**: Code snippets, screenshots, or videos (if applicable)
5. **Affected Versions**: Which versions are affected
6. **Environment**: Laravel version, PHP version, database type
7. **Suggested Fix**: If you have ideas for a fix (optional)

### Example Report

```
Subject: [SECURITY] SQL Injection in Role Assignment

Description:
The role assignment functionality in version 1.0.0 appears to be vulnerable 
to SQL injection when processing user input in the assignRole() method.

Impact:
An attacker could potentially execute arbitrary SQL queries, leading to 
data theft or database compromise.

Reproduction Steps:
1. Create a user with role assignment permissions
2. Use the following payload in role name: '; DROP TABLE users; --
3. Observe that the SQL injection is executed

Affected Versions:
- 1.0.0
- Likely affects all 1.x versions

Environment:
- Laravel 10.48.0
- PHP 8.1.0
- MySQL 8.0
- OpenBackend Laravel Permission 1.0.0

Proof of Concept:
[Include code snippet or screenshots]
```

## Response Timeline

We are committed to responding to security reports promptly:

| Timeframe | Action |
|-----------|--------|
| 24 hours | Initial acknowledgment of your report |
| 72 hours | Preliminary assessment and severity classification |
| 1 week | Detailed investigation and impact analysis |
| 2 weeks | Fix development and testing |
| 4 weeks | Security release (if confirmed vulnerability) |

## Severity Classification

We use the following severity levels:

### Critical
- Remote code execution
- Full system compromise
- Unauthorized admin access
- Mass data exposure

### High
- Privilege escalation
- Authentication bypass
- Sensitive data exposure
- SQL injection

### Medium
- Cross-site scripting (XSS)
- Information disclosure
- Denial of service
- CSRF vulnerabilities

### Low
- Minor information leaks
- Non-sensitive configuration exposure
- Limited impact vulnerabilities

## Security Measures

### Package Security Features

Our package includes several built-in security measures:

#### 1. Input Validation
- All user inputs are validated and sanitized
- Type checking on all parameters
- Length limits on permission and role names
- SQL injection prevention through Eloquent ORM

#### 2. Authentication & Authorization
- Proper guard integration
- Permission inheritance validation
- Role hierarchy depth limits
- Access control for GUI dashboard

#### 3. Audit Trail
- Complete logging of permission changes
- User action tracking
- Immutable audit records
- Configurable retention periods

#### 4. Rate Limiting
- API endpoint rate limiting
- Configurable request limits
- Per-user rate limiting support

#### 5. Cache Security
- Secure cache key generation
- Cache invalidation on permission changes
- No sensitive data in cache keys

### Database Security

#### 1. Query Protection
```php
// ✅ SECURE: Using Eloquent ORM with parameter binding
$role = Role::where('name', $roleName)->first();

// ❌ INSECURE: Raw SQL without binding (WE DON'T DO THIS)
$role = DB::select("SELECT * FROM roles WHERE name = '$roleName'");
```

#### 2. Mass Assignment Protection
```php
// All models use $guarded = [] with explicit fillable arrays
protected $fillable = ['name', 'guard_name', 'description'];
```

#### 3. Foreign Key Constraints
- Proper database relationships
- Cascade deletions where appropriate
- Referential integrity enforcement

### Application Security

#### 1. CSRF Protection
- All web forms include CSRF tokens
- API endpoints use sanctum tokens
- No GET requests for state-changing operations

#### 2. XSS Prevention
```php
// All output is escaped
{{ $permission->name }} // Automatically escaped
{!! $permission->name !!} // Only when explicitly safe
```

#### 3. SQL Injection Prevention
- Exclusive use of Eloquent ORM
- No raw SQL queries with user input
- Parameterized queries throughout

#### 4. Authorization Checks
```php
// Every sensitive operation checks permissions
if (!auth()->user()->can('manage permissions')) {
    throw UnauthorizedException::forPermissions(['manage permissions']);
}
```

## Security Best Practices

### For Users

#### 1. Regular Updates
```bash
# Keep the package updated
composer update openbackend/laravel-permission

# Check for security advisories
composer audit
```

#### 2. Secure Configuration
```env
# Production settings
PERMISSION_GUI_ENABLED=false
PERMISSION_API_ENABLED=false  # If not needed
PERMISSION_AUDIT_ENABLED=true
PERMISSION_CACHE_STORE=redis  # More secure than file
```

#### 3. Database Security
```env
# Use strong database credentials
DB_USERNAME=secure_username
DB_PASSWORD=very_strong_password_123!@#

# Use SSL connections
DB_SSLMODE=require
```

#### 4. Access Control
```php
// Always check permissions before sensitive operations
if (!$user->hasPermissionTo('delete users')) {
    abort(403, 'Unauthorized');
}

// Use middleware for route protection
Route::middleware('permission:manage users')->group(function () {
    // Protected routes
});
```

#### 5. Audit Monitoring
```php
// Monitor audit logs for suspicious activity
$suspiciousActivity = PermissionAudit::where('created_at', '>', now()->subHour())
    ->where('event', 'permission_granted')
    ->whereJsonContains('meta->bulk_operation', true)
    ->get();
```

### For Developers

#### 1. Code Reviews
- All permission-related code requires review
- Security-focused code reviews for sensitive operations
- Automated security scanning in CI/CD

#### 2. Testing
```php
// Always test authorization
public function test_unauthorized_user_cannot_assign_roles()
{
    $user = User::factory()->create();
    $response = $this->actingAs($user)->post('/admin/users/1/roles');
    $response->assertStatus(403);
}
```

#### 3. Input Validation
```php
// Validate all inputs
$request->validate([
    'role_name' => 'required|string|max:255|alpha_dash',
    'permissions' => 'array|max:100',
    'permissions.*' => 'string|exists:permissions,name'
]);
```

## Security Advisories

Security advisories will be published through:

1. **GitHub Security Advisories**: [GitHub Security Tab](https://github.com/openbackend/laravel-permission/security)
2. **Packagist**: Composer security advisories
3. **Package Releases**: Security releases with detailed changelogs
4. **Email Notifications**: To registered security contacts

## Bug Bounty Program

While we don't currently offer monetary rewards, we do provide:

- **Public recognition** in our security acknowledgments (with your permission)
- **Priority support** for contributors who report valid security issues
- **Contributor status** for significant security improvements
- **Early access** to beta versions for security testing

## Hall of Fame

We thank the following security researchers for their responsible disclosure:

*No security issues reported yet - be the first to help make this package more secure!*

## Security Contact

For any security-related questions or concerns:

- **Email**: security@openbackend.org
- **Response Time**: Within 24 hours
- **Encryption**: PGP key available upon request

## Legal

By reporting security vulnerabilities, you agree to:

1. Allow us reasonable time to fix the issue before public disclosure
2. Not access or modify data beyond what's necessary to demonstrate the vulnerability
3. Not perform any destructive actions
4. Keep confidential any information discovered during security research

We commit to:

1. Respond to your report promptly and professionally
2. Keep you informed of our progress
3. Credit you for the discovery (if desired)
4. Not pursue legal action for good-faith security research

---

**Thank you for helping keep OpenBackend Laravel Permission secure!**

Last updated: July 26, 2025
