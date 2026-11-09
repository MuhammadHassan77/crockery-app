@extends('layouts.app')

@section('title', 'Database Backups')
@section('page-title', 'Database Backups')
@section('breadcrumb')
    <li class="breadcrumb-item active">Database Backups</li>
@endsection

@section('content')
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h5 class="card-title mb-1">Backup Management</h5>
                    <p class="text-muted mb-0">Create, download, restore, or remove SQL backups.</p>
                </div>
                <form action="{{ route('database-backups.store') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-download me-1"></i> Create Backup
                    </button>
                </form>
            </div>

            <div class="row g-4">
                <div class="col-lg-7">
                    <div class="card border-0 bg-light">
                        <div class="card-body">
                            <h6 class="card-title">Available Backups</h6>
                            @if (!empty($backups))
                                <div class="list-group">
                                    @foreach ($backups as $file)
                                        <div class="list-group-item d-flex justify-content-between align-items-center">
                                            <div>
                                                <div class="fw-semibold">{{ $file->getFileName() }}</div>
                                                <small
                                                    class="text-muted">{{ date('Y-m-d H:i:s', $file->getMTime()) }}</small>
                                            </div>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="{{ route('database-backups.download', ['filename' => $file->getFileName()]) }}"
                                                    class="btn btn-outline-primary">
                                                    <i class="bi bi-download"></i>
                                                </a>
                                                <button type="button" class="btn btn-outline-success"
                                                    data-bs-toggle="modal" data-bs-target="#restoreModal"
                                                    data-filename="{{ $file->getFileName() }}">
                                                    <i class="bi bi-arrow-clockwise"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal"
                                                    data-bs-target="#deleteModal"
                                                    data-filename="{{ $file->getFileName() }}">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-muted mb-0">No backups found.</p>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="card border-0 bg-light">
                        <div class="card-body">
                            <h6 class="card-title">Restore from File</h6>
                            <form action="{{ route('database-backups.restore') }}" method="POST"
                                enctype="multipart/form-data">
                                @csrf
                                <div class="mb-3">
                                    <label for="backupFile" class="form-label">Select SQL Backup</label>
                                    <input type="file" class="form-control" id="backupFile" name="backupFile" required
                                        accept=".sql,.txt">
                                </div>
                                <button type="submit" class="btn btn-success w-100"
                                    onclick="return confirm('This will overwrite the current database. Continue?');">
                                    <i class="bi bi-arrow-clockwise me-1"></i> Restore Backup
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="restoreModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Restore Backup</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Restoring this backup will overwrite the current database. Continue?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form id="restoreForm" method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="fromStorage" value="1">
                        <input type="hidden" name="backupFile" id="restoreBackupFile">
                        <button type="submit" class="btn btn-success">Restore</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Backup</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this backup permanently?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form id="deleteForm" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const restoreModal = document.getElementById('restoreModal');
                const deleteModal = document.getElementById('deleteModal');

                restoreModal?.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    const filename = button?.getAttribute('data-filename');
                    const form = document.getElementById('restoreForm');
                    form.action = '{{ route('database-backups.restore') }}';
                    document.getElementById('restoreBackupFile').value = filename || '';
                });

                deleteModal?.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    const filename = button?.getAttribute('data-filename');
                    const form = document.getElementById('deleteForm');
                    form.action = '{{ url('admin/database-backups') }}/' + encodeURIComponent(filename || '');
                });
            });
        </script>
    @endpush
@endsection
