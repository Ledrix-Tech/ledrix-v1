@extends('admin.layout.layout')

@section('title', 'Admin | Order Tickets')

@section('admin-content')

    <div class="crm-page-header">
        <div>
            <h1>Order tickets</h1>
            <p>Support tickets raised against orders</p>
        </div>
        <div class="crm-page-actions">
            @if (isAdmin())
                <a href="{{ route('export.csv', ['table' => 'client_tickets', 'columns' => 'id,order_type,service_name,currency,unit_amount,amount_paid,balance_due,status,buyer_name,buyer_email,provider_session_id,provider_payment_intent_id,paid_at']) }}"
                    class="btn btn-sm btn-crm-teal">
                    <i class="fa fa-file-excel-o me-1"></i> Export CSV
                </a>
            @endif
            <a href="{{ route('admin.orders.get') }}" class="btn btn-sm btn-crm-outline">
                <i class="bi bi-arrow-left me-1"></i> Orders
            </a>
        </div>
    </div>

    <div class="crm-card">
        <div class="crm-card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped" id="invoiceTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Seller</th>
                            <th>Client</th>
                            <th>Subject</th>
                            <th>Priority</th>
                            <th>Created</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tickets as $ticket)
                            <tr>
                                <td data-label="ID">#{{ $ticket->id }}</td>
                                <td data-label="Seller">
                                    {{ $ticket->seller->name }}
                                    <div class="text-muted small">{{ $ticket->seller->email ?? '—' }}</div>
                                </td>
                                <td data-label="Client">
                                    {{ $ticket->client->name }}
                                    <div class="text-muted small">{{ $ticket->client->email ?? '—' }}</div>
                                </td>
                                <td data-label="Subject">{{ $ticket->subject }}</td>
                                <td data-label="Priority">
                                    <span class="crm-status crm-status-info">{{ ucfirst($ticket->priority) }}</span>
                                </td>
                                <td data-label="Created">{{ $ticket->created_at->format('M d, Y H:i') }}</td>
                                <td data-label="Status">
                                    <form method="POST" action="{{ route('ticket.update-status') }}">
                                        @csrf
                                        <input type="hidden" name="ticket_id" value="{{ $ticket->id }}">
                                        <select name="status" class="form-select form-select-sm"
                                            onchange="this.form.submit()">
                                            <option value="open" @selected($ticket->status == 'open')>Open</option>
                                            <option value="in_progress" @selected($ticket->status == 'in_progress')>In Progress</option>
                                            <option value="on_hold" @selected($ticket->status == 'on_hold')>On Hold</option>
                                            <option value="resolved" @selected($ticket->status == 'resolved')>Resolved</option>
                                            <option value="closed" @selected($ticket->status == 'closed')>Closed</option>
                                            <option value="reopened" @selected($ticket->status == 'reopened')>Reopened</option>
                                        </select>
                                    </form>
                                </td>
                                <td data-label="Actions" class="text-end">
                                    <div class="crm-action-group">
                                        <a href="javascript:void(0);" class="crm-icon-btn info view-ticket"
                                            data-id="{{ $ticket->id }}" title="View details">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                        @if (isAdmin())
                                            <form method="POST" action="{{ route('admin.tickets.delete', $ticket->id) }}"
                                                class="d-inline"
                                                onsubmit="return confirm('Delete this ticket?')">
                                                @csrf
                                                <button type="submit" class="crm-icon-btn danger border-0"
                                                    title="Delete ticket">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8">
                                    <div class="crm-empty">
                                        <i class="bi bi-ticket-perforated d-block"></i>
                                        No order tickets yet.
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($tickets->hasPages())
                <div class="crm-pagination">{{ $tickets->links() }}</div>
            @endif
        </div>
    </div>

    <div class="modal fade" id="ticketInfo" data-backdrop="true" data-keyboard="true" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ticket details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="ticket-details-loader" class="text-center py-4" style="display:none;">
                        <i class="fa fa-spinner fa-spin fa-2x text-primary"></i>
                    </div>
                    <div id="ticket-details" style="display:none;">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item"><strong>ID:</strong> <span id="t-id"></span></li>
                            <li class="list-group-item"><strong>Subject:</strong> <span id="t-subject"></span></li>
                            <li class="list-group-item"><strong>Client:</strong> <span id="t-client"></span>
                                (<span id="t-client-email"></span>)</li>
                            <li class="list-group-item"><strong>Order:</strong> <span id="t-order"></span></li>
                            <li class="list-group-item"><strong>Status:</strong> <span id="t-status"></span></li>
                            <li class="list-group-item"><strong>Message:</strong><br><span id="t-description"></span></li>
                            <li class="list-group-item"><strong>Created:</strong> <span id="t-created"></span></li>
                            <li class="list-group-item"><strong>Updated:</strong> <span id="t-updated"></span></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        $(document).on('click', '.view-ticket', function() {
            const id = $(this).data('id');
            const modal = $('#ticketInfo');
            const detailsBox = $('#ticket-details');
            const loader = $('#ticket-details-loader');

            detailsBox.hide();
            loader.show();
            modal.modal('show');

            var url = '{{ route('admin.tickets.details', ['id' => ':id']) }}';
            $.ajax({
                url: url.replace(':id', id),
                method: 'GET',
                success: function(data) {
                    loader.hide();
                    detailsBox.show();
                    $('#t-id').text(data.id);
                    $('#t-subject').text(data.subject);
                    $('#t-client').text(data.client);
                    $('#t-client-email').text(data.client_email);
                    $('#t-order').text(data.order);
                    $('#t-status').text(data.status);
                    $('#t-description').text(data.description);
                    $('#t-created').text(data.created_at);
                    $('#t-updated').text(data.updated_at);
                },
                error: function() {
                    loader.hide();
                    detailsBox.html('<p class="text-danger">Failed to load ticket details.</p>').show();
                }
            });
        });
    </script>
@endpush
