<div class="payment-entries-page" @payments-panel-opened.window="$wire.$refresh()">
    <section class="module-workspace__hero" style="margin-bottom: 16px;">
        <div class="module-workspace__hero-main">
            <p class="module-workspace__eyebrow">
                <span class="module-workspace__eyebrow-dot"></span>
                Accounts
            </p>
            <h2 class="module-workspace__title">Payments</h2>
            <p class="module-workspace__desc">Update agreed, paid, and balance when client pays again.</p>
        </div>
    </section>

    @if ($successMessage)
        <x-admin-alert type="success" wire-property="successMessage">
            {{ $successMessage }}
        </x-admin-alert>
    @endif

    @if ($editingId)
        <div class="data-panel import-ticket__payment" style="margin-bottom: 16px;">
            <div class="data-panel__head">
                <h3 class="data-panel__title">Edit Payment</h3>
            </div>

            <div class="import-ticket__ledger-grid">
                <label class="import-ticket__ledger-field">
                    <span>Amount Agreed ({{ $currency }})</span>
                    <input type="number" min="0" step="1" wire:model.live="edit_amount_agreed" class="import-ticket__input">
                    @error('edit_amount_agreed')
                        <p class="import-ticket__error">{{ $message }}</p>
                    @enderror
                </label>

                <label class="import-ticket__ledger-field">
                    <span>Amount Paid ({{ $currency }})</span>
                    <input type="number" min="0" step="1" wire:model.live="edit_amount_paid" class="import-ticket__input">
                    @error('edit_amount_paid')
                        <p class="import-ticket__error">{{ $message }}</p>
                    @enderror
                </label>

                <div class="import-ticket__ledger-field import-ticket__ledger-field--balance">
                    <span>Balance ({{ $currency }})</span>
                    <strong>{{ number_format($this->editBalance, 0) }}</strong>
                    <small>Agreed − Paid</small>
                </div>
            </div>

            <label class="import-ticket__ledger-status">
                <span>Payment Status</span>
                <select wire:model="edit_payment_status" class="import-ticket__select">
                    @foreach ($paymentStatuses as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </label>

            <div class="import-ticket__actions" style="margin-top: 14px;">
                <button type="button" wire:click="saveEdit" class="hero-btn hero-btn--primary">Save Changes</button>
                <button type="button" wire:click="cancelEdit" class="hero-btn hero-btn--secondary">Cancel</button>
            </div>
        </div>
    @endif

    <x-payment-entries-table
        :entries="$entries"
        :ledger-totals="$ledgerTotals"
        :payment-statuses="$paymentStatuses"
        :show-actions="true"
        title="Payments"
    />
</div>
