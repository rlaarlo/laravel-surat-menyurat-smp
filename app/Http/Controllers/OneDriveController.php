<?php

namespace App\Http\Controllers;

use App\Services\OneDriveService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class OneDriveController extends Controller
{
    protected $oneDriveService;

    public function __construct(OneDriveService $oneDriveService)
    {
        $this->oneDriveService = $oneDriveService;
    }

    /**
     * Show OneDrive file browser
     */
    public function index(Request $request)
    {
        try {
            $folder = $request->get('folder', config('microsoft-graph.onedrive.upload_folder'));
            $files = $this->oneDriveService->listFiles($folder);
            $status = $this->oneDriveService->getConnectionStatus();

            return view('pages.onedrive.index', [
                'files' => $files,
                'status' => $status,
                'current_folder' => $folder,
            ]);
        } catch (\Exception $e) {
            Log::error('OneDrive index failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to load OneDrive files: ' . $e->getMessage());
        }
    }

    /**
     * List files in OneDrive folder (API endpoint)
     */
    public function listFiles(Request $request): JsonResponse
    {
        try {
            $folder = $request->get('folder', config('microsoft-graph.onedrive.upload_folder'));
            $result = $this->oneDriveService->listFiles($folder);

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('OneDrive list files API failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload file to OneDrive
     */
    public function upload(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'file' => 'required|file|max:102400', // 100MB max
                'folder' => 'sometimes|string',
            ]);

            $file = $request->file('file');
            $folder = $request->get('folder');

            $result = $this->oneDriveService->uploadFile($file, null, $folder);

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('OneDrive upload failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete file from OneDrive
     */
    public function deleteFile(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'path' => 'required|string',
            ]);

            $path = $request->get('path');
            $success = $this->oneDriveService->deleteFile($path);

            return response()->json([
                'success' => $success,
                'message' => $success ? 'File deleted successfully' : 'Failed to delete file'
            ]);
        } catch (\Exception $e) {
            Log::error('OneDrive delete failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create folder in OneDrive
     */
    public function createFolder(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'parent' => 'sometimes|string',
            ]);

            $name = $request->get('name');
            $parent = $request->get('parent');

            $success = $this->oneDriveService->createFolder($name, $parent);

            return response()->json([
                'success' => $success,
                'message' => $success ? 'Folder created successfully' : 'Failed to create folder'
            ]);
        } catch (\Exception $e) {
            Log::error('OneDrive create folder failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sync files between OneDrive and database
     */
    public function sync(): JsonResponse
    {
        try {
            $result = $this->oneDriveService->syncFiles();
            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('OneDrive sync failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get storage statistics
     */
    public function storageStats(): JsonResponse
    {
        try {
            $stats = $this->oneDriveService->getStorageStats();
            return response()->json($stats);
        } catch (\Exception $e) {
            Log::error('OneDrive storage stats failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}