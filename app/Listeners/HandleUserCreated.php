<?php

namespace App\Listeners;

use App\Events\UserCreated;
use App\Mail\UserActivationMail;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class HandleUserCreated
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(UserCreated $event): void
    {
        $user = $event->user;
        Mail::to($user->email)->send(new UserActivationMail($user->email, $user->api_token));
    }
}
