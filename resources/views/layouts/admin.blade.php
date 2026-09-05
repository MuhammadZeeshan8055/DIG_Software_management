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

    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}?v=3">
    <link rel="shortcut icon" type="image/png" href="{{ asset('favicon.png') }}?v=3">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    @livewireStyles
</head>
<body
    class="admin-body"
    x-data="{
        sidebarOpen: false,
        activeModule: null,
        activeOption: null,
        modules: @js($modulesMap),
        workspace: @js($workspace),
        lastPageKey: 'dhothar_last_page',

        init() {
            document.body.classList.remove('mu-modal-open');
            this.openLastPage();
        },

        // Remember current page in browser (survives refresh)
        saveLastPage() {
            if (! this.activeModule) {
                localStorage.removeItem(this.lastPageKey);
                return;
            }

            localStorage.setItem(this.lastPageKey, JSON.stringify({
                module: this.activeModule,
                option: this.activeOption,
            }));
        },

        // Open last page after refresh
        openLastPage() {
            const saved = localStorage.getItem(this.lastPageKey);
            if (! saved) {
                return;
            }

            let page;
            try {
                page = JSON.parse(saved);
            } catch (e) {
                localStorage.removeItem(this.lastPageKey);
                return;
            }

            if (! page.module || ! this.modules[page.module]) {
                return;
            }

            this.activeModule = page.module;
            this.activeOption = page.option || null;

            if (this.activeOption === 'import-ticket-details') {
                this.$nextTick(() => {
                    window.dispatchEvent(new CustomEvent('import-ticket-panel-opened'));
                });
            }

            if (this.activeOption === 'payments') {
                this.$nextTick(() => {
                    window.dispatchEvent(new CustomEvent('payments-panel-opened'));
                });
            }

            if (this.activeOption === 'bank-accounts') {
                this.$nextTick(() => {
                    window.dispatchEvent(new CustomEvent('bank-accounts-panel-opened'));
                });
            }

            if (this.activeOption === 'users') {
                this.$nextTick(() => {
                    window.dispatchEvent(new CustomEvent('users-panel-opened'));
                });
            }
        },

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
            window.dispatchEvent(new CustomEvent('close-ticket-view'));
            this.activeModule = key;
            this.activeOption = null;
            this.saveLastPage();
            if (window.matchMedia('(max-width: 1024px)').matches) {
                this.sidebarOpen = true;
            }
        },
        closeModule() {
            window.dispatchEvent(new CustomEvent('close-ticket-view'));
            this.activeModule = null;
            this.activeOption = null;
            this.sidebarOpen = false;
            this.saveLastPage();
        },
        selectOption(key) {
            if (key !== 'import-ticket-details') {
                window.dispatchEvent(new CustomEvent('close-ticket-view'));
            }
            this.activeOption = key;
            this.saveLastPage();
            if (key === 'import-ticket-details') {
                window.dispatchEvent(new CustomEvent('import-ticket-panel-opened'));
            }
            if (key === 'payments') {
                window.dispatchEvent(new CustomEvent('payments-panel-opened'));
            }
            if (key === 'bank-accounts') {
                window.dispatchEvent(new CustomEvent('bank-accounts-panel-opened'));
            }
            if (key === 'users') {
                window.dispatchEvent(new CustomEvent('users-panel-opened'));
            }
            if (window.matchMedia('(max-width: 1024px)').matches) {
                this.sidebarOpen = false;
            }
        },
        clearOption() {
            this.activeOption = null;
            this.saveLastPage();
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

    {{--
      Manage Users lives OUTSIDE admin-shell so the modal can cover header + sidebar.
      Uses x-show (not x-if) so Livewire stays mounted.
    --}}
    <div
        class="manage-users-layer"
        x-show="activeModule === 'settings' && activeOption === 'users'"
        x-cloak
        x-transition.opacity.duration.200ms
    >
        <livewire:admin.settings.manage-users />
    </div>

    <div
        class="admin-overlay"
        x-show="sidebarOpen"
        x-transition.opacity
        @click="sidebarOpen = false"
        x-cloak
    ></div>

    @livewireScripts
</body>
</html>
