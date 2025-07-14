<?php

namespace App\Http\Controllers;

use App\Services\MicrosoftGraphAuthService;
use App\Services\OneDriveService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class OneDriveAuthController extends Controller
{
    protected $authService;
    protected $oneDriveService;

    public function __construct(MicrosoftGraphAuthService $authService, OneDriveService $oneDriveService)
    {
        $this->authService = $authService;
        $this->oneDriveService = $oneDriveService;
    }

    /**
     * Initiate OneDrive OAuth2 authentication
     */
    public function authenticate(Request $request): RedirectResponse
    {
        try {
            if (!$this->authService->isConfigured()) {
                return back()->with('error', 'OneDrive authentication is not properly configured. Please check your Microsoft Graph API credentials.');
            }

            $state = bin2hex(random_bytes(16));
            Cache::put("oauth_state_{$state}", auth()->id(), now()->addMinutes(10));

            $authUrl = $this->authService->getAuthorizationUrl($state);

            return redirect($authUrl);
        } catch (\Exception $e) {
            Log::error('OneDrive authentication initiation failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to initiate OneDrive authentication: ' . $e->getMessage());
        }
    }

    /**
     * Handle OAuth2 callback from Microsoft
     */
    public function callback(Request $request): RedirectResponse
    {
        try {
            $code = $request->get('code');
            $state = $request->get('state');
            $error = $request->get('error');

            if ($error) {
                Log::warning("OneDrive OAuth error: {$error}");
                return redirect()->route('settings.show')
                    ->with('error', "OneDrive authentication failed: {$error}");
            }

            if (!$code || !$state) {
                return redirect()->route('settings.show')
                    ->with('error', 'Invalid callback parameters from OneDrive');
            }

            // Verify state parameter
            $cachedUserId = Cache::get("oauth_state_{$state}");
            if (!$cachedUserId || $cachedUserId !== auth()->id()) {
                return redirect()->route('settings.show')
                    ->with('error', 'Invalid state parameter. Possible CSRF attack.');
            }

            // Exchange code for access token
            $tokenResult = $this->authService->getAccessToken($code);
            
            if (!$tokenResult['success']) {
                Log::error('OneDrive token exchange failed', $tokenResult);
                return redirect()->route('settings.show')
                    ->with('error', 'Failed to get access token: ' . ($tokenResult['error'] ?? 'Unknown error'));
            }

            // Get user info to verify the connection
            $userInfo = $this->authService->getUserInfo($tokenResult['access_token']);
            
            if (!$userInfo['success']) {
                return redirect()->route('settings.show')
                    ->with('error', 'Failed to verify OneDrive connection');
            }

            // Store the access token (in production, this should be encrypted and stored securely)
            $this->updateOneDriveConfig($tokenResult['access_token'], $tokenResult['refresh_token'] ?? null);

            // Clear the state cache
            Cache::forget("oauth_state_{$state}");

            return redirect()->route('settings.show')
                ->with('success', "OneDrive connected successfully! Connected as: " . ($userInfo['user']['displayName'] ?? 'Unknown User'));

        } catch (\Exception $e) {
            Log::error('OneDrive callback failed: ' . $e->getMessage());
            return redirect()->route('settings.show')
                ->with('error', 'OneDrive authentication failed: ' . $e->getMessage());
        }
    }

    /**
     * Disconnect OneDrive
     */
    public function disconnect(): RedirectResponse
    {
        try {
            $currentToken = config('filesystems.disks.onedrive.access_token');
            
            if ($currentToken) {
                $this->authService->revokeToken($currentToken);
            }

            // Clear the access token from configuration
            $this->updateOneDriveConfig('', '');

            // Clear cached data
            Cache::forget('microsoft_refresh_token');

            return back()->with('success', 'OneDrive disconnected successfully');
        } catch (\Exception $e) {
            Log::error('OneDrive disconnect failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to disconnect OneDrive: ' . $e->getMessage());
        }
    }

    /**
     * Get OneDrive connection status
     */
    public function status()
    {
        try {
            $status = $this->oneDriveService->getConnectionStatus();
            $stats = $this->oneDriveService->getStorageStats();

            return response()->json([
                'connection' => $status,
                'storage' => $stats,
                'configured' => $this->authService->isConfigured(),
            ]);
        } catch (\Exception $e) {
            Log::error('OneDrive status check failed: ' . $e->getMessage());
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Refresh OneDrive access token
     */
    public function refreshToken(): RedirectResponse
    {
        try {
            $result = $this->authService->refreshAccessToken();
            
            if (!$result['success']) {
                return back()->with('error', 'Failed to refresh OneDrive token: ' . ($result['error'] ?? 'Unknown error'));
            }

            $this->updateOneDriveConfig($result['access_token'], $result['refresh_token'] ?? null);

            return back()->with('success', 'OneDrive token refreshed successfully');
        } catch (\Exception $e) {
            Log::error('OneDrive token refresh failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to refresh OneDrive token: ' . $e->getMessage());
        }
    }

    /**
     * Update OneDrive configuration
     * 
     * Note: In production, you should store these tokens securely,
     * preferably encrypted in the database per user
     */
    protected function updateOneDriveConfig(string $accessToken, string $refreshToken = null): void
    {
        // For now, we'll update the environment file
        // In production, consider storing per-user tokens in the database
        $envPath = base_path('.env');
        
        if (file_exists($envPath)) {
            $envContent = file_get_contents($envPath);
            
            // Update access token
            if (strpos($envContent, 'ONEDRIVE_ACCESS_TOKEN=') !== false) {
                $envContent = preg_replace(
                    '/ONEDRIVE_ACCESS_TOKEN=.*/',
                    'ONEDRIVE_ACCESS_TOKEN=' . $accessToken,
                    $envContent
                );
            } else {
                $envContent .= "\nONEDRIVE_ACCESS_TOKEN=" . $accessToken;
            }

            file_put_contents($envPath, $envContent);
        }

        // Cache the refresh token
        if ($refreshToken) {
            Cache::put('microsoft_refresh_token', $refreshToken, now()->addDays(30));
        }
    }
}