@php
    $modulesList = $modules ?? config('admin.modules', []);
    $modulesMap = collect($modulesList)->keyBy('key')->all();
    $workspace = $workspace ?? config('admin_workspace', []);
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Dashboard') — DHOTHAR</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
</head>
<body
    class="admin-body"
    x-data="{
        sidebarOpen: false,
        activeModule: null,
        activeOption: null,
        modules: @js($modulesMap),
        workspace: @js($workspace),
        currentModule() {
            return this.activeModule ? (this.modules[this.activeModule] || null) : null;
        },
        currentWorkspace() {
            return this.activeModule ? (this.workspace[this.activeModule] || null) : null;
        },
        currentStats() {
            return this.currentWorkspace()?.stats || [];
        },
        currentTrend() {
            return this.currentWorkspace()?.trend || null;
        },
        currentShare() {
            return this.currentWorkspace()?.share || null;
        },
        currentTable() {
            if (!this.activeModule || !this.activeOption) return null;
            return (this.currentWorkspace()?.tables || {})[this.activeOption] || null;
        },
        trendPoints() {
            const trend = this.currentTrend();
            if (!trend?.values?.length) return '';
            const vals = trend.values;
            const max = Math.max(...vals, 1);
            const w = 320;
            const h = 120;
            const step = w / Math.max(vals.length - 1, 1);
            return vals.map((v, i) => {
                const x = (i * step).toFixed(1);
                const y = (h - ((v / max) * (h - 16)) - 8).toFixed(1);
                return x + ',' + y;
            }).join(' ');
        },
        trendArea() {
            const pts = this.trendPoints();
            if (!pts) return '';
            return '0,120 ' + pts + ' 320,120';
        },
        openModule(key) {
            if (!key || !this.modules[key]) return;
            this.activeModule = key;
            this.activeOption = null;
            if (window.matchMedia('(max-width: 1024px)').matches) {
                this.sidebarOpen = true;
            }
        },
        closeModule() {
            this.activeModule = null;
            this.activeOption = null;
            this.sidebarOpen = false;
        },
        selectOption(key) {
            this.activeOption = key;
            if (window.matchMedia('(max-width: 1024px)').matches) {
                this.sidebarOpen = false;
            }
        },
        clearOption() {
            this.activeOption = null;
        }
    }"
>

    <div class="admin-shell">
        @include('admin.partials.sidebar')

        <div class="admin-main">
            @include('admin.partials.header')

            <div class="admin-content">
                @yield('content')
            </div>
        </div>
    </div>

    <div
        class="admin-overlay"
        x-show="sidebarOpen"
        x-transition.opacity
        @click="sidebarOpen = false"
        x-cloak
    ></div>

</body>
</html>
