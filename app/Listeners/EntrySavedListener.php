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
        if ($event->entry->collectionHandle() === 'entre_les_lignes') {
            Cache::tags(['entre_les_lignes'])->flush();
            Cache::forget('btl_topsection_default');
            Cache::forget('btl_topsection_anglais');
        }
        if ($event->entry->collectionHandle() === 'billets_mensuels') {
            Cache::tags(['billets_mensuels'])->flush();
            Cache::forget('bm_topsection_default');
            Cache::forget('bm_topsection_anglais');
        }
        if ($event->entry->collectionHandle() === 'commentaires_trimestriels') {
            Cache::tags(['commentaires_trimestriels'])->flush();
            Cache::forget('mc_topsection_default');
            Cache::forget('mc_topsection_anglais');
        }
    }
}
