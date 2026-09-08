@props([
    'type' => 'success',
    'title' => null,
    'seconds' => 6,
    'wireProperty' => null,
])

@php
    // Default title by type (override with title="...")
    $resolvedTitle = $title ?? match ($type) {
        'error' => 'Error',
        'info' => 'Notice',
        default => 'Saved',
    };

    $clearWire = $wireProperty
        ? "setTimeout(() => \$wire.set('{$wireProperty}', null), 280)"
        : '';
@endphp

{{--
  Reusable top-right toast (teleported to <body> so sticky header never covers it).

  Livewire example:
    <x-admin-toast wire-property="successMessage">{{ $successMessage }}</x-admin-toast>
    <x-admin-toast type="error" title="Failed" wire-property="errorMessage">{{ $errorMessage }}</x-admin-toast>
--}}
<template x-teleport="body">
    <div
        {{ $attributes->merge(['class' => "admin-toast admin-toast--{$type}"]) }}
        x-data="{ show: true }"
        x-show="show"
        x-transition:enter="admin-toast-enter"
        x-transition:enter-start="admin-toast-enter-start"
        x-transition:enter-end="admin-toast-enter-end"
        x-transition:leave="admin-toast-leave"
        x-transition:leave-start="admin-toast-leave-start"
        x-transition:leave-end="admin-toast-leave-end"
        x-init="setTimeout(() => {
            show = false;
            {{ $clearWire }}
        }, {{ (int) $seconds * 1000 }})"
        role="{{ $type === 'error' ? 'alert' : 'status' }}"
        aria-live="polite"
    >
        <span class="admin-toast__accent" aria-hidden="true"></span>
        <div class="admin-toast__body">
            <p class="admin-toast__title">{{ $resolvedTitle }}</p>
            <p class="admin-toast__text">{{ $slot }}</p>
        </div>
        <button
            type="button"
            class="admin-toast__close"
            aria-label="Dismiss"
            @click="show = false; {{ $clearWire }}"
        >&times;</button>
    </div>
</template>
