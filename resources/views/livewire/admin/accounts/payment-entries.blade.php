<div class="payment-entries-page" @payments-panel-opened.window="$wire.$refresh()">
    <section class="module-workspace__hero" style="margin-bottom: 16px;">
        <div class="module-workspace__hero-main">
            <p class="module-workspace__eyebrow">
                <span class="module-workspace__eyebrow-dot"></span>
                Accounts
            </p>
            <h2 class="module-workspace__title">Payments</h2>
            <p class="module-workspace__desc">All payment entries from ticket imports.</p>
        </div>
    </section>

    @if ($successMessage)
        <x-admin-alert type="success" wire-property="successMessage">
            {{ $successMessage }}
        </x-admin-alert>
    @endif

    <x-payment-entries-table
        :entries="$entries"
        :ledger-totals="$ledgerTotals"
        :payment-statuses="$paymentStatuses"
        :show-actions="true"
        title="Payments"
        hint="All payment entries saved from ticket imports. Edit coming soon — delete works now."
    />
</div>
