<div class="crm-order-actions">

    @if ($tenantHasSupportTickets ?? false)

        <a href="{{ route('seller.order-tickets.get', $order) }}" class="btn btn-sm btn-crm-outline" title="Tickets">

            <i class="fa fa-ticket"></i> Tickets

        </a>

    @endif

    @if ($tenantHasDualInvoicing ?? false)

        <a href="{{ route('seller.order.generate-invoice', $order) }}" class="btn btn-sm btn-crm-outline" title="Invoice">

            <i class="fa fa-file-text-o"></i> Invoice

        </a>

    @endif

    @if ($showRenewals ?? true)

        @if ($order->order_type === 'renewal')

            <span class="crm-status crm-status-danger">

                <i class="bi bi-arrow-repeat"></i> Renewed

            </span>

        @else

            <a href="{{ route('seller.renewed-orders.get', $order->id) }}"

                class="btn btn-sm btn-crm-teal" title="Renewals">

                <i class="bi bi-arrow-repeat"></i> Renewals

            </a>

        @endif

    @endif

</div>

