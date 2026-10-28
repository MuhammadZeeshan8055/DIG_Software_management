<?php

namespace App\Livewire\Concerns;

trait ConfirmsDeletion
{
    public ?int $pendingDeleteId = null;

    public string $pendingDeleteTitle = 'Delete this entry?';

    public string $pendingDeleteMessage = 'This action cannot be undone.';

    public function askDelete(
        int $id,
        string $title = 'Delete this entry?',
        string $message = 'This action cannot be undone.',
    ): void {
        $this->pendingDeleteId = $id;
        $this->pendingDeleteTitle = $title;
        $this->pendingDeleteMessage = $message;
        $this->js('document.body.classList.add("mu-modal-open")');
    }

    public function cancelPendingDelete(): void
    {
        $this->pendingDeleteId = null;
        $this->pendingDeleteTitle = 'Delete this entry?';
        $this->pendingDeleteMessage = 'This action cannot be undone.';
        $this->js('document.body.classList.remove("mu-modal-open")');
    }

    public function confirmPendingDelete(): void
    {
        $id = $this->pendingDeleteId;

        $this->cancelPendingDelete();

        if ($id === null) {
            return;
        }

        $this->performPendingDelete($id);
    }

    abstract protected function performPendingDelete(int $id): void;
}
