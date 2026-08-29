<div class="receiving-accounts-page" @bank-accounts-panel-opened.window="$wire.$refresh()">
    <section class="module-workspace__hero" style="margin-bottom: 16px;">
        <div class="module-workspace__hero-main">
            <p class="module-workspace__eyebrow">
                <span class="module-workspace__eyebrow-dot"></span>
                Accounts
            </p>
            <h2 class="module-workspace__title">Bank &amp; Payment Accounts</h2>
        </div>
    </section>

    @if ($successMessage)
        <x-admin-alert type="success" wire-property="successMessage">
            {{ $successMessage }}
        </x-admin-alert>
    @endif

    <div class="data-panel add-account-panel" style="margin-bottom: 16px;">
        <div class="data-panel__head">
            <h3 class="data-panel__title">Add Account</h3>
        </div>

        <form wire:submit="addAccount" class="add-account" wire:ignore x-data="{ method: @js($method) }">
            <div class="add-account__section">
                <p class="add-account__label">Account Type</p>
                <div class="add-account__methods">
                    @foreach ($methods as $value => $label)
                        <button
                            type="button"
                            @click="method = '{{ $value }}'; $wire.set('method', '{{ $value }}', false)"
                            :class="{ 'add-account__method--active': method === '{{ $value }}' }"
                            class="add-account__method"
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="add-account__row">
                <label class="add-account__field">
                    <span>Account Name</span>
                    <input
                        type="text"
                        wire:model="name"
                        class="add-account__input"
                        placeholder="e.g. HBL Main Account"
                    >
                    @error('name')
                        <p class="import-ticket__error">{{ $message }}</p>
                    @enderror
                </label>

                <button type="submit" class="hero-btn hero-btn--primary add-account__submit" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="addAccount">Add Account</span>
                    <span wire:loading wire:target="addAccount">Adding...</span>
                </button>
            </div>
        </form>
    </div>

    <div class="data-panel">
        <div class="data-panel__head">
            <h3 class="data-panel__title">All Accounts</h3>
            <span class="add-account__count">{{ $accounts->count() }} total</span>
        </div>

        <div class="data-table-wrap">
            <table class="data-table receiving-accounts-table">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Account Name</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($accounts as $account)
                        <tr wire:key="receiving-account-{{ $account->id }}">
                            <td>
                                <span @class(['method-badge', 'method-badge--'.strtolower($account->method)])>
                                    {{ $account->methodLabel() }}
                                </span>
                            </td>
                            <td class="receiving-accounts-table__name">{{ $account->name }}</td>
                            <td>
                                <button
                                    type="button"
                                    class="payment-actions__btn payment-actions__btn--danger"
                                    wire:click="deleteAccount({{ $account->id }})"
                                    wire:confirm="Delete this account?"
                                >Delete</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="receiving-accounts-table__empty">No accounts yet. Add one above.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
