<?php

namespace App\Livewire;

use App\Traits\Cards;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Jonassiewertsen\LiveSearch\Http\Livewire\Search;

class SearchTitle extends Search
{
    use Cards;

    public $q = '';

    public $results = [];

    public $blockedResults = [];

    public $template;

    public function render()
    {
        return view($this->template);
    }

    public function updatedQ()
    {
        $q = $this->q;
        if ($this->q == '') {
            $this->getResults();
        }
        $this->results = array_filter($this->results, function ($item) use ($q) {
            return stripos($item['title'], $q) !== false;
        });
    }

    protected function getResults()
    {
        $key = $this->portfolio.'_'.$this->locale;
        $this->results = Cache::tags(['portfolios'])
            ->remember($key, now()->addDay(), function () {
                return $this->getSimpleSearch();
            });
    }

    public function mount(string $template)
    {
        // You can pass these as parameters or they can be hardcoded.
        $this->template = $template;
        $this->getResults();
    }

    protected function getSimpleSearch()
    {
        if (! Auth::check()) {
            return false;
        }

        return $this->searchCards();

    }
}
