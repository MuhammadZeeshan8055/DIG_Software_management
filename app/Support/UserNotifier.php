<?php

namespace App\Support;

use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Support\Collection;

class UserNotifier
{
    /**
     * @param  User|iterable<User>|Collection<int, User>  $users
     */
    public static function send(
        User|iterable $users,
        string $type,
        string $title,
        ?string $body = null,
        ?string $moduleKey = null,
        ?string $featureKey = null,
    ): void {
        $recipients = $users instanceof User
            ? collect([$users])
            : collect($users);

        foreach ($recipients as $user) {
            UserNotification::create([
                'user_id' => $user->id,
                'type' => $type,
                'title' => $title,
                'body' => $body,
                'module_key' => $moduleKey,
                'feature_key' => $featureKey,
            ]);
        }
    }

    /** All admin and super_admin users. */
    public static function admins(): Collection
    {
        return User::query()
            ->whereIn('role', ['admin', 'super_admin'])
            ->get();
    }
}
