@php
    $dismissRouteName = $dismissRoute ?? 'tenant.announcements.dismiss';
@endphp
@foreach ($announcements ?? [] as $announcement)
    <div class="alert alert-{{ $announcement->type === 'danger' ? 'danger' : ($announcement->type === 'warning' ? 'warning' : ($announcement->type === 'success' ? 'success' : 'info')) }} d-flex justify-content-between align-items-start gap-3">
        <div>
            <strong>{{ $announcement->title }}</strong>
            <div class="mt-1" style="white-space: pre-wrap;">{{ $announcement->message }}</div>
        </div>
        @if ($announcement->is_dismissible)
            <form method="POST" action="{{ route($dismissRouteName, $announcement->id) }}" class="flex-shrink-0">
                @csrf
                <button type="submit" class="btn-close" aria-label="Dismiss"></button>
            </form>
        @endif
    </div>
@endforeach
