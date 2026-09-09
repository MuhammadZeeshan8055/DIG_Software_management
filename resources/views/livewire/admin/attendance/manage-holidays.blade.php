<div class="manage-holidays-page" wire:poll.10s.visible>
    @if ($denied ?? false)
        <div class="data-panel">
            <p>Only admin or super admin can manage holidays.</p>
        </div>
    @else

        <section class="module-workspace__hero" style="margin-bottom: 16px;">
            <div class="module-workspace__hero-main">
                <p class="module-workspace__eyebrow">
                    <span class="module-workspace__eyebrow-dot"></span>
                    Attendance
                </p>
                <h2 class="module-workspace__title">Holidays</h2>
                <p class="module-workspace__desc" style="margin: 8px 0 0; font-size: 0.9rem; opacity: 0.85;">
                    Public holidays for the office. Sundays are already off — you do not need to add them.
                </p>
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
                <h3 class="data-panel__title">Add holiday</h3>
            </div>
            <form wire:submit="addHoliday" class="manage-users-form" style="padding: 16px;">
                <div class="manage-users-form__grid">
                    <div class="mu-field">
                        <label class="mu-field__label" for="hol-date">Date</label>
                        <input id="hol-date" type="date" class="mu-field__input" wire:model="date">
                        @error('date') <span style="color:#b91c1c;font-size:12px;">{{ $message }}</span> @enderror
                    </div>
                    <div class="mu-field">
                        <label class="mu-field__label" for="hol-title">Title</label>
                        <input id="hol-title" type="text" class="mu-field__input" wire:model="title" placeholder="e.g. Independence Day" maxlength="120">
                        @error('title') <span style="color:#b91c1c;font-size:12px;">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div style="margin-top: 14px;">
                    <button type="submit" class="hero-btn hero-btn--primary" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="addHoliday">Add holiday</span>
                        <span wire:loading wire:target="addHoliday">Saving...</span>
                    </button>
                </div>
            </form>
        </div>

        <div class="data-panel">
            <div class="data-panel__head">
                <h3 class="data-panel__title">Saved holidays</h3>
            </div>
            <div class="data-table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Title</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($holidays as $holiday)
                            <tr wire:key="holiday-{{ $holiday->id }}">
                                <td>{{ format_date($holiday->date, 'd M Y') }}</td>
                                <td>{{ $holiday->title }}</td>
                                <td>
                                    <button
                                        type="button"
                                        class="payment-actions__btn payment-actions__btn--danger"
                                        wire:click="deleteHoliday({{ $holiday->id }})"
                                        wire:confirm="Remove this holiday?"
                                    >Delete</button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3">No holidays yet. Add one above.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
