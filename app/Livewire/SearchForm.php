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

    public $tagCollection = '';

    public $chosenDateSpan = '0';

    public $dateSpanArray = [];

    public $index;

    public $results = [];

    public $bubble = '';

    protected $locale = 'default';

    protected $entries;

    protected $collection = 'entre_les_lignes';

    public function hydrate()
    {
        $this->categoryArray = $this->getCategoryArray();
        $this->dateSpanArray = $this->getDateSpanArray();

    }

    protected function getCategoryArray(): array
    {
        $tags = Taxonomy::findByHandle($this->tagCollection);
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

    public function mount(string $template, ?string $index = null, string $cat = 'categories', string $collection = 'entre_les_lignes', string $bubble = '')
    {
        // You can pass these as parameters or they can be hardcoded.
        $this->template = $template;
        $this->bubble = $bubble;
        $this->index = $index;
        $this->tagCollection = $cat;
        $this->collection = $collection;
        $this->categoryArray = $this->getCategoryArray();
        $this->dateSpanArray = $this->getDateSpanArray();
        $this->getDateSpanArray();
        $this->locale = Site::current()->handle();

    }

    public function render()
    {
        $this->results = $this->getSimpleSearch();

        return view($this->template, ['bubble' => $this->bubble]);
    }

    protected function getSimpleSearch()
    {
        $query = Entry::query()
            ->where('collection', $this->collection)
            ->orderBy('date', 'desc')
            ->offset(3)
            ->limit(4);
        $query->when(strlen($this->q) > 4, function ($query) {
            $query->where('title', 'like', '%'.$this->q.'%')
                ->orWhere('chapeau', 'like', '%'.$this->q.'%')
                ->orWhere('html_content', 'like', '%'.$this->q.'%');
        });

        $query->when($this->chosenCategory != '', function ($query) {
            $category = $this->categoryArray[$this->chosenCategory];
            $query->whereTaxonomy($this->tagCollection.'::'.$category);
        });
        $entries = $query->where('locale', $this->locale)->get()
            ->map(function ($entry) {
                return [
                    'id' => $entry->id,
                    'title' => $entry->title,
                    'chapeau' => $entry->chapeau,
                    'date' => $entry->date->format('Y-m-d'),
                    'url' => $entry->url(),
                    'image' => $entry->main_visual ? $entry->main_visual->toArray() : null,
                ];
            })->toArray();

        return $entries;
    }

    /*  public function updated()
      {
          if (strlen($this->q) > 4 || $this->chosenCategory != '' || $this->chosenDateSpan != '0') {
              $this->results = $this->getSimpleSearch();
          }
      }*/

    protected function getWordSearch()
    {
        $this->entries
            ->where('title', 'like', '%'.$this->q.'%');
    }
}
