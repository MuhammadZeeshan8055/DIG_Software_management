<div class="office-settings-page">
    @if ($denied ?? false)
        <div class="data-panel">
            <p>You do not have access to office settings.</p>
        </div>
    @else

        <div class="data-panel">
            <div class="data-panel__head">
                <h3 class="data-panel__title">Office settings</h3>
            </div>

            @if ($successMessage)
                <x-admin-toast wire-property="successMessage" :seconds="6">
                    {{ $successMessage }}
                </x-admin-toast>
            @endif

            <form wire:submit="save" class="manage-users-form" style="padding: 16px;">
                <div class="office-settings-layout">

                    {{-- LEFT ≈ col-8 — hours, IPs, grace --}}
                    <div class="office-settings-layout__main">
                        <h4 class="data-panel__title" style="margin: 0 0 12px;">Hours & office IPs</h4>

                        <div class="manage-users-form__grid">
                            <div class="mu-field">
                                <label class="mu-field__label" for="os-start">Office start</label>
                                <input id="os-start" type="time" class="mu-field__input" wire:model="office_start">
                                @error('office_start') <span style="color:#b91c1c;font-size:12px;">{{ $message }}</span> @enderror
                            </div>

                            <div class="mu-field">
                                <label class="mu-field__label" for="os-end">Office end</label>
                                <input id="os-end" type="time" class="mu-field__input" wire:model="office_end">
                                @error('office_end') <span style="color:#b91c1c;font-size:12px;">{{ $message }}</span> @enderror
                            </div>

                            <div class="mu-field">
                                <label class="mu-field__label" for="os-required">Required hours</label>
                                <input id="os-required" type="number" step="0.5" min="1" max="24" class="mu-field__input" wire:model="required_hours">
                                @error('required_hours') <span style="color:#b91c1c;font-size:12px;">{{ $message }}</span> @enderror
                            </div>

                            <div class="mu-field">
                                <label class="mu-field__label" for="os-break">Break minutes</label>
                                <input id="os-break" type="number" min="0" max="240" class="mu-field__input" wire:model="break_minutes">
                                @error('break_minutes') <span style="color:#b91c1c;font-size:12px;">{{ $message }}</span> @enderror
                            </div>

                            <div class="mu-field">
                                <label class="mu-field__label" for="os-lunch-start">Lunch start</label>
                                <input id="os-lunch-start" type="time" class="mu-field__input" wire:model="lunch_start">
                                @error('lunch_start') <span style="color:#b91c1c;font-size:12px;">{{ $message }}</span> @enderror
                            </div>

                            <div class="mu-field">
                                <label class="mu-field__label" for="os-lunch-end">Lunch end</label>
                                <input id="os-lunch-end" type="time" class="mu-field__input" wire:model="lunch_end">
                                @error('lunch_end') <span style="color:#b91c1c;font-size:12px;">{{ $message }}</span> @enderror
                            </div>

                            <div class="mu-field">
                                <label class="mu-field__label" for="os-ip1">Office IP 1</label>
                                <input id="os-ip1" type="text" class="mu-field__input" wire:model="office_ip_1" placeholder="e.g. 192.168.1.10">
                                @error('office_ip_1') <span style="color:#b91c1c;font-size:12px;">{{ $message }}</span> @enderror
                            </div>

                            <div class="mu-field">
                                <label class="mu-field__label" for="os-ip2">Office IP 2</label>
                                <input id="os-ip2" type="text" class="mu-field__input" wire:model="office_ip_2" placeholder="Optional">
                                @error('office_ip_2') <span style="color:#b91c1c;font-size:12px;">{{ $message }}</span> @enderror
                            </div>

                            <div class="mu-field">
                                <label class="mu-field__label" for="os-ip3">Office IP 3</label>
                                <input id="os-ip3" type="text" class="mu-field__input" wire:model="office_ip_3" placeholder="Optional">
                                @error('office_ip_3') <span style="color:#b91c1c;font-size:12px;">{{ $message }}</span> @enderror
                            </div>

                            <div class="mu-field">
                                <label class="mu-field__label" for="os-grace-minutes">Grace minutes</label>
                                <input id="os-grace-minutes" type="number" min="0" max="60" class="mu-field__input" wire:model="grace_minutes">
                                @error('grace_minutes') <span style="color:#b91c1c;font-size:12px;">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>

                    {{-- RIGHT ≈ col-4 — leave allowance card --}}
                    <div class="office-settings-layout__side">
                        <h4 class="data-panel__title" style="margin: 0 0 8px;">Leave allowance</h4>
                        <p style="margin: 0 0 14px; font-size: 13px; color: #6b7280;">
                            Same for every staff member each month.
                        </p>

                        <div class="mu-field" style="margin-bottom: 14px;">
                            <label class="mu-field__label" for="os-leave-full">Full days allowed</label>
                            <input id="os-leave-full" type="number" min="0" max="31" class="mu-field__input" wire:model="monthly_full_days">
                            @error('monthly_full_days') <span style="color:#b91c1c;font-size:12px;">{{ $message }}</span> @enderror
                        </div>

                        <div class="mu-field">
                            <label class="mu-field__label" for="os-leave-half">Half days allowed</label>
                            <input id="os-leave-half" type="number" min="0" max="62" class="mu-field__input" wire:model="monthly_half_days">
                            @error('monthly_half_days') <span style="color:#b91c1c;font-size:12px;">{{ $message }}</span> @enderror
                        </div>
                    </div>

                </div>

                <div style="margin-top: 16px;">
                    <button type="submit" class="hero-btn hero-btn--primary" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="save">Save settings</span>
                        <span wire:loading wire:target="save">Saving…</span>
                    </button>
                </div>
            </form>
        </div>
    @endif
</div>
