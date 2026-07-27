@extends('clients.layouts.layout')

@section('title', 'Tickets | Client Portal')

@section('client-content')
    <div class="crm-page-header">
        <div>
            <h1>Support tickets</h1>
            <p>Track tickets you have raised for your orders.</p>
        </div>
    </div>

    <div class="crm-card">
        <div class="crm-card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped crm-table mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Brand</th>
                            <th>Seller</th>
                            <th>Service</th>
                            <th>Status</th>
                            <th>Subject</th>
                            <th>Priority</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tickets as $ticket)
                            <tr>
                                <td data-label="ID">#{{ str_pad($ticket->id, 6, '0', STR_PAD_LEFT) }}</td>
                                <td data-label="Brand">{{ $ticket->brand->brand_name ?? '—' }}</td>
                                <td data-label="Seller">
                                    <div>{{ $ticket->seller->name ?? '—' }}</div>
                                    <div class="text-muted small">{{ $ticket->seller->email ?? '' }}</div>
                                </td>
                                <td data-label="Service">{{ $ticket->order->service_name ?? '—' }}</td>
                                <td data-label="Status">@include('clients.includes.status-badge', ['status' => $ticket->status ?? 'open'])</td>
                                <td data-label="Subject">{{ $ticket->subject }}</td>
                                <td data-label="Priority">
                                    <span class="crm-status crm-status-neutral">{{ ucfirst($ticket->priority) }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">No tickets raised yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($tickets->hasPages())
            <div class="crm-card-body border-top d-flex justify-content-center">
                {{ $tickets->links() }}
            </div>
        @endif
    </div>
@endsection
