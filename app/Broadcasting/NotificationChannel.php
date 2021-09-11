<?php

namespace App\Broadcasting;

use App\Models\Base\Notification;
use App\Models\Base\User;

class NotificationChannel
{
    /**
     * Create a new channel instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Authenticate the user's access to the channel.
     *
     * @param  \App\Models\Base\User  $user
     * @return array|bool
     */
    public function join(User $user, Notification $notification)
    {
        return $user->id === $notification->user_id;
    }
}
