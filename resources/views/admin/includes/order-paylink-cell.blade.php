@if ($order->latestPaymentLink)
    <div class="crm-paylink-actions">
        @if ($order->latestPaymentLink->is_active_link)
            <a href="javascript:void(0);" class="crm-status crm-status-success crm-paylink-toggle togglePaylink"
                data-id="{{ $order->latestPaymentLink->id }}" data-status="false" title="Click to deactivate">
                <i class="bi bi-check-circle"></i> Active
            </a>
            @if ($order->latestPaymentLink->last_issued_url)
                <button type="button" class="btn btn-sm btn-crm-outline copyBtn"
                    data-url="{{ $order->latestPaymentLink->last_issued_url }}">
                    <i class="bi bi-clipboard"></i> Copy
                </button>
            @endif
        @else
            <a href="javascript:void(0);" class="crm-status crm-status-danger crm-paylink-toggle togglePaylink"
                data-id="{{ $order->latestPaymentLink->id }}" data-status="true" title="Click to activate">
                <i class="bi bi-x-circle"></i> Inactive
            </a>
        @endif
    </div>
@else
    <span class="crm-status crm-status-neutral">No link</span>
@endif
