@php
    $reference = $payment->transaction_id;
    $amount = (float) $payment->amount;
    $dueAt = $invoice?->due_at;
    $reportedTxnId = $payment->customerReportedTxnId();
    $showReportForm = $showReportForm ?? true;
@endphp

@if (! empty($qrDataUri))
    <div class="billing-qr-wrap text-center mb-4">
        <img src="{{ $qrDataUri }}" alt="Raast payment QR code" class="billing-qr-image" width="240" height="240">
        <p class="small fw-semibold text-primary mb-1 mt-2">Raast QR</p>
        <p class="small text-muted mb-2">
            In your bank app open <strong>Raast</strong> or <strong>Scan QR</strong> (not the phone camera).
            @if (($raastQrMode ?? 'dynamic') === 'static')
                Enter amount <strong>PKR {{ number_format($amount, 0) }}</strong> and reference <strong>{{ $reference }}</strong> after scanning.
            @else
                Amount should pre-fill as <strong>PKR {{ number_format($amount, 0) }}</strong>.
            @endif
        </p>
        <p class="small text-muted mb-0">
            QR not working? Use <strong>Raast → Send money → IBAN</strong> below and paste the details.
        </p>
    </div>
@elseif (! empty($qrError))
    <div class="alert alert-warning small py-2">
        {{ $qrError }} Use the account details below and include reference <strong>{{ $reference }}</strong>.
    </div>
@endif

@if ($reportedTxnId)
    <div class="alert alert-success py-2 small mb-3">
        <i class="bi bi-check-circle me-1"></i>
        Transaction ID submitted: <code>{{ $reportedTxnId }}</code>.
        We will verify on our bank statement and activate your subscription.
    </div>
@else
    <div class="billing-pending mb-0">
        <div class="d-flex align-items-start gap-2 mb-2">
            <i class="bi bi-qr-code text-primary fs-5"></i>
            <div>
                <strong>Pay via Raast (any bank)</strong>
                <p class="small text-muted mb-0">
                    Option A: Scan the Raast QR above. Option B: Raast → IBAN transfer using the details below (works in every app).
                </p>
            </div>
        </div>
    </div>
@endif

<div class="billing-bank-details mt-3">
    <div class="billing-stat">
        <span class="billing-stat__label">Amount</span>
        <span class="billing-stat__value">
            <code id="billing-amt-{{ $payment->id }}">PKR {{ number_format($amount, 0) }}</code>
            <button type="button" class="btn btn-sm btn-outline-secondary ms-1 billing-copy-btn"
                data-copy-target="billing-amt-{{ $payment->id }}" title="Copy amount">
                <i class="bi bi-clipboard"></i>
            </button>
        </span>
    </div>
    <div class="billing-stat">
        <span class="billing-stat__label">Ledrix payment ID <small class="text-muted">(required in remarks)</small></span>
        <span class="billing-stat__value">
            <code id="billing-ref-{{ $payment->id }}">{{ $reference }}</code>
            <button type="button" class="btn btn-sm btn-outline-secondary ms-1 billing-copy-btn"
                data-copy-target="billing-ref-{{ $payment->id }}" title="Copy reference">
                <i class="bi bi-clipboard"></i>
            </button>
        </span>
    </div>
    @if ($invoice?->invoice_number)
        <div class="billing-stat">
            <span class="billing-stat__label">Invoice</span>
            <span class="billing-stat__value">{{ $invoice->invoice_number }}</span>
        </div>
    @endif
    @if (! empty($bank['iban']))
        <div class="billing-stat">
            <span class="billing-stat__label">IBAN (Raast)</span>
            <span class="billing-stat__value">
                <code id="billing-iban-{{ $payment->id }}">{{ $bank['iban'] }}</code>
                <button type="button" class="btn btn-sm btn-outline-secondary ms-1 billing-copy-btn"
                    data-copy-target="billing-iban-{{ $payment->id }}" title="Copy IBAN">
                    <i class="bi bi-clipboard"></i>
                </button>
            </span>
        </div>
    @endif
    <div class="billing-stat">
        <span class="billing-stat__label">Receiving bank</span>
        <span class="billing-stat__value">{{ $bank['bank_name'] ?? '—' }}</span>
    </div>
    <div class="billing-stat">
        <span class="billing-stat__label">Account title</span>
        <span class="billing-stat__value">{{ $bank['account_title'] ?? '—' }}</span>
    </div>
    <div class="billing-stat">
        <span class="billing-stat__label">Account number</span>
        <span class="billing-stat__value"><code>{{ $bank['account_number'] ?? '—' }}</code></span>
    </div>
    @if (! empty($bank['branch']))
        <div class="billing-stat">
            <span class="billing-stat__label">Branch</span>
            <span class="billing-stat__value">{{ $bank['branch'] }}</span>
        </div>
    @endif
    @if ($dueAt)
        <div class="billing-stat">
            <span class="billing-stat__label">Pay before</span>
            <span class="billing-stat__value">{{ $dueAt->format('M d, Y') }}</span>
        </div>
    @endif
</div>

@if ($showReportForm && ! $reportedTxnId)
    <div class="billing-report-form mt-4 pt-3 border-top">
        <h6 class="fw-bold mb-2"><i class="bi bi-receipt me-1"></i> Step 2 — Submit bank transaction ID</h6>
        <p class="small text-muted mb-3">Copy the transaction or receipt ID from your bank app / SMS after payment.</p>
        <form method="POST" action="{{ org_route('billing.bank-transfer.report', $payment) }}">
            @csrf
            <div class="mb-3">
                <label class="form-label small fw-semibold" for="bank_txn_id_{{ $payment->id }}">Bank transaction ID *</label>
                <input type="text" id="bank_txn_id_{{ $payment->id }}" name="bank_txn_id"
                    class="form-control @error('bank_txn_id') is-invalid @enderror"
                    value="{{ old('bank_txn_id') }}"
                    placeholder="e.g. TXN123456789" required maxlength="64">
                @error('bank_txn_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label small" for="note_{{ $payment->id }}">Note (optional)</label>
                <input type="text" id="note_{{ $payment->id }}" name="note" class="form-control"
                    value="{{ old('note') }}" maxlength="500" placeholder="Any extra detail for verification">
            </div>
            <button type="submit" class="btn btn-billing w-100">
                <i class="bi bi-send me-1"></i> I've paid — submit transaction ID
            </button>
        </form>
    </div>
@endif

@once
    @push('scripts')
        <script>
            document.querySelectorAll('.billing-copy-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var el = document.getElementById(btn.dataset.copyTarget);
                    if (! el) return;
                    navigator.clipboard.writeText(el.textContent.trim()).then(function () {
                        btn.innerHTML = '<i class="bi bi-check2"></i>';
                        setTimeout(function () { btn.innerHTML = '<i class="bi bi-clipboard"></i>'; }, 2000);
                    });
                });
            });
        </script>
    @endpush
@endonce
