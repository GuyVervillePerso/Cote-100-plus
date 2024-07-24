<?php

namespace App\Livewire;

use Jonassiewertsen\LiveSearch\Http\Livewire\Search;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;
use Statamic\Facades\Taxonomy;

class SearchForm extends Search
{
    public $template;

    public $q = '';

    public $categoryArray = [];

    public $chosenCategory = '';

    public $chosenDateSpan = '0';

    public $dateSpanArray = [];

    public $index;

    public $results = [];

    protected $locale = 'default';

    public function hydrate()
    {
        $this->categoryArray = $this->getCategoryArray();
        $this->dateSpanArray = $this->getDateSpanArray();
    }

    protected function getCategoryArray(): array
    {
        $tags = Taxonomy::findByHandle('categories');
        $tags = $tags->queryTerms()->where('locale', $this->locale)->pluck('title')->toArray();
        array_unshift($tags, __('site.all'));

        return $tags;
    }

    protected function getDateSpanArray()
    {
        return [
            '0' => __('site.datealltime'),
            '1' => __('site.datelast3months'),
            '2' => __('site.datelast6months'),
            '3' => __('site.datelastyear'),
            '4' => __('site.datelast2years'),
            '5' => __('site.datelast3years'), ];
    }

    public function mount(string $template, ?string $index = null, string $cat = 'categories')
    {
        // You can pass these as parameters or they can be hardcoded.
        $this->template = $template;
        $this->index = $index;
        $this->categoryArray = $this->getCategoryArray($cat);
        $this->dateSpanArray = $this->getDateSpanArray();
        $this->getDateSpanArray();
        $this->locale = Site::current()->handle();

    }

    public function render()
    {
        $this->results = $this->getSimpleSearch();

        return view($this->template);
    }

    protected function getSimpleSearch()
    {
        $entries = Entry::query()
            ->where('collection', 'entre_les_lignes')
            ->where('locale', $this->locale)
            ->orderBy('date', 'desc')
            ->offset(3)
            ->limit(4)
            ->get()
            ->map(function ($entry) {
                return [
                    'id' => $entry->id,
                    'title' => $entry->title,
                    'chapeau' => $entry->chapeau,
                    'date' => $entry->date->format('Y-m-d'),
                    'url' => $entry->url(),
                    'image' => $entry->main_visual->toArray(),
                ];
            })->toArray();

        return $entries;
    }
}
