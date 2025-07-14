@props(['status' => null])

<div class="card mb-4" id="onedrive-status-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="bx bxl-microsoft me-2"></i>
            OneDrive Status
        </h5>
        <div class="dropdown">
            <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                <i class="bx bx-dots-vertical"></i>
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="#" onclick="refreshOneDriveStatus()">
                    <i class="bx bx-refresh me-2"></i>Refresh Status
                </a></li>
                @if(Auth::user()->hasRole('admin'))
                    <li><a class="dropdown-item" href="{{ route('onedrive.index') }}">
                        <i class="bx bx-folder me-2"></i>File Manager
                    </a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="#" onclick="syncOneDriveFiles()">
                        <i class="bx bx-sync me-2"></i>Sync Files
                    </a></li>
                @endif
            </ul>
        </div>
    </div>
    <div class="card-body">
        <div id="onedrive-status-content">
            @if($status && $status['connected'])
                <div class="d-flex align-items-center mb-3">
                    <div class="avatar me-3">
                        <span class="avatar-initial rounded bg-success">
                            <i class="bx bx-check text-white"></i>
                        </span>
                    </div>
                    <div>
                        <h6 class="mb-0 text-success">Connected</h6>
                        <small class="text-muted">{{ $status['owner'] ?? 'Unknown User' }}</small>
                    </div>
                </div>

                @if(isset($status['total_space']) && $status['total_space'] > 0)
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Storage Used</span>
                            <span class="text-muted">
                                {{ formatBytes($status['used_space'] ?? 0) }} / {{ formatBytes($status['total_space']) }}
                            </span>
                        </div>
                        @php
                            $usedPercentage = $status['total_space'] > 0 ? ($status['used_space'] / $status['total_space']) * 100 : 0;
                            $progressClass = $usedPercentage > 80 ? 'bg-danger' : ($usedPercentage > 60 ? 'bg-warning' : 'bg-primary');
                        @endphp
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar {{ $progressClass }}" 
                                 style="width: {{ $usedPercentage }}%"></div>
                        </div>
                    </div>
                @endif

                <div class="text-center">
                    @if(Auth::user()->hasRole('admin'))
                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="disconnectOneDrive()">
                            <i class="bx bx-unlink me-1"></i>
                            Disconnect
                        </button>
                    @endif
                </div>
            @else
                <div class="text-center">
                    <div class="avatar mx-auto mb-3">
                        <span class="avatar-initial rounded bg-warning">
                            <i class="bx bx-cloud-off text-white"></i>
                        </span>
                    </div>
                    <h6 class="mb-2 text-warning">Not Connected</h6>
                    <p class="text-muted mb-3">
                        {{ $status['error'] ?? 'OneDrive is not connected. Connect to enable cloud storage for your documents.' }}
                    </p>
                    @if(Auth::user()->hasRole('admin'))
                        <a href="{{ route('onedrive.auth') }}" class="btn btn-primary btn-sm">
                            <i class="bx bxl-microsoft me-1"></i>
                            Connect OneDrive
                        </a>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
function refreshOneDriveStatus() {
    const content = document.getElementById('onedrive-status-content');
    content.innerHTML = '<div class="text-center"><div class="spinner-border spinner-border-sm" role="status"></div> Loading...</div>';
    
    fetch('{{ route("onedrive.status") }}')
        .then(response => response.json())
        .then(data => {
            // Reload the page to show updated status
            window.location.reload();
        })
        .catch(error => {
            console.error('Error:', error);
            content.innerHTML = '<div class="text-danger text-center">Failed to load status</div>';
        });
}

function disconnectOneDrive() {
    if (confirm('Are you sure you want to disconnect OneDrive? This will remove access to cloud storage.')) {
        fetch('{{ route("onedrive.disconnect") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                alert('Failed to disconnect: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to disconnect OneDrive');
        });
    }
}

function syncOneDriveFiles() {
    if (confirm('This will sync your database with OneDrive files. Continue?')) {
        fetch('{{ route("onedrive.sync") }}')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(`Sync completed! Synced: ${data.synced_files}, Total OneDrive files: ${data.total_onedrive_files}`);
                    if (data.errors && data.errors.length > 0) {
                        console.log('Sync errors:', data.errors);
                    }
                } else {
                    alert('Sync failed: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to sync files');
            });
    }
}

// Helper function to format bytes
function formatBytes(bytes, decimals = 2) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const dm = decimals < 0 ? 0 : decimals;
    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
}
</script>
@endpush

@php
if (!function_exists('formatBytes')) {
    function formatBytes($bytes, $precision = 2) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
@endphp