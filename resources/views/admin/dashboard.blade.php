@extends('layouts.admin')

@section('title', 'Operations Overview')

@section('content')
    <div class="dashboard-page">
        {{-- Main overview (default) --}}
        <div
            class="dashboard-view"
            x-show="!activeModule"
            x-transition:enter="dash-enter"
            x-transition:enter-start="dash-enter-start"
            x-transition:enter-end="dash-enter-end"
            x-transition:leave="dash-leave"
            x-transition:leave-start="dash-leave-start"
            x-transition:leave-end="dash-leave-end"
        >
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
                    <p class="dashboard-hero__panel-date">{{ format_date(now()) }}</p>
                    <ul class="dashboard-hero__panel-list">
                        <li><span>Workspace</span><strong>Employee Portal</strong></li>
                        <li><span>Data mode</span><strong>Online sync</strong></li>
                        <li><span>Access</span><strong>Authorized</strong></li>
                    </ul>
                </aside>
            </section>

            <div class="module-grid">
                @foreach ($modules as $module)
                    @php $menuKey = $module['key'] ?? \Illuminate\Support\Str::slug($module['title']); @endphp
                    <button
                        type="button"
                        class="module-card dashboard-reveal"
                        style="--reveal-delay: {{ 180 + ($loop->index * 45) }}ms"
                        @click="openModule('{{ $menuKey }}')"
                    >
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

                        <span class="module-card__link">
                            Open module
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="5" y1="12" x2="19" y2="12"/>
                                <polyline points="12 5 19 12 12 19"/>
                            </svg>
                        </span>
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Module workspace (replaces overview) --}}
        <div
            class="dashboard-view module-workspace"
            x-show="activeModule && activeOption !== 'import-ticket-details' && activeOption !== 'payments'"
            x-cloak
            x-transition:enter="dash-enter"
            x-transition:enter-start="dash-enter-start"
            x-transition:enter-end="dash-enter-end"
            x-transition:leave="dash-leave"
            x-transition:leave-start="dash-leave-start"
            x-transition:leave-end="dash-leave-end"
        >
            <template x-if="currentModule()">
                <div>

                    <section class="module-workspace__hero">
                        <div class="module-workspace__hero-glow" aria-hidden="true"></div>
                        <div class="module-workspace__hero-main">
                            <p class="module-workspace__eyebrow">
                                <span class="module-workspace__eyebrow-dot"></span>
                                Module workspace
                            </p>
                            <h2 class="module-workspace__title" x-text="currentModule().title"></h2>
                            <p class="module-workspace__desc" x-text="currentModule().description"></p>
                        </div>
                        <div class="module-workspace__hero-badge">
                            <span class="module-card__status-dot"></span>
                            Active
                        </div>
                    </section>

                    {{-- Module overview: stats + charts --}}
                    <div
                        class="module-panel"
                        x-show="!activeOption"
                        x-transition.opacity.duration.200ms
                    >
                        <div class="stat-grid">
                            <template x-for="(stat, index) in currentStats()" :key="stat.label + '-' + index">
                                <article
                                    class="stat-card"
                                    :class="'stat-card--' + (stat.tone || 'blue')"
                                    :style="'--stat-i:' + index"
                                >
                                    <div class="stat-card__top">
                                        <p class="stat-card__label" x-text="stat.label"></p>
                                        <span class="stat-card__mark" aria-hidden="true"></span>
                                    </div>
                                    <p class="stat-card__value" x-text="stat.value"></p>
                                    <p class="stat-card__hint" x-text="stat.hint || ''"></p>
                                </article>
                            </template>
                        </div>

                        <template x-if="currentTrend() || currentShare()">
                            <div class="chart-grid">
                                <article class="chart-card" x-show="currentTrend()">
                                    <div class="chart-card__head">
                                        <h3 class="chart-card__title" x-text="currentTrend()?.title"></h3>
                                        <span class="chart-card__badge">7 days</span>
                                    </div>
                                    <div class="chart-card__body">
                                        <svg class="trend-chart" viewBox="0 0 320 120" preserveAspectRatio="none" aria-hidden="true">
                                            <defs>
                                                <linearGradient id="trendFill" x1="0" y1="0" x2="0" y2="1">
                                                    <stop offset="0%" stop-color="#0091d4" stop-opacity="0.28"/>
                                                    <stop offset="100%" stop-color="#0091d4" stop-opacity="0.02"/>
                                                </linearGradient>
                                                <linearGradient id="trendStroke" x1="0" y1="0" x2="1" y2="0">
                                                    <stop offset="0%" stop-color="#0091d4"/>
                                                    <stop offset="100%" stop-color="#fdca09"/>
                                                </linearGradient>
                                            </defs>
                                            <polygon class="trend-chart__area" :points="trendArea()" fill="url(#trendFill)"></polygon>
                                            <polyline class="trend-chart__line" :points="trendPoints()" fill="none" stroke="url(#trendStroke)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"></polyline>
                                        </svg>
                                        <div class="trend-chart__labels">
                                            <template x-for="label in (currentTrend()?.labels || [])" :key="label">
                                                <span x-text="label"></span>
                                            </template>
                                        </div>
                                    </div>
                                </article>

                                <article class="chart-card" x-show="currentShare()">
                                    <div class="chart-card__head">
                                        <h3 class="chart-card__title" x-text="currentShare()?.title"></h3>
                                        <span class="chart-card__badge">Share %</span>
                                    </div>
                                    <div class="chart-card__body chart-card__body--bars">
                                        <template x-for="item in (currentShare()?.items || [])" :key="item.label">
                                            <div class="bar-row">
                                                <div class="bar-row__meta">
                                                    <span class="bar-row__label" x-text="item.label"></span>
                                                    <span class="bar-row__value" x-text="item.value + '%'"></span>
                                                </div>
                                                <div class="bar-row__track">
                                                    <span
                                                        class="bar-row__fill"
                                                        :class="'bar-row__fill--' + (item.tone || 'blue')"
                                                        :style="'width:' + item.value + '%'"
                                                    ></span>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </article>
                            </div>
                        </template>
                    </div>

                    {{-- Other option tables (static demo data) --}}
                    <div
                        class="module-panel"
                        x-show="activeOption && activeOption !== 'import-ticket-details' && activeOption !== 'payments'"
                        x-cloak
                        x-transition.opacity.duration.200ms
                    >
                        <template x-if="currentTable()">
                            <div class="data-panel">
                                <div class="data-panel__head">
                                    <h3 class="data-panel__title" x-text="currentTable().title"></h3>
                                </div>

                                <div class="data-table-wrap">
                                    <table class="data-table">
                                        <thead>
                                            <tr>
                                                <template x-for="col in currentTable().columns" :key="col">
                                                    <th x-text="col"></th>
                                                </template>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template x-for="(row, rIndex) in currentTable().rows" :key="rIndex">
                                                <tr>
                                                    <template x-for="(cell, cIndex) in row" :key="cIndex">
                                                        <td x-text="cell"></td>
                                                    </template>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
        </div>

        {{-- Import Ticket Details (Livewire — must be in page HTML on load, not inside Alpine x-if) --}}
        <div
            class="dashboard-view dashboard-view--panel"
            x-show="activeModule === 'ticketing' && activeOption === 'import-ticket-details'"
            x-cloak
            x-transition.opacity.duration.200ms
        >
            <livewire:admin.ticketing.import-ticket-details />
        </div>

        {{-- Accounts → Payments (Livewire) --}}
        <div
            class="dashboard-view dashboard-view--panel"
            x-show="activeModule === 'accounts' && activeOption === 'payments'"
            x-cloak
            x-transition.opacity.duration.200ms
        >
            <livewire:admin.accounts.payment-entries />
        </div>
    </div>
@endsection
