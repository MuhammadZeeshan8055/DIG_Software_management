<aside
    class="admin-sidebar"
    :class="{ 'admin-sidebar--open': sidebarOpen }"
>
    <div class="admin-sidebar__brand">
        <div class="admin-sidebar__brand-card">
            <img src="{{ asset('images/logo-icon.png') }}" alt="DHOTHAR" class="admin-sidebar__logo">
            <div class="admin-sidebar__brand-text">
                <strong>DHOTHAR</strong>
                <span>International Group</span>
                <small>Travels &amp; Tours</small>
            </div>
        </div>
    </div>

    <nav class="admin-sidebar__nav">
        @foreach ($navigation as $section)
            <div class="admin-nav-section">
                <p class="admin-nav-section__label">{{ $section['label'] }}</p>
                <ul>
                    @foreach ($section['items'] as $item)
                        <li>
                            @if ($item['route'] && Route::has($item['route']))
                                <a
                                    href="{{ route($item['route']) }}"
                                    class="admin-nav-link {{ ($item['active'] ?? false) ? 'admin-nav-link--active' : '' }}"
                                    @mouseenter="$el.classList.add('nav-touched')"
                                >
                                    <span class="admin-nav-link__inner">
                                        <span class="admin-nav-link__icon-box">
                                            <x-admin-icon :name="$item['icon'] ?? 'grid'" :size="15" />
                                        </span>
                                        <span class="admin-nav-link__label">{{ $item['label'] }}</span>
                                    </span>
                                    @if ($item['badge'])
                                        <span class="admin-nav-badge admin-nav-badge--gold">{{ $item['badge'] }}</span>
                                    @endif
                                </a>
                            @else
                                <span class="admin-nav-link admin-nav-link--disabled" @mouseenter="$el.classList.add('nav-touched')">
                                    <span class="admin-nav-link__inner">
                                        <span class="admin-nav-link__icon-box">
                                            <x-admin-icon :name="$item['icon'] ?? 'grid'" :size="15" />
                                        </span>
                                        <span class="admin-nav-link__label">{{ $item['label'] }}</span>
                                    </span>
                                    @if ($item['badge'])
                                        <span class="admin-nav-badge admin-nav-badge--blue">{{ $item['badge'] }}</span>
                                    @endif
                                </span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </nav>

    <div class="admin-sidebar__user">
        <div class="admin-sidebar__avatar">
            {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
        </div>
        <div class="admin-sidebar__user-info">
            <strong>{{ auth()->user()->name }}</strong>
            <span>Staff Member</span>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="admin-sidebar__logout" title="Logout">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                    <polyline points="16 17 21 12 16 7"/>
                    <line x1="21" y1="12" x2="9" y2="12"/>
                </svg>
            </button>
        </form>
    </div>
</aside>
