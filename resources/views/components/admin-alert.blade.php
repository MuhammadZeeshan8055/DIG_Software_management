@props([
    'type' => 'success',
    'seconds' => 5,
    'wireProperty' => null,
])

<div
    {{ $attributes->merge(['class' => "admin-alert admin-alert--{$type}"]) }}
    x-data="{ show: true }"
    x-show="show"
    x-transition:leave="transition ease-in duration-300"
    x-transition:leave-start="opacity-100 translate-y-0"
    x-transition:leave-end="opacity-0 -translate-y-1"
    x-init="setTimeout(() => {
        show = false;
        setTimeout(() => {
            @if ($wireProperty)
                $wire.set('{{ $wireProperty }}', null);
            @endif
        }, 300);
    }, {{ (int) $seconds * 1000 }})"
    role="alert"
>
    {{ $slot }}
</div>
