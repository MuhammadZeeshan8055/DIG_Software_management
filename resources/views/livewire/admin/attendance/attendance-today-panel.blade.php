<aside class="dashboard-hero__panel dashboard-reveal" style="--reveal-delay: 120ms">
    <p class="dashboard-hero__panel-label">Current Workday</p>
    <p class="dashboard-hero__panel-date">{{ $todayLabel }}</p>

    @if ($successMessage)
        <p
            class="att-panel__flash att-panel__flash--ok"
            x-data
            x-init="setTimeout(() => $wire.set('successMessage', null), 4000)"
        >{{ $successMessage }}</p>
    @endif

    @if ($errorMessage)
        <p
            class="att-panel__flash att-panel__flash--err"
            x-data
            x-init="setTimeout(() => $wire.set('errorMessage', null), 5000)"
        >{{ $errorMessage }}</p>
    @endif

    <ul class="dashboard-hero__panel-list">
        <li>
            <span>Check-in</span>
            <strong>{{ $checkInLabel }}</strong>
        </li>
        <li>
            <span>Check-out</span>
            <strong>{{ $checkOutLabel }}</strong>
        </li>
        <li>
            <span>Worked</span>
            @if ($isRunning && $checkInAtMs)
                <strong
                    wire:key="worked-running-{{ $checkInAtMs }}"
                    x-data="{
                        start: {{ $checkInAtMs }},
                        label: '—',
                        tick() {
                            const total = Math.max(0, Math.floor((Date.now() - this.start) / 1000));
                            const h = Math.floor(total / 3600);
                            const m = Math.floor((total % 3600) / 60);
                            const s = total % 60;
                            this.label = h + 'h ' + m + 'm ' + s + 's (running)';
                        },
                        init() {
                            this.tick();
                            this._timer = setInterval(() => this.tick(), 1000);
                        },
                        destroy() {
                            clearInterval(this._timer);
                        }
                    }"
                    x-text="label"
                ></strong>
            @else
                <strong wire:key="worked-done">{{ $workedLabel }}</strong>
            @endif
        </li>
    </ul>

    <div class="att-panel__actions">
        @if ($canStart)
            <button
                type="button"
                class="att-panel__btn att-panel__btn--start"
                wire:click="startShift"
                wire:loading.attr="disabled"
            >
                <span wire:loading.remove wire:target="startShift">Start Shift</span>
                <span wire:loading wire:target="startShift">Saving…</span>
            </button>
        @elseif ($canEnd)
            <button
                type="button"
                class="att-panel__btn att-panel__btn--end"
                wire:click="askEndShift"
                wire:loading.attr="disabled"
            >
                End Shift
            </button>
        @else
            <p class="att-panel__done">Shift complete for today</p>
        @endif
    </div>

    {{-- Confirm before ending (blocks accidental double-click after Start) --}}
    @if ($showEndConfirm)
        <div class="att-end-confirm" role="dialog" aria-modal="true" aria-labelledby="att-end-title">
            <button type="button" class="att-end-confirm__backdrop" wire:click="cancelEndShift" aria-label="Cancel"></button>
            <div class="att-end-confirm__card">
                <p id="att-end-title" class="att-end-confirm__title">End your shift?</p>
                <p class="att-end-confirm__text">This will check you out for today. You can’t undo it.</p>
                <div class="att-end-confirm__actions">
                    <button
                        type="button"
                        class="att-panel__btn att-panel__btn--ghost"
                        wire:click="cancelEndShift"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        class="att-panel__btn att-panel__btn--start"
                        wire:click="endShift"
                        wire:loading.attr="disabled"
                    >
                        <span wire:loading.remove wire:target="endShift">Yes, end shift</span>
                        <span wire:loading wire:target="endShift">Saving…</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</aside>
