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
        switch ($this->locale) {
            case 'anglais':
                $lang = 'en';
                break;
            case 'default':
                $lang = 'fr';
                break;
        }
        Carbon::setLocale($lang);
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
            $new = false;
            switch ($entry->statut) {
                case 'achat-recemment':
                    $header = __('site.statusBought').' '.$entry->date_achat_vente->format('d/m/Y');
                    $new = Carbon::parse($entry->date_achat_vente)->gt(Carbon::now()->subDays(10));
                    break;
                case 'vendu':
                    $header = __('site.statusSold').' '.$entry->date_achat_vente->format('d/m/Y');
                    $new = Carbon::parse($entry->date_achat_vente)->gt(Carbon::now()->subDays(10));
                    break;
                case 'acquisition-potentielle':
                    $header = __('site.statusUnderReview');
                    break;
                default:
                    if ($hasAnalysis) {
                        $header = __('site.statusUpdated').' '.$entry->updated_at->format('d/m/Y');
                        $new = Carbon::parse($entry->updated_at)->gt(Carbon::now()->subDays(10));
                    } else {
                        $header = __('site.statusBought').' '.$entry->date_de_recommandation->format('d/m/Y');
                        $new = Carbon::parse($entry->date_de_recommandation)->gt(Carbon::now()->subDays(10));
                    }
                    break;
            }

            return [
                'id' => $entry->id,
                'title' => $entry->title,
                'description' => $entry->courte_description,
                'date' => $entry->date->format('Y-m-d'),
                'date_de_recommandation' => ucfirst($entry->date_de_recommandation->translatedFormat('F Y')),
                'cours_actuel' => $this->actualValueInDollars($entry->cours_actuel, $entry->devise_evaluation),
                'update' => $entry->updated_at->format('Y-m-d'),
                'url' => $url,
                'bref' => $this->createInBriefArray($entry),
                'stock' => $entry->symbole_en_bourse,
                'hasAnalysis' => $hasAnalysis,
                'image' => $entry->main_visual ? $entry->main_visual->permalink : null,
                'included' => $included,
                'blocked' => $blocked,
                'header' => $header,
                'new' => $new,
            ];
        })->reject(function ($entry) {
            return ! $entry['included'];
        });

        return $entries;
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

    protected function actualValueInDollars($value, $format)
    {
        switch ($format) {
            case 'ca':
                $string = $this->locale = 'default' ? $value.' $CAN' : '$'.$value.'CAN';
                break;
            case 'us':
                $string = $this->locale = 'default' ? $value.' $US' : '$'.$value.'US';
                break;
            default:
                $string = $value;
                break;
        }

        return $string;
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
