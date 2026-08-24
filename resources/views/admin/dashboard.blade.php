@extends('layouts.admin')

@section('title', 'Operations Overview')

@section('content')
    <div class="dashboard-page">
        <section class="dashboard-hero">
            <div class="dashboard-hero__glow dashboard-hero__glow--one" aria-hidden="true"></div>
            <div class="dashboard-hero__glow dashboard-hero__glow--two" aria-hidden="true"></div>
            <div class="dashboard-hero__glow dashboard-hero__glow--three" aria-hidden="true"></div>
            <div class="dashboard-hero__shine" aria-hidden="true"></div>

            <div class="dashboard-hero__main dashboard-reveal" style="--reveal-delay: 0ms">
                <p class="dashboard-hero__eyebrow">
                    <span class="dashboard-hero__eyebrow-dot"></span>
                    Employee Operations Center
                </p>
                <h2 class="dashboard-hero__title">Welcome back, {{ auth()->user()->name }}</h2>
                <p class="dashboard-hero__desc">
                    This workspace brings together your daily travel, Umrah, visa, hotel, ticketing,
                    transport, and customer operations in one place.
                </p>

                <div class="dashboard-hero__actions">
                    @foreach ($quickActions as $action)
                        <button
                            type="button"
                            class="hero-btn {{ $action['primary'] ? 'hero-btn--primary' : 'hero-btn--secondary' }}"
                            style="--btn-delay: {{ $loop->index * 40 }}ms"
                        >
                            @if ($action['primary'])
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                                </svg>
                            @else
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
                                    <polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/>
                                </svg>
                            @endif
                            {{ $action['label'] }}
                        </button>
                    @endforeach
                </div>
            </div>

            <aside class="dashboard-hero__panel dashboard-reveal" style="--reveal-delay: 120ms">
                <p class="dashboard-hero__panel-label">Current Workday</p>
                <p class="dashboard-hero__panel-date">{{ now()->format('l, j F Y') }}</p>
                <ul class="dashboard-hero__panel-list">
                    <li><span>Workspace</span><strong>Employee Portal</strong></li>
                    <li><span>Data mode</span><strong>Online sync</strong></li>
                    <li><span>Access</span><strong>Authorized</strong></li>
                </ul>
            </aside>
        </section>

        <div class="module-grid">
            @foreach ($modules as $module)
                <article class="module-card dashboard-reveal" style="--reveal-delay: {{ 180 + ($loop->index * 45) }}ms">
                    <div class="module-card__sheen" aria-hidden="true"></div>
                    <div class="module-card__top">
                        <div class="module-card__icon">
                            <x-admin-icon :name="$module['icon'] ?? 'grid'" :size="20" />
                        </div>
                        <div class="module-card__status">
                            <span class="module-card__status-dot"></span>
                            Active
                        </div>
                    </div>

                    <h2 class="module-card__title">{{ $module['title'] }}</h2>
                    <p class="module-card__desc">{{ $module['description'] }}</p>

                    @if ($module['route'] && Route::has($module['route']))
                        <a href="{{ route($module['route']) }}" class="module-card__link">
                            Open workspace
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="5" y1="12" x2="19" y2="12"/>
                                <polyline points="12 5 19 12 12 19"/>
                            </svg>
                        </a>
                    @else
                        <span class="module-card__link module-card__link--soon">
                            Coming soon
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="5" y1="12" x2="19" y2="12"/>
                                <polyline points="12 5 19 12 12 19"/>
                            </svg>
                        </span>
                    @endif
                </article>
            @endforeach
        </div>
    </div>
@endsection
