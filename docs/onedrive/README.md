# OneDrive Integration Tutorial for Laravel Surat Menyurat SMP

This comprehensive tutorial will guide you through setting up and using Microsoft OneDrive integration with the Laravel Surat Menyurat (Letter Management) application.

## Table of Contents

1. [Overview](#overview)
2. [Prerequisites](#prerequisites)
3. [Setting up Microsoft Graph API](#setting-up-microsoft-graph-api)
4. [Installation and Configuration](#installation-and-configuration)
5. [Features Overview](#features-overview)
6. [Usage Guide](#usage-guide)
7. [Troubleshooting](#troubleshooting)
8. [Best Practices](#best-practices)
9. [Advanced Configuration](#advanced-configuration)

## Overview

The OneDrive integration allows your Laravel Surat Menyurat application to:

- **Store documents in OneDrive** - Automatically save uploaded letters and attachments to Microsoft OneDrive
- **Retrieve documents from OneDrive** - Access and display stored documents directly in the application
- **Sync document management** - Keep local database records synchronized with OneDrive storage
- **Backup and restore** - Use OneDrive as a secure cloud backup solution for important documents
- **User interface for file management** - Browse, upload, and manage OneDrive files directly from the application

## Prerequisites

Before setting up OneDrive integration, ensure you have:

1. **Microsoft Azure Account** - You need an Azure account to create the application registration
2. **Laravel Application** - The Surat Menyurat application should be installed and running
3. **Admin Access** - You need administrator privileges to configure the integration
4. **SSL Certificate** - Microsoft Graph API requires HTTPS for production environments

## Setting up Microsoft Graph API

### Step 1: Create Azure App Registration

1. **Navigate to Azure Portal**
   - Go to [Azure Portal](https://portal.azure.com)
   - Sign in with your Microsoft account

2. **Register a new application**
   - Go to "Azure Active Directory" > "App registrations"
   - Click "New registration"
   - Fill in the details:
     - **Name**: "Laravel Surat Menyurat OneDrive"
     - **Supported account types**: "Accounts in any organizational directory and personal Microsoft accounts"
     - **Redirect URI**: `https://yourdomain.com/auth/onedrive/callback`

3. **Configure API permissions**
   - Go to "API permissions" in your app registration
   - Click "Add a permission" > "Microsoft Graph" > "Delegated permissions"
   - Add the following permissions:
     - `Files.ReadWrite`
     - `Files.ReadWrite.All`
     - `offline_access`
     - `openid`
     - `profile`
     - `email`
   - Click "Grant admin consent"

4. **Create client secret**
   - Go to "Certificates & secrets"
   - Click "New client secret"
   - Add a description and set expiration
   - **Important**: Copy the secret value immediately (it won't be shown again)

5. **Note down the following values**:
   - Application (client) ID
   - Directory (tenant) ID
   - Client secret value

### Step 2: Configure Redirect URI

Make sure your redirect URI in Azure matches exactly with your application URL:
- Development: `http://localhost:8000/auth/onedrive/callback`
- Production: `https://yourdomain.com/auth/onedrive/callback`

## Installation and Configuration

### Step 1: Environment Configuration

1. **Update your `.env` file** with the Microsoft Graph API credentials:

```env
# Microsoft Graph API Configuration for OneDrive
MICROSOFT_GRAPH_CLIENT_ID=your_client_id_here
MICROSOFT_GRAPH_CLIENT_SECRET=your_client_secret_here
MICROSOFT_GRAPH_TENANT_ID=your_tenant_id_here
MICROSOFT_GRAPH_REDIRECT_URI="${APP_URL}/auth/onedrive/callback"

# OneDrive Configuration (already configured)
FILESYSTEM_DISK=onedrive
ONEDRIVE_ROOT=me
ONEDRIVE_ACCESS_TOKEN=
ONEDRIVE_DIR_TYPE=drives
```

2. **Verify the configuration file** exists:
   - The file `config/microsoft-graph.php` should already be present
   - This contains all the API endpoints and OneDrive-specific settings

### Step 2: Initial Setup

1. **Clear configuration cache**:
```bash
php artisan config:clear
php artisan cache:clear
```

2. **Verify the installation**:
```bash
php artisan route:list | grep onedrive
```

You should see the OneDrive routes listed.

### Step 3: Connect to OneDrive

1. **Access the application as an administrator**
2. **Navigate to Settings** (gear icon in the sidebar)
3. **Look for the OneDrive Status card** on the right side
4. **Click "Connect OneDrive"** button
5. **Complete the Microsoft OAuth flow**:
   - You'll be redirected to Microsoft's login page
   - Sign in with your Microsoft account
   - Grant permissions to the application
   - You'll be redirected back to the settings page

6. **Verify the connection**:
   - The OneDrive Status card should show "Connected"
   - You should see storage information and user details

## Features Overview

### 1. OneDrive Status Monitoring

The OneDrive Status component shows:
- **Connection status** - Whether OneDrive is connected or not
- **User information** - Name of the connected Microsoft account
- **Storage usage** - Used space, total space, and available space
- **Quick actions** - Refresh, sync, and file management links

### 2. File Management Interface

The OneDrive File Manager (`/onedrive`) provides:
- **File browser** - View all files stored in OneDrive
- **Upload functionality** - Upload new files to OneDrive
- **Folder creation** - Create new folders for organization
- **File operations** - View, download, and delete files
- **Sync capabilities** - Synchronize database with OneDrive files

### 3. Automatic Document Storage

When users upload attachments to letters:
- Files are automatically stored in OneDrive
- Database records are created with OneDrive paths
- Files are organized in folders (incoming/outgoing)
- Error handling ensures data integrity

### 4. Enhanced Security

The integration includes:
- **Token validation** - Regular checks of access token validity
- **Secure storage** - Tokens are stored securely
- **Error handling** - Graceful handling of API failures
- **File validation** - Only allowed file types and sizes

## Usage Guide

### For Administrators

#### Connecting OneDrive

1. **Navigate to Settings** (`/settings`)
2. **Look for OneDrive Status card**
3. **Click "Connect OneDrive"**
4. **Complete Microsoft authentication**
5. **Verify connection is successful**

#### Managing OneDrive Files

1. **Access File Manager** (`/onedrive`)
2. **Upload files**:
   - Click "Upload File" button
   - Select file (max 100MB)
   - Choose folder (optional)
   - Click "Upload"

3. **Create folders**:
   - Click "New Folder" button
   - Enter folder name
   - Click "Create"

4. **File operations**:
   - **View**: Click on file name or use Actions menu
   - **Download**: Use Actions menu > Download
   - **Delete**: Use Actions menu > Delete (with confirmation)

#### Synchronizing Files

1. **From OneDrive Status card**: Click "Sync Files"
2. **From File Manager**: Use sync button
3. **Review sync results**: Check for any discrepancies

#### Disconnecting OneDrive

1. **Go to Settings**
2. **In OneDrive Status card**, click "Disconnect"
3. **Confirm disconnection**
4. **Note**: This only removes local access tokens, files remain in OneDrive

### For Regular Users

#### Uploading Letter Attachments

1. **Create or edit a letter** (incoming/outgoing)
2. **In the attachments section**, select files
3. **Files are automatically uploaded to OneDrive**
4. **View attachments** by clicking on them in the letter view

#### Viewing Attachments

1. **Open any letter** with attachments
2. **Click on attachment names** to view/download
3. **Files are served directly from OneDrive**

### API Usage (for Developers)

#### Getting OneDrive Status

```javascript
fetch('/onedrive/status')
  .then(response => response.json())
  .then(data => {
    if (data.connection.connected) {
      console.log('OneDrive is connected');
      console.log('Storage used:', data.storage.used_space);
    }
  });
```

#### Uploading Files

```javascript
const formData = new FormData();
formData.append('file', fileInput.files[0]);
formData.append('folder', 'custom-folder');

fetch('/onedrive/upload', {
  method: 'POST',
  body: formData,
  headers: {
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
  }
})
.then(response => response.json())
.then(data => {
  if (data.success) {
    console.log('File uploaded:', data.path);
  }
});
```

#### Listing Files

```javascript
fetch('/onedrive/files?folder=attachments')
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      data.files.forEach(file => {
        console.log(`${file.name} - ${file.size} bytes`);
      });
    }
  });
```

## Troubleshooting

### Common Issues

#### 1. "OneDrive not connected" Error

**Symptoms**: OneDrive Status shows "Not Connected"

**Solutions**:
1. **Check environment variables**:
   ```bash
   php artisan config:clear
   ```
2. **Verify Azure app registration**:
   - Ensure client ID and secret are correct
   - Check redirect URI matches exactly
3. **Re-authenticate**:
   - Click "Connect OneDrive" again
   - Complete the OAuth flow

#### 2. "Access token invalid" Error

**Symptoms**: Upload or file operations fail

**Solutions**:
1. **Refresh the token**:
   - Go to Settings > OneDrive Status
   - Click the refresh button
2. **Re-authenticate if refresh fails**:
   - Disconnect and reconnect OneDrive

#### 3. File Upload Failures

**Symptoms**: Files fail to upload to OneDrive

**Solutions**:
1. **Check file size**: Must be under 100MB
2. **Check file type**: Only PDF, DOC, DOCX, JPG, JPEG, PNG allowed
3. **Check OneDrive storage**: Ensure you have available space
4. **Check logs**: Look in `storage/logs/laravel.log` for detailed errors

#### 4. Permission Denied Errors

**Symptoms**: Cannot access OneDrive features

**Solutions**:
1. **Check user role**: Only admins can connect/disconnect OneDrive
2. **Verify Azure permissions**: Ensure all required permissions are granted
3. **Check admin consent**: Ensure admin consent was given in Azure

### Debug Mode

To enable detailed logging for OneDrive operations:

1. **Set log level in `.env`**:
```env
LOG_LEVEL=debug
```

2. **Check logs**:
```bash
tail -f storage/logs/laravel.log
```

3. **Look for OneDrive-related messages**:
- Authentication attempts
- File upload/download operations
- API errors

### Testing Connection

You can test the OneDrive connection manually:

```bash
# Test the OneDrive service
php artisan tinker

>>> $service = app(\App\Services\OneDriveService::class);
>>> $status = $service->getConnectionStatus();
>>> print_r($status);
```

## Best Practices

### Security

1. **Secure Token Storage**:
   - In production, consider encrypting access tokens
   - Store refresh tokens securely
   - Regularly rotate client secrets

2. **Access Control**:
   - Limit OneDrive connection to administrators only
   - Monitor access logs regularly
   - Use HTTPS in production

3. **Data Protection**:
   - Validate all uploaded files
   - Implement rate limiting for uploads
   - Regular security audits

### Performance

1. **Caching**:
   - OneDrive status is cached for 5 minutes
   - Clear cache if experiencing stale data
   - Consider longer cache times for stable connections

2. **File Management**:
   - Organize files in logical folder structures
   - Regular cleanup of old/unused files
   - Monitor storage usage

3. **Error Handling**:
   - Implement retry logic for transient failures
   - Graceful degradation when OneDrive is unavailable
   - User-friendly error messages

### Maintenance

1. **Regular Monitoring**:
   - Check OneDrive connection status daily
   - Monitor storage usage
   - Review error logs weekly

2. **Backup Strategy**:
   - OneDrive serves as cloud backup
   - Consider additional backup for critical data
   - Test restore procedures regularly

3. **Updates**:
   - Keep Laravel packages updated
   - Monitor Microsoft Graph API changes
   - Test after any updates

## Advanced Configuration

### Custom Folder Structure

You can customize the folder structure by modifying `config/microsoft-graph.php`:

```php
'onedrive' => [
    'upload_folder' => 'surat-menyurat',
    'subfolders' => [
        'incoming' => 'attachments/incoming',
        'outgoing' => 'attachments/outgoing',
        'dispositions' => 'attachments/dispositions',
    ],
],
```

### File Type Restrictions

Modify allowed file types in the configuration:

```php
'onedrive' => [
    'allowed_extensions' => ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'txt'],
    'max_file_size' => 50 * 1024 * 1024, // 50MB
],
```

### Custom Error Handling

Implement custom error handling by extending the OneDriveService:

```php
class CustomOneDriveService extends OneDriveService
{
    protected function handleError(\Exception $e): array
    {
        // Custom error handling logic
        return parent::handleError($e);
    }
}
```

### Webhook Integration

For real-time sync, you can implement Microsoft Graph webhooks:

1. **Register webhook endpoint** in Azure
2. **Handle webhook notifications** in your application
3. **Update local database** when OneDrive files change

## Conclusion

The OneDrive integration provides a robust cloud storage solution for your Laravel Surat Menyurat application. By following this tutorial, you should now have:

- ✅ OneDrive successfully connected to your application
- ✅ Automatic file storage for letter attachments
- ✅ User-friendly file management interface
- ✅ Proper error handling and security measures
- ✅ Understanding of troubleshooting and maintenance

For additional support or questions, refer to the troubleshooting section or check the application logs for detailed error information.