<div class="payment-entries-page" @payments-panel-opened.window="$wire.$refresh()">
    <section class="module-workspace__hero" style="margin-bottom: 16px;">
        <div class="module-workspace__hero-main">
            <p class="module-workspace__eyebrow">
                <span class="module-workspace__eyebrow-dot"></span>
                Accounts
            </p>
            <h2 class="module-workspace__title">Payments</h2>
        </div>
    </section>

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

    @if ($editingId)
        <div
            class="data-panel import-ticket__payment"
            style="margin-bottom: 16px;"
            wire:key="edit-panel-{{ $editingId }}"
            x-data="{
                agreed: @js($edit_amount_agreed),
                paid: @js($edit_amount_paid),
                paymentStatus: @js($edit_payment_status),
                method: @js($edit_payment_method),
                accountId: @js($edit_receiving_account_id),
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
                saveChanges() {
                    $wire.saveEditWithLedger(this.agreed, this.paid, this.paymentStatus, this.method, this.accountId)
                }
            }"
        >
            <div class="data-panel__head">
                <h3 class="data-panel__title">Edit Payment</h3>
            </div>

            <div wire:ignore>
                <div class="import-ticket__ledger-grid">
                    <label class="import-ticket__ledger-field">
                        <span>Amount Agreed ({{ $currency }})</span>
                        <input type="text" inputmode="numeric" x-model="agreed" @input="cleanAmount('agreed')" class="import-ticket__input">
                    </label>

                    <label class="import-ticket__ledger-field">
                        <span>Amount Paid ({{ $currency }})</span>
                        <input type="text" inputmode="numeric" x-model="paid" @input="cleanAmount('paid')" class="import-ticket__input" placeholder="0">
                    </label>

                    <div class="import-ticket__ledger-field import-ticket__ledger-field--balance">
                        <span>Balance ({{ $currency }})</span>
                        <strong x-text="formatAmount(balance)">0</strong>
                        <small>Agreed − Paid</small>
                    </div>
                </div>

                <label class="import-ticket__ledger-status">
                    <span>Payment Status</span>
                    <select x-model="paymentStatus" @change="statusManual = true" class="import-ticket__select">
                        @foreach ($paymentStatuses as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <x-receiving-account-picker
                    embedded
                    :methods="$paymentMethods"
                    :all-accounts="$allReceivingAccounts"
                    :selected-method="$edit_payment_method"
                    :selected-account-id="$edit_receiving_account_id"
                    method-wire="edit_payment_method"
                    account-wire="edit_receiving_account_id"
                />
            </div>

            <x-validation-errors class="import-ticket__validation-errors" />

            <div class="import-ticket__actions" style="margin-top: 14px;">
                <button type="button" @click="saveChanges()" wire:loading.attr="disabled" class="hero-btn hero-btn--primary">
                    <span wire:loading.remove wire:target="saveEditWithLedger">Save Changes</span>
                    <span wire:loading wire:target="saveEditWithLedger">Saving...</span>
                </button>
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
