@props([
    'show' => false,
    'title' => 'Delete this entry?',
    'message' => 'This action cannot be undone.',
    'confirmLabel' => 'Yes, delete',
    'cancelMethod' => 'cancelPendingDelete',
    'confirmMethod' => 'confirmPendingDelete',
])

@if ($show)
    <template x-teleport="body">
        <div
            class="mu-modal confirm-delete-modal"
            wire:keydown.escape.window="{{ $cancelMethod }}"
            wire:key="confirm-delete-modal"
        >
            <button
                type="button"
                class="mu-modal__backdrop"
                wire:click="{{ $cancelMethod }}"
                aria-label="Close"
            ></button>

            <div
                class="mu-modal__dialog confirm-delete-modal__dialog"
                role="dialog"
                aria-modal="true"
                aria-labelledby="confirm-delete-title"
            >
                <div class="mu-modal__head">
                    <div class="confirm-delete-modal__head-main">
                        <span class="confirm-delete-modal__icon" aria-hidden="true">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                                <path d="M10 11v6M14 11v6"/>
                            </svg>
                        </span>
                        <h3 id="confirm-delete-title" class="mu-modal__title">{{ $title }}</h3>
                    </div>
                    <button
                        type="button"
                        class="mu-modal__close"
                        wire:click="{{ $cancelMethod }}"
                        aria-label="Close"
                    >&times;</button>
                </div>

                <div class="confirm-delete-modal__body">
                    <p>{{ $message }}</p>
                </div>

                <div class="mu-modal__footer">
                    <button
                        type="button"
                        class="payment-actions__btn"
                        wire:click="{{ $cancelMethod }}"
                        wire:loading.attr="disabled"
                        wire:target="{{ $confirmMethod }}"
                    >Cancel</button>
                    <button
                        type="button"
                        class="hero-btn confirm-delete-modal__confirm"
                        wire:click="{{ $confirmMethod }}"
                        wire:loading.attr="disabled"
                        wire:target="{{ $confirmMethod }}"
                    >
                        <span wire:loading.remove wire:target="{{ $confirmMethod }}">{{ $confirmLabel }}</span>
                        <span wire:loading wire:target="{{ $confirmMethod }}">Deleting...</span>
                    </button>
                </div>
            </div>
        </div>
    </template>
@endif
