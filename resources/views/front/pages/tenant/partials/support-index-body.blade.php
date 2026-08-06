@php $isAdminOrg = ($organizationPortal ?? 'tenant') === 'admin'; @endphp
    <main class="{{ $isAdminOrg ? 'pb-4' : 'py-5' }}">
        <div class="{{ $isAdminOrg ? '' : 'container' }}">
            @if ($isAdminOrg)
                <div class="crm-page-header mb-4">
                    <div>
                        <h1>Platform support</h1>
                        <p>Tickets for Ledrix billing, access, and account help.</p>
                    </div>
                    <div class="crm-page-actions">
                        <a href="{{ org_route('support.create') }}" class="btn btn-crm-primary btn-sm">New ticket</a>
                    </div>
                </div>
            @else
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <div>
                    <a href="{{ org_route('dashboard') }}" class="text-muted small text-decoration-none">&larr; Dashboard</a>
                    <h4 class="mb-0 mt-1">Support tickets</h4>
                </div>
                <a href="{{ org_route('support.create') }}" class="btn btn-primary btn-sm">New ticket</a>
            </div>
            @endif

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <div class="card shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Subject</th>
                                    <th>Category</th>
                                    <th>Priority</th>
                                    <th>Status</th>
                                    <th>Updated</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($tickets as $ticket)
                                    <tr>
                                        <td>{{ $ticket->id }}</td>
                                        <td>{{ $ticket->subject }}</td>
                                        <td>{{ ucfirst(str_replace('_', ' ', $ticket->category)) }}</td>
                                        <td>{{ ucfirst($ticket->priority) }}</td>
                                        <td>{{ ucfirst(str_replace('_', ' ', $ticket->status)) }}</td>
                                        <td>{{ $ticket->updated_at?->format('M d, Y') }}</td>
                                        <td><a href="{{ org_route('support.show', $ticket->id) }}" class="btn btn-sm btn-outline-primary">View</a></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">No tickets yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($tickets->hasPages())
                    <div class="card-footer">{{ $tickets->links() }}</div>
                @endif
            </div>
        </div>
    </main>
