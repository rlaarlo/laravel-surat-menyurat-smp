<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Microsoft Graph API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Microsoft Graph API integration with OneDrive
    |
    */

    'client_id' => env('MICROSOFT_GRAPH_CLIENT_ID'),
    'client_secret' => env('MICROSOFT_GRAPH_CLIENT_SECRET'),
    'tenant_id' => env('MICROSOFT_GRAPH_TENANT_ID', 'common'),
    'redirect_uri' => env('MICROSOFT_GRAPH_REDIRECT_URI'),

    /*
    |--------------------------------------------------------------------------
    | Microsoft Graph Scopes
    |--------------------------------------------------------------------------
    |
    | The OAuth2 scopes required for OneDrive access
    |
    */

    'scopes' => [
        'Files.ReadWrite',
        'Files.ReadWrite.All',
        'offline_access',
        'openid',
        'profile',
        'email',
    ],

    /*
    |--------------------------------------------------------------------------
    | API Endpoints
    |--------------------------------------------------------------------------
    |
    | Microsoft Graph API endpoints
    |
    */

    'endpoints' => [
        'authorization' => 'https://login.microsoftonline.com/{tenant}/oauth2/v2.0/authorize',
        'token' => 'https://login.microsoftonline.com/{tenant}/oauth2/v2.0/token',
        'graph' => 'https://graph.microsoft.com/v1.0',
    ],

    /*
    |--------------------------------------------------------------------------
    | OneDrive Configuration
    |--------------------------------------------------------------------------
    |
    | Additional OneDrive specific settings
    |
    */

    'onedrive' => [
        'root_folder' => env('ONEDRIVE_ROOT', 'me'),
        'upload_folder' => 'surat-menyurat',
        'max_file_size' => 100 * 1024 * 1024, // 100MB
        'allowed_extensions' => ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'],
        'chunk_size' => 4 * 1024 * 1024, // 4MB for large file uploads
    ],
];