<?php

namespace App\Livewire;

use App\Traits\Cards;
use Illuminate\Support\Facades\Auth;
use Jonassiewertsen\LiveSearch\Http\Livewire\Search;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;

class SearchTitle extends Search
{
    use Cards;

    protected $locale = 'default';

    public $results = [];

    public $blockedResults = [];

    protected $portfolio = '';

    protected $template;

    public function render()
    {
        $this->getSession();
        $this->results = $this->getSimpleSearch();

        return view($this->template);
    }

    public function mount(string $template)
    {
        // You can pass these as parameters or they can be hardcoded.
        $this->template = $template;
        $this->locale = Site::current()->handle();
        session(['locale' => $this->locale]);
    }

    protected function getSession()
    {
        $this->portfolio = session('portfolio');
        $this->locale = session('locale');
    }

    protected function getAnalyses($id)
    {
        $entry = Entry::query()
            ->where('collection', 'analyses')
            ->where('titre', $id)
            ->orderBy('date', 'desc')
            ->first();

        if ($entry) {
            return $entry->url();
        }

        return '';

    }

    protected function getAllTitles()
    {
        return $this->searchCards($this->locale);
    }

    protected function createInBriefArray($entry): array
    {
        $array = [];
        $trimestreEnBref = $entry->augmentedValue('trimestre_en_bref');
        foreach ($trimestreEnBref as $set) {
            $fields = $set->all();
            $icon = $fields['icon'] ?? null;
            $comment = $fields['comment'] ?? null;
            $array[] = ['icon' => $icon->raw(), 'comment' => $comment->raw()];
        }

        return $array;
    }

    protected function getSimpleSearch()
    {
        if (! Auth::check()) {
            return false;
        }

        $this->getSession();
        $entries = $this->getAllTitles();

        // Sorting and partitioning entries
        $blockedEntries = [];
        $allowedEntries = [];

        foreach ($entries as $entry) {
            if ($entry['blocked']) {
                $blockedEntries[] = $entry;
            } else {
                $allowedEntries[] = $entry;
            }
        }

        // Sorting each partition by 'new' in descending order
        usort($blockedEntries, function ($a, $b) {
            return $b['new'] <=> $a['new'];
        });

        usort($allowedEntries, function ($a, $b) {
            return $b['new'] <=> $a['new'];
        });

        // Merging sorted partitions
        $finalSortedEntries = array_merge($allowedEntries, $blockedEntries);

        return $finalSortedEntries;
    }
}
