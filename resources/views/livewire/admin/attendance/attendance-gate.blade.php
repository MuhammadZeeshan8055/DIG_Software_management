<div class="att-checkin">
    <div class="att-checkin__orb att-checkin__orb--one" aria-hidden="true"></div>
    <div class="att-checkin__orb att-checkin__orb--two" aria-hidden="true"></div>

    <div class="att-checkin__card">
        <div class="att-checkin__shine" aria-hidden="true"></div>

        <div class="att-checkin__brand att-checkin__reveal" style="--d: 0ms">
            <img
                src="{{ asset('images/logo-icon.png') }}"
                alt="DHOTHAR"
                class="att-checkin__logo"
            >
            <div class="att-checkin__brand-text">
                <span class="att-checkin__brand-line att-checkin__brand-line--name">Dhothar</span>
                <span class="att-checkin__brand-line att-checkin__brand-line--group">International Group</span>
                <span class="att-checkin__brand-line att-checkin__brand-line--tag">Travels &amp; Tours (PVT LTD)</span>
            </div>
        </div>

        <div class="att-checkin__icon att-checkin__reveal" style="--d: 60ms" aria-hidden="true">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="4" width="18" height="18" rx="2"/>
                <line x1="16" y1="2" x2="16" y2="6"/>
                <line x1="8" y1="2" x2="8" y2="6"/>
                <line x1="3" y1="10" x2="21" y2="10"/>
                <path d="M9 16l2 2 4-4"/>
            </svg>
        </div>

        <h1 class="att-checkin__title att-checkin__reveal" style="--d: 100ms">Mark Today’s Attendance</h1>
        <p class="att-checkin__subtitle att-checkin__reveal" style="--d: 140ms">
            Welcome {{ $userName }}. Check in to record your office arrival.
        </p>

        @if ($errorMessage)
            <div
                class="att-checkin__error"
                x-data
                x-init="setTimeout(() => $wire.set('errorMessage', null), 5000)"
                role="alert"
            >{{ $errorMessage }}</div>
        @endif

        <div class="att-checkin__user att-checkin__reveal" style="--d: 180ms">
            <p class="att-checkin__role">{{ $roleLabel }}</p>
            <p class="att-checkin__name">{{ $userName }}</p>
            <p class="att-checkin__date">{{ $shortDate }}</p>
            <p
                class="att-checkin__clock"
                x-data="{
                    label: '',
                    tick() {
                        this.label = new Date().toLocaleTimeString([], {
                            hour: '2-digit',
                            minute: '2-digit',
                            second: '2-digit',
                            hour12: true
                        });
                    },
                    init() {
                        this.tick();
                        setInterval(() => this.tick(), 1000);
                    }
                }"
                x-text="label"
            ></p>
        </div>

        <button
            type="button"
            class="att-checkin__btn att-checkin__reveal"
            style="--d: 220ms"
            wire:click="startShift"
            wire:loading.attr="disabled"
        >
            <span class="att-checkin__btn-inner" wire:loading.remove wire:target="startShift">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M20 6L9 17l-5-5"/>
                </svg>
                Check In &amp; Enter Portal
            </span>
            <span wire:loading wire:target="startShift">Please wait…</span>
        </button>

        <form method="POST" action="{{ route('logout') }}" class="att-checkin__logout att-checkin__reveal" style="--d: 260ms">
            @csrf
            <button type="submit">Sign out</button>
        </form>

        <p class="att-checkin__footer att-checkin__reveal" style="--d: 300ms">
            Your exact office check-in time will be recorded for the attendance register.
        </p>
    </div>
</div>
