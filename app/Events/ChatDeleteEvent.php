<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use App\Models\Base\User;
use App\Models\Chat\Group;

class ChatDeleteEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user, $group, $messageId;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(User $user, Group $group, $messageId)
    {
        $this->user = $user;
        $this->group = $group;
        $this->messageId = $messageId;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('group.'.$this->group->id);
    }

    public function broadcastWith()
    {
        return [
            'group_name' => $this->group->name,
            'group_hash' => $this->group->hash,
            'status' => 'delete',
            'message_id' => $this->messageId
        ];
    }

    public function broadcastAs()
    {
        return 'chat';
    }
}
