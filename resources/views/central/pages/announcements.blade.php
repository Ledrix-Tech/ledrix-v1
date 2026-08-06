@extends('central.layout.layout')

@section('title', 'Ledrix | Announcements')

@section('central-content')
    @php $canManage = auth('super_admin')->user()?->isAdmin() ?? false; @endphp
    <div class="sa-page-header">
        <div>
            <h1>System Announcements</h1>
            <p>Banners shown on the tenant workspace dashboard. Target all tenants or a specific plan.</p>
        </div>
        @if ($canManage)
            <button type="button" class="btn btn-sa-primary" data-bs-toggle="modal" data-bs-target="#addAnnouncement">
                <i class="bi bi-plus-lg me-1"></i> New announcement
            </button>
        @endif
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="sa-card">
        <div class="sa-card-body p-0">
            <div class="sa-table-wrap">
                <table class="table sa-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Target</th>
                            <th>Window</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($announcements as $announcement)
                            <tr>
                                <td data-label="#">{{ $announcement->id }}</td>
                                <td data-label="Title">
                                    <strong>{{ $announcement->title }}</strong><br>
                                    <small class="text-muted">{{ \Illuminate\Support\Str::limit(strip_tags($announcement->message), 80) }}</small>
                                </td>
                                <td data-label="Type"><span class="badge bg-{{ $announcement->type === 'danger' ? 'danger' : ($announcement->type === 'warning' ? 'warning text-dark' : ($announcement->type === 'success' ? 'success' : 'info')) }}">{{ $announcement->type }}</span></td>
                                <td data-label="Target"><code>{{ $announcement->target }}</code></td>
                                <td data-label="Window">
                                    {{ $announcement->show_from?->format('M d') ?? '—' }}
                                    →
                                    {{ $announcement->show_until?->format('M d, Y') ?? '—' }}
                                    @if ($announcement->is_dismissible)
                                        <br><small class="text-muted">Dismissible</small>
                                    @endif
                                </td>
                                <td data-label="Status">
                                    <span class="badge bg-{{ $announcement->status === 'active' ? 'success' : 'secondary' }}">{{ $announcement->status }}</span>
                                </td>
                                <td data-label="Action">
                                    @if ($canManage)
                                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                            data-bs-target="#editAnnouncement{{ $announcement->id }}">Edit</button>
                                        <form method="POST" action="{{ route('super-admin.announcements.destroy', $announcement->id) }}"
                                            class="d-inline" onsubmit="return confirm('Delete this announcement?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                        </form>
                                    @else
                                        <span class="text-muted">View only</span>
                                    @endif
                                </td>
                            </tr>

                            @if ($canManage)
                                <div class="modal fade" id="editAnnouncement{{ $announcement->id }}" tabindex="-1">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <form method="POST" action="{{ route('super-admin.announcements.update', $announcement->id) }}">
                                                @csrf
                                                @method('PUT')
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Edit announcement</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    @include('central.pages.partials.announcement-form', [
                                                        'announcement' => $announcement,
                                                        'plans' => $plans,
                                                    ])
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-sa-primary">Save</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">No announcements yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($announcements->hasPages())
                <div class="sa-pagination">{{ $announcements->links() }}</div>
            @endif
        </div>
    </div>

    @if ($canManage)
        <div class="modal fade" id="addAnnouncement" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form method="POST" action="{{ route('super-admin.announcements.store') }}">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">New announcement</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            @include('central.pages.partials.announcement-form', [
                                'announcement' => null,
                                'plans' => $plans,
                            ])
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-sa-primary">Create</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endsection
