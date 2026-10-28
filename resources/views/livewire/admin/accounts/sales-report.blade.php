<div
    class="sales-report-page"
    @sales-report-panel-opened.window="$wire.$refresh()"
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

        <section class="module-workspace__hero" style="margin-bottom: 16px;">
            <div class="module-workspace__hero-main">
                <p class="module-workspace__eyebrow">
                    <span class="module-workspace__eyebrow-dot"></span>
                    Accounts
                </p>
                <h2 class="module-workspace__title">Sales Report</h2>
            </div>
        </section>

        {{-- Month filter + import --}}
        <div class="data-panel" style="margin-bottom: 16px;">
            <div class="data-panel__head">
                <h3 class="data-panel__title">Import monthly sales</h3>
            </div>

            <form wire:submit="importSheet" class="sales-report__import">
                <div class="sales-report__import-row">
                    <label class="sales-report__field">
                        <span>Report month</span>
                        <input type="month" class="sales-report__input" wire:model.live="report_month">
                    </label>

                    <label class="sales-report__field sales-report__field--file">
                        <span>Excel file</span>
                        <input
                            type="file"
                            accept=".xlsx,.xls,.csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel,text/csv"
                            wire:model="excelFile"
                            class="sales-report__file"
                        >
                        @if ($excelFile)
                            <span class="sales-report__file-name">{{ $excelFile->getClientOriginalName() }}</span>
                        @else
                            <span class="sales-report__hint">Columns: DATE, PASSENGER NAME, SECTOR, AIRLINE, PAYABLE, RECEIVED, VIA</span>
                        @endif
                    </label>

                    <button
                        type="submit"
                        class="hero-btn hero-btn--primary"
                        wire:loading.attr="disabled"
                        wire:target="importSheet,excelFile"
                    >
                        <span wire:loading.remove wire:target="importSheet">Import</span>
                        <span wire:loading wire:target="importSheet">Importing...</span>
                    </button>
                </div>

                <x-validation-errors class="import-ticket__validation-errors" />
            </form>
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

    @endif
</div>
