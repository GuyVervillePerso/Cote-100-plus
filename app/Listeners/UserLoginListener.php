<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Auth\Events\Login;
class UserLoginListener
{
    /**
     * Create the event listener.
     */

    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        ray($event->user);
    }
}
