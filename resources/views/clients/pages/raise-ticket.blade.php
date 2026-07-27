@extends('clients.layouts.layout')

@section('title', 'Raise Ticket | Client Portal')

@section('client-content')
    <div class="crm-page-header">
        <div>
            <a href="{{ route('client.raised-tickets.get') }}" class="crm-back"><i class="bi bi-arrow-left"></i> Back to tickets</a>
            <h1>Raise a ticket</h1>
            <p>{{ $order->service_name ?? '—' }} · Invoice #{{ str_pad($order->id, 6, '0', STR_PAD_LEFT) }}</p>
        </div>
    </div>

    <div class="crm-card">
        <div class="crm-card-body">
            <form action="{{ route('client.raised-tickets.post') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="order_id" value="{{ $order->id }}">

                <div class="mb-3">
                    <label for="subject" class="form-label">Subject</label>
                    <input type="text" class="form-control" id="subject" name="subject" required
                        placeholder="Brief summary of your issue">
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Message</label>
                    <textarea class="form-control" id="description" name="description" rows="6" required
                        placeholder="Describe your issue in detail…"></textarea>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label for="priority" class="form-label">Priority</label>
                        <select class="form-select" id="priority" name="priority">
                            <option value="medium">Medium</option>
                            <option value="low">Low</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="attachments" class="form-label">Attachments (optional)</label>
                        <input type="file" class="form-control" name="attachments[]" id="attachments" multiple
                            accept=".jpg,.jpeg,.png,.pdf,.docx,.xlsx">
                    </div>
                </div>

                <button type="submit" class="btn btn-crm-primary">
                    <i class="bi bi-send me-1"></i> Submit ticket
                </button>
            </form>
        </div>
    </div>
@endsection
