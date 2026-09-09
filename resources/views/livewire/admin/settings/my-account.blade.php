<div class="my-account-page">
    <section class="module-workspace__hero" style="margin-bottom: 16px;">
        <div class="module-workspace__hero-main">
            <p class="module-workspace__eyebrow">
                <span class="module-workspace__eyebrow-dot"></span>
                Account
            </p>
            <h2 class="module-workspace__title">My Account</h2>
            <p class="module-workspace__desc" style="margin: 8px 0 0; font-size: 0.9rem; opacity: 0.85;">
                Set your own email and password. Your actions in the system are tied to this login.
            </p>
        </div>
    </section>

    @if ($successMessage)
        <x-admin-toast wire-property="successMessage" :seconds="6">
            {{ $successMessage }}
        </x-admin-toast>
    @endif

    <div class="data-panel" style="max-width: 560px;">
        <div class="data-panel__head">
            <h3 class="data-panel__title">Email &amp; password</h3>
        </div>

        <form wire:submit="save" class="manage-users-form" style="padding: 16px;">
            <div class="manage-users-form__grid" style="grid-template-columns: 1fr;">
                <div class="mu-field">
                    <label class="mu-field__label" for="ma-name">Name</label>
                    <input id="ma-name" type="text" class="mu-field__input" wire:model="name" autocomplete="name">
                    @error('name') <span style="color:#b91c1c;font-size:12px;">{{ $message }}</span> @enderror
                </div>

                <div class="mu-field">
                    <label class="mu-field__label" for="ma-email">Email</label>
                    <input id="ma-email" type="email" class="mu-field__input" wire:model="email" autocomplete="username">
                    @error('email') <span style="color:#b91c1c;font-size:12px;">{{ $message }}</span> @enderror
                </div>

                <div class="mu-field">
                    <label class="mu-field__label" for="ma-current">Current password</label>
                    <input id="ma-current" type="password" class="mu-field__input" wire:model="current_password" autocomplete="current-password">
                    @error('current_password') <span style="color:#b91c1c;font-size:12px;">{{ $message }}</span> @enderror
                    <p style="margin: 6px 0 0; font-size: 12px; opacity: 0.7;">Required to confirm it is you.</p>
                </div>

                <div class="mu-field">
                    <label class="mu-field__label" for="ma-password">New password (optional)</label>
                    <input id="ma-password" type="password" class="mu-field__input" wire:model="password" autocomplete="new-password" placeholder="Leave blank to keep current password">
                    @error('password') <span style="color:#b91c1c;font-size:12px;">{{ $message }}</span> @enderror
                </div>

                <div class="mu-field">
                    <label class="mu-field__label" for="ma-password2">Confirm new password</label>
                    <input id="ma-password2" type="password" class="mu-field__input" wire:model="password_confirmation" autocomplete="new-password">
                </div>
            </div>

            <div style="margin-top: 16px;">
                <button type="submit" class="hero-btn hero-btn--primary" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="save">Save account</span>
                    <span wire:loading wire:target="save">Saving...</span>
                </button>
            </div>
        </form>
    </div>
</div>
