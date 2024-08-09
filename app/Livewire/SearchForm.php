<?php

namespace App\Livewire;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Jonassiewertsen\LiveSearch\Http\Livewire\Search;
use Statamic\Facades\Entry;
use Statamic\Facades\Taxonomy;

class SearchForm extends Search
{
    public $bubble = '';

    public $categoryArray = [];

    public $chosenCategory = '';

    public $chosenDateSpan = '0';

    public $dateSpanArray = [];

    public $index;

    public $currentOffset = 3;

    public $noOffset = false;

    public $preservedQuery;

    public $q = '';

    public $results = [];

    public $tagCollection = '';

    public $template;

    protected $locale = 'default';

    protected $entries;

    protected $collection = 'entre_les_lignes';

    public function hydrate() {}

    protected function getCategoryArray(): array
    {
        $tagArray = [];
        $tagArray[] = ['slug' => '0', 'title' => __('site.all')];
        $tags = Taxonomy::findByHandle($this->tagCollection);

        $tags = $tags->queryTerms()
            ->where('locale', $this->locale)
            ->get()
            ->toArray();
        foreach ($tags as $tag) {
            $tagArray[] = [
                'slug' => $tag['slug'],
                'title' => $tag['title'],
            ];
        }

        return $tagArray;
    }

    protected function getDateSpanArray()
    {
        return [
            '0' => __('site.datealltime'),
            '1' => __('site.datelast3months'),
            '2' => __('site.datelast6months'),
            '3' => __('site.date1yearAgo'),
            '4' => __('site.date2yearsAgo'),
            '5' => __('site.date3yearsAgo'), ];
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
        $this->results = $this->getSimpleSearch();

    }

    public function render()
    {
        return view($this->template, ['bubble' => $this->bubble]);
    }

    public function getCachedEntries()
    {
        $key = $this->collection.'_'.$this->locale;
        $entries = Cache::tags([$this->collection])
            ->remember($key, now()->addDay(), function () {
                return Entry::query()
                    ->where('collection', $this->collection)
                    ->where('locale', $this->locale)
                    ->orderBy('date', 'desc')
                    ->get()
                    ->toArray();
            });

        return collect($entries);
    }

    protected function getSimpleSearch()
    {
        $collection = $this->getCachedEntries();
        $searchResults = $collection->when(strlen($this->q) > 4, function ($collection) {
            return $collection->filter(function ($entry) {
                $html_content_blocks = $entry['html_content'];
                $html_string = '';
                foreach ($html_content_blocks as $block) {
                    if ($block['type'] == 'text') {
                        $html_string .= strip_tags($block['text']);
                    }
                }
                $result = str_contains($entry['title'], $this->q) ||
                    str_contains($entry['chapeau'], $this->q) ||
                    str_contains($html_string, $this->q);

                return $result;
            });
        })->when($this->chosenCategory != '' && $this->chosenCategory != '0', function ($collection) {
            return $collection->filter(function ($entry) {
                return in_array($this->chosenCategory, $entry['categories']);
            });
        })->when($this->chosenDateSpan != '0', function ($collection) {
            $fromDate = null;
            $toDate = null;
            switch ($this->chosenDateSpan) {
                case '1':
                    $fromDate = Carbon::now()->subMonths(6);
                    $toDate = Carbon::now()->subMonths(3);
                    break;
                case '2':
                    $fromDate = Carbon::now()->subYears(1);
                    $toDate = Carbon::now()->subMonths(6);
                    break;
                case '3':
                    $fromDate = Carbon::now()->subYears(2);
                    $toDate = Carbon::now()->subYears(1);
                    break;
                case '4':
                    $fromDate = Carbon::now()->subYears(3);
                    $toDate = Carbon::now()->subYears(2);
                    break;
                case '5':
                    $fromDate = Carbon::now()->subYears(3);
                    break;
            }

            return $collection->filter(function ($entry) use ($fromDate, $toDate) {
                $entryDate = new Carbon($entry['date']);
                if ($fromDate && $toDate) {
                    return $entryDate->gt($fromDate) && $entryDate->lte($toDate);
                } elseif ($fromDate) {
                    return $entryDate->gte($fromDate);
                } elseif ($toDate) {
                    return $entryDate->lte($toDate);
                }

                return true;
            });
        })->values();
        $searchResults = $searchResults->map(function ($entry) {
            return [
                'id' => $entry['id'],
                'title' => $entry['title'],
                'chapeau' => $entry['chapeau'],
                'date' => $entry['date']->format('Y-m-d'),
                'url' => $entry['url'],
                'image' => $entry['main_visual'] ? $entry['main_visual']['id'] : null,
            ];
        })->toArray();
        if (count($searchResults) < 5) {
            $this->noOffset = true;
        }
        ray($searchResults);

        if (! $this->noOffset) {
            $searchResults = array_slice($searchResults, $this->currentOffset, 4);
        }

        return $searchResults;
    }

    public function loadMore()
    {
        $this->currentOffset += 4;
        $this->results = $this->getSimpleSearch();
    }

    public function loadLess()
    {
        $this->currentOffset -= 4;
        $this->results = $this->getSimpleSearch();
    }

    public function updatedQ($value)
    {
        $this->resetSearch($value);
        $this->results = $this->getSimpleSearch();
    }

    public function updatedChosenCategory($value)
    {
        $this->resetSearch($value);
        $this->results = $this->getSimpleSearch();
    }

    public function updatedChosenDateSpan($value)
    {
        $this->resetSearch($value);
        $this->results = $this->getSimpleSearch();
    }

    public function resetSearch($value)
    {
        if ($value == '' || $value == '0' || $value == null) {
            $this->noOffset = false;
            $this->currentOffset = 0;
        } else {
            $this->noOffset = true;
        }
        ray($this->noOffset);
    }
}
