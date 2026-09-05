<header class="admin-header">
    <div class="admin-header__left">
        <button type="button" class="admin-header__menu" @click="sidebarOpen = !sidebarOpen" aria-label="Toggle menu">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="3" y1="6" x2="21" y2="6"/>
                <line x1="3" y1="12" x2="21" y2="12"/>
                <line x1="3" y1="18" x2="21" y2="18"/>
            </svg>
        </button>

        <div class="admin-header__titles">
            <p class="admin-header__breadcrumb">
                <span x-text="viewingMyAttendance
                    ? 'Employee Portal / My Attendance'
                    : (activeModule
                        ? (activeOption ? 'Employee Portal / Module / Option' : 'Employee Portal / Module')
                        : '{{ implode(' / ', $breadcrumb ?? ['Employee Portal']) }}')"></span>
            </p>
            <h1 class="admin-header__title">
                <span x-show="viewingMyAttendance || (activeModule === 'attendance' && activeOption === 'my-daily-attendance')" x-cloak>My Daily Attendance</span>
                <span x-show="!activeModule && !viewingMyAttendance">{{ $pageTitle ?? 'Dashboard' }}</span>
                <span
                    x-show="activeModule && !viewingMyAttendance && activeOption !== 'my-daily-attendance'"
                    x-cloak
                    x-text="currentTable()?.title || currentModule()?.title || 'Module'"
                ></span>
            </h1>
        </div>
    </div>

    <div class="admin-header__search">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8"/>
            <line x1="21" y1="21" x2="16.65" y2="16.65"/>
        </svg>
        <input type="search" placeholder="Search candidates, invoices, quotations, cases…" disabled>
    </div>

    <div class="admin-header__right">
        <button type="button" class="admin-header__icon-btn" title="Notifications" aria-label="Notifications">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
            </svg>
            <span class="admin-header__icon-badge">3</span>
        </button>

        <span class="admin-header__badge" title="Staff Access">
            <span class="admin-header__badge-dot"></span>
            <span class="admin-header__badge-text">Staff Access</span>
        </span>

        <div
            class="admin-header__profile"
            x-data="{ open: false }"
            @click.outside="open = false"
            @keydown.escape.window="open = false"
        >
            <button
                type="button"
                class="admin-header__profile-btn"
                @click="open = !open"
                :aria-expanded="open"
                aria-haspopup="true"
            >
                <span class="admin-header__profile-avatar">
                    {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                </span>
                <span class="admin-header__profile-name">{{ auth()->user()->name }}</span>
                <svg class="admin-header__profile-caret" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <polyline points="6 9 12 15 18 9"/>
                </svg>
            </button>

            <div class="admin-header__profile-menu" x-show="open" x-cloak x-transition.opacity.duration.150ms>
                <button
                    type="button"
                    class="admin-header__profile-item"
                    @click="open = false; $dispatch('open-my-attendance')"
                >
                    My Daily Attendance
                </button>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="admin-header__profile-item admin-header__profile-item--muted">
                        Sign out
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>
