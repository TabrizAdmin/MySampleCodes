<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Events\NewPrivateEvent;
use App\Models\Base\User;

class SendPusher implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $user;
    protected $notification;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(User $user, $notification)
    {
        $this->user = $user;
        $this->notification = $notification;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        event( new NewPrivateEvent($this->user, $this->notification) );
    }
}
