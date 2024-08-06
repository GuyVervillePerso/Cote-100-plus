<?php

namespace App\Livewire;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Jonassiewertsen\LiveSearch\Http\Livewire\Search;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;

class SearchTitle extends Search
{
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
        $query = Entry::query()
            ->where('collection', 'titres')
            ->where('locale', $this->locale)
            ->orderBy('date', 'desc');
        $entries = $query->get();
        $entries = $entries->map(function ($entry) {
            $hasAnalysis = false;
            $included = false;
            $blocked = false;
            $url = $this->getAnalyses($entry->id);
            if ($url) {
                $hasAnalysis = true;
            }
            $termsAllowed = ['cote-100-croissance', 'cote-100-valeur'];
            $entry->variantes_portefeuille->each(function ($item) use ($termsAllowed, &$blocked, &$included) {
                $term = $item->slug;
                if ($this->portfolio === $term) {
                    $included = true;
                    $blocked = false;
                } elseif ($this->portfolio === 'cote-100-abonne' && in_array($term, $termsAllowed)) {
                    $included = true;
                    $blocked = true;
                }
            });
            $new = Carbon::parse($entry->updated_at)->gt(Carbon::now()->subDays(10));

            return [
                'id' => $entry->id,
                'title' => $entry->title,
                'date' => $entry->date->format('Y-m-d'),
                'update' => $entry->updated_at->format('Y-m-d'),
                'url' => $url,
                'hasAnalysis' => $hasAnalysis,
                'image' => $entry->main_visual ? $entry->main_visual->permalink : null,
                'included' => $included,
                'blocked' => $blocked,
                'new' => $new,
            ];
        })->reject(function ($entry) {
            return ! $entry['included'];
        });

        return $entries;
    }

    protected function getSimpleSearch()
    {

        if (! Auth::check()) {
            return false;
        }
        $this->getSession();
        $entries = $this->getAllTitles();

        ///TODO sort entries from search here
        ///
        $entries = $entries->sortBy('blocked')->values()->all();
        ray($entries);
        /*
             $query->when(strlen($this->q) > 4, function ($query) {
            $query->where('title', 'like', '%'.$this->q.'%')
        });
$query->when($this->chosenCategory != '' && $this->chosenCategory != '0', function ($query) {
            $query->whereTaxonomy($this->tagCollection.'::'.$this->chosenCategory);
        });
        */

        /* $entries = $query->where('locale', $this->locale)->get()
             ->map(function ($entry) {
                 return [
                     'id' => $entry->id,
                     'title' => $entry->title,
                     'chapeau' => $entry->chapeau,
                     'date' => $entry->date->format('Y-m-d'),
                     'url' => $entry->url(),
                     'temps_lecture' => $entry->temps_lecture,
                     'image' => $entry->main_visual ? $entry->main_visual->toArray() : null,
                 ];
             })->toArray();*/

        return $entries;
    }
}
