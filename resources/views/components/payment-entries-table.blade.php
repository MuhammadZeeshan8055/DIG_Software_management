@props([
    'entries',
    'ledgerTotals',
    'paymentStatuses',
    'showActions' => false,
    'title' => 'Payment Entries',
])

<div class="data-panel import-ticket__history">
    <div class="data-panel__head">
        <h3 class="data-panel__title">{{ $title }}</h3>
    </div>

    <div class="data-table-wrap import-ticket__history-table">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Passenger</th>
                    <th>Booking Ref</th>
                    <th>Agreed</th>
                    <th>Paid</th>
                    <th>Balance</th>
                    <th>Status</th>
                    <th>Method</th>
                    <th>Account</th>
                    <th>Saved At</th>
                    @if ($showActions)
                        <th>Actions</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse ($entries as $entry)
                    <tr wire:key="payment-entry-{{ $entry->id }}">
                        <td>{{ $entry->passenger_name }}</td>
                        <td>{{ $entry->booking_reference ?? '—' }}</td>
                        <td>{{ number_format($entry->amount_agreed, 0) }}</td>
                        <td>{{ number_format($entry->amount_paid, 0) }}</td>
                        <td>{{ number_format($entry->balance, 0) }}</td>
                        <td>
                            <span @class([
                                'payment-badge',
                                'payment-badge--paid' => $entry->payment_status === 'PAID',
                                'payment-badge--pending' => $entry->payment_status === 'PENDING',
                                'payment-badge--half' => $entry->payment_status === 'HALF_RECEIVE',
                            ])>
                                {{ $paymentStatuses[$entry->payment_status] ?? $entry->payment_status }}
                            </span>
                        </td>
                        <td>{{ $entry->receivedInLabel() }}</td>
                        <td>{{ $entry->receivedAccountLabel() }}</td>
                        <td>{{ format_datetime($entry->created_at) }}</td>
                        @if ($showActions)
                            <td class="payment-actions">
                                <button
                                    type="button"
                                    class="payment-actions__pill payment-actions__pill--edit"
                                    wire:click="startEdit({{ $entry->id }})"
                                >
                                    <svg class="payment-actions__icon" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                                        <path d="M11.3 2.3a1 1 0 0 1 1.4 0l1 1a1 1 0 0 1 0 1.4L5.6 12.8l-2.4.6.6-2.4L11.3 2.3Z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/>
                                    </svg>
                                    Edit
                                </button>
                                <button
                                    type="button"
                                    class="payment-actions__pill payment-actions__pill--delete"
                                    wire:click="deleteEntry({{ $entry->id }})"
                                    wire:confirm="Delete this payment entry?"
                                >
                                    <svg class="payment-actions__icon" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                                        <path d="M3.5 4.5h9M6 4.5V3.5a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v1M6.5 7v4.5M9.5 7v4.5M4.5 4.5l.5 8a1 1 0 0 0 1 .9h4a1 1 0 0 0 1-.9l.5-8" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    Delete
                                </button>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $showActions ? 10 : 9 }}">No payment entries yet.</td>
                    </tr>
                @endforelse
            </tbody>
            @if ($entries->count() > 0)
                <tfoot>
                    <tr class="import-ticket__ledger-total">
                        <td colspan="2"><strong>Total</strong></td>
                        <td><strong>{{ number_format($ledgerTotals['agreed'], 0) }}</strong></td>
                        <td><strong>{{ number_format($ledgerTotals['paid'], 0) }}</strong></td>
                        <td><strong>{{ number_format($ledgerTotals['balance'], 0) }}</strong></td>
                        <td colspan="{{ $showActions ? 5 : 4 }}"></td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</div>
