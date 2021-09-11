<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use App\Models\Base\Notification;
use App\Models\Base\User;

class NewNotificationEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user, $notification;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(User $user, Notification $notification)
    {
        $this->user = $user;
        $this->notification = $notification;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('notification.'.$this->user->id);
    }

    public function broadcastWith()
    {
        return [
            'rel_user_id' => $this->notification->rel_user_id,
            'content' => $this->notification->content,
            'notifiable_type' => $this->notification->notifiable_type,
            'notifiable_id' => $this->notification->notifiable_id,
            'type' => $this->notification->type
        ];
    }

    public function broadcastAs()
    {
        return 'new-notification-event';
    }
}
