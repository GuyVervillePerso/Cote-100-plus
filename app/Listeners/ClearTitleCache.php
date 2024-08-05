<?php

namespace App\Listeners;

use Illuminate\Support\Facades\Cache;
use Statamic\Events\CollectionSaved;

class ClearTitleCache
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
    public function handle(CollectionSaved $event): void
    {
        if ($event->collection->handle() === 'titres') {
            Cache::forget('searchtitres');
        }
    }
}
