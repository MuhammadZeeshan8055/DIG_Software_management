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
            x-show="activeModule"
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
                    <!-- <div class="module-workspace__toolbar">
                        <button type="button" class="module-back-btn" @click="closeModule()">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                                <line x1="19" y1="12" x2="5" y2="12"/>
                                <polyline points="12 19 5 12 12 5"/>
                            </svg>
                            Back to Operations Overview
                        </button>
                    </div> -->

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

                    <div class="option-grid">
                        <template x-for="(child, index) in (currentModule().children || [])" :key="child.label">
                            <button
                                type="button"
                                class="option-card"
                                :style="'--opt-i:' + index"
                                :class="{ 'option-card--active': activeOption === child.label }"
                                @click="selectOption(child.label)"
                            >
                                <div class="option-card__icon">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                        <polyline points="14 2 14 8 20 8"/>
                                        <line x1="8" y1="13" x2="16" y2="13"/>
                                        <line x1="8" y1="17" x2="13" y2="17"/>
                                    </svg>
                                </div>
                                <div class="option-card__body">
                                    <strong x-text="child.label"></strong>
                                    <span>Open this option</span>
                                </div>
                                <svg class="option-card__arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="5" y1="12" x2="19" y2="12"/>
                                    <polyline points="12 5 19 12 12 19"/>
                                </svg>
                            </button>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>
@endsection
