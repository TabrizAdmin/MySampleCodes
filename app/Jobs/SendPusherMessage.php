<?php

namespace App\Jobs;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class SendPusherMessage implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user, $group, $message;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(User $user, Group $group, $message)
    {
        $this->user = $user;
        $this->group = $group;
        $this->message = $message;
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
            'content' => $this->message,
            'sender_id' => $this->user->id
        ];
    }

    public function broadcastAs()
    {
        return 'chat';
    }
}
