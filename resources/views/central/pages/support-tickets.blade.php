@extends('central.layout.layout')

@section('title', 'Ledrix | Support Tickets')

@section('central-content')
    <div class="sa-page-header">
        <div>
            <h1>Platform Support Tickets</h1>
            <p>Tenant-to-platform support inbox (billing, account, technical).</p>
        </div>
    </div>

    <div class="sa-card mb-4">
        <div class="sa-card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All</option>
                        <option value="open" @selected(request('status') === 'open')>Open / In progress</option>
                        @foreach (['on_hold', 'resolved', 'closed'] as $st)
                            <option value="{{ $st }}" @selected(request('status') === $st)>{{ ucfirst(str_replace('_', ' ', $st)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <div class="form-check mt-4">
                        <input class="form-check-input" type="checkbox" name="unassigned" value="1" id="unassigned"
                            @checked(request()->boolean('unassigned'))>
                        <label class="form-check-label" for="unassigned">Unassigned only</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="urgent" value="1" id="urgent"
                            @checked(request()->boolean('urgent'))>
                        <label class="form-check-label" for="urgent">Urgent only</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-sa-primary">Filter</button>
                    <a href="{{ route('super-admin.support-tickets.get') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="sa-card">
        <div class="sa-card-body p-0">
            <div class="sa-table-wrap">
                <table class="table sa-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Subject</th>
                            <th>Tenant</th>
                            <th>Category</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Assigned</th>
                            <th>Updated</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tickets as $ticket)
                            @php
                                $prioClass = match ($ticket->priority) {
                                    'urgent' => 'danger',
                                    'high' => 'warning text-dark',
                                    'medium' => 'info',
                                    default => 'secondary',
                                };
                            @endphp
                            <tr>
                                <td data-label="#">{{ $ticket->id }}</td>
                                <td data-label="Subject"><strong>{{ $ticket->subject }}</strong></td>
                                <td data-label="Tenant">
                                    @if ($ticket->tenant)
                                        <a href="{{ route('super-admin.tenant.show', $ticket->tenant->id) }}">{{ $ticket->tenant->name }}</a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td data-label="Category">{{ ucfirst(str_replace('_', ' ', $ticket->category)) }}</td>
                                <td data-label="Priority"><span class="badge bg-{{ $prioClass }}">{{ $ticket->priority }}</span></td>
                                <td data-label="Status">{{ ucfirst(str_replace('_', ' ', $ticket->status)) }}</td>
                                <td data-label="Assigned">{{ $ticket->assignedTo?->name ?? '—' }}</td>
                                <td data-label="Updated">{{ $ticket->updated_at?->format('M d, H:i') }}</td>
                                <td data-label="Action">
                                    <a href="{{ route('super-admin.support-tickets.show', $ticket->id) }}"
                                        class="btn btn-sm btn-outline-primary">Open</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">No support tickets.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($tickets->hasPages())
                <div class="sa-pagination">{{ $tickets->links() }}</div>
            @endif
        </div>
    </div>
@endsection
