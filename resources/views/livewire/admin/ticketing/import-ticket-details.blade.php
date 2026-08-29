<div class="import-ticket" @import-ticket-panel-opened.window="$wire.$refresh()">
    @if ($successMessage)
        <div class="import-ticket__alert import-ticket__alert--success">
            {{ $successMessage }}
        </div>
    @endif

    <div class="data-panel import-ticket__panel">
        <div class="data-panel__head">
            <h3 class="data-panel__title">Import Ticket Details</h3>
            <p class="import-ticket__hint">Upload a PDF, review the auto-filled fields, then confirm.</p>
        </div>

        <div class="import-ticket__body">
            <div @class(['import-ticket__upload', 'import-ticket__upload--compact' => $parsed])>
                <p class="import-ticket__label">Ticket PDF</p>

                <label
                    for="pdfFile"
                    @class([
                        'import-ticket__dropzone',
                        'import-ticket__dropzone--has-file' => (bool) $pdfFile,
                    ])
                >
                    <input
                        id="pdfFile"
                        type="file"
                        accept="application/pdf,.pdf"
                        wire:model="pdfFile"
                        class="import-ticket__file-input"
                    >

                    <span class="import-ticket__dropzone-icon" aria-hidden="true">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                            <line x1="12" y1="18" x2="12" y2="12"/>
                            <polyline points="9 15 12 12 15 15"/>
                        </svg>
                    </span>

                    <span class="import-ticket__dropzone-text">
                        @if ($pdfFile)
                            <strong class="import-ticket__file-name">{{ $pdfFile->getClientOriginalName() }}</strong>
                            <span class="import-ticket__file-hint">PDF selected — click to replace</span>
                        @else
                            <strong class="import-ticket__file-name">Upload ticket PDF</strong>
                            <span class="import-ticket__file-hint">PDF only · up to 5 MB</span>
                        @endif
                    </span>

                    <span class="import-ticket__browse-btn">Browse</span>
                </label>

                @error('pdfFile')
                    <p class="import-ticket__error">{{ $message }}</p>
                @enderror

                <div wire:loading wire:target="pdfFile" class="import-ticket__loading">
                    <span class="import-ticket__loading-dot"></span>
                    Uploading and reading PDF...
                </div>

                @if ($pdfFile && ! $parsed)
                    <button type="button" wire:click="parseUploadedPdf" class="hero-btn hero-btn--secondary import-ticket__read-btn">
                        Read PDF
                    </button>
                @endif
            </div>

            @if ($parseError)
                <div class="import-ticket__alert import-ticket__alert--error">
                    {{ $parseError }}
                </div>
            @endif

            @if ($parsed)
                <div class="import-ticket__review">
                <p class="import-ticket__review-note">
                    Review below — layout matches your PDF. Edit any field, then confirm.
                </p>

                <div class="itinerary-receipt">
                    {{-- Header (matches PDF top) --}}
                    <div class="itinerary-receipt__agency-meta">
                        <input type="text" wire:model="form.agency_name" class="itinerary-receipt__inline itinerary-receipt__inline--agency" placeholder="Agency name">
                        <input type="text" wire:model="form.agency_phone" class="itinerary-receipt__inline itinerary-receipt__inline--phone" placeholder="Agency phone">
                    </div>

                    <div class="itinerary-receipt__hero">
                        <div class="itinerary-receipt__hero-left">
                            <input type="text" wire:model="form.document_title" class="itinerary-receipt__title-input" placeholder="Itinerary Receipt">
                            <p class="itinerary-receipt__subtitle">Below are the details of your electronic ticket. Note: All timings are local</p>
                        </div>
                        <div class="itinerary-receipt__hero-right">
                            <div class="itinerary-receipt__ref-row">
                                <span>Booking Reference:</span>
                                <input type="text" wire:model="form.booking_reference" class="itinerary-receipt__inline" placeholder="Booking reference">
                            </div>
                            <div class="itinerary-receipt__ref-row">
                                <span>Agency PNR:</span>
                                <input type="text" wire:model="form.agency_pnr" class="itinerary-receipt__inline" placeholder="Agency PNR">
                            </div>
                        </div>
                    </div>

                    {{-- Flight Information --}}
                    <section class="itinerary-receipt__section">
                        <div class="itinerary-receipt__section-head">Flight Information</div>

                        @foreach ($flightSegments as $index => $segment)
                            <div class="itinerary-receipt__flight" wire:key="segment-{{ $index }}">
                                <div class="itinerary-receipt__flight-grid">
                                    <div class="itinerary-receipt__flight-col">
                                        <input type="text" wire:model="flightSegments.{{ $index }}.flight_number" class="itinerary-receipt__flight-code" placeholder="TK-983">
                                        <input type="text" wire:model="flightSegments.{{ $index }}.airline" class="itinerary-receipt__inline itinerary-receipt__inline--bold" placeholder="Airline">
                                    </div>

                                    <div class="itinerary-receipt__flight-col">
                                        <div class="itinerary-receipt__time-row">
                                            <input type="text" wire:model="flightSegments.{{ $index }}.departure_time" class="itinerary-receipt__inline itinerary-receipt__inline--time" placeholder="17:40">
                                            <span>(</span>
                                            <input type="text" wire:model="flightSegments.{{ $index }}.departure_date" class="itinerary-receipt__inline itinerary-receipt__inline--date" placeholder="11 Oct">
                                            <span>)</span>
                                        </div>
                                        <input type="text" wire:model="flightSegments.{{ $index }}.departure_location" class="itinerary-receipt__inline" placeholder="Nicosia (ECN)">
                                        <span class="itinerary-receipt__muted">Terminal - NA</span>
                                    </div>

                                    <div class="itinerary-receipt__flight-col">
                                        <div class="itinerary-receipt__time-row">
                                            <input type="text" wire:model="flightSegments.{{ $index }}.arrival_time" class="itinerary-receipt__inline itinerary-receipt__inline--time" placeholder="19:30">
                                            <span>(</span>
                                            <input type="text" wire:model="flightSegments.{{ $index }}.arrival_date" class="itinerary-receipt__inline itinerary-receipt__inline--date" placeholder="11 Oct">
                                            <span>)</span>
                                        </div>
                                        <input type="text" wire:model="flightSegments.{{ $index }}.arrival_location" class="itinerary-receipt__inline" placeholder="Istanbul (IST)">
                                        <span class="itinerary-receipt__muted">Terminal - NA</span>
                                    </div>

                                    <div class="itinerary-receipt__flight-col itinerary-receipt__flight-col--meta">
                                        <label class="itinerary-receipt__meta-line">
                                            <span>Status :</span>
                                            <input type="text" wire:model="flightSegments.{{ $index }}.status" class="itinerary-receipt__inline" placeholder="Confirm">
                                        </label>
                                        <label class="itinerary-receipt__meta-line">
                                            <span>Class :</span>
                                            <input type="text" wire:model="flightSegments.{{ $index }}.class" class="itinerary-receipt__inline" placeholder="Economy (T)">
                                        </label>
                                        <label class="itinerary-receipt__meta-line">
                                            <span>Baggage :</span>
                                            <input type="text" wire:model="flightSegments.{{ $index }}.baggage" class="itinerary-receipt__inline" placeholder="15.0 KG">
                                        </label>
                                        <label class="itinerary-receipt__meta-line">
                                            <span>PNR :</span>
                                            <input type="text" wire:model="flightSegments.{{ $index }}.pnr" class="itinerary-receipt__inline" placeholder="RRGHVZ">
                                        </label>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </section>

                    {{-- Passenger & Ticket Details --}}
                    <section class="itinerary-receipt__section">
                        <div class="itinerary-receipt__section-head">Passenger &amp; Ticket Details</div>

                        <div class="itinerary-receipt__passenger-table">
                            <div class="itinerary-receipt__passenger-head">
                                <span>Traveller Name</span>
                                <span>Frequent Flyer</span>
                                <span>Ticket No.</span>
                            </div>
                            <div class="itinerary-receipt__passenger-row">
                                <input type="text" wire:model="form.passenger_name" class="itinerary-receipt__inline" placeholder="Mr MUDDASIR IMTIAZ (ADT)">
                                <input type="text" wire:model="form.frequent_flyer" class="itinerary-receipt__inline" placeholder="-">
                                <input type="text" wire:model="form.ticket_number" class="itinerary-receipt__inline" placeholder="Ticket number">
                            </div>
                        </div>
                        @error('passenger_name')
                            <p class="import-ticket__error">{{ $message }}</p>
                        @enderror
                    </section>

                    <div class="itinerary-receipt__notice">
                        <strong>Notice:</strong>
                        <p>Refund and Cancellation policies are Governed by the respective Fare Rules applicable</p>
                    </div>
                </div>

                {{-- Payment ledger (demo — session only, no DB) --}}
                <div class="import-ticket__payment">
                    <p class="import-ticket__label">Payment Ledger</p>

                    <div class="import-ticket__ledger-grid">
                        <label class="import-ticket__ledger-field">
                            <span>Amount Agreed ({{ $currency }})</span>
                            <input
                                type="number"
                                min="0"
                                step="1"
                                wire:model.live="amount_agreed"
                                class="import-ticket__input"
                                placeholder="e.g. 85000"
                            >
                            @error('amount_agreed')
                                <p class="import-ticket__error">{{ $message }}</p>
                            @enderror
                        </label>

                        <label class="import-ticket__ledger-field">
                            <span>Amount Paid ({{ $currency }})</span>
                            <input
                                type="number"
                                min="0"
                                step="1"
                                wire:model.live="amount_paid"
                                class="import-ticket__input"
                                placeholder="e.g. 42500"
                            >
                            @error('amount_paid')
                                <p class="import-ticket__error">{{ $message }}</p>
                            @enderror
                        </label>

                        <div class="import-ticket__ledger-field import-ticket__ledger-field--balance">
                            <span>Balance ({{ $currency }})</span>
                            <strong>{{ number_format($this->balance, 0) }}</strong>
                            <small>Agreed − Paid</small>
                        </div>
                    </div>

                    <label class="import-ticket__ledger-status">
                        <span>Payment Status</span>
                        <select wire:model="payment_status" class="import-ticket__select">
                            @foreach ($paymentStatuses as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>

                    <p class="import-ticket__payment-hint">
                        Status auto-updates from amounts: 0 paid = Pending, partial = Half Receive, full = Paid. You can override the dropdown.
                    </p>
                </div>

                <details class="import-ticket__raw">
                    <summary>Raw PDF text (for tracing import errors)</summary>
                    <pre>{{ $raw_pdf_text }}</pre>
                </details>

                <div class="import-ticket__actions">
                    <button type="button" wire:click="confirm" wire:loading.attr="disabled" class="hero-btn hero-btn--primary">
                        <span wire:loading.remove wire:target="confirm">Confirm Details</span>
                        <span wire:loading wire:target="confirm">Saving...</span>
                    </button>
                    <button type="button" wire:click="resetForm" class="hero-btn hero-btn--secondary">
                        Cancel
                    </button>
                </div>
                </div>
            @endif
        </div>
    </div>

    <div class="data-panel import-ticket__history">
        <div class="data-panel__head">
            <h3 class="data-panel__title">Payment Ledger (This Session)</h3>
            <p class="import-ticket__hint">Simple ledger — agreed, paid, balance per ticket. Session only until you add a migration.</p>
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
                        <th>Saved At</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse (array_reverse($demoPayments) as $record)
                        <tr>
                            <td>{{ $record['passenger'] }}</td>
                            <td>{{ $record['booking_reference'] }}</td>
                            <td>{{ number_format($record['amount_agreed'] ?? 0, 0) }}</td>
                            <td>{{ number_format($record['amount_paid'] ?? 0, 0) }}</td>
                            <td>{{ number_format($record['balance'] ?? 0, 0) }}</td>
                            <td>
                                <span @class([
                                    'payment-badge',
                                    'payment-badge--paid' => ($record['payment_status'] ?? '') === 'PAID',
                                    'payment-badge--pending' => ($record['payment_status'] ?? '') === 'PENDING',
                                    'payment-badge--half' => ($record['payment_status'] ?? '') === 'HALF_RECEIVE',
                                ])>
                                    {{ $paymentStatuses[$record['payment_status']] ?? ($record['payment_status'] ?? '—') }}
                                </span>
                            </td>
                            <td>{{ $record['saved_at'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">Confirm an import above to see ledger entries here.</td>
                        </tr>
                    @endforelse
                </tbody>
                @if (count($demoPayments) > 0)
                    <tfoot>
                        <tr class="import-ticket__ledger-total">
                            <td colspan="2"><strong>Total</strong></td>
                            <td><strong>{{ number_format($ledgerTotals['agreed'], 0) }}</strong></td>
                            <td><strong>{{ number_format($ledgerTotals['paid'], 0) }}</strong></td>
                            <td><strong>{{ number_format($ledgerTotals['balance'], 0) }}</strong></td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>

    <div class="data-panel import-ticket__history">
        <div class="data-panel__head">
            <h3 class="data-panel__title">Recent Confirmed Imports</h3>
        </div>

        <div class="data-table-wrap import-ticket__history-table">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>File</th>
                        <th>Passenger</th>
                        <th>Booking Ref</th>
                        <th>PNR</th>
                        <th>Ticket No.</th>
                        <th>Route</th>
                        <th>Flights</th>
                        <th>Confirmed By</th>
                        <th>Confirmed At</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recentImports as $import)
                        <tr>
                            <td class="import-ticket__history-file">{{ $import->original_filename }}</td>
                            <td>{{ $import->passenger_name ?? '—' }}</td>
                            <td>{{ $import->booking_reference ?? '—' }}</td>
                            <td>{{ $import->agency_pnr ?? '—' }}</td>
                            <td>{{ $import->ticket_number ?? '—' }}</td>
                            <td class="import-ticket__history-route">{{ $import->routeLabel() }}</td>
                            <td>{{ $import->flightNumbersLabel() }}</td>
                            <td>{{ $import->user?->name ?? '—' }}</td>
                            <td>{{ $import->confirmed_at?->format('d M Y, H:i') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9">No confirmed imports yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
