@props(['invoice'])

@php
    $taxLabel = rtrim(rtrim(number_format((float) $invoice->tax_percent, 2), '0'), '.');
    $currency = config('payment_status.currency', 'PKR');
@endphp

<div {{ $attributes->merge(['class' => 'ticket-doc invoice-doc']) }}>
    <header class="ticket-doc__header">
        <div class="ticket-doc__brand-card">
            <img src="{{ asset('images/logo-icon.png') }}" alt="DHOTHAR" class="ticket-doc__logo">
            <div class="ticket-doc__brand-text">
                <strong class="ticket-doc__brand-name">DHOTHAR</strong>
                <span class="ticket-doc__brand-group">International Group</span>
                <small class="ticket-doc__brand-tagline">Travel &amp; Tours (Pvt Ltd)</small>
            </div>
        </div>

        <div class="ticket-doc__title-block invoice-doc__title-block">
            <p class="ticket-doc__eyebrow">Official Accounts Document</p>
            <h1 class="ticket-doc__title">Invoice</h1>
            <p class="ticket-doc__subtitle">Accounts &amp; Billing</p>

            <div class="invoice-doc__header-meta">
                <div class="invoice-doc__header-meta-row">
                    <span>Invoice Number</span>
                    <strong>{{ $invoice->invoice_number }}</strong>
                </div>
                <div class="invoice-doc__header-meta-row">
                    <span>Invoice Date</span>
                    <strong>{{ optional($invoice->invoice_date)->format('d M Y') ?: '—' }}</strong>
                </div>
                <div class="invoice-doc__header-meta-row">
                    <span>Due Date</span>
                    <strong>{{ optional($invoice->due_date)->format('d M Y') ?: '—' }}</strong>
                </div>
            </div>

            <div class="ticket-doc__badges">
                <span class="{{ $invoice->paymentBadgeClass() }}">{{ $invoice->paymentStatusLabel() }}</span>
            </div>
        </div>
    </header>

    <section class="invoice-doc__billed">
        <h3 class="invoice-doc__billed-title">Billed to :</h3>
        <div class="invoice-doc__billed-list">
            <p class="invoice-doc__billed-line invoice-doc__billed-line--primary">
                {{ $invoice->customer_name ?: '—' }}
            </p>
            <p class="invoice-doc__billed-line invoice-doc__billed-line--primary">
                {{ $invoice->package_label ?: $invoice->categoryLabel() }}
            </p>
            <p class="invoice-doc__billed-line invoice-doc__billed-line--muted">
                {{ $invoice->customer_phone ?: '—' }}
            </p>
            @if ($invoice->customer_email)
                <p class="invoice-doc__billed-line invoice-doc__billed-line--muted">
                    {{ $invoice->customer_email }}
                </p>
            @endif
            @if ($invoice->reference_number)
                <p class="invoice-doc__billed-line invoice-doc__billed-line--muted">
                    {{ $invoice->reference_number }}
                </p>
            @endif
            @if ($invoice->cnic_passport)
                <p class="invoice-doc__billed-line invoice-doc__billed-line--muted">
                    {{ $invoice->cnic_passport }}
                </p>
            @endif
            @if ($invoice->customer_address)
                <p class="invoice-doc__billed-line invoice-doc__billed-line--muted">
                    {{ $invoice->customer_address }}
                </p>
            @endif
        </div>
    </section>

    <section class="ticket-doc__section invoice-doc__lines">
        <div class="invoice-doc__table-wrap">
            <table class="invoice-doc__table">
                <thead>
                    <tr>
                        <th class="invoice-doc__col-desc">Description</th>
                        <th class="invoice-doc__col-qty">Qty</th>
                        <th class="invoice-doc__col-price">Price</th>
                        <th class="invoice-doc__col-amount">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($invoice->items as $row)
                        <tr>
                            <td class="invoice-doc__col-desc">
                                <span class="invoice-doc__item-name">{{ $row->description }}</span>
                            </td>
                            <td class="invoice-doc__col-qty">
                                <span class="invoice-doc__qty">{{ $row->qty }}</span>
                            </td>
                            <td class="invoice-doc__col-price">Rs {{ number_format((float) $row->unit_price, 0) }}</td>
                            <td class="invoice-doc__col-amount">Rs {{ number_format((float) $row->amount, 0) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="invoice-doc__totals">
            <div class="invoice-doc__total-row">
                <span>Subtotal</span>
                <strong>Rs {{ number_format((float) $invoice->subtotal, 0) }}</strong>
            </div>
            <div class="invoice-doc__total-row">
                <span>Tax ({{ $taxLabel }}%)</span>
                <strong>Rs {{ number_format((float) $invoice->tax_amount, 0) }}</strong>
            </div>
            <div class="invoice-doc__total-row invoice-doc__total-row--grand">
                <span>Total ({{ $currency }})</span>
                <strong>Rs {{ number_format((float) $invoice->total_amount, 0) }}</strong>
            </div>
        </div>
    </section>

    <section class="ticket-doc__section invoice-doc__payments">
        <div class="ticket-doc__section-head">Payments Received</div>
        @forelse ($invoice->payments as $payment)
            <div class="invoice-doc__payment-row">
                <span>{{ format_datetime($payment->paid_at, 'd M Y') }}{{ $payment->note ? ' · '.$payment->note : '' }}</span>
                <span>Rs {{ number_format((float) $payment->amount, 0) }}</span>
            </div>
        @empty
            <p class="ticket-doc__empty">No payments recorded yet.</p>
        @endforelse

        <div class="invoice-doc__payment-summary">
            <div class="invoice-doc__payment-summary-row invoice-doc__payment-summary-row--received">
                <span>Total received</span>
                <strong>Rs {{ number_format((float) $invoice->paid_amount, 0) }}</strong>
            </div>
            <div class="invoice-doc__payment-summary-row">
                <span>Balance due</span>
                <strong>Rs {{ number_format((float) $invoice->balance, 0) }}</strong>
            </div>
        </div>
    </section>
</div>
