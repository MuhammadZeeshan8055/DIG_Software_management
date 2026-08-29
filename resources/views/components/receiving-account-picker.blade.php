@props([
    'methods',
    'allAccounts',
    'selectedMethod',
    'selectedAccountId' => null,
    'methodWire' => 'payment_method',
    'accountWire' => 'receiving_account_id',
    'embedded' => false,
])

@if (! $embedded)
<div
    class="account-picker"
    wire:ignore
    x-data="{
        method: @js($selectedMethod),
        accountId: @js($selectedAccountId),
        accounts: @js($allAccounts),
        get filtered() {
            return this.accounts.filter(a => a.method === this.method)
        },
        selectMethod(key) {
            this.method = key
            const first = this.accounts.find(a => a.method === key)
            this.accountId = first ? first.id : null
            $wire.set('{{ $methodWire }}', key, false)
            $wire.set('{{ $accountWire }}', this.accountId, false)
        },
        selectAccount(id) {
            this.accountId = id
            $wire.set('{{ $accountWire }}', id, false)
        },
        isSelected(id) {
            return Number(this.accountId) === Number(id)
        }
    }"
>
@else
<div class="account-picker">
@endif
    <p class="account-picker__label">Payment Method</p>
    <div class="account-picker__methods">
        @foreach ($methods as $key => $label)
            <button
                type="button"
                @click="selectMethod('{{ $key }}')"
                :class="{ 'account-picker__method--active': method === '{{ $key }}' }"
                class="account-picker__method"
            >
                {{ $label }}
            </button>
        @endforeach
    </div>

    <p class="account-picker__label">Select Account</p>
    <div class="account-picker__accounts">
        <template x-for="account in filtered" :key="account.id">
            <button
                type="button"
                @click="selectAccount(account.id)"
                class="account-picker__card"
                :class="{ 'account-picker__card--selected': isSelected(account.id) }"
            >
                <span class="account-picker__tick" x-show="isSelected(account.id)" x-cloak aria-hidden="true">✓</span>
                <span class="account-picker__card-type" x-text="account.type"></span>
                <strong class="account-picker__card-name" x-text="account.name"></strong>
            </button>
        </template>

        <div class="account-picker__empty" x-show="filtered.length === 0" x-cloak>
            No accounts for this type. Add in Accounts → Bank Accounts.
        </div>
    </div>

    @if (! $embedded)
        @error($accountWire)
            <p class="import-ticket__error">{{ $message }}</p>
        @enderror
    @endif
</div>
