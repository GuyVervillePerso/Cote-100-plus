<?php

namespace App\Livewire;

use App\Traits\Cards;
use Illuminate\Support\Facades\Auth;
use Jonassiewertsen\LiveSearch\Http\Livewire\Search;

class SearchTitle extends Search
{
    use Cards;

    public $results = [];

    public $blockedResults = [];

    protected $template;

    public function render()
    {
        $this->results = $this->getSimpleSearch();

        return view($this->template);
    }

    public function mount(string $template)
    {
        // You can pass these as parameters or they can be hardcoded.
        $this->template = $template;
    }

    protected function getSimpleSearch()
    {
        if (! Auth::check()) {
            return false;
        }

        return $this->searchCards();

    }
}
