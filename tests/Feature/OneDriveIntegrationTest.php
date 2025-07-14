<?php

namespace Tests\Feature;

use App\Services\OneDriveService;
use App\Services\MicrosoftGraphAuthService;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Tests\TestCase;

class OneDriveIntegrationTest extends TestCase
{
    use WithoutMiddleware;

    public function test_onedrive_service_can_be_instantiated()
    {
        $service = app(OneDriveService::class);
        $this->assertInstanceOf(OneDriveService::class, $service);
    }

    public function test_microsoft_graph_auth_service_can_be_instantiated()
    {
        $service = app(MicrosoftGraphAuthService::class);
        $this->assertInstanceOf(MicrosoftGraphAuthService::class, $service);
    }

    public function test_onedrive_connection_status_returns_array()
    {
        $service = app(OneDriveService::class);
        $status = $service->getConnectionStatus();
        
        $this->assertIsArray($status);
        $this->assertArrayHasKey('connected', $status);
    }

    public function test_onedrive_service_validates_files_correctly()
    {
        $service = app(OneDriveService::class);
        
        // Test with reflection to access protected method
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('validateFile');
        $method->setAccessible(true);
        
        // Create a mock file
        $mockFile = $this->createMock(\Illuminate\Http\UploadedFile::class);
        $mockFile->method('getClientOriginalExtension')->willReturn('pdf');
        $mockFile->method('getSize')->willReturn(1024 * 1024); // 1MB
        
        // Should not throw exception for valid file
        try {
            $method->invoke($service, $mockFile);
            $this->assertTrue(true); // If no exception, test passes
        } catch (\Exception $e) {
            $this->fail('Valid file should not throw exception: ' . $e->getMessage());
        }
    }

    public function test_onedrive_service_rejects_large_files()
    {
        $service = app(OneDriveService::class);
        
        // Test with reflection to access protected method
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('validateFile');
        $method->setAccessible(true);
        
        // Create a mock file that's too large
        $mockFile = $this->createMock(\Illuminate\Http\UploadedFile::class);
        $mockFile->method('getClientOriginalExtension')->willReturn('pdf');
        $mockFile->method('getSize')->willReturn(150 * 1024 * 1024); // 150MB (over limit)
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('File size exceeds maximum allowed size');
        
        $method->invoke($service, $mockFile);
    }

    public function test_onedrive_service_rejects_invalid_extensions()
    {
        $service = app(OneDriveService::class);
        
        // Test with reflection to access protected method
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('validateFile');
        $method->setAccessible(true);
        
        // Create a mock file with invalid extension
        $mockFile = $this->createMock(\Illuminate\Http\UploadedFile::class);
        $mockFile->method('getClientOriginalExtension')->willReturn('exe');
        $mockFile->method('getSize')->willReturn(1024 * 1024); // 1MB
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('File extension \'exe\' is not allowed');
        
        $method->invoke($service, $mockFile);
    }

    public function test_microsoft_graph_auth_service_generates_auth_url()
    {
        $service = app(MicrosoftGraphAuthService::class);
        
        // Skip if not configured
        if (!$service->isConfigured()) {
            $this->markTestSkipped('Microsoft Graph API not configured');
        }
        
        $url = $service->getAuthorizationUrl('test-state');
        
        $this->assertIsString($url);
        $this->assertStringContainsString('login.microsoftonline.com', $url);
        $this->assertStringContainsString('client_id=', $url);
        $this->assertStringContainsString('state=test-state', $url);
    }

    public function test_microsoft_graph_config_exists()
    {
        $config = config('microsoft-graph');
        
        $this->assertIsArray($config);
        $this->assertArrayHasKey('scopes', $config);
        $this->assertArrayHasKey('endpoints', $config);
        $this->assertArrayHasKey('onedrive', $config);
    }

    public function test_onedrive_routes_are_registered()
    {
        $response = $this->get('/onedrive');
        
        // Should not return 404 (route exists)
        $this->assertNotEquals(404, $response->getStatusCode());
    }

    public function test_onedrive_auth_routes_are_registered()
    {
        // Test auth route exists (will redirect without login)
        $response = $this->get('/onedrive/auth');
        $this->assertNotEquals(404, $response->getStatusCode());
        
        // Test callback route exists
        $response = $this->get('/auth/onedrive/callback');
        $this->assertNotEquals(404, $response->getStatusCode());
    }

    public function test_onedrive_storage_stats_returns_array()
    {
        $service = app(OneDriveService::class);
        $stats = $service->getStorageStats();
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('success', $stats);
        
        if ($stats['success']) {
            $this->assertArrayHasKey('total_space', $stats);
            $this->assertArrayHasKey('used_space', $stats);
            $this->assertArrayHasKey('available_space', $stats);
            $this->assertArrayHasKey('used_percentage', $stats);
        }
    }

    public function test_onedrive_sync_files_returns_array()
    {
        $service = app(OneDriveService::class);
        $result = $service->syncFiles();
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        
        if ($result['success']) {
            $this->assertArrayHasKey('synced_files', $result);
            $this->assertArrayHasKey('total_onedrive_files', $result);
            $this->assertArrayHasKey('errors', $result);
        }
    }
}