# Quick Setup Guide for OneDrive Integration

This is a condensed setup guide to get OneDrive integration working quickly.

## 1. Azure Setup (5 minutes)

1. Go to [Azure Portal](https://portal.azure.com) → Azure Active Directory → App registrations
2. Click "New registration":
   - Name: "Laravel Surat Menyurat"
   - Account types: "Personal and organizational accounts"
   - Redirect URI: `https://yourdomain.com/auth/onedrive/callback`
3. Note down: **Application ID**, **Directory ID**
4. Go to "Certificates & secrets" → "New client secret" → Note down the **secret value**
5. Go to "API permissions" → "Add permission" → "Microsoft Graph" → "Delegated permissions"
6. Add these permissions:
   - `Files.ReadWrite`
   - `Files.ReadWrite.All`
   - `offline_access`
   - `openid`, `profile`, `email`
7. Click "Grant admin consent for..."

## 2. Laravel Configuration (2 minutes)

Add to your `.env` file:

```env
MICROSOFT_GRAPH_CLIENT_ID=your_application_id
MICROSOFT_GRAPH_CLIENT_SECRET=your_secret_value
MICROSOFT_GRAPH_TENANT_ID=your_directory_id
MICROSOFT_GRAPH_REDIRECT_URI="${APP_URL}/auth/onedrive/callback"
```

## 3. Connect OneDrive (1 minute)

1. Login as admin
2. Go to Settings
3. Click "Connect OneDrive" in the OneDrive Status card
4. Complete Microsoft login
5. Done! ✅

## 4. Test the Integration

1. Go to OneDrive File Manager (`/onedrive`)
2. Upload a test file
3. Create/edit a letter and attach files
4. Verify files are stored in OneDrive

## Troubleshooting

- **"Not connected"**: Check environment variables with `php artisan config:clear`
- **"Permission denied"**: Ensure you granted admin consent in Azure
- **"Redirect URI mismatch"**: URI in Azure must match exactly

## Features Now Available

✅ **Automatic cloud storage** for all letter attachments  
✅ **OneDrive file browser** with upload/download  
✅ **Storage monitoring** and usage statistics  
✅ **File synchronization** between app and OneDrive  
✅ **Secure OAuth2 authentication** with Microsoft  

For detailed setup and configuration, see the [full tutorial](README.md).