<div
    class="notification-bell"
    x-data
    @click.outside="$wire.close()"
    @keydown.escape.window="$wire.close()"
>
    <button
        type="button"
        class="admin-header__icon-btn"
        title="Notifications"
        aria-label="Notifications"
        wire:click="toggle"
        :aria-expanded="$wire.open"
    >
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
            <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
        </svg>
        @if ($unreadCount > 0)
            <span class="admin-header__icon-badge">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
        @endif
    </button>

    @if ($open)
        <div class="notification-bell__panel" wire:click.stop>
            <div class="notification-bell__head">
                <strong>Notifications</strong>
                @if ($unreadCount > 0)
                    <button type="button" class="notification-bell__mark-all" wire:click="markAllAsRead">
                        Mark all read
                    </button>
                @endif
            </div>

            <div class="notification-bell__list">
                @forelse ($notifications as $notification)
                    <button
                        type="button"
                        wire:key="notification-{{ $notification->id }}"
                        class="notification-bell__item {{ $notification->isRead() ? 'notification-bell__item--read' : 'notification-bell__item--unread' }}"
                        wire:click="markAsRead({{ $notification->id }})"
                        @click="
                            $wire.close();
                            window.dispatchEvent(new CustomEvent('open-notification-nav', {
                                detail: {
                                    module: @js($notification->module_key),
                                    option: @js($notification->feature_key),
                                }
                            }));
                        "
                    >
                        <span class="notification-bell__item-title">{{ $notification->title }}</span>
                        @if ($notification->body)
                            <span class="notification-bell__item-body">{{ $notification->body }}</span>
                        @endif
                        <span class="notification-bell__item-time">{{ $notification->created_at->diffForHumans() }}</span>
                    </button>
                @empty
                    <p class="notification-bell__empty">No notifications yet.</p>
                @endforelse
            </div>
        </div>
    @endif
</div>
