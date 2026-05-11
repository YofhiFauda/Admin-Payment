# 🔒 Security Checklist - Production

## Critical Security Items

### 1. Environment Variables
- [ ] `APP_ENV=production` (bukan local/development)
- [ ] `APP_DEBUG=false` (CRITICAL - jangan expose error details)
- [ ] `APP_KEY` di-generate ulang untuk production
- [ ] Semua password diganti dari default
- [ ] `.env` file permissions: `chmod 600 .env`
- [ ] `.env` tidak ter-commit ke git (sudah ada di .gitignore)

### 2. Database Security
- [ ] Database password kuat (min 16 karakter, random)
- [ ] Database user hanya punya akses ke database yang diperlukan
- [ ] Database tidak exposed ke public (hanya internal network)
- [ ] SSL/TLS enabled untuk database connection
- [ ] Regular backup dengan encryption
- [ ] Prepared statements digunakan (Laravel default)

### 3. Redis Security
- [ ] Redis password kuat
- [ ] Redis tidak exposed ke public
- [ ] Redis persistence enabled untuk data penting
- [ ] Redis maxmemory policy configured

### 4. HTTPS & SSL
- [ ] SSL certificate valid dan tidak expired
- [ ] Force HTTPS untuk semua requests
- [ ] HSTS header enabled
- [ ] SSL certificate auto-renewal setup

### 5. Authentication & Authorization
- [ ] Strong password policy enforced
- [ ] Password hashing dengan bcrypt (Laravel default)
- [ ] Session timeout configured (120 minutes default)
- [ ] CSRF protection enabled (Laravel default)
- [ ] Rate limiting pada login endpoint
- [ ] Account lockout setelah failed attempts

### 6. API Security
- [ ] API rate limiting configured
- [ ] API authentication required
- [ ] API versioning implemented
- [ ] Input validation pada semua endpoints
- [ ] Output sanitization

### 7. File Upload Security
- [ ] File type validation
- [ ] File size limits
- [ ] Virus scanning (jika memungkinkan)
- [ ] Upload directory tidak executable
- [ ] Random filename generation
- [ ] Storage outside web root

### 8. Headers Security
- [ ] X-Frame-Options: SAMEORIGIN
- [ ] X-Content-Type-Options: nosniff
- [ ] X-XSS-Protection: 1; mode=block
- [ ] Strict-Transport-Security
- [ ] Content-Security-Policy

### 9. Dependencies
- [ ] Composer dependencies up-to-date
- [ ] NPM dependencies up-to-date
- [ ] No known vulnerabilities (run `composer audit`)
- [ ] Regular security updates

### 10. Logging & Monitoring
- [ ] Sensitive data tidak di-log (passwords, tokens, etc)
- [ ] Failed login attempts logged
- [ ] Suspicious activities logged
- [ ] Log files tidak accessible via web
- [ ] Log rotation configured

---

## Implementation Guide

### 1. Force HTTPS

**Middleware**: `app/Http/Middleware/ForceHttps.php`
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ForceHttps
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->secure() && app()->environment('production')) {
            return redirect()->secure($request->getRequestUri(), 301);
        }

        return $next($request);
    }
}
```

Register di `bootstrap/app.php`:
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [
        \App\Http\Middleware\ForceHttps::class,
    ]);
})
```

### 2. Security Headers Middleware

**Middleware**: `app/Http/Middleware/SecurityHeaders.php`
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        
        if (app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // Content Security Policy
        $csp = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval'", // Adjust based on needs
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data: https:",
            "font-src 'self' data:",
            "connect-src 'self' wss: https:",
            "frame-ancestors 'self'",
        ];
        $response->headers->set('Content-Security-Policy', implode('; ', $csp));

        return $response;
    }
}
```

### 3. Rate Limiting

**File**: `bootstrap/app.php` atau `app/Providers/RouteServiceProvider.php`
```php
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

// API rate limiting
RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
});

// Login rate limiting
RateLimiter::for('login', function (Request $request) {
    return Limit::perMinute(5)->by($request->ip());
});

// OCR endpoint - lebih ketat
RateLimiter::for('ocr', function (Request $request) {
    return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
});

