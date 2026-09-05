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
        <div class="admin-nav-section">
            <p class="admin-nav-section__label">Workspace</p>
            <ul>
                <li>
                    <button
                        type="button"
                        class="admin-nav-link"
                        :class="{ 'admin-nav-link--active': !activeModule }"
                        @click="closeModule()"
                        @mouseenter="$el.classList.add('nav-touched')"
                    >
                        <span class="admin-nav-link__inner">
                            <span class="admin-nav-link__icon-box">
                                <x-admin-icon name="grid" :size="15" />
                            </span>
                            <span class="admin-nav-link__label">Operations Overview</span>
                        </span>
                    </button>
                </li>
            </ul>
        </div>

        {{-- Only the opened module's options (flat items) --}}
        <template x-if="currentModule()">
            <div class="admin-nav-section admin-nav-section--module" x-cloak>
                <p class="admin-nav-section__label" x-text="currentModule().title"></p>
                <ul>
                    <li>
                        <button
                            type="button"
                            class="admin-nav-link admin-nav-link--option"
                            :class="{ 'admin-nav-link--active': !activeOption }"
                            @click="clearOption()"
                            @mouseenter="$el.classList.add('nav-touched')"
                        >
                            <span class="admin-nav-link__inner">
                                <span class="admin-nav-link__icon-box">
                                    <x-admin-icon name="chart" :size="15" />
                                </span>
                                <span class="admin-nav-link__label">Module Dashboard</span>
                            </span>
                        </button>
                    </li>
                    <template x-for="(child, index) in (currentModule().children || [])" :key="child.key">
                        <li :style="'--opt-i:' + (index + 1)">
                            <button
                                type="button"
                                class="admin-nav-link admin-nav-link--option"
                                :class="{ 'admin-nav-link--active': activeOption === child.key }"
                                @click="selectOption(child.key)"
                                @mouseenter="$el.classList.add('nav-touched')"
                            >
                                <span class="admin-nav-link__inner">
                                    <span class="admin-nav-link__icon-box">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                            <polyline points="14 2 14 8 20 8"/>
                                            <line x1="8" y1="13" x2="16" y2="13"/>
                                            <line x1="8" y1="17" x2="13" y2="17"/>
                                        </svg>
                                    </span>
                                    <span class="admin-nav-link__label" x-text="child.label"></span>
                                </span>
                            </button>
                        </li>
                    </template>
                </ul>
            </div>
        </template>
    </nav>

    <div class="admin-sidebar__user">
        <div class="admin-sidebar__avatar">
            {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
        </div>
        <div class="admin-sidebar__user-info">
            <strong>{{ auth()->user()->name }}</strong>
            <span>{{ str_replace('_', ' ', auth()->user()->role ?? 'staff') }}</span>
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
