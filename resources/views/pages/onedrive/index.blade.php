@extends('layout.main')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">
                            <i class="bx bxl-microsoft me-2"></i>
                            OneDrive File Manager
                        </h5>
                        <small class="text-muted">Current folder: {{ $current_folder }}</small>
                    </div>
                    <div class="btn-group">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
                            <i class="bx bx-upload me-1"></i>
                            Upload File
                        </button>
                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#createFolderModal">
                            <i class="bx bx-folder-plus me-1"></i>
                            New Folder
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="refreshFiles()">
                            <i class="bx bx-refresh me-1"></i>
                            Refresh
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Connection Status -->
                    @if(!$status['connected'])
                        <div class="alert alert-warning">
                            <i class="bx bx-warning me-2"></i>
                            OneDrive is not connected. {{ $status['error'] ?? '' }}
                            @if(Auth::user()->hasRole('admin'))
                                <a href="{{ route('onedrive.auth') }}" class="btn btn-sm btn-warning ms-2">
                                    Connect OneDrive
                                </a>
                            @endif
                        </div>
                    @endif

                    <!-- Files List -->
                    <div id="files-container">
                        @if($files['success'])
                            @if(count($files['files']) > 0)
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Size</th>
                                                <th>Modified</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($files['files'] as $file)
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <i class="bx {{ getFileIcon(pathinfo($file['name'], PATHINFO_EXTENSION)) }} me-2"></i>
                                                            <span>{{ $file['name'] }}</span>
                                                        </div>
                                                    </td>
                                                    <td>{{ formatBytes($file['size']) }}</td>
                                                    <td>{{ date('Y-m-d H:i', $file['modified']) }}</td>
                                                    <td>
                                                        <div class="dropdown">
                                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                                                Actions
                                                            </button>
                                                            <ul class="dropdown-menu">
                                                                <li>
                                                                    <a class="dropdown-item" href="{{ $file['url'] }}" target="_blank">
                                                                        <i class="bx bx-show me-2"></i>View
                                                                    </a>
                                                                </li>
                                                                <li>
                                                                    <a class="dropdown-item" href="{{ $file['url'] }}" download>
                                                                        <i class="bx bx-download me-2"></i>Download
                                                                    </a>
                                                                </li>
                                                                <li><hr class="dropdown-divider"></li>
                                                                <li>
                                                                    <a class="dropdown-item text-danger" href="#" onclick="deleteFile('{{ $file['path'] }}', '{{ $file['name'] }}')">
                                                                        <i class="bx bx-trash me-2"></i>Delete
                                                                    </a>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center py-4">
                                    <i class="bx bx-folder-open display-1 text-muted"></i>
                                    <h5 class="mt-3 text-muted">No files found</h5>
                                    <p class="text-muted">Upload some files to get started</p>
                                </div>
                            @endif
                        @else
                            <div class="alert alert-danger">
                                <i class="bx bx-error me-2"></i>
                                {{ $files['error'] ?? 'Failed to load files' }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Upload Modal -->
<div class="modal fade" id="uploadModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Upload File to OneDrive</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="uploadForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="fileInput" class="form-label">Select File</label>
                        <input type="file" class="form-control" id="fileInput" name="file" required>
                        <div class="form-text">
                            Maximum file size: 100MB. Allowed types: PDF, DOC, DOCX, JPG, JPEG, PNG
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="folderInput" class="form-label">Folder (optional)</label>
                        <input type="text" class="form-control" id="folderInput" name="folder" 
                               value="{{ $current_folder }}" placeholder="Leave empty for default folder">
                    </div>
                    <div id="uploadProgress" class="d-none">
                        <div class="progress mb-2">
                            <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                        </div>
                        <small class="text-muted">Uploading...</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Upload</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Create Folder Modal -->
<div class="modal fade" id="createFolderModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create New Folder</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="createFolderForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="folderNameInput" class="form-label">Folder Name</label>
                        <input type="text" class="form-control" id="folderNameInput" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="parentFolderInput" class="form-label">Parent Folder</label>
                        <input type="text" class="form-control" id="parentFolderInput" name="parent" 
                               value="{{ $current_folder }}" readonly>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Upload form handler
document.getElementById('uploadForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const progressDiv = document.getElementById('uploadProgress');
    const progressBar = progressDiv.querySelector('.progress-bar');
    
    progressDiv.classList.remove('d-none');
    
    fetch('{{ route("onedrive.upload") }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('uploadModal')).hide();
            alert('File uploaded successfully!');
            refreshFiles();
        } else {
            alert('Upload failed: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Upload failed: ' + error.message);
    })
    .finally(() => {
        progressDiv.classList.add('d-none');
        progressBar.style.width = '0%';
        document.getElementById('uploadForm').reset();
    });
});

// Create folder form handler
document.getElementById('createFolderForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('{{ route("onedrive.folder.create") }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('createFolderModal')).hide();
            alert('Folder created successfully!');
            refreshFiles();
        } else {
            alert('Failed to create folder: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to create folder: ' + error.message);
    })
    .finally(() => {
        document.getElementById('createFolderForm').reset();
    });
});

function refreshFiles() {
    const container = document.getElementById('files-container');
    container.innerHTML = '<div class="text-center py-4"><div class="spinner-border" role="status"></div></div>';
    
    window.location.reload();
}

function deleteFile(path, name) {
    if (confirm(`Are you sure you want to delete "${name}"? This action cannot be undone.`)) {
        fetch('{{ route("onedrive.delete") }}', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ path: path })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('File deleted successfully!');
                refreshFiles();
            } else {
                alert('Failed to delete file: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to delete file: ' + error.message);
        });
    }
}
</script>
@endpush

@php
function getFileIcon($extension) {
    $icons = [
        'pdf' => 'bxs-file-pdf',
        'doc' => 'bxs-file-doc',
        'docx' => 'bxs-file-doc',
        'xls' => 'bxs-spreadsheet',
        'xlsx' => 'bxs-spreadsheet',
        'jpg' => 'bxs-image',
        'jpeg' => 'bxs-image',
        'png' => 'bxs-image',
        'gif' => 'bxs-image',
        'zip' => 'bxs-file-archive',
        'rar' => 'bxs-file-archive',
    ];
    
    return $icons[strtolower($extension)] ?? 'bx-file';
}

function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    for ($i = 0; $bytes > 1024; $i++) {
        $bytes /= 1024;
    }
    return round($bytes, $precision) . ' ' . $units[$i];
}
@endphp