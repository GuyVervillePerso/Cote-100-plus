<?php

namespace App\Livewire;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Livewire\Component;
use Statamic\Facades\Entry;
use Statamic\Facades\Taxonomy;

class SearchForm extends Component
{
    public $bubble = '';

    public $noSearch = true;

    protected $tagEntries;

    public $categoryArray = [];

    public $chosenCategory = '';

    public $chosenDateSpan = '0';

    public $dateSpanArray = [];

    public $index;

    public $currentOffset = 3;

    public $q = '';

    protected $protectedResults = [];

    public $results = [];

    public $tagCollection = '';

    public $template;

    protected $locale = 'default';

    protected $entries;

    public $collection = 'entre_les_lignes';

    protected function getSession()
    {
        $this->collection = session('collection');
        $this->index = session('index');
        $this->tagCollection = session('category');
        $this->locale = session('locale');
    }

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

    public function mount(string $template, ?string $index = null, ?string $tagCat = '', $collection = '', string $bubble = '')
    {
        switch (App::getLocale()) {
            case 'en':
                $this->locale = 'anglais';
                break;
            default:
                $this->locale = 'default';
                break;
        }
        // You can pass these as parameters or they can be hardcoded.
        $this->template = $template;
        $this->bubble = $bubble;
        $this->index = $index;
        $this->tagCollection = $tagCat;
        $this->collection = $collection;
        $this->categoryArray = $this->getCategoryArray();
        $this->dateSpanArray = $this->getDateSpanArray();
        $this->getDateSpanArray();
        session(['collection' => $collection]);
        session(['category' => $tagCat]);
        session(['index' => $index]);
        session(['locale' => $this->locale]);
        $this->getAllEntries();
        $this->getSimpleSearch();
    }

    public function render()
    {
        $this->categoryArray = $this->getCategoryArray();
        $this->dateSpanArray = $this->getDateSpanArray();
        $this->getSession();

        return view($this->template, ['bubble' => $this->bubble]);
    }

    protected function getAllEntries()
    {
        /*        $key = $this->collection.'_'.$this->locale;
                $cacheKey = 'cache:'.$key;
                $tagKey = 'tag:'.$this->collection;*/

        $this->protectedResults = $this->getCachedEntries();

        /*        $this->protectedResults = Cache::remember($cacheKey, now()->addDay(), function () {
                    return $this->getCachedEntries();
                });*/

    }

    public function clearCacheByTag()
    {
        $tagKey = 'tag:'.$this->collection;
        $tagEntries = Cache::get($tagKey, []);

        foreach ($tagEntries as $cacheKey) {
            Cache::forget($cacheKey);
        }

        Cache::forget($tagKey);
    }

    protected function getCachedEntries()
    {
        $entries = Entry::query()
            ->where('collection', $this->collection)
            ->orderBy('date', 'desc')
            ->where('locale', $this->locale)
            ->get();
        $transformedEntries = $entries->map(function ($entry) {
            $html_content_blocks = $entry->html_content;
            $html_string = '';
            foreach ($html_content_blocks as $block) {
                if ($block['type'] == 'text') {
                    $html_string .= strip_tags($block['text']);
                }
            }
            $particles = ['l’', 'd’', 'm’', 'n’', '’s', 'les', 'des', 'mes', 'ses', 'ont', 'ils', 'elles', 'they', 'she', 'he', 'une'];
            $str = strtolower($entry->title.' '.strip_tags($entry->chapeau).' '.$html_string);
            $str = str_replace("'", '’', $str);
            $str = str_ireplace($particles, '', $str);
            $str = preg_replace('/\b\w{1,2}\b/u', ' ', $str);
            $str = preg_replace('/\s+/', ' ', $str);
            $termSlugs = collect($entry->categories->toAugmentedArray('slug'))
                ->map(function ($term) {
                    return $term['slug']->value();
                })
                ->implode(',');

            $data = [
                'id' => $entry->id,
                'title' => $entry->title,
                'chapeau' => Str::limit($entry->chapeau, 200, '…', true),
                'image' => $entry->main_visual ? $entry->main_visual->url : null,
                'categories' => $termSlugs,
                'url' => $entry->url(),
                'date' => $entry->date->format('Y-m-d'),
                'temps_lecture' => $entry->temps_lecture,
                'text' => $str,
            ];

            return $data; // Use the id as key and the filtered string as value
        });

        return $transformedEntries->toArray();
    }

    public function getSimpleSearch()
    {

        if (! Auth::check()) {
            return false;
        }
        $this->results = array_slice($this->protectedResults, $this->currentOffset, 4);

    }

    public function loadMore()
    {
        $this->getAllEntries();
        $this->currentOffset += 4;
    }

    public function loadLess()
    {
        $this->getAllEntries();
        $this->currentOffset -= 4;
    }

    public function resetSearch($value)
    {
        $this->getAllEntries();
        // If the search query is empty, we should not do a search.
        if (empty($value)) {
            $this->results = $this->protectedResults;
            $this->noSearch = true;

            return;
        }
        $this->noSearch = false;
    }

    public function updatedQ($value)
    {
        $this->resetSearch($value);
        // Perform search using the updated search query.
        // Perform search using the updated search query.
        $this->results = array_filter($this->protectedResults, function ($item) use ($value) {
            return Str::contains(Str::lower($item['text']), Str::lower($value));
        });
    }

    public function updatedChosenCategory($value)
    {
        $this->resetSearch($value);
        $this->results = array_filter($this->protectedResults, function ($item) use ($value) {
            return Str::contains(Str::lower($item['categories']), Str::lower($value));
        });
    }

    public function updatedChosenDateSpan($value)
    {
        $this->resetSearch($value);

        // Get current timestamp
        $dateNow = strtotime(date('Y-m-d'));

        // Apply filters based on chosenDateSpan
        switch ($this->chosenDateSpan) {
            case '1':
                $threshold = strtotime('-3 months', $dateNow);
                $results = array_filter($this->protectedResults, function ($item) use ($threshold) {
                    return strtotime($item['date']) >= $threshold;
                });
                break;
            case '2':
                $threshold = strtotime('-6 months', $dateNow);
                $results = array_filter($this->protectedResults, function ($item) use ($threshold) {
                    return strtotime($item['date']) >= $threshold;
                });
                break;
            case '3':
                $thresholdMin = strtotime('-2 years', $dateNow);
                $thresholdMax = strtotime('-1 year', $dateNow);
                $results = array_filter($this->protectedResults, function ($item) use ($thresholdMin, $thresholdMax) {
                    $itemDate = strtotime($item['date']);

                    return $itemDate >= $thresholdMin && $itemDate < $thresholdMax;
                });
                break;
            case '4':
                $thresholdMin = strtotime('-3 years', $dateNow);
                $thresholdMax = strtotime('-2 years', $dateNow);
                $results = array_filter($this->protectedResults, function ($item) use ($thresholdMin, $thresholdMax) {
                    $itemDate = strtotime($item['date']);

                    return $itemDate >= $thresholdMin && $itemDate < $thresholdMax;
                });
                break;
            case '5':
                $threshold = strtotime('-3 years', $dateNow);
                $results = array_filter($this->protectedResults, function ($item) use ($threshold) {
                    return strtotime($item['date']) < $threshold;
                });
                break;
            default:
                $results = $this->protectedResults;
        }
        $this->results = $results;
    }
}
