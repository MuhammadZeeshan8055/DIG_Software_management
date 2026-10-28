@props([
    'event' => 'confirm-delete',
    'confirmMethod' => 'confirmPendingDelete',
    'confirmLabel' => 'Yes, delete',
])

{{--
  Instant Alpine confirm (no Livewire round-trip to open).
  Open:  $dispatch('{{ $event }}', { id, title, message })
  Confirm calls $wire.{{ $confirmMethod }}(id)
--}}
<div
        class="mu-modal confirm-delete-modal"
        x-data="{
            open: false,
            id: null,
            title: 'Delete this entry?',
            message: 'This action cannot be undone.',
            show(detail) {
                this.id = detail.id;
                this.title = detail.title || 'Delete this entry?';
                this.message = detail.message || 'This action cannot be undone.';
                this.open = true;
                document.body.classList.add('mu-modal-open');
            },
            close() {
                this.open = false;
                document.body.classList.remove('mu-modal-open');
            },
            confirm() {
                const deleteId = this.id;
                this.close();
                if (deleteId != null) {
                    $wire.{{ $confirmMethod }}(deleteId);
                }
            }
        }"
        x-show="open"
        x-cloak
        x-transition:enter="confirm-delete-modal-enter"
        x-transition:enter-start="confirm-delete-modal-enter-start"
        x-transition:enter-end="confirm-delete-modal-enter-end"
        x-transition:leave="confirm-delete-modal-leave"
        x-transition:leave-start="confirm-delete-modal-leave-start"
        x-transition:leave-end="confirm-delete-modal-leave-end"
        @{{ $event }}.window="show($event.detail)"
        @keydown.escape.window="if (open) close()"
        role="dialog"
        aria-modal="true"
        aria-labelledby="confirm-delete-title-{{ $event }}"
        style="display: none;"
    >
        <button
            type="button"
            class="mu-modal__backdrop"
            @click="close()"
            aria-label="Close"
        ></button>

        <div class="mu-modal__dialog confirm-delete-modal__dialog confirm-delete-modal__dialog--pop">
            <div class="mu-modal__head">
                <div class="confirm-delete-modal__head-main">
                    <span class="confirm-delete-modal__icon" aria-hidden="true">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                            <path d="M10 11v6M14 11v6"/>
                        </svg>
                    </span>
                    <h3 id="confirm-delete-title-{{ $event }}" class="mu-modal__title" x-text="title"></h3>
                </div>
                <button type="button" class="mu-modal__close" @click="close()" aria-label="Close">&times;</button>
            </div>

            <div class="confirm-delete-modal__body">
                <p x-text="message"></p>
            </div>

            <div class="mu-modal__footer">
                <button type="button" class="payment-actions__btn" @click="close()">Cancel</button>
                <button
                    type="button"
                    class="hero-btn confirm-delete-modal__confirm"
                    @click="confirm()"
                    wire:loading.attr="disabled"
                    wire:target="{{ $confirmMethod }}"
                >
                    <span wire:loading.remove wire:target="{{ $confirmMethod }}">{{ $confirmLabel }}</span>
                    <span wire:loading wire:target="{{ $confirmMethod }}">Deleting...</span>
                </button>
            </div>
        </div>
    </div>