// Webhook endpoints
RateLimiter::for('webhook', function (Request $request) {
    return Limit::perMinute(100)->by($request->ip());
});
```

Apply di routes:
```php
Route::middleware(['throttle:login'])->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware(['throttle:ocr'])->group(function () {
    Route::post('/api/ocr', [OcrController::class, 'process']);
});
```

### 4. Input Validation

**Example FormRequest**: `app/Http/Requests/TransactionRequest.php`
```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Adjust based on authorization logic
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0', 'max:999999999'],
            'description' => ['required', 'string', 'max:500'],
            'category_id' => ['required', 'exists:transaction_categories,id'],
            'date' => ['required', 'date', 'before_or_equal:today'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'], // 10MB
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'Jumlah harus diisi',
            'amount.numeric' => 'Jumlah harus berupa angka',
            'attachment.mimes' => 'File harus berformat JPG, PNG, atau PDF',
            'attachment.max' => 'Ukuran file maksimal 10MB',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Sanitize input
        $this->merge([
            'description' => strip_tags($this->description),
        ]);
    }
}
```

### 5. File Upload Security

**Service**: `app/Services/SecureFileUploadService.php`
```php
<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SecureFileUploadService
{
    private const ALLOWED_MIMES = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'application/pdf',
    ];

    private const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB

    public function upload(UploadedFile $file, string $directory = 'uploads'): string
    {
        // Validate file type
        if (!in_array($file->getMimeType(), self::ALLOWED_MIMES)) {
            throw new \InvalidArgumentException('File type not allowed');
        }

        // Validate file size
        if ($file->getSize() > self::MAX_FILE_SIZE) {
            throw new \InvalidArgumentException('File size exceeds limit');
        }

        // Generate secure filename
        $extension = $file->getClientOriginalExtension();
        $filename = Str::random(40) . '.' . $extension;

        // Store file
        $path = $file->storeAs($directory, $filename, 'private');

        return $path;
    }

    public function delete(string $path): bool
    {
        return Storage::disk('private')->delete($path);
    }

    public function url(string $path): string
    {
        // Generate temporary signed URL (expires in 1 hour)
        return Storage::disk('private')->temporaryUrl(
            $path,
            now()->addHour()
        );
    }
}
```

### 6. SQL Injection Prevention

Laravel Eloquent dan Query Builder sudah menggunakan prepared statements secara default.

**✅ AMAN (Recommended)**:
```php
// Eloquent
User::where('email', $email)->first();

// Query Builder
DB::table('users')->where('email', $email)->first();

// Raw query dengan binding
DB::select('SELECT * FROM users WHERE email = ?', [$email]);
```

**❌ TIDAK AMAN (Hindari)**:
```php
// Raw query tanpa binding
DB::select("SELECT * FROM users WHERE email = '$email'");
```

### 7. XSS Prevention

Blade template engine sudah escape output secara default.

**✅ AMAN**:
```blade
{{ $user->name }}  <!-- Auto-escaped -->
```

**❌ TIDAK AMAN (Hanya gunakan untuk trusted content)**:
```blade
{!! $htmlContent !!}  <!-- Not escaped -->
```

Jika harus render HTML dari user, gunakan sanitizer:
```bash
composer require mews/purifier
```

```php
use Mews\Purifier\Facades\Purifier;

$clean = Purifier::clean($dirtyHtml);
```

### 8. CSRF Protection

Laravel sudah include CSRF protection secara default.

Pastikan form punya token:
```blade
<form method="POST" action="/submit">
    @csrf
    <!-- form fields -->
</form>
```

Untuk AJAX requests:
```javascript
axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').content;
```

### 9. Mass Assignment Protection

**Model**: Gunakan `$fillable` atau `$guarded`
```php
class User extends Model
{
    // Whitelist approach (recommended)
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    // OR Blacklist approach
    protected $guarded = [
        'id',
        'is_admin',
        'created_at',
        'updated_at',
    ];
}
```

### 10. Secure Session Configuration

**File**: `config/session.php`
```php
return [
    'driver' => env('SESSION_DRIVER', 'redis'),
    'lifetime' => env('SESSION_LIFETIME', 120),
    'expire_on_close' => false,
    'encrypt' => true,
    'http_only' => true,
    'same_site' => 'lax',
    'secure' => env('SESSION_SECURE_COOKIE', true), // true di production
];
```

---

## Security Audit Commands

### Check for vulnerabilities
```bash
# Composer dependencies
composer audit

# NPM dependencies
npm audit
npm audit fix

# Check for outdated packages
composer outdated
npm outdated
```

### Security scanning tools
```bash
# Install security checker
composer require --dev enlightn/security-checker

# Run security check
php artisan security-check

# Static analysis
composer require --dev phpstan/phpstan
./vendor/bin/phpstan analyse app
```

---

## Incident Response Plan

### If Security Breach Detected:

1. **Immediate Actions**:
   - Put application in maintenance mode
   - Disable compromised accounts
   - Change all passwords and secrets
   - Review access logs

2. **Investigation**:
   - Identify breach vector
   - Assess data exposure
   - Document timeline

3. **Remediation**:
   - Patch vulnerability
   - Restore from clean backup if needed
   - Update security measures

4. **Communication**:
   - Notify affected users
   - Report to authorities if required
   - Document lessons learned

5. **Prevention**:
   - Implement additional security measures
   - Update security policies
   - Train team

---

## Regular Security Tasks

### Daily
- [ ] Monitor error logs for suspicious activity
- [ ] Check failed login attempts
- [ ] Review security alerts

### Weekly
- [ ] Review access logs
- [ ] Check for failed jobs (might indicate attack)
- [ ] Monitor disk space and resource usage

### Monthly
- [ ] Update dependencies
- [ ] Review user permissions
- [ ] Test backup restoration
- [ ] Security audit

### Quarterly
- [ ] Penetration testing
- [ ] Security training for team
- [ ] Review and update security policies
- [ ] Rotate secrets and credentials

---

## Security Resources

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [Laravel Security Best Practices](https://laravel.com/docs/security)
- [PHP Security Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/PHP_Configuration_Cheat_Sheet.html)
- [CWE Top 25](https://cwe.mitre.org/top25/)

---

**Last Updated**: May 4, 2026
