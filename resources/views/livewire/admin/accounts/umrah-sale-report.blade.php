<div
    class="sales-report-page"
    @umrah-sales-report-panel-opened.window="$wire.$refresh()"
    x-data="{ importOpen: false }"
    x-effect="document.body.classList.toggle('mu-modal-open', !!$wire.showReplaceConfirm)"
    x-on:destroy="document.body.classList.remove('mu-modal-open')"
>
    @if ($denied ?? false)
        <div class="data-panel">
            <p>You do not have access to Umrah Sale Report. Ask an admin to enable it in Manage Users.</p>
        </div>
    @else

        @if ($successMessage)
            <x-admin-toast
                :title="str_starts_with($successMessage, 'Deleted') ? 'Deleted' : 'Saved'"
                wire-property="successMessage"
                :seconds="5"
            >
                {{ $successMessage }}
            </x-admin-toast>
        @endif

        @if ($errorMessage)
            <x-admin-toast type="error" title="Import failed" wire-property="errorMessage" :seconds="7">
                {{ $errorMessage }}
            </x-admin-toast>
        @endif

        <div class="data-panel import-ticket__panel" style="margin-bottom: 16px;">
            <div class="data-panel__head sales-report__head">
                <div>
                    <h3 class="data-panel__title">Umrah Sale Report</h3>
                    <p class="import-ticket__hint">Import Umrah Ledger Sales Report. Profit / Loss / Remaining come from the Excel file.</p>
                </div>
                <button
                    type="button"
                    class="sales-report__toggle"
                    @click="importOpen = !importOpen"
                    :aria-expanded="importOpen.toString()"
                >
                    <span x-text="importOpen ? 'Hide import' : 'Import Excel'"></span>
                    <svg
                        class="sales-report__toggle-chevron"
                        :class="{ 'sales-report__toggle-chevron--open': importOpen }"
                        width="16"
                        height="16"
                        viewBox="0 0 16 16"
                        fill="none"
                        aria-hidden="true"
                    >
                        <path d="M4 6l4 4 4-4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
            </div>

            <div class="import-ticket__body sales-report__body">
                <div class="sales-report__toolbar">
                    <label class="import-ticket__field sales-report__month-field">
                        <span class="import-ticket__label">Report month</span>
                        <input
                            type="month"
                            class="import-ticket__input"
                            wire:model.live="report_month"
                        >
                    </label>
                </div>

                <div
                    class="sales-report__import-panel"
                    x-show="importOpen"
                    x-cloak
                    x-transition:enter="sales-report-collapse-enter"
                    x-transition:enter-start="sales-report-collapse-enter-start"
                    x-transition:enter-end="sales-report-collapse-enter-end"
                    x-transition:leave="sales-report-collapse-leave"
                    x-transition:leave-start="sales-report-collapse-leave-start"
                    x-transition:leave-end="sales-report-collapse-leave-end"
                    style="display: none;"
                >
                    <form wire:submit.prevent="requestImport" class="import-ticket__upload">
                        <p class="import-ticket__label">DIG Umrah Accounts Ledger</p>

                        <label
                            for="umrahExcelFile"
                            @class([
                                'import-ticket__dropzone',
                                'import-ticket__dropzone--has-file' => (bool) $excelFile,
                            ])
                        >
                            <input
                                id="umrahExcelFile"
                                type="file"
                                accept=".xls,.xlsx,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                                wire:model="excelFile"
                                class="import-ticket__file-input"
                            >

                            <span class="import-ticket__dropzone-icon" aria-hidden="true">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                    <polyline points="14 2 14 8 20 8"/>
                                    <path d="M8 13h8M8 17h5"/>
                                </svg>
                            </span>

                            <span class="import-ticket__dropzone-text">
                                @if ($excelFile)
                                    <strong class="import-ticket__file-name">{{ $excelFile->getClientOriginalName() }}</strong>
                                    <span class="import-ticket__file-hint">Ledger selected — click to replace</span>
                                @else
                                    <strong class="import-ticket__file-name">Upload Umrah ledger</strong>
                                    <span class="import-ticket__file-hint">.xlsx / .xls · up to 20 MB</span>
                                @endif
                            </span>

                            <span class="import-ticket__browse-btn">Browse</span>
                        </label>

                        <div class="sales-report__columns">
                            <p class="sales-report__columns-title">Expected columns</p>
                            <div class="sales-report__column-chips" aria-label="Report columns">
                                <span>DATE</span>
                                <span>ID</span>
                                <span>HEAD / PERSON</span>
                                <span>PASSPORT</span>
                                <span>TOTAL PAX</span>
                                <span>AIRLINE</span>
                                <span>PER PERSON COSTS</span>
                                <span>COST PER PERSON</span>
                                <span>TOTAL AMOUNT</span>
                                <span>RECEIVED</span>
                                <span>REMAINING</span>
                                <span>PROFIT</span>
                                <span>LOSS</span>
                                <span>LINE TOTALS</span>
                            </div>
                            <p class="sales-report__columns-hint">
                                All values are taken from the Excel (including Profit, Loss, Remaining). Nothing is recalculated.
                            </p>
                        </div>

                        @error('excelFile')
                            <p class="import-ticket__error">{{ $message }}</p>
                        @enderror
                        @error('report_month')
                            <p class="import-ticket__error">{{ $message }}</p>
                        @enderror

                        <div wire:loading wire:target="excelFile" class="import-ticket__loading">
                            <span class="import-ticket__loading-dot"></span>
                            Uploading file...
                        </div>

                        <div class="import-ticket__actions" style="margin-top: 14px;">
                            <button
                                type="submit"
                                class="hero-btn hero-btn--primary"
                                wire:loading.attr="disabled"
                                wire:target="requestImport,importSheetReplacing,excelFile"
                                @disabled(! $excelFile)
                            >
                                <span wire:loading.remove wire:target="requestImport,importSheetReplacing">Import sheet</span>
                                <span wire:loading wire:target="requestImport,importSheetReplacing">Working...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="stat-grid sales-report__stats sales-report__stats--4" style="margin-bottom: 16px;">
            <article class="stat-card stat-card--amber" style="--stat-i: 0">
                <div class="stat-card__top"><p class="stat-card__label">Total Amount (PKR)</p></div>
                <p class="stat-card__value">{{ $currency }} {{ number_format($totals['total_amount'] ?? 0, 2) }}</p>
                <p class="stat-card__hint">{{ number_format($totals['entries'], 0) }} entries</p>
            </article>
            <article class="stat-card" style="--stat-i: 1">
                <div class="stat-card__top"><p class="stat-card__label">Received (PKR)</p></div>
                <p class="stat-card__value">{{ $currency }} {{ number_format($totals['received'], 2) }}</p>
                <p class="stat-card__hint">From Excel</p>
            </article>
            <article class="stat-card stat-card--red" style="--stat-i: 2">
                <div class="stat-card__top"><p class="stat-card__label">Loss (PKR)</p></div>
                <p class="stat-card__value">{{ $currency }} {{ number_format($totals['loss'] ?? 0, 2) }}</p>
                <p class="stat-card__hint">From Excel</p>
            </article>
            <article class="stat-card {{ ($totals['profit'] ?? 0) < 0 ? 'stat-card--red' : 'stat-card--green' }}" style="--stat-i: 3">
                <div class="stat-card__top"><p class="stat-card__label">Profit (PKR)</p></div>
                <p class="stat-card__value">{{ $currency }} {{ number_format($totals['profit'], 2) }}</p>
                <p class="stat-card__hint">From Excel</p>
            </article>
        </div>

        <div class="data-panel">
            <div class="data-panel__head">
                <h3 class="data-panel__title">Umrah sale entries</h3>
                <span class="add-account__count">{{ $entries->count() }} shown</span>
            </div>

            <div class="data-table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>ID</th>
                            <th>Passenger</th>
                            <th>Passport</th>
                            <th>Airline</th>
                            <th>Pax</th>
                            <th>Air Ticket</th>
                            <th>Visa</th>
                            <th>Makkah</th>
                            <th>Madinah</th>
                            <th>Ziarat</th>
                            <th>Transport</th>
                            <th>Other</th>
                            <th>Agent Profit</th>
                            <th>Cost / Person</th>
                            <th>Total Amount</th>
                            <th>Received</th>
                            <th>Remaining</th>
                            <th>Profit</th>
                            <th>Loss</th>
                            <th>Air Ticket Tot</th>
                            <th>Visa Tot</th>
                            <th>Makkah Tot</th>
                            <th>Madinah Tot</th>
                            <th>Ziarat Tot</th>
                            <th>Transport Tot</th>
                            <th>Other Tot</th>
                            <th>Agent Profit Tot</th>
                            @if (auth()->user()->isAdmin())
                                <th>Actions</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($entries as $entry)
                            <tr wire:key="umrah-sale-entry-{{ $entry->id }}">
                                <td>{{ optional($entry->sale_date)->format('d-M-y') }}</td>
                                <td>{{ $entry->booking_id ?: '—' }}</td>
                                <td>{{ $entry->passenger_name }}</td>
                                <td>{{ $entry->passport_no ?: '—' }}</td>
                                <td>{{ $entry->airline ?: '—' }}</td>
                                <td>{{ $entry->total_pax !== null ? $entry->total_pax : '—' }}</td>
                                <td>{{ number_format((float) $entry->air_ticket_pp, 2) }}</td>
                                <td>{{ number_format((float) $entry->visa_cost_pp, 2) }}</td>
                                <td>{{ number_format((float) $entry->makkah_hotel_pp, 2) }}</td>
                                <td>{{ number_format((float) $entry->madinah_hotel_pp, 2) }}</td>
                                <td>{{ number_format((float) $entry->ziarat_pp, 2) }}</td>
                                <td>{{ number_format((float) $entry->transport_pp, 2) }}</td>
                                <td>{{ number_format((float) $entry->other_expense_pp, 2) }}</td>
                                <td>{{ number_format((float) $entry->agent_profit_pp, 2) }}</td>
                                <td>{{ number_format((float) $entry->payable, 2) }}</td>
                                <td>{{ number_format((float) ($entry->total_amount ?? 0), 2) }}</td>
                                <td>{{ number_format((float) $entry->received, 2) }}</td>
                                <td>{{ number_format((float) ($entry->remaining_amount ?? 0), 2) }}</td>
                                <td>{{ number_format((float) $entry->profit, 2) }}</td>
                                <td>{{ number_format((float) ($entry->loss ?? 0), 2) }}</td>
                                <td>{{ number_format((float) ($entry->air_ticket_total ?? 0), 2) }}</td>
                                <td>{{ number_format((float) ($entry->visa_total ?? 0), 2) }}</td>
                                <td>{{ number_format((float) ($entry->makkah_hotel_total ?? 0), 2) }}</td>
                                <td>{{ number_format((float) ($entry->madinah_hotel_total ?? 0), 2) }}</td>
                                <td>{{ number_format((float) ($entry->ziarat_total ?? 0), 2) }}</td>
                                <td>{{ number_format((float) ($entry->transport_total ?? 0), 2) }}</td>
                                <td>{{ number_format((float) ($entry->other_expense_total ?? 0), 2) }}</td>
                                <td>{{ number_format((float) ($entry->agent_profit_total ?? 0), 2) }}</td>
                                @if (auth()->user()->isAdmin())
                                    <td class="manage-users-actions">
                                        <button
                                            type="button"
                                            class="payment-actions__btn payment-actions__btn--danger"
                                            @click="$dispatch('confirm-delete-umrah-sales', {
                                                id: {{ $entry->id }},
                                                title: 'Delete umrah sale entry?',
                                                message: 'This umrah sales row will be permanently removed from the report.'
                                            })"
                                        >Delete</button>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ auth()->user()->isAdmin() ? 29 : 28 }}">No umrah sales for this month yet. Import the Umrah Ledger Sales Report above.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <x-confirm-delete-modal event="confirm-delete-umrah-sales" />

        @if ($showReplaceConfirm)
            <div
                class="mu-modal confirm-delete-modal"
                wire:key="umrah-sales-replace-confirm"
                x-transition:enter="confirm-delete-modal-enter"
                x-transition:enter-start="confirm-delete-modal-enter-start"
                x-transition:enter-end="confirm-delete-modal-enter-end"
                wire:keydown.escape.window="cancelReplaceConfirm"
            >
                <button
                    type="button"
                    class="mu-modal__backdrop"
                    wire:click="cancelReplaceConfirm"
                    aria-label="Close"
                ></button>

                <div class="mu-modal__dialog confirm-delete-modal__dialog confirm-delete-modal__dialog--pop" role="dialog" aria-modal="true">
                    <div class="mu-modal__head">
                        <div class="confirm-delete-modal__head-main">
                            <span class="confirm-delete-modal__icon" aria-hidden="true">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path d="M3 6h18M8 6V4a2 2 0 0 0 2-2h4a2 2 0 0 0 2 2v2M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                                    <path d="M10 11v6M14 11v6"/>
                                </svg>
                            </span>
                            <h3 class="mu-modal__title">{{ $replaceConfirmTitle }}</h3>
                        </div>
                        <button type="button" class="mu-modal__close" wire:click="cancelReplaceConfirm" aria-label="Close">&times;</button>
                    </div>

                    <div class="confirm-delete-modal__body">
                        <p>{{ $replaceConfirmMessage }}</p>
                    </div>

                    <div class="mu-modal__footer">
                        <button type="button" class="payment-actions__btn" wire:click="cancelReplaceConfirm">Cancel</button>
                        <button
                            type="button"
                            class="hero-btn confirm-delete-modal__confirm"
                            wire:click="importSheetReplacing"
                            wire:loading.attr="disabled"
                            wire:target="importSheetReplacing"
                        >
                            <span wire:loading.remove wire:target="importSheetReplacing">Yes, replace</span>
                            <span wire:loading wire:target="importSheetReplacing">Replacing...</span>
                        </button>
                    </div>
                </div>
            </div>
        @endif

    @endif
</div>
