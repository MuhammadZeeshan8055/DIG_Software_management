@props(['status', 'seconds' => 5])

@if ($status)
    <div
        {{ $attributes->merge(['class' => 'font-medium text-sm text-green-600']) }}
        x-data="{ show: true }"
        x-show="show"
        x-transition:leave="transition ease-in duration-300"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        x-init="setTimeout(() => show = false, {{ (int) $seconds * 1000 }})"
        role="status"
    >
        {{ $status }}
    </div>
@endif
