<?php

namespace App\Http\Controllers;

use App\Models\Share;
use DateTime;
use Statamic\Facades\Entry;

class LireArticleController extends Controller
{
    protected $token;

    protected $article;

    protected $sharedArticle;

    public function lireArticle($token)
    {
        $now = new DateTime;
        $canread = false;
        session()->put('canreadarticle', $canread);
        $this->token = strip_tags($token);
        $this->sharedArticle = Share::where('token', $this->token)->first();
        if ($this->sharedArticle) {
            $already = $this->sharedArticle->read_at;
            if (! $already) {
                $nowFormatted = $now->format('Y-m-d H:i:s');
                $this->sharedArticle->read_at = $nowFormatted;
                $this->sharedArticle->save();
            }
            $readAt = new DateTime($already);
            $interval = $now->diff($readAt);
            if ($interval->h < 24) {
                $canread = true;
            }
        }
        session()->put('canreadarticle', $canread);
        if ($canread) {
            $entry = Entry::query()->where('id', $this->sharedArticle->article_id)->first();

            return redirect(url($entry->url()));
        } else {
            abort(403);
        }
    }
}
