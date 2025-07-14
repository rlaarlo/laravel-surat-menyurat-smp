<?php

namespace Tests\Feature;

use App\Models\Attachment;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Tests\TestCase;

class OneDriveFileStorageTest extends TestCase
{
    use WithoutMiddleware;

    public function test_attachment_model_generates_onedrive_url_when_path_is_set()
    {
        // Create an attachment with a path (OneDrive file) without saving to database
        $attachment = new Attachment();
        $attachment->filename = 'test-file.pdf';
        $attachment->path = 'attachments/test-file.pdf';
        $attachment->extension = 'pdf';

        // Test that the path_url attribute tries to use OneDrive storage
        $url = $attachment->path_url;
        
        // The URL should not be the old local asset path
        $this->assertNotEquals(asset('storage/attachments/test-file.pdf'), $url);
        
        // Should attempt to use the OneDrive path
        $this->assertNotEmpty($url);
    }

    public function test_attachment_model_fallback_to_local_when_no_path()
    {
        // Create an attachment without a path (legacy local file)
        $attachment = new Attachment();
        $attachment->filename = 'test-file.pdf';
        $attachment->path = null;
        $attachment->extension = 'pdf';

        // Test that it falls back to local asset path
        $url = $attachment->path_url;
        $this->assertEquals(asset('storage/attachments/test-file.pdf'), $url);
    }

    public function test_onedrive_filesystem_configuration_exists()
    {
        // Test that the OneDrive disk is configured
        $this->assertTrue(config('filesystems.disks.onedrive') !== null);
        $this->assertEquals('onedrive', config('filesystems.disks.onedrive.driver'));
        $this->assertArrayHasKey('root', config('filesystems.disks.onedrive'));
        $this->assertArrayHasKey('access_token', config('filesystems.disks.onedrive'));
        $this->assertArrayHasKey('directory_type', config('filesystems.disks.onedrive'));
    }

    public function test_default_filesystem_is_onedrive()
    {
        // Test that the default filesystem is set to onedrive
        $this->assertEquals('onedrive', config('filesystems.default'));
    }

    public function test_onedrive_service_provider_is_registered()
    {
        // Test that the OneDrive service provider is registered
        $providers = config('app.providers');
        $this->assertTrue(in_array('Justus\FlysystemOneDrive\Providers\OneDriveAdapterServiceProvider', $providers));
    }
}