<?php

namespace App\Listeners;

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
        $current_user = $event->user;
        session()->put('porfolio', $current_user->portfolio);
        session()->put('canreadarticle', false);
    }
}
