# OneDrive Configuration Guide

This guide provides detailed information about configuring the OneDrive integration for different environments and use cases.

## Configuration Files

### Environment Configuration (.env)

The primary configuration is done through environment variables:

```env
# Application Settings
APP_NAME=Surat
APP_ENV=production
APP_URL=https://yourdomain.com

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=surat
DB_USERNAME=your_username
DB_PASSWORD=your_password

# File Storage Configuration
FILESYSTEM_DISK=onedrive

# OneDrive Basic Configuration
ONEDRIVE_ROOT=me
ONEDRIVE_ACCESS_TOKEN=
ONEDRIVE_DIR_TYPE=drives

# Microsoft Graph API Configuration
MICROSOFT_GRAPH_CLIENT_ID=your_client_id
MICROSOFT_GRAPH_CLIENT_SECRET=your_client_secret
MICROSOFT_GRAPH_TENANT_ID=your_tenant_id
MICROSOFT_GRAPH_REDIRECT_URI="${APP_URL}/auth/onedrive/callback"
```

### Microsoft Graph Configuration (config/microsoft-graph.php)

Advanced OneDrive settings can be customized in the configuration file:

```php
<?php

return [
    // OAuth2 Configuration
    'client_id' => env('MICROSOFT_GRAPH_CLIENT_ID'),
    'client_secret' => env('MICROSOFT_GRAPH_CLIENT_SECRET'),
    'tenant_id' => env('MICROSOFT_GRAPH_TENANT_ID', 'common'),
    'redirect_uri' => env('MICROSOFT_GRAPH_REDIRECT_URI'),

    // OAuth2 Scopes
    'scopes' => [
        'Files.ReadWrite',
        'Files.ReadWrite.All',
        'offline_access',
        'openid',
        'profile',
        'email',
    ],

    // API Endpoints
    'endpoints' => [
        'authorization' => 'https://login.microsoftonline.com/{tenant}/oauth2/v2.0/authorize',
        'token' => 'https://login.microsoftonline.com/{tenant}/oauth2/v2.0/token',
        'graph' => 'https://graph.microsoft.com/v1.0',
    ],

    // OneDrive Specific Settings
    'onedrive' => [
        'root_folder' => env('ONEDRIVE_ROOT', 'me'),
        'upload_folder' => 'surat-menyurat',
        'max_file_size' => 100 * 1024 * 1024, // 100MB
        'allowed_extensions' => ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'],
        'chunk_size' => 4 * 1024 * 1024, // 4MB for large uploads
    ],
];
```

### Filesystem Configuration (config/filesystems.php)

The OneDrive disk configuration is already set up:

```php
'disks' => [
    'onedrive' => [
        'driver' => 'onedrive',
        'root' => env('ONEDRIVE_ROOT', 'me'),
        'access_token' => env('ONEDRIVE_ACCESS_TOKEN'),
        'directory_type' => env('ONEDRIVE_DIR_TYPE', 'drives'),
    ],
],
```

## Environment-Specific Configuration

### Development Environment

For local development:

```env
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# OneDrive Configuration
MICROSOFT_GRAPH_CLIENT_ID=your_dev_client_id
MICROSOFT_GRAPH_CLIENT_SECRET=your_dev_client_secret
MICROSOFT_GRAPH_TENANT_ID=common
MICROSOFT_GRAPH_REDIRECT_URI="http://localhost:8000/auth/onedrive/callback"
```

**Important Notes for Development:**
- Create a separate Azure app registration for development
- Use `http://localhost:8000/auth/onedrive/callback` as redirect URI
- Consider using a test OneDrive account for development

### Staging Environment

For staging/testing:

```env
APP_ENV=staging
APP_DEBUG=false
APP_URL=https://staging.yourdomain.com

# OneDrive Configuration
MICROSOFT_GRAPH_CLIENT_ID=your_staging_client_id
MICROSOFT_GRAPH_CLIENT_SECRET=your_staging_client_secret
MICROSOFT_GRAPH_TENANT_ID=your_tenant_id
MICROSOFT_GRAPH_REDIRECT_URI="https://staging.yourdomain.com/auth/onedrive/callback"
```

### Production Environment

For production:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# OneDrive Configuration
MICROSOFT_GRAPH_CLIENT_ID=your_prod_client_id
MICROSOFT_GRAPH_CLIENT_SECRET=your_prod_client_secret
MICROSOFT_GRAPH_TENANT_ID=your_tenant_id
MICROSOFT_GRAPH_REDIRECT_URI="https://yourdomain.com/auth/onedrive/callback"
```

## Advanced Configuration Options

### Custom Upload Folder Structure

Modify the upload folder structure in `config/microsoft-graph.php`:

```php
'onedrive' => [
    'upload_folder' => 'surat-menyurat',
    'folder_structure' => [
        'incoming' => 'attachments/incoming/{year}/{month}',
        'outgoing' => 'attachments/outgoing/{year}/{month}',
        'dispositions' => 'attachments/dispositions/{year}',
    ],
],
```

### File Size and Type Restrictions

Customize file validation rules:

```php
'onedrive' => [
    'max_file_size' => 50 * 1024 * 1024, // 50MB instead of 100MB
    'allowed_extensions' => [
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 
        'jpg', 'jpeg', 'png', 'gif', 'bmp',
        'txt', 'rtf'
    ],
    'allowed_mime_types' => [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'image/jpeg',
        'image/png',
    ],
],
```

### Chunk Upload for Large Files

For files larger than 4MB, configure chunk upload:

```php
'onedrive' => [
    'chunk_size' => 8 * 1024 * 1024, // 8MB chunks
    'enable_chunked_upload' => true,
    'chunk_retry_attempts' => 3,
],
```

### Token Management

Configure token caching and refresh behavior:

```php
'token_management' => [
    'cache_duration' => 3600, // 1 hour
    'auto_refresh' => true,
    'refresh_threshold' => 300, // Refresh if expires in 5 minutes
],
```

### Multi-Tenant Configuration

For organizations with multiple tenants:

```php
'multi_tenant' => [
    'enabled' => true,
    'tenant_mapping' => [
        'admin' => env('MICROSOFT_GRAPH_TENANT_ID'),
        'users' => 'common',
    ],
],
```

## Security Configuration

### SSL/TLS Configuration

Ensure HTTPS is properly configured for production:

```env
# Force HTTPS
FORCE_HTTPS=true

