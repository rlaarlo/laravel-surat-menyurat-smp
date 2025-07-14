<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Exception;

class MicrosoftGraphAuthService
{
    protected $clientId;
    protected $clientSecret;
    protected $tenantId;
    protected $redirectUri;
    protected $scopes;

    public function __construct()
    {
        $this->clientId = config('microsoft-graph.client_id');
        $this->clientSecret = config('microsoft-graph.client_secret');
        $this->tenantId = config('microsoft-graph.tenant_id', 'common');
        $this->redirectUri = config('microsoft-graph.redirect_uri');
        $this->scopes = config('microsoft-graph.scopes');
    }

    /**
     * Generate authorization URL for OAuth2 flow
     */
    public function getAuthorizationUrl(string $state = null): string
    {
        $state = $state ?: bin2hex(random_bytes(16));
        
        $params = [
            'client_id' => $this->clientId,
            'response_type' => 'code',
            'redirect_uri' => $this->redirectUri,
            'scope' => implode(' ', $this->scopes),
            'state' => $state,
            'response_mode' => 'query',
        ];

        $authUrl = str_replace('{tenant}', $this->tenantId, config('microsoft-graph.endpoints.authorization'));
        
        return $authUrl . '?' . http_build_query($params);
    }

    /**
     * Exchange authorization code for access token
     */
    public function getAccessToken(string $code): array
    {
        try {
            $tokenUrl = str_replace('{tenant}', $this->tenantId, config('microsoft-graph.endpoints.token'));
            
            $response = Http::asForm()->post($tokenUrl, [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'scope' => implode(' ', $this->scopes),
                'code' => $code,
                'redirect_uri' => $this->redirectUri,
                'grant_type' => 'authorization_code',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                // Cache the refresh token for future use
                if (isset($data['refresh_token'])) {
                    Cache::put('microsoft_refresh_token', $data['refresh_token'], now()->addDays(30));
                }

                return [
                    'success' => true,
                    'access_token' => $data['access_token'],
                    'refresh_token' => $data['refresh_token'] ?? null,
                    'expires_in' => $data['expires_in'] ?? 3600,
                    'token_type' => $data['token_type'] ?? 'Bearer',
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to get access token',
                'details' => $response->json()
            ];
        } catch (Exception $e) {
            Log::error('Microsoft Graph token exchange failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Refresh access token using refresh token
     */
    public function refreshAccessToken(string $refreshToken = null): array
    {
        try {
            $refreshToken = $refreshToken ?: Cache::get('microsoft_refresh_token');
            
            if (!$refreshToken) {
                return [
                    'success' => false,
                    'error' => 'No refresh token available'
                ];
            }

            $tokenUrl = str_replace('{tenant}', $this->tenantId, config('microsoft-graph.endpoints.token'));
            
            $response = Http::asForm()->post($tokenUrl, [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'scope' => implode(' ', $this->scopes),
                'refresh_token' => $refreshToken,
                'grant_type' => 'refresh_token',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                // Update cached refresh token if provided
                if (isset($data['refresh_token'])) {
                    Cache::put('microsoft_refresh_token', $data['refresh_token'], now()->addDays(30));
                }

                return [
                    'success' => true,
                    'access_token' => $data['access_token'],
                    'refresh_token' => $data['refresh_token'] ?? $refreshToken,
                    'expires_in' => $data['expires_in'] ?? 3600,
                    'token_type' => $data['token_type'] ?? 'Bearer',
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to refresh access token',
                'details' => $response->json()
            ];
        } catch (Exception $e) {
            Log::error('Microsoft Graph token refresh failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get user information from Microsoft Graph
     */
    public function getUserInfo(string $accessToken): array
    {
        try {
            $response = Http::withToken($accessToken)
                ->get(config('microsoft-graph.endpoints.graph') . '/me');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'user' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to get user information',
                'details' => $response->json()
            ];
        } catch (Exception $e) {
            Log::error('Microsoft Graph user info failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Validate access token
     */
    public function validateToken(string $accessToken): bool
    {
        try {
            $response = Http::withToken($accessToken)
                ->get(config('microsoft-graph.endpoints.graph') . '/me');

            return $response->successful();
        } catch (Exception $e) {
            Log::warning('Token validation failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Revoke access token
     */
    public function revokeToken(string $accessToken): bool
    {
        try {
            // Clear cached refresh token
            Cache::forget('microsoft_refresh_token');
            
            // Microsoft Graph doesn't have a direct revoke endpoint
            // But we can clear local storage and the token will expire naturally
            return true;
        } catch (Exception $e) {
            Log::error('Token revocation failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if the service is properly configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->clientId) && 
               !empty($this->clientSecret) && 
               !empty($this->redirectUri);
    }
}