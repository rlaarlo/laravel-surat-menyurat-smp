<?php

namespace App\Console\Commands;

use App\Services\OneDriveService;
use Illuminate\Console\Command;

class CheckOneDriveStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'onedrive:status {--json : Output as JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check OneDrive connection status and storage information';

    protected $oneDriveService;

    public function __construct(OneDriveService $oneDriveService)
    {
        parent::__construct();
        $this->oneDriveService = $oneDriveService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Checking OneDrive status...');

        $status = $this->oneDriveService->getConnectionStatus();
        $stats = $this->oneDriveService->getStorageStats();

        if ($this->option('json')) {
            $this->line(json_encode([
                'connection' => $status,
                'storage' => $stats,
                'timestamp' => now()->toISOString(),
            ], JSON_PRETTY_PRINT));
            return 0;
        }

        // Display human-readable output
        $this->newLine();
        
        if ($status['connected']) {
            $this->info('✅ OneDrive is connected');
            $this->table(
                ['Property', 'Value'],
                [
                    ['User', $status['owner'] ?? 'Unknown'],
                    ['Drive Name', $status['drive_name'] ?? 'OneDrive'],
                    ['Drive ID', substr($status['drive_id'] ?? 'Unknown', 0, 20) . '...'],
                ]
            );

            if ($stats['success']) {
                $this->newLine();
                $this->info('📊 Storage Information');
                $this->table(
                    ['Metric', 'Value'],
                    [
                        ['Total Space', $this->formatBytes($stats['total_space'])],
                        ['Used Space', $this->formatBytes($stats['used_space'])],
                        ['Available Space', $this->formatBytes($stats['available_space'])],
                        ['Usage Percentage', $stats['used_percentage'] . '%'],
                    ]
                );

                // Warning if storage is getting full
                if ($stats['used_percentage'] > 80) {
                    $this->warn('⚠️  Storage is over 80% full. Consider cleaning up old files.');
                } elseif ($stats['used_percentage'] > 90) {
                    $this->error('🚨 Storage is over 90% full! Immediate action required.');
                }
            }
        } else {
            $this->error('❌ OneDrive is not connected');
            $this->line('Error: ' . ($status['error'] ?? 'Unknown error'));
            $this->newLine();
            $this->line('To connect OneDrive:');
            $this->line('1. Login as an administrator');
            $this->line('2. Go to Settings page');
            $this->line('3. Click "Connect OneDrive" button');
            $this->line('4. Complete Microsoft authentication');
        }

        // Check sync status
        $this->newLine();
        $this->info('🔄 Checking file synchronization...');
        $syncResult = $this->oneDriveService->syncFiles();
        
        if ($syncResult['success']) {
            $this->table(
                ['Sync Metric', 'Value'],
                [
                    ['Files in Database', $syncResult['synced_files']],
                    ['Files in OneDrive', $syncResult['total_onedrive_files']],
                    ['Sync Errors', count($syncResult['errors'])],
                ]
            );

            if (!empty($syncResult['errors'])) {
                $this->warn('Sync issues found:');
                foreach ($syncResult['errors'] as $error) {
                    $this->line('  - ' . $error);
                }
            }
        } else {
            $this->error('Sync check failed: ' . ($syncResult['error'] ?? 'Unknown error'));
        }

        return $status['connected'] ? 0 : 1;
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
