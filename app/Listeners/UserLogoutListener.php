<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Logout;

class UserLogoutListener
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
    public function handle(Logout $event): void
    {
        session()->forget('portfolio');
        session()->forget('canreadarticle');
    }
}
