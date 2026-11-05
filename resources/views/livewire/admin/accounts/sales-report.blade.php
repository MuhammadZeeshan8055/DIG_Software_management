<div
    class="sales-report-page"
    @sales-report-panel-opened.window="$wire.$refresh()"
    x-data="{ importOpen: false }"
    x-effect="document.body.classList.toggle('mu-modal-open', !!$wire.showReplaceConfirm)"
    x-on:destroy="document.body.classList.remove('mu-modal-open')"
>
    @if ($denied ?? false)
        <div class="data-panel">
            <p>You do not have access to Sales Report. Ask an admin to enable it in Manage Users.</p>
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
                    <h3 class="data-panel__title">Sales Report</h3>
                    <p class="import-ticket__hint">Import your monthly travel Excel. Profit = Received − Payable.</p>
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
                        <p class="import-ticket__label">Travel sales report</p>

                        <label
                            for="excelFile"
                            @class([
                                'import-ticket__dropzone',
                                'import-ticket__dropzone--has-file' => (bool) $excelFile,
                            ])
                        >
                            <input
                                id="excelFile"
                                type="file"
                                accept=".xls,.xlsx,.csv,.html,.htm,text/html,text/csv,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
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
                                    <span class="import-ticket__file-hint">Report selected — click to replace</span>
                                @else
                                    <strong class="import-ticket__file-name">Upload travel report</strong>
                                    <span class="import-ticket__file-hint">.xls / .xlsx / .csv · Excel HTML .xls supported · up to 20 MB</span>
                                @endif
                            </span>

                            <span class="import-ticket__browse-btn">Browse</span>
                        </label>

                        <div class="sales-report__columns">
                            <p class="sales-report__columns-title">Expected columns</p>
                            <div class="sales-report__column-chips" aria-label="Report columns">
                                <span>DATE</span>
                                <span>PASSENGER NAME</span>
                                <span>SECTOR</span>
                                <span>AIRLINE</span>
                                <span>PAYABLE</span>
                                <span>RECEIVED</span>
                                <span>VIA</span>
                            </div>
                            <p class="sales-report__columns-hint">
                                Profit = RECEIVED − PAYABLE. Rows named TOTAL or BALANCE are ignored.
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

        {{-- Summary cards — Payable, Received, Profit (Received − Payable) --}}
        <div class="stat-grid sales-report__stats" style="margin-bottom: 16px;">
            <article class="stat-card stat-card--amber" style="--stat-i: 0">
                <div class="stat-card__top"><p class="stat-card__label">Payable (PKR)</p></div>
                <p class="stat-card__value">{{ $currency }} {{ number_format($totals['payable'], 2) }}</p>
                <p class="stat-card__hint">{{ number_format($totals['entries'], 0) }} entries</p>
            </article>
            <article class="stat-card" style="--stat-i: 1">
                <div class="stat-card__top"><p class="stat-card__label">Received (PKR)</p></div>
                <p class="stat-card__value">{{ $currency }} {{ number_format($totals['received'], 2) }}</p>
                <p class="stat-card__hint">From report</p>
            </article>
            <article class="stat-card {{ $totals['profit'] < 0 ? 'stat-card--red' : 'stat-card--green' }}" style="--stat-i: 2">
                <div class="stat-card__top"><p class="stat-card__label">Profit (PKR)</p></div>
                <p class="stat-card__value">{{ $currency }} {{ number_format($totals['profit'], 2) }}</p>
                <p class="stat-card__hint">Received − Payable</p>
            </article>
        </div>

        {{-- Entries table --}}
        <div class="data-panel">
            <div class="data-panel__head">
                <h3 class="data-panel__title">Sales entries</h3>
                <span class="add-account__count">{{ $entries->count() }} shown</span>
            </div>

            <div class="data-table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Passenger Name</th>
                            <th>Sector</th>
                            <th>Airline</th>
                            <th>Payable</th>
                            <th>Received</th>
                            <th>Via</th>
                            <th>Profit</th>
                            @if (auth()->user()->isAdmin())
                                <th>Actions</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($entries as $entry)
                            @php
                                // Profit for this row = received - payable
                                $rowProfit = round((float) $entry->received - (float) $entry->payable, 2);
                            @endphp
                            <tr wire:key="sale-entry-{{ $entry->id }}">
                                <td>{{ optional($entry->sale_date)->format('d-M-y') }}</td>
                                <td>{{ $entry->passenger_name }}</td>
                                <td>{{ $entry->sector ? $entry->sector : '—' }}</td>
                                <td>{{ $entry->airline ? $entry->airline : '—' }}</td>
                                <td>{{ number_format((float) $entry->payable, 2) }}</td>
                                <td>{{ number_format((float) $entry->received, 2) }}</td>
                                <td>{{ $entry->via ? $entry->via : '—' }}</td>
                                <td>{{ number_format($rowProfit, 2) }}</td>
                                @if (auth()->user()->isAdmin())
                                    <td class="manage-users-actions">
                                        <button
                                            type="button"
                                            class="payment-actions__btn payment-actions__btn--danger"
                                            @click="$dispatch('confirm-delete-sales', {
                                                id: {{ $entry->id }},
                                                title: 'Delete sale entry?',
                                                message: 'This sales row will be permanently removed from the report.'
                                            })"
                                        >Delete</button>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ auth()->user()->isAdmin() ? 9 : 8 }}">No sales for this month yet. Import the travel sales report above.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <x-confirm-delete-modal event="confirm-delete-sales" />

        @if ($showReplaceConfirm)
            <div
                class="mu-modal confirm-delete-modal"
                wire:key="sales-replace-confirm"
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
