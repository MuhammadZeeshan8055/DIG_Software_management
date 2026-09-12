@props(['invoice'])

@php
    $taxLabel = rtrim(rtrim(number_format((float) $invoice->tax_percent, 2), '0'), '.');
@endphp

<div {{ $attributes->merge(['class' => 'invoice-doc']) }}>
    <header class="invoice-doc__header">
        <div class="invoice-doc__brand">
            <img src="{{ asset('images/logo-icon.png') }}" alt="DHOTHAR" class="invoice-doc__logo">
            <div>
                <div class="invoice-doc__brand-name">Dhothar International Group</div>
                <div class="invoice-doc__brand-tag">TRAVEL &amp; TOURS (Pvt. Ltd.)</div>
            </div>
        </div>

        <div class="invoice-doc__meta">
            <div class="invoice-doc__number">Invoice {{ $invoice->invoice_number }}</div>
            <div class="invoice-doc__dates">
                {{ optional($invoice->invoice_date)->format('Y-m-d') }}
                · Due {{ optional($invoice->due_date)->format('Y-m-d') ?: '—' }}
                · {{ $invoice->statusLabel() }}
            </div>
            <div class="invoice-doc__printed">Printed: {{ format_datetime(now(), 'M j, Y, g:i A') }}</div>
        </div>
    </header>

    <hr class="invoice-doc__rule">

    <section class="invoice-doc__billed">
        <div class="invoice-doc__billed-label">Billed to</div>
        <div class="invoice-doc__customer">{{ $invoice->customer_name }}</div>
        <div class="invoice-doc__package">{{ $invoice->package_label }}</div>
    </section>

    <table class="invoice-doc__table">
        <thead>
            <tr>
                <th>Description</th>
                <th>Qty</th>
                <th>Price</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->items as $row)
                <tr>
                    <td>{{ $row->description }}</td>
                    <td>{{ $row->qty }}</td>
                    <td>Rs {{ number_format((float) $row->unit_price, 0) }}</td>
                    <td>Rs {{ number_format((float) $row->amount, 0) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

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
            <span>Total</span>
            <strong>Rs {{ number_format((float) $invoice->total_amount, 0) }}</strong>
        </div>
    </div>

    <section class="invoice-doc__payments">
        <div class="invoice-doc__payments-label">Payments received</div>
        @forelse ($invoice->payments as $payment)
            <div class="invoice-doc__payment-row">
                <span>{{ format_datetime($payment->paid_at, 'Y-m-d') }}{{ $payment->note ? ' · '.$payment->note : '' }}</span>
                <span>Rs {{ number_format((float) $payment->amount, 0) }}</span>
            </div>
        @empty
            <div class="invoice-doc__payments-empty">No payments recorded yet</div>
        @endforelse

        <div class="invoice-doc__payment-row invoice-doc__payment-row--received">
            <span>Total received</span>
            <strong>Rs {{ number_format((float) $invoice->paid_amount, 0) }}</strong>
        </div>
        <div class="invoice-doc__payment-row invoice-doc__payment-row--balance">
            <span>Balance due</span>
            <strong>Rs {{ number_format((float) $invoice->balance, 0) }}</strong>
        </div>
    </section>

    <footer class="invoice-doc__signs">
        <div class="invoice-doc__sign">
            <div class="invoice-doc__sign-line"></div>
            <div>Prepared by</div>
        </div>
        <div class="invoice-doc__sign">
            <div class="invoice-doc__sign-line"></div>
            <div>Approved by</div>
        </div>
    </footer>
</div>