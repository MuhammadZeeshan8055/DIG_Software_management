@props(['slip'])

@php
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
            <p class="ticket-doc__eyebrow">Official HR Document</p>
            <h1 class="ticket-doc__title">Salary Slip</h1>
            <p class="ticket-doc__subtitle">Attendance &amp; Payroll</p>

            <div class="invoice-doc__header-meta">
                <div class="invoice-doc__header-meta-row">
                    <span>Month</span>
                    <strong>{{ $slip->monthLabel() }}</strong>
                </div>
                <div class="invoice-doc__header-meta-row">
                    <span>Generated</span>
                    <strong>{{ optional($slip->updated_at)->format('d M Y') ?: '—' }}</strong>
                </div>
            </div>
        </div>
    </header>

    <section class="invoice-doc__billed">
        <h3 class="invoice-doc__billed-title">Employee :</h3>
        <div class="invoice-doc__billed-list">
            <p class="invoice-doc__billed-line invoice-doc__billed-line--primary">
                {{ $slip->user?->name ?: '—' }}
            </p>
            @if ($slip->user?->email)
                <p class="invoice-doc__billed-line invoice-doc__billed-line--muted">
                    {{ $slip->user->email }}
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
                        <th class="invoice-doc__col-qty">Days</th>
                        <!-- <th class="invoice-doc__col-price">Rate</th> -->
                        <th class="invoice-doc__col-amount">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="invoice-doc__col-desc">
                            <span class="invoice-doc__item-name">Monthly salary (gross)</span>
                        </td>
                        <td class="invoice-doc__col-qty">
                            <span class="invoice-doc__qty">{{ $slip->working_days }}</span>
                        </td>
                        <!-- <td class="invoice-doc__col-price">Rs {{ number_format((float) $slip->daily_rate, 2) }}</td> -->
                        <td class="invoice-doc__col-amount">Rs {{ number_format((float) $slip->monthly_salary, 0) }}</td>
                    </tr>
                    @if ((float) $slip->absent_days > 0)
                        <tr>
                            <td class="invoice-doc__col-desc">
                                <span class="invoice-doc__item-name">Absent (deduction)</span>
                            </td>
                            <td class="invoice-doc__col-qty">
                                <span class="invoice-doc__qty">{{ $slip->absent_days }}</span>
                            </td>
                            <!-- <td class="invoice-doc__col-price">Rs {{ number_format((float) $slip->daily_rate, 2) }}</td> -->
                            <td class="invoice-doc__col-amount">
                                − Rs {{ number_format((float) $slip->absent_days * (float) $slip->daily_rate, 0) }}
                            </td>
                        </tr>
                    @endif
                    @if ((float) $slip->unpaid_leave_days > 0)
                        <tr>
                            <td class="invoice-doc__col-desc">
                                <span class="invoice-doc__item-name">Unpaid leave (deduction)</span>
                            </td>
                            <td class="invoice-doc__col-qty">
                                <span class="invoice-doc__qty">{{ $slip->unpaid_leave_days }}</span>
                            </td>
                            <!-- <td class="invoice-doc__col-price">Rs {{ number_format((float) $slip->daily_rate, 2) }}</td> -->
                            <td class="invoice-doc__col-amount">
                                − Rs {{ number_format((float) $slip->unpaid_leave_days * (float) $slip->daily_rate, 0) }}
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        <div class="invoice-doc__totals">
            <div class="invoice-doc__total-row">
                <span>Gross salary</span>
                <strong>Rs {{ number_format((float) $slip->monthly_salary, 0) }}</strong>
            </div>
            <div class="invoice-doc__total-row">
                <span>Total deduction ({{ $slip->deduct_days }} day{{ (float) $slip->deduct_days == 1 ? '' : 's' }})</span>
                <strong>Rs {{ number_format((float) $slip->deduction, 0) }}</strong>
            </div>
            <div class="invoice-doc__total-row invoice-doc__total-row--grand">
                <span>Net pay ({{ $currency }})</span>
                <strong>Rs {{ number_format((float) $slip->net_pay, 0) }}</strong>
            </div>
        </div>
    </section>
</div>
