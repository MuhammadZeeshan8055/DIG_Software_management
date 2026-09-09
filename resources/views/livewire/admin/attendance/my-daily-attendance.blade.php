<div class="my-att-page" wire:poll.10s.visible @my-attendance-opened.window="$wire.$refresh()">
    <section class="module-workspace__hero" style="margin-bottom: 16px;">
        <div class="module-workspace__hero-main">
            <p class="module-workspace__eyebrow">
                <span class="module-workspace__eyebrow-dot"></span>
                My attendance
            </p>
            <h2 class="module-workspace__title">My Daily Attendance</h2>
        </div>
    </section>

    <div class="my-att-filter data-panel">
        <div class="my-att-filter__inner">
            <div class="my-att-filter__group">
                <label class="my-att-filter__label" for="my-att-month">Month</label>
                <input
                    id="my-att-month"
                    type="month"
                    class="my-att-filter__input"
                    wire:model.live="month"
                >
            </div>
        </div>
    </div>

    <div class="data-panel">
        <div class="data-panel__head">
            <h3 class="data-panel__title">Daily records — {{ $monthLabel }}</h3>
        </div>
        @include('livewire.admin.attendance.partials.daily-attendance-table', ['rows' => $rows])
    </div>
</div>
