@props([
    'title' => 'Please fix the following:',
])

@if ($errors->any())
    <div
        {{ $attributes->merge(['class' => 'validation-errors']) }}
        role="alert"
        wire:key="validation-errors-{{ md5(implode('|', $errors->all())) }}"
        x-data
        x-init="$el.scrollIntoView({ behavior: 'smooth', block: 'center' })"
    >
        <p class="validation-errors__title">{{ $title }}</p>
        <ul class="validation-errors__list">
            @foreach ($errors->all() as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    </div>
@endif
