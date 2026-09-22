<?php

namespace App\Livewire\Admin;

use App\Models\UserNotification;
use Livewire\Component;

class NotificationBell extends Component
{
    public bool $open = false;

    public function toggle(): void
    {
        $this->open = ! $this->open;
    }

    public function close(): void
    {
        $this->open = false;
    }

    public function markAsRead(int $id): void
    {
        $notification = UserNotification::query()
            ->where('user_id', auth()->id())
            ->findOrFail($id);

        $notification->markAsRead();
    }

    public function markAllAsRead(): void
    {
        UserNotification::query()
            ->where('user_id', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function render()
    {
        $notifications = UserNotification::query()
            ->where('user_id', auth()->id())
            ->latest()
            ->limit(20)
            ->get();

        $unreadCount = UserNotification::query()
            ->where('user_id', auth()->id())
            ->whereNull('read_at')
            ->count();

        return view('livewire.admin.notification-bell', [
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
        ]);
    }
}
