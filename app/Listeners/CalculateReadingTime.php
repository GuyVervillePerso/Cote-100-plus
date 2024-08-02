<?php

namespace App\Listeners;

use Statamic\Events\EntrySaving;
use Statamic\Fieldtypes\Bard;

class CalculateReadingTime
{
    private $wordsPerMinute = 250;

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
    public function handle(EntrySaving $event): void
    {
        $collections = [
            'entre_les_lignes', 'billets_mensuels', 'commentaires_trimestriels',
        ];
        $handle = $event->entry->collection()->handle();
        if (! in_array($handle, $collections)) {
            return;
        }

        $content = $event->entry->get('html_content');
        $wordCount = str_word_count(strip_tags($this->bardToHtml($content)));

        $event->entry->set('temps_lecture', round($wordCount / $this->wordsPerMinute));
    }

    private function bardToHtml($bard)
    {
        return (new Bard)->augment($bard);
    }
}
