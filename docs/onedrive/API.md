# OneDrive API Reference

This document provides detailed information about the OneDrive integration API endpoints available in the Laravel Surat Menyurat application.

## Base URL

All OneDrive API endpoints are prefixed with `/onedrive` and require authentication.

## Authentication

Most OneDrive endpoints require user authentication. Admin-only endpoints are marked accordingly.

## Endpoints

### File Management

#### List Files
```http
GET /onedrive/files
```

**Parameters:**
- `folder` (string, optional): Folder path to list files from. Defaults to the configured upload folder.

**Response:**
```json
{
  "success": true,
  "files": [
    {
      "name": "document.pdf",
      "path": "surat-menyurat/document.pdf",
      "url": "https://onedrive.live.com/...",
      "size": 1048576,
      "modified": 1640995200
    }
  ]
}
```

#### Upload File
```http
POST /onedrive/upload
```

**Parameters:**
- `file` (file, required): File to upload (max 100MB)
- `folder` (string, optional): Destination folder path

**Response:**
```json
{
  "success": true,
  "path": "surat-menyurat/document.pdf",
  "filename": "1640995200-document.pdf",
  "size": 1048576,
  "url": "https://onedrive.live.com/..."
}
```

#### Delete File
```http
DELETE /onedrive/file
```

**Parameters:**
- `path` (string, required): Path of the file to delete

**Response:**
```json
{
  "success": true,
  "message": "File deleted successfully"
}
```

#### Create Folder
```http
POST /onedrive/folder
```

**Parameters:**
- `name` (string, required): Name of the folder to create
- `parent` (string, optional): Parent folder path

**Response:**
```json
{
  "success": true,
  "message": "Folder created successfully"
}
```

### Connection Management (Admin Only)

#### Get Connection Status
```http
GET /onedrive/status
```

**Response:**
```json
{
  "connection": {
    "connected": true,
    "drive_id": "b!...",
    "drive_name": "OneDrive",
    "owner": "John Doe",
    "total_space": 1099511627776,
    "used_space": 104857600,
    "available_space": 1099406770176
  },
  "storage": {
    "success": true,
    "total_space": 1099511627776,
    "used_space": 104857600,
    "available_space": 1099406770176,
    "used_percentage": 9.54
  },
  "configured": true
}
```

#### Initiate Authentication
```http
GET /onedrive/auth
```

Redirects to Microsoft OAuth flow.

#### Disconnect OneDrive
```http
POST /onedrive/disconnect
```

**Response:**
```json
{
  "success": true,
  "message": "OneDrive disconnected successfully"
}
```

#### Refresh Token
```http
POST /onedrive/refresh
```

**Response:**
```json
{
  "success": true,
  "message": "OneDrive token refreshed successfully"
}
```

### Synchronization

#### Sync Files
```http
GET /onedrive/sync
```

**Response:**
```json
{
  "success": true,
  "synced_files": 25,
  "total_onedrive_files": 30,
  "errors": [
    "File document.pdf exists in OneDrive but not in database"
  ]
}
```

#### Get Storage Statistics
```http
GET /onedrive/stats
```

**Response:**
```json
{
  "success": true,
  "total_space": 1099511627776,
  "used_space": 104857600,
  "available_space": 1099406770176,
  "used_percentage": 9.54
}
```

## Error Responses

All endpoints may return error responses in the following format:

```json
{
  "success": false,
  "error": "Error message describing what went wrong"
}
```

Common HTTP status codes:
- `200` - Success
- `400` - Bad Request (validation errors)
- `401` - Unauthorized
- `403` - Forbidden (insufficient permissions)
- `404` - Not Found
- `422` - Unprocessable Entity (validation errors)
- `500` - Internal Server Error

## Rate Limiting

The OneDrive integration respects Microsoft Graph API rate limits:
- **Per-app**: 10,000 requests per 10 minutes
- **Per-user**: 1,000 requests per 10 minutes

When rate limits are exceeded, the API will return HTTP 429 with retry-after headers.

## File Validation

Uploaded files must meet the following criteria:
- **Maximum size**: 100MB
- **Allowed extensions**: pdf, doc, docx, jpg, jpeg, png
- **Content validation**: Files are checked for valid headers

## Security Considerations

1. **CSRF Protection**: All POST/PUT/DELETE requests require CSRF tokens
2. **Authentication**: Most endpoints require user authentication
3. **Authorization**: Admin-only endpoints check user roles
4. **File Validation**: Strict validation of uploaded files
5. **Token Security**: Access tokens are handled securely

## Examples

### JavaScript/Fetch API

#### Upload a file
```javascript
const formData = new FormData();
formData.append('file', fileInput.files[0]);
formData.append('folder', 'documents');

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
    console.log('File uploaded to:', data.path);
  } else {
    console.error('Upload failed:', data.error);
  }
});
```

#### Get connection status
```javascript
fetch('/onedrive/status')
  .then(response => response.json())
  .then(data => {
    if (data.connection.connected) {
      console.log('OneDrive connected as:', data.connection.owner);
      console.log('Storage used:', data.storage.used_percentage + '%');
    } else {
      console.log('OneDrive not connected');
    }
  });
```

#### List files
```javascript
fetch('/onedrive/files?folder=attachments')
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      data.files.forEach(file => {
        console.log(`${file.name} (${formatBytes(file.size)})`);
      });
    }
  });
```

### cURL Examples

#### Upload file
```bash
curl -X POST \
  -H "X-CSRF-TOKEN: your-csrf-token" \
  -F "file=@document.pdf" \
  -F "folder=attachments" \
  http://your-app.com/onedrive/upload
```

#### Get status
```bash
curl -X GET \
  -H "Authorization: Bearer your-session-token" \
  http://your-app.com/onedrive/status
```

#### Delete file
```bash
curl -X DELETE \
  -H "Content-Type: application/json" \
  -H "X-CSRF-TOKEN: your-csrf-token" \
  -d '{"path":"attachments/document.pdf"}' \
  http://your-app.com/onedrive/file
```

## Helper Functions

The frontend includes several helper functions for working with the OneDrive API:

### formatBytes(bytes, decimals = 2)
Formats byte values into human-readable strings.

```javascript
formatBytes(1048576); // "1 MB"
formatBytes(1536, 1); // "1.5 KB"
```

### refreshOneDriveStatus()
Refreshes the OneDrive connection status display.

### disconnectOneDrive()
Disconnects OneDrive with user confirmation.

### syncOneDriveFiles()
Triggers file synchronization with user confirmation.

## Webhooks (Future Enhancement)

While not currently implemented, the API is designed to support Microsoft Graph webhooks for real-time file synchronization:

```http
POST /onedrive/webhooks/subscribe
POST /onedrive/webhooks/notification
DELETE /onedrive/webhooks/unsubscribe
```

This would enable automatic updates when files are modified directly in OneDrive.