# Session Configuration
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=strict
```

### CORS Configuration

If using API endpoints from external applications:

```php
// config/cors.php
'paths' => [
    'api/*',
    'onedrive/files',
    'onedrive/upload',
    'onedrive/status',
],

'allowed_methods' => ['*'],
'allowed_origins' => ['https://yourdomain.com'],
'allowed_headers' => ['*'],
'exposed_headers' => [],
'max_age' => 0,
'supports_credentials' => true,
```

### Rate Limiting

Configure rate limiting for OneDrive endpoints:

```php
// In RouteServiceProvider or custom middleware
Route::middleware(['throttle:onedrive'])->group(function () {
    // OneDrive routes
});

// In Kernel.php
'onedrive' => \Illuminate\Routing\Middleware\ThrottleRequests::class.':60,1',
```

## Performance Optimization

### Caching Configuration

Optimize caching for OneDrive operations:

```env
# Cache Configuration
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

Configure cache settings:

```php
// config/cache.php
'onedrive' => [
    'driver' => 'redis',
    'connection' => 'default',
    'prefix' => 'onedrive:',
],
```

### Queue Configuration

For background file operations:

```env
QUEUE_CONNECTION=redis
```

Configure queue for OneDrive operations:

```php
// In a service provider
Queue::after(function (JobProcessed $event) {
    if ($event->job->resolveName() === 'OneDriveUploadJob') {
        // Handle completion
    }
});
```

### Database Optimization

Add indexes for OneDrive-related queries:

```php
// In a migration
Schema::table('attachments', function (Blueprint $table) {
    $table->index('path');
    $table->index(['letter_id', 'path']);
});
```

## Backup and Recovery

### OneDrive Backup Strategy

Configure automated backups:

```php
'backup' => [
    'enabled' => true,
    'schedule' => 'daily',
    'retention_days' => 30,
    'backup_folder' => 'backups',
],
```

### Local Backup Fallback

Configure local backup when OneDrive is unavailable:

```php
'fallback' => [
    'enabled' => true,
    'disk' => 'local',
    'sync_when_available' => true,
],
```

## Monitoring and Logging

### Enhanced Logging

Configure detailed logging for OneDrive operations:

```php
// config/logging.php
'channels' => [
    'onedrive' => [
        'driver' => 'daily',
        'path' => storage_path('logs/onedrive.log'),
        'level' => 'debug',
        'days' => 14,
    ],
],
```

### Monitoring Configuration

Set up monitoring for OneDrive health:

```php
'monitoring' => [
    'enabled' => true,
    'check_interval' => 300, // 5 minutes
    'alert_on_failure' => true,
    'max_retry_attempts' => 3,
],
```

## Troubleshooting Configuration Issues

### Common Configuration Problems

1. **Invalid Redirect URI**
   ```
   Error: redirect_uri_mismatch
   Solution: Ensure redirect URI in Azure matches exactly
   ```

2. **Missing Permissions**
   ```
   Error: insufficient_scope
   Solution: Add required permissions in Azure app registration
   ```

3. **Token Issues**
   ```
   Error: invalid_grant
   Solution: Clear cached tokens and re-authenticate
   ```

### Configuration Validation

Add validation for configuration:

```php
// In a service provider
public function boot()
{
    if (!config('microsoft-graph.client_id')) {
        throw new \Exception('Microsoft Graph client ID not configured');
    }
}
```

### Debug Mode

Enable debug mode for troubleshooting:

```env
APP_DEBUG=true
LOG_LEVEL=debug
MICROSOFT_GRAPH_DEBUG=true
```

## Deployment Considerations

### Environment Variables Security

Use secure methods to manage environment variables:

1. **Use encrypted secrets** in production
2. **Rotate client secrets** regularly
3. **Monitor access logs** for suspicious activity
4. **Use different credentials** for each environment

### Configuration Management

Use configuration management tools:

```bash
# Using Laravel Envoy
@servers(['production' => 'user@production-server'])

@task('deploy-config')
    cd /var/www/html
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
@endtask
```

### Health Checks

Implement health checks for OneDrive integration:

```php
// Route for health check
Route::get('/health/onedrive', function() {
    $service = app(\App\Services\OneDriveService::class);
    $status = $service->getConnectionStatus();
    
    return response()->json([
        'status' => $status['connected'] ? 'healthy' : 'unhealthy',
        'timestamp' => now()->toISOString(),
    ]);
});
```

This configuration guide should help you set up OneDrive integration for any environment and customize it according to your specific needs.