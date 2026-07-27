@extends('sellers.layout.layout')

@section('title', 'Seller | Tickets')

@section('sellers-content')

    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="heading d-flex justify-content-between">
                    <div>
                        <h1 class="fw-bold" style="color: #003C51;">Order Tickets</h1>
                        @if (isAdmin())
                            <a href="{{ route('export.csv', ['table' => 'client_tickets', 'columns' => 'id,order_type,service_name,currency,unit_amount,amount_paid,balance_due,status,buyer_name,buyer_email,provider_session_id,provider_payment_intent_id,paid_at']) }}"
                                style="text-decoration: none;">
                                {{-- id="download-csv" --}}
                                <button class="btn btn-sm bg-gradient-3" type="button">
                                    <i class="fa fa-file-excel-o"></i> CSV
                                </button>
                            </a>
                        @endif
                    </div>
                    <div class="examplesearch-form mx-3">
                        <form action="" method="" class="example">
                            <div class="d-flex">
                                <input type="text" placeholder="Search.." value="" name="search"
                                    class="form-control">
                                <button type="submit" class="btn text-white bg-gradient-3"><i
                                        class="fa fa-search"></i></button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <hr>
        <div class="row my-5 fullInfo">
            <div class="col-lg-12">
                @php
                    $isAdmin = auth('admin')->check();
                    $isSeller = auth('seller')->check();
                @endphp
                <div class="table-responsive">
                    <table class="table table-striped" id="invoiceTable">
                        <thead class="text-white" style="background:#000;">
                            <tr>
                                <th>ID</th>
                                <th>Seller</th>
                                <th>Client</th>
                                <th>Subject</th>
                                <th>Priority</th>
                                <th>Created At</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody class="border">
                            @forelse ($tickets as $ticket)
                                <tr>
                                    <td>#{{ $ticket->id }}</td>
                                    <td>
                                        {{ $ticket->seller->name }}
                                        <div class="text-sm text-muted">
                                            {{ $ticket->seller->email ?? '_' }}
                                        </div>
                                    </td>
                                    <td>
                                        {{ $ticket->client->name }}
                                        <div class="text-sm text-muted">
                                            {{ $ticket->client->email ?? '_' }}
                                        </div>
                                    </td>
                                    <td>{{ $ticket->subject }}</td>
                                    <td>{{ ucfirst($ticket->priority) }}</td>
                                    <td>{{ $ticket->created_at->format('Y-m-d H:i') }}</td>
                                    <td>
                                        <form method="POST" action="{{ route('ticket.update-status') }}">
                                            @csrf
                                            <input type="hidden" name="ticket_id" value="{{ $ticket->id }}">
                                            <select name="status" class="form-control" onchange="this.form.submit()">
                                                <option value="open" {{ $ticket->status == 'open' ? 'selected' : '' }}>
                                                    Open</option>
                                                <option value="in_progress"
                                                    {{ $ticket->status == 'in_progress' ? 'selected' : '' }}>
                                                    In Progress</option>
                                                <option value="on_hold"
                                                    {{ $ticket->status == 'on_hold' ? 'selected' : '' }}>
                                                    On Hold</option>
                                                <option value="resolved"
                                                    {{ $ticket->status == 'resolved' ? 'selected' : '' }}>
                                                    Resolved</option>
                                                <option value="closed" {{ $ticket->status == 'closed' ? 'selected' : '' }}>
                                                    Closed</option>
                                                <option value="reopened"
                                                    {{ $ticket->status == 'reopened' ? 'selected' : '' }}>
                                                    Reopened</option>
                                            </select>
                                        </form>
                                    </td>
                                    <td>
                                        <a href="javascript:void(0);" class="badge badge-sm badge-info view-ticket"
                                            data-id="{{ $ticket->id }}">
                                            View
                                        </a>
                                        @if (isAdmin())
                                            <form method="POST" action="{{ route('seller.tickets.delete', $ticket->id) }}"
                                                class="d-inline"
                                                onsubmit="return confirm('Delete this ticket?')">
                                                @csrf
                                                <button type="submit" class="badge badge-primary">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="12">
                                        <div class="text-center alert alert-info m-0">
                                            <h6>You don't have any order tickets yet !!!</h6>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="paginate d-flex justify-content-center align-item-center bg-light p-2"
                    style="border-radius:10px;">
                    <div class="text-dark pt-3">
                        {{ $tickets->links() }}
                        <div hidden>
                            @if ($tickets->lastPage() > 1)
                                <ul class="pagination justify-content-center">
                                    <li class="page-item {{ $tickets->currentPage() == 1 ? ' disabled' : '' }}">
                                        <a class="page-link border_none_pagination"
                                            href="{{ $tickets->url($tickets->currentPage() - 1) }}">Previous</a>
                                    </li>
                                    @for ($i = $tickets->currentPage(); $i <= $tickets->currentPage() + 8; $i++)
                                        <li class="page-item">
                                            <a class="page-link {{ $tickets->currentPage() == $i ? ' border_active' : 'border_non_active' }} border_none2"
                                                href="{{ $tickets->url($i) }}">{{ $i }}</a>
                                        </li>
                                    @endfor
                                    <li
                                        class="page-item {{ $tickets->currentPage() == $tickets->lastPage() ? ' disabled' : '' }}">
                                        <a class="page-link border_none_pagination"
                                            href="{{ $tickets->url($tickets->currentPage() + 1) }}">Next</a>
                                    </li>
                                </ul>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="ticketInfo" data-backdrop="true" data-keyboard="true" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Raised Ticket Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="ticket-details-loader" class="text-center py-4" style="display:none;">
                        <i class="fa fa-spinner fa-spin fa-2x text-primary"></i>
                    </div>

                    <div id="ticket-details" style="display:none;">
                        <ul class="list-group">
                            <li class="list-group-item"><strong>ID:</strong> <span id="t-id"></span></li>
                            <li class="list-group-item"><strong>Subject:</strong> <span id="t-subject"></span></li>
                            <li class="list-group-item"><strong>Client:</strong> <span id="t-client"></span> (<span
                                    id="t-client-email"></span>)</li>
                            <li class="list-group-item"><strong>Order:</strong> <span id="t-order"></span></li>
                            <li class="list-group-item"><strong>Status:</strong> <span id="t-status"></span></li>
                            <li class="list-group-item"><strong>Message:</strong><br><span id="t-description"></span></li>
                            <li class="list-group-item"><strong>Created At:</strong> <span id="t-created"></span></li>
                            <li class="list-group-item"><strong>Updated At:</strong> <span id="t-updated"></span></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).on('click', '.view-ticket', function() {
            const id = $(this).data('id');
            const modal = $('#ticketInfo');
            const detailsBox = $('#ticket-details');
            const loader = $('#ticket-details-loader');

            detailsBox.hide();
            loader.show();
            modal.modal('show');

            var url = '{{ route('seller.tickets.details', ['id' => ':id']) }}';
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



@endsection
