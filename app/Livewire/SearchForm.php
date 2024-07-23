<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Cache;
use Jonassiewertsen\LiveSearch\Http\Livewire\Search;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;
use Statamic\Facades\Term;

class SearchForm extends Search
{
    public $template;

    public $q = '';

    public $categories = [];

    public $chosenCategory = '';

    public $chosenDateSpan = '0';

    public $dateSpanArray = [];

    public $index;

    protected $locale = 'default';

    public function hydrate()
    {
        $this->categories = $this->getCategories();
        $this->dateSpanArray = $this->getDateSpans();
    }

    protected function getCategories(): array
    {
        return Term::query()
            ->where('categories')
            ->where('locale', 'default')
            ->get()->toArray();
    }

    protected function getDateSpans()
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
        $this->categories = $this->getCategories($cat);
        $this->dateSpanArray = $this->getDateSpans();
        $this->getDateSpans();
        $this->locale = Site::current()->handle();

    }

    public function render()
    {
        return view($this->template, [
            'results' => $this->getSimpleSearch(),
        ]);
    }

    protected function getSimpleSearch()
    {
        // Define cache key
        $cacheKey = 'simple_search_cache';

        // Define cache expiry time in seconds (e.g., 3600 seconds = 1 hour)
        $expiryTime = 3600;

        // Use Laravel Cache::remember function to cache the results
        return Cache::remember($cacheKey, $expiryTime, function () {
            return Entry::query()
                ->where('collection', 'entre_les_lignes')
                ->where('locale', $this->locale)
                ->orderBy('date', 'desc')
                ->offset(3)
                ->limit(4)
                ->get();
        });
    }
}
