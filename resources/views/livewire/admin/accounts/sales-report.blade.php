<div
    class="sales-report-page"
    @sales-report-panel-opened.window="$wire.$refresh()"
    x-data="{
        importOpen: false,
        async startImport() {
            if (! $wire.excelFile) {
                return;
            }

            const count = await $wire.existingCountForMonth();

            if (count > 0) {
                const label = await $wire.monthLabel();
                window.dispatchEvent(new CustomEvent('confirm-replace-sales', {
                    detail: {
                        title: 'Replace month data?',
                        message: 'Sales data already exists for ' + label + ' (' + count + ' row' + (count === 1 ? '' : 's') + '). Do you want to replace it with this file? Existing profit values for this month will be lost.'
                    }
                }));
                return;
            }

            $wire.importSheet(false);
        }
    }"
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
                    <p class="import-ticket__hint">View monthly totals below. Open import only when uploading a sheet.</p>
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
                    <form wire:submit.prevent="startImport" class="import-ticket__upload">
                        <p class="import-ticket__label">Sales Excel</p>

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
                                accept=".xlsx,.xls,.csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel,text/csv"
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
                                    <span class="import-ticket__file-hint">Excel selected — click to replace</span>
                                @else
                                    <strong class="import-ticket__file-name">Upload sales Excel</strong>
                                    <span class="import-ticket__file-hint">.xlsx / .xls / .csv · up to 10 MB</span>
                                @endif
                            </span>

                            <span class="import-ticket__browse-btn">Browse</span>
                        </label>

                        <p class="sales-report__columns-hint">
                            Columns: DATE, PASSENGER NAME, SECTOR, AIRLINE, PAYABLE, RECEIVED, VIA
                        </p>

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
                                wire:target="importSheet,importSheetReplacing,excelFile,existingCountForMonth"
                                @disabled(! $excelFile)
                            >
                                <span wire:loading.remove wire:target="importSheet,importSheetReplacing,existingCountForMonth">Import sheet</span>
                                <span wire:loading wire:target="importSheet,importSheetReplacing,existingCountForMonth">Working...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Summary cards --}}
        <div class="stat-grid" style="margin-bottom: 16px;">
            <article class="stat-card stat-card--blue">
                <div class="stat-card__top"><p class="stat-card__label">Total sales</p></div>
                <p class="stat-card__value">{{ $currency }} {{ number_format($totals['received'], 0) }}</p>
                <p class="stat-card__hint">Sum of Received</p>
            </article>
            <article class="stat-card stat-card--amber">
                <div class="stat-card__top"><p class="stat-card__label">Total payable</p></div>
                <p class="stat-card__value">{{ $currency }} {{ number_format($totals['payable'], 0) }}</p>
                <p class="stat-card__hint">Sum of Payable</p>
            </article>
            <article class="stat-card stat-card--green">
                <div class="stat-card__top"><p class="stat-card__label">Total profit</p></div>
                <p class="stat-card__value">{{ $currency }} {{ number_format($totals['profit'], 0) }}</p>
                <p class="stat-card__hint">{{ $totals['profit_filled'] }} of {{ $totals['entries'] }} filled</p>
            </article>
            <article class="stat-card stat-card--navy">
                <div class="stat-card__top"><p class="stat-card__label">Entries</p></div>
                <p class="stat-card__value">{{ number_format($totals['entries'], 0) }}</p>
                <p class="stat-card__hint">For selected month</p>
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
                            <th>Passenger</th>
                            <th>Sector</th>
                            <th>Airline</th>
                            <th>Payable</th>
                            <th>Received</th>
                            <th>Via</th>
                            <th>Profit</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($entries as $entry)
                            <tr wire:key="sale-entry-{{ $entry->id }}">
                                <td>{{ optional($entry->sale_date)->format('d-M-y') }}</td>
                                <td>{{ $entry->passenger_name }}</td>
                                <td>{{ $entry->sector ?: '—' }}</td>
                                <td>{{ $entry->airline ?: '—' }}</td>
                                <td>{{ number_format((float) $entry->payable, 0) }}</td>
                                <td>{{ number_format((float) $entry->received, 0) }}</td>
                                <td style="max-width: 160px; white-space: normal; word-break: break-word;">{{ $entry->via ?: '—' }}</td>
                                <td>
                                    @if ($editingId === $entry->id)
                                        <div class="sales-report__profit-edit">
                                            <input
                                                type="number"
                                                step="0.01"
                                                class="sales-report__profit-input"
                                                wire:model="edit_profit"
                                                wire:keydown.enter.prevent="saveProfit"
                                            >
                                            <button type="button" class="hero-btn hero-btn--primary" style="padding: 4px 10px; font-size: 0.75rem;" wire:click="saveProfit">Save</button>
                                            <button type="button" class="payment-actions__btn" wire:click="cancelEditProfit">Cancel</button>
                                        </div>
                                        @error('edit_profit')
                                            <div style="color:#b91c1c;font-size:12px;margin-top:4px;">{{ $message }}</div>
                                        @enderror
                                    @elseif ($entry->profit !== null)
                                        {{ number_format((float) $entry->profit, 0) }}
                                    @else
                                        <span style="color:#94a3b8;">—</span>
                                    @endif
                                </td>
                                <td class="manage-users-actions">
                                    @if ($editingId !== $entry->id)
                                        <button
                                            type="button"
                                            class="payment-actions__btn"
                                            wire:click="startEditProfit({{ $entry->id }})"
                                        >{{ $entry->profit !== null ? 'Edit profit' : 'Add profit' }}</button>
                                    @endif
                                    @if (auth()->user()->isAdmin())
                                        <button
                                            type="button"
                                            class="payment-actions__btn payment-actions__btn--danger"
                                            @click="$dispatch('confirm-delete-sales', {
                                                id: {{ $entry->id }},
                                                title: 'Delete sale entry?',
                                                message: 'This sales row will be permanently removed from the report.'
                                            })"
                                        >Delete</button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9">No sales for this month yet. Import an Excel sheet above.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <x-confirm-delete-modal event="confirm-delete-sales" />

        <x-confirm-delete-modal
            event="confirm-replace-sales"
            confirm-method="importSheetReplacing"
            confirm-label="Yes, replace"
            :pass-id="false"
        />

    @endif
</div>
