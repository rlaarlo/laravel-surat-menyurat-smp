# OneDrive File Storage Configuration

This application has been configured to use Microsoft OneDrive for file attachments instead of local storage.

## Configuration

### 1. Environment Variables

Add the following variables to your `.env` file:

```bash
# OneDrive Configuration
ONEDRIVE_ROOT=me
ONEDRIVE_ACCESS_TOKEN=your_onedrive_access_token_here
ONEDRIVE_DIR_TYPE=drives

# Set OneDrive as default filesystem
FILESYSTEM_DISK=onedrive
```

### 2. OneDrive Access Token

To get an OneDrive access token:

1. Register your application in the [Microsoft Azure Portal](https://portal.azure.com)
2. Create an App Registration
3. Configure API permissions for Microsoft Graph:
   - `Files.ReadWrite` (for personal OneDrive)
   - `Files.ReadWrite.All` (for shared OneDrive)
4. Generate a client secret
5. Use OAuth 2.0 flow to get an access token

### 3. Configuration Options

- **ONEDRIVE_ROOT**: 
  - `me` for personal OneDrive
  - `{group_id}/drive` for group shared OneDrive
- **ONEDRIVE_DIR_TYPE**:
  - `drives` for personal OneDrive
  - `groups` for group shared OneDrive

## File Storage Structure

Files are stored in OneDrive under the path: `attachments/{timestamp}-{original_filename}`

## Backward Compatibility

The system maintains backward compatibility with existing local files. Files without a `path` in the database will still use the local asset URLs.

## Implementation Details

### Controllers Modified
- `IncomingLetterController` - file upload methods
- `OutgoingLetterController` - file upload methods

### Model Changes
- `Attachment` model - URL generation method updated to use OneDrive

### Filesystem Configuration
- Added OneDrive disk configuration in `config/filesystems.php`
- Registered OneDrive service provider in `config/app.php`

## Testing

Run the OneDrive tests to verify configuration:

```bash
php artisan test tests/Feature/OneDriveFileStorageTest.php
```