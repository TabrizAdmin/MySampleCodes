<?php

namespace App\Broadcasting;

use App\Models\Base\User;
use App\Models\Chat\Group;

class ChatChannel
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
    public function join(User $user, Group $group)
    {
        return $group->participants->contains($user->id);
    }
}
