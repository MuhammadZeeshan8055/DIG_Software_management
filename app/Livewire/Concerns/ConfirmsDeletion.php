<?php

namespace App\Livewire\Concerns;

trait ConfirmsDeletion
{
    /**
     * Run delete after the Alpine confirm modal. Id comes from the client.
     */
    public function confirmPendingDelete(int $id): void
    {
        $this->performPendingDelete($id);
    }

    abstract protected function performPendingDelete(int $id): void;
}
