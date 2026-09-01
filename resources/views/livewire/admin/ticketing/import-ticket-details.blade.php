<div
    class="import-ticket"
    @import-ticket-panel-opened.window="$wire.$refresh()"
    @close-ticket-view.window="$wire.closeView()"
    x-data
    x-effect="document.body.classList.toggle('ticket-view-modal-open', $wire.viewingImportId !== null)"
    x-on:destroy="document.body.classList.remove('ticket-view-modal-open')"
>
    @if ($successMessage)
        <x-admin-alert type="success" wire-property="successMessage">
            {{ $successMessage }}
        </x-admin-alert>
    @endif

    @if ($errorMessage)
        <x-admin-alert type="error" :seconds="10" wire-property="errorMessage">
            {{ $errorMessage }}
        </x-admin-alert>
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
                <x-admin-alert type="error" :seconds="7" wire-property="parseError">
                    {{ $parseError }}
                </x-admin-alert>
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

                    <details class="import-ticket__raw">
                        <summary>Raw PDF text (for tracing import errors)</summary>
                        <pre>{{ $raw_pdf_text }}</pre>
                    </details>

                </div>

                {{-- Payment Details --}}
                <div
                    x-data="{
                        agreed: @js($amount_agreed),
                        paid: @js($amount_paid),
                        paymentStatus: @js($payment_status),
                        method: @js($payment_method),
                        accountId: @js($receiving_account_id),
                        accounts: @js($allReceivingAccounts),
                        statusManual: false,
                        get balance() {
                            const a = parseFloat(this.agreed) || 0;
                            const p = parseFloat(this.paid) || 0;
                            return Math.max(0, a - p);
                        },
                        get filtered() {
                            return this.accounts.filter(a => a.method === this.method)
                        },
                        formatAmount(value) {
                            return (parseFloat(value) || 0).toLocaleString('en-US', { maximumFractionDigits: 0 });
                        },
                        autoStatus() {
                            const a = parseFloat(this.agreed) || 0;
                            const p = parseFloat(this.paid) || 0;
                            if (a <= 0) {
                                return 'PENDING';
                            }
                            if (p >= a) {
                                return 'PAID';
                            }
                            if (p > 0) {
                                return 'HALF_RECEIVE';
                            }
                            return 'PENDING';
                        },
                        syncStatusLocal() {
                            if (! this.statusManual) {
                                this.paymentStatus = this.autoStatus();
                            }
                        },
                        cleanAmount(field) {
                            this[field] = String(this[field]).replace(/\D/g, '');
                            this.syncStatusLocal();
                        },
                        selectMethod(key) {
                            this.method = key
                            const first = this.accounts.find(a => a.method === key)
                            this.accountId = first ? first.id : null
                        },
                        selectAccount(id) {
                            this.accountId = id
                        },
                        isSelected(id) {
                            return Number(this.accountId) === Number(id)
                        },
                        confirmSave() {
                            $wire.confirmWithLedger(this.agreed, this.paid, this.paymentStatus, this.method, this.accountId)
                        }
                    }"
                >
                    <div class="import-ticket__payment" wire:ignore>
                        <p class="import-ticket__label">Payment Details</p>

                        <div class="import-ticket__ledger-grid">
                            <label class="import-ticket__ledger-field">
                                <span>Amount Agreed ({{ $currency }})</span>
                                <input
                                    type="text"
                                    inputmode="numeric"
                                    x-model="agreed"
                                    @input="cleanAmount('agreed')"
                                    class="import-ticket__input"
                                    placeholder="e.g. 85000"
                                >
                            </label>

                            <label class="import-ticket__ledger-field">
                                <span>Amount Paid ({{ $currency }})</span>
                                <input
                                    type="text"
                                    inputmode="numeric"
                                    x-model="paid"
                                    @input="cleanAmount('paid')"
                                    class="import-ticket__input"
                                    placeholder="0"
                                >
                            </label>

                            <div class="import-ticket__ledger-field import-ticket__ledger-field--balance">
                                <span>Balance ({{ $currency }})</span>
                                <strong x-text="formatAmount(balance)">0</strong>
                                <small>Agreed − Paid</small>
                            </div>
                        </div>

                        <label class="import-ticket__ledger-status">
                            <span>Payment Status</span>
                            <select
                                x-model="paymentStatus"
                                @change="statusManual = true"
                                class="import-ticket__select"
                            >
                                @foreach ($paymentStatuses as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>

                        <x-receiving-account-picker
                            embedded
                            :methods="$paymentMethods"
                            :all-accounts="$allReceivingAccounts"
                            :selected-method="$payment_method"
                            :selected-account-id="$receiving_account_id"
                        />
                    </div>

                    <x-validation-errors class="import-ticket__validation-errors" />

                    <div class="import-ticket__actions">
                        <button type="button" @click="confirmSave()" wire:loading.attr="disabled" class="hero-btn hero-btn--primary">
                            <span wire:loading.remove wire:target="confirmWithLedger">Confirm Details</span>
                            <span wire:loading wire:target="confirmWithLedger">Saving...</span>
                        </button>
                        <button type="button" wire:click="resetForm" class="hero-btn hero-btn--secondary">
                            Cancel
                        </button>
                    </div>
                </div>
                </div>
            @endif
        </div>
    </div>

    <x-payment-entries-table
        :entries="$paymentEntries"
        :ledger-totals="$ledgerTotals"
        :payment-statuses="$paymentStatuses"
        title="Payment Details"
    />

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
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recentImports as $import)
                        <tr wire:key="import-row-{{ $import->id }}">
                            <td class="import-ticket__history-file">{{ $import->original_filename }}</td>
                            <td>{{ $import->passenger_name ?? '—' }}</td>
                            <td>{{ $import->booking_reference ?? '—' }}</td>
                            <td>{{ $import->agency_pnr ?? '—' }}</td>
                            <td>{{ $import->ticket_number ?? '—' }}</td>
                            <td class="import-ticket__history-route">{{ $import->routeLabel() }}</td>
                            <td>{{ $import->flightNumbersLabel() }}</td>
                            <td>{{ $import->user?->name ?? '—' }}</td>
                            <td>{{ format_datetime($import->confirmed_at) }}</td>
                            <td>
                                <button
                                    type="button"
                                    class="payment-actions__pill payment-actions__pill--edit"
                                    wire:click="viewImport({{ $import->id }})"
                                >View</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10">No confirmed imports yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($viewingImport)
        <template x-teleport="body">
            <div
                class="ticket-view-modal"
                x-on:keydown.escape.window="$wire.closeView()"
                wire:key="ticket-view-modal-{{ $viewingImport->id }}"
            >
                <button type="button" class="ticket-view-modal__backdrop" wire:click="closeView" aria-label="Close"></button>

                <div class="ticket-view-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="ticket-view-title">
                    <div class="ticket-view-modal__head">
                        <div>
                            <p class="ticket-view-modal__eyebrow">Official Operations Document</p>
                            <h3 id="ticket-view-title" class="ticket-view-modal__title">{{ $viewingImport->passenger_name ?: $viewingImport->original_filename }}</h3>
                            <p class="ticket-view-modal__meta">{{ $viewingImport->documentSerial() }} · {{ $viewingImport->booking_reference ?: 'No CRM ref' }}</p>
                        </div>
                        <div class="ticket-view-modal__head-actions">
                            <a
                                href="{{ route('ticket-imports.document', ['ticketImport' => $viewingImport, 'print' => 1]) }}"
                                target="_blank"
                                rel="noopener"
                                class="hero-btn hero-btn--secondary ticket-view-modal__pdf-btn"
                            >Download PDF</a>
                            <button type="button" class="ticket-view-modal__close" wire:click="closeView" aria-label="Close">&times;</button>
                        </div>
                    </div>

                    <div class="ticket-view-modal__body">
                        <x-ticket-document :import="$viewingImport" />
                    </div>
                </div>
            </div>
        </template>
    @endif
</div>
