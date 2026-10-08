<div class="manage-desks-page" @desks-panel-opened.window="$wire.$refresh()">
    @if ($denied ?? false)
        <div class="data-panel">
            <p>Only admin or super admin can manage desks.</p>
        </div>
    @else

        <section class="module-workspace__hero" style="margin-bottom: 16px;">
            <div class="module-workspace__hero-main">
                <p class="module-workspace__eyebrow">
                    <span class="module-workspace__eyebrow-dot"></span>
                    Settings
                </p>
                <h2 class="module-workspace__title">Manage Desks</h2>
                
            </div>
        </section>

        @if ($successMessage)
            <x-admin-toast wire-property="successMessage" :seconds="6">
                {{ $successMessage }}
            </x-admin-toast>
        @endif

        @if ($errorMessage)
            <x-admin-toast type="error" title="Failed" wire-property="errorMessage" :seconds="6">
                {{ $errorMessage }}
            </x-admin-toast>
        @endif

        <div class="data-panel" style="margin-bottom: 16px;">
            <div class="data-panel__head">
                <h3 class="data-panel__title">Add desk</h3>
            </div>
            <form wire:submit="addDesk" class="manage-users-form" style="padding: 16px;">
                <div class="manage-users-form__grid">
                    <div class="mu-field">
                        <label class="mu-field__label" for="desk-name">Desk name</label>
                        <input
                            id="desk-name"
                            type="text"
                            class="mu-field__input"
                            wire:model="name"
                            placeholder="e.g. Insurance Desk"
                            maxlength="80"
                        >
                        @error('name') <span style="color:#b91c1c;font-size:12px;">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div style="margin-top: 14px;">
                    <button type="submit" class="hero-btn hero-btn--primary" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="addDesk">Add desk</span>
                        <span wire:loading wire:target="addDesk">Saving...</span>
                    </button>
                </div>
            </form>
        </div>

        <div class="data-panel">
            <div class="data-panel__head">
                <h3 class="data-panel__title">All desks</h3>
                <span class="add-account__count">{{ $desks->count() }} total</span>
            </div>
            <div class="data-table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($desks as $desk)
                            <tr wire:key="desk-{{ $desk->id }}">
                                <td>{{ $desk->name }}</td>
                                <td>
                                    @if ($desk->is_active)
                                        <span class="payment-badge payment-badge--paid">Active</span>
                                    @else
                                        <span class="payment-badge payment-badge--draft">Off</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($desk->is_active)
                                        <button
                                            type="button"
                                            class="payment-actions__btn"
                                            wire:click="turnOff({{ $desk->id }})"
                                            wire:confirm="Turn off this desk?"
                                        >Turn off</button>
                                    @else
                                        <button
                                            type="button"
                                            class="payment-actions__btn"
                                            wire:click="turnOn({{ $desk->id }})"
                                        >Turn on</button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3">No desks yet. Add one above.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    @endif
</div>
