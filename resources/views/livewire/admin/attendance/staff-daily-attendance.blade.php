<div class="staff-att-page" wire:poll.10s.visible>
    @if ($denied ?? false)
        <div class="data-panel">
            <p>Only admin or super admin can view staff attendance.</p>
        </div>
    @else

        <section class="module-workspace__hero" style="margin-bottom: 16px;">
            <div class="module-workspace__hero-main">
                <p class="module-workspace__eyebrow">
                    <span class="module-workspace__eyebrow-dot"></span>
                    Attendance
                </p>
                <h2 class="module-workspace__title">Staff Attendance</h2>
                <p class="module-workspace__desc" style="margin: 8px 0 0; font-size: 0.9rem; opacity: 0.85;">
                    View any staff member’s daily check-in, late/early, leave, and holidays.
                </p>
            </div>
        </section>

        {{-- Filters --}}
        <div class="my-att-filter data-panel">
            <div class="my-att-filter__inner staff-att-filters">
                <div class="my-att-filter__group">
                    <label class="my-att-filter__label" for="staff-att-user">Staff</label>
                    <select
                        id="staff-att-user"
                        class="my-att-filter__input staff-att-filters__select"
                        wire:model.live="userId"
                    >
                        <option value="">Select staff…</option>
                        @foreach ($staffUsers as $staff)
                            <option value="{{ $staff->id }}">{{ $staff->name }} ({{ $staff->email }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="my-att-filter__group">
                    <label class="my-att-filter__label" for="staff-att-month">Month</label>
                    <input
                        id="staff-att-month"
                        type="month"
                        class="my-att-filter__input"
                        wire:model.live="month"
                    >
                </div>
            </div>
        </div>

        {{-- Table --}}
        <div class="data-panel">
            <div class="data-panel__head">
                <h3 class="data-panel__title">
                    @if ($selectedUser)
                        {{ $selectedUser->name }} — {{ $monthLabel }}
                    @else
                        Daily records
                    @endif
                </h3>
            </div>

            @if ($staffUsers->isEmpty())
                <p class="staff-att-empty">No staff users found.</p>
            @elseif (! $selectedUser)
                <p class="staff-att-empty">Select a staff member to view attendance.</p>
            @else
                @include('livewire.admin.attendance.partials.daily-attendance-table', ['rows' => $rows])
            @endif
        </div>
    @endif
</div>
