<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Support\Facades\Storage;
use App\Models\Polymorph\Attachment;
use App\Models\Chat\Message;
use App\Models\Chat\Group;
use App\Models\Base\User;
use Carbon\Carbon;

class NewChatEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user, $group, $message;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(User $user, Group $group, Message $message)
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
        $avatar = (object)[];
        foreach (Attachment::avatars($this->user->id)->get() as $media) {
            $avatar = [
                'id' => $media->id,
                'path' => Storage::disk('s3')->url($media->path),
                'type' => $media->user_media_type
            ];
        }
        $user_array = [
            'id' => $this->user->id,
            'avatar' => $avatar,
            'first_name' => $this->user->first_name,
            'last_name' => $this->user->last_name,
            'fullname' => $this->user->first_name.' '.$this->user->last_name,
            'username' => $this->user->username,
            'verified' => $this->user->verified
        ];
        return [
            'id' => $this->message->id,
            'sender' => $user_array,
            'group_id' => $this->group->id,
            'content' => $this->message->content,
            'created_at' => $this->message->created_at,
            'updated_at' => $this->message->updated_at,
            'message_time' => $this->message->created_at->diffForHumans()
        ];
    }

    public function broadcastAs()
    {
        return 'chat.'.$this->user->id;
    }
}
