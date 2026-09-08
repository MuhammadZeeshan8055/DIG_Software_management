<div class="apply-leave-page">
    @if ($denied ?? false)
        <div class="data-panel">
            <p>You do not have access to apply leave.</p>
        </div>
    @else

        <section class="module-workspace__hero" style="margin-bottom: 16px;">
            <div class="module-workspace__hero-main">
                <p class="module-workspace__eyebrow">
                    <span class="module-workspace__eyebrow-dot"></span>
                    Leave
                </p>
                <h2 class="module-workspace__title">Apply Leave</h2>
                <p class="module-workspace__desc" style="margin: 8px 0 0; font-size: 0.9rem; opacity: 0.85;">
                    Submit a request. Balance changes only after admin approval.
                </p>
            </div>
        </section>

        @if ($successMessage)
            <x-admin-toast wire-property="successMessage" :seconds="6">
                {{ $successMessage }}
            </x-admin-toast>
        @endif

        <div class="data-panel">
            <div class="data-panel__head">
                <h3 class="data-panel__title">New request</h3>
            </div>

            <form wire:submit="submit" class="manage-users-form apply-leave-form" style="padding: 16px;">
                <div class="manage-users-form__grid apply-leave-form__grid">
                    <div class="mu-field">
                        <label class="mu-field__label" for="al-type">Leave type</label>
                        <select id="al-type" class="mu-field__input" wire:model.live="leave_type">
                            <option value="full">Full day</option>
                            <option value="half">Half day</option>
                        </select>
                        @error('leave_type') <span style="color:#b91c1c;font-size:12px;">{{ $message }}</span> @enderror
                    </div>

                    {{-- One calendar: single day (half) or date range (full) --}}
                    <div class="mu-field" wire:key="leave-calendar-{{ $leave_type }}">
                        <label class="mu-field__label" for="al-calendar">
                            {{ $leave_type === 'half' ? 'Select date' : 'Select date range' }}
                        </label>
                        <div
                            class="leave-calendar"
                            wire:ignore
                            x-data
                            x-init="
                                const mode = @js($leave_type === 'half' ? 'single' : 'range');
                                const defaults = @js(
                                    $leave_type === 'half'
                                        ? $from_date
                                        : array_values(array_filter([$from_date, $to_date]))
                                );

                                flatpickr($refs.calInput, {
                                    mode: mode,
                                    dateFormat: 'Y-m-d',
                                    altInput: true,
                                    altFormat: 'd M Y',
                                    allowInput: false,
                                    defaultDate: defaults,
                                    showMonths: mode === 'range' ? 1 : 1,
                                    onChange(selectedDates) {
                                        if (! selectedDates.length) {
                                            return;
                                        }

                                        const fmt = (d) => flatpickr.formatDate(d, 'Y-m-d');

                                        if (mode === 'single') {
                                            const day = fmt(selectedDates[0]);
                                            $wire.set('from_date', day);
                                            $wire.set('to_date', day);
                                            return;
                                        }

                                        // Full-day range: 1 click = that day only; 2 clicks = from–to
                                        const start = fmt(selectedDates[0]);
                                        const end = selectedDates.length === 2
                                            ? fmt(selectedDates[1])
                                            : start;

                                        $wire.set('from_date', start);
                                        $wire.set('to_date', end);
                                    }
                                });
                            "
                        >
                            <input
                                id="al-calendar"
                                x-ref="calInput"
                                type="text"
                                class="mu-field__input leave-calendar__input"
                                placeholder="{{ $leave_type === 'half' ? 'Pick a date' : 'Pick from – to dates' }}"
                                readonly
                            >
                        </div>
                        @error('from_date') <span style="color:#b91c1c;font-size:12px;">{{ $message }}</span> @enderror
                        @error('to_date') <span style="color:#b91c1c;font-size:12px;">{{ $message }}</span> @enderror
                        @if ($leave_type === 'full' && $from_date && $to_date)
                            <p class="leave-calendar__hint">
                                Selected:
                                <strong>{{ \Carbon\Carbon::parse($from_date)->format('d M Y') }}</strong>
                                –
                                <strong>{{ \Carbon\Carbon::parse($to_date)->format('d M Y') }}</strong>
                            </p>
                        @endif
                    </div>

                    <div class="mu-field">
                        <label class="mu-field__label" for="al-reason">Reason (optional)</label>
                        <input id="al-reason" type="text" class="mu-field__input" wire:model="reason" placeholder="e.g. Personal work" maxlength="255">
                        @error('reason') <span style="color:#b91c1c;font-size:12px;">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div style="margin-top: 16px;">
                    <button type="submit" class="hero-btn hero-btn--primary" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="submit">Submit request</span>
                        <span wire:loading wire:target="submit">Submitting…</span>
                    </button>
                </div>
            </form>
        </div>

        <div class="data-panel" style="margin-top: 16px;">
            <div class="data-panel__head">
                <h3 class="data-panel__title">My leave requests</h3>
            </div>
            <div class="data-table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Dates</th>
                            <th>Days</th>
                            <th>Status</th>
                            <th>Submitted</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($requests as $req)
                            <tr>
                                <td>{{ $req->leave_type === 'half' ? 'Half day' : 'Full day' }}</td>
                                <td>
                                    @if ($req->leave_type === 'half' || $req->from_date->equalTo($req->to_date))
                                        {{ format_date($req->from_date, 'd M Y') }}
                                    @else
                                        {{ format_date($req->from_date, 'd M Y') }}
                                        –
                                        {{ format_date($req->to_date, 'd M Y') }}
                                    @endif
                                </td>
                                <td>
                                    @if ($req->leave_type === 'half')
                                        {{ $req->half_days }} half
                                    @else
                                        {{ $req->full_days }} full
                                    @endif
                                </td>
                                <td>{{ ucfirst($req->status) }}</td>
                                <td>{{ format_datetime($req->created_at, 'd M Y, h:i A') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">No leave requests yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
