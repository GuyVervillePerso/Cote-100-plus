<?php

namespace App\Listeners;

use Illuminate\Support\Facades\Cache;
use Statamic\Events\EntrySaved;

class EntrySavedListener
{
    /**
     * Handle the event.
     */
    public function handle(EntrySaved $event): void
    {
        if ($event->entry->collectionHandle() === 'titres') {
            Cache::tags(['portfolios'])->flush();
        }
    }
}
