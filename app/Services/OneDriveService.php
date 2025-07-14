<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Exception;

class OneDriveService
{
    protected $accessToken;
    protected $graphApiUrl;
    protected $uploadFolder;

    public function __construct()
    {
        $this->accessToken = config('filesystems.disks.onedrive.access_token');
        $this->graphApiUrl = config('microsoft-graph.endpoints.graph');
        $this->uploadFolder = config('microsoft-graph.onedrive.upload_folder');
    }

    /**
     * Check if OneDrive is connected and accessible
     */
    public function isConnected(): bool
    {
        try {
            if (empty($this->accessToken)) {
                return false;
            }

            $response = Http::withToken($this->accessToken)
                ->get("{$this->graphApiUrl}/me/drive");

            return $response->successful();
        } catch (Exception $e) {
            Log::warning('OneDrive connection check failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get OneDrive connection status and details
     */
    public function getConnectionStatus(): array
    {
        $cacheKey = 'onedrive_status_' . md5($this->accessToken);
        
        return Cache::remember($cacheKey, 300, function () {
            try {
                if (!$this->isConnected()) {
                    return [
                        'connected' => false,
                        'error' => 'OneDrive not connected or access token invalid'
                    ];
                }

                $response = Http::withToken($this->accessToken)
                    ->get("{$this->graphApiUrl}/me/drive");

                if ($response->successful()) {
                    $data = $response->json();
                    return [
                        'connected' => true,
                        'drive_id' => $data['id'] ?? null,
                        'drive_name' => $data['name'] ?? 'OneDrive',
                        'owner' => $data['owner']['user']['displayName'] ?? 'Unknown',
                        'total_space' => $data['quota']['total'] ?? 0,
                        'used_space' => $data['quota']['used'] ?? 0,
                        'available_space' => ($data['quota']['total'] ?? 0) - ($data['quota']['used'] ?? 0),
                    ];
                }

                return [
                    'connected' => false,
                    'error' => 'Failed to retrieve OneDrive information'
                ];
            } catch (Exception $e) {
                Log::error('OneDrive status check failed: ' . $e->getMessage());
                return [
                    'connected' => false,
                    'error' => $e->getMessage()
                ];
            }
        });
    }

    /**
     * Upload file to OneDrive
     */
    public function uploadFile($file, $fileName = null, $folder = null): array
    {
        try {
            $fileName = $fileName ?: time() . '-' . $file->getClientOriginalName();
            $fileName = str_replace(' ', '-', $fileName);
            $folder = $folder ?: $this->uploadFolder;
            $path = $folder . '/' . $fileName;

            // Check file size and extension
            $this->validateFile($file);

            // Use Laravel's Storage facade for upload
            $success = Storage::disk('onedrive')->put($path, file_get_contents($file));

            if ($success) {
                return [
                    'success' => true,
                    'path' => $path,
                    'filename' => $fileName,
                    'size' => $file->getSize(),
                    'url' => $this->getFileUrl($path),
                ];
            }

            return ['success' => false, 'error' => 'Upload failed'];
        } catch (Exception $e) {
            Log::error('OneDrive upload failed: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get file URL from OneDrive
     */
    public function getFileUrl(string $path): string
    {
        try {
            return Storage::disk('onedrive')->url($path);
        } catch (Exception $e) {
            Log::warning("Failed to get OneDrive URL for {$path}: " . $e->getMessage());
            return $path; // Return path as fallback
        }
    }

    /**
     * Delete file from OneDrive
     */
    public function deleteFile(string $path): bool
    {
        try {
            return Storage::disk('onedrive')->delete($path);
        } catch (Exception $e) {
            Log::error("OneDrive delete failed for {$path}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * List files in OneDrive folder
     */
    public function listFiles(string $folder = null): array
    {
        try {
            $folder = $folder ?: $this->uploadFolder;
            $files = Storage::disk('onedrive')->files($folder);
            
            $fileList = [];
            foreach ($files as $file) {
                $fileList[] = [
                    'name' => basename($file),
                    'path' => $file,
                    'url' => $this->getFileUrl($file),
                    'size' => Storage::disk('onedrive')->size($file),
                    'modified' => Storage::disk('onedrive')->lastModified($file),
                ];
            }

            return ['success' => true, 'files' => $fileList];
        } catch (Exception $e) {
            Log::error('OneDrive list files failed: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Create folder in OneDrive
     */
    public function createFolder(string $folderName, string $parentFolder = null): bool
    {
        try {
            $parentFolder = $parentFolder ?: $this->uploadFolder;
            $fullPath = $parentFolder . '/' . $folderName;
            
            return Storage::disk('onedrive')->makeDirectory($fullPath);
        } catch (Exception $e) {
            Log::error("OneDrive create folder failed for {$folderName}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Sync database records with OneDrive files
     */
    public function syncFiles(): array
    {
        try {
            $oneDriveFiles = $this->listFiles();
            if (!$oneDriveFiles['success']) {
                return ['success' => false, 'error' => 'Failed to list OneDrive files'];
            }

            $synced = 0;
            $errors = [];

            foreach ($oneDriveFiles['files'] as $file) {
                // Check if file exists in database
                $attachment = \App\Models\Attachment::where('path', $file['path'])->first();
                
                if (!$attachment) {
                    // File exists in OneDrive but not in database
                    $errors[] = "File {$file['name']} exists in OneDrive but not in database";
                } else {
                    $synced++;
                }
            }

            return [
                'success' => true,
                'synced_files' => $synced,
                'total_onedrive_files' => count($oneDriveFiles['files']),
                'errors' => $errors,
            ];
        } catch (Exception $e) {
            Log::error('OneDrive sync failed: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Validate uploaded file
     */
    protected function validateFile($file): void
    {
        $maxSize = config('microsoft-graph.onedrive.max_file_size');
        $allowedExtensions = config('microsoft-graph.onedrive.allowed_extensions');

        if ($file->getSize() > $maxSize) {
            throw new Exception("File size exceeds maximum allowed size of " . ($maxSize / 1024 / 1024) . "MB");
        }

        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, $allowedExtensions)) {
            throw new Exception("File extension '{$extension}' is not allowed. Allowed: " . implode(', ', $allowedExtensions));
        }
    }

    /**
     * Get storage usage statistics
     */
    public function getStorageStats(): array
    {
        $status = $this->getConnectionStatus();
        
        if (!$status['connected']) {
            return ['success' => false, 'error' => 'OneDrive not connected'];
        }

        return [
            'success' => true,
            'total_space' => $status['total_space'] ?? 0,
            'used_space' => $status['used_space'] ?? 0,
            'available_space' => $status['available_space'] ?? 0,
            'used_percentage' => $status['total_space'] > 0 ? 
                round(($status['used_space'] / $status['total_space']) * 100, 2) : 0,
        ];
    }
}