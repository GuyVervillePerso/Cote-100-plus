<?php

namespace App\Livewire;

use App\Mail\ShareArticleMail;
use App\Models\Share;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Component;
use Statamic\Facades\Entry;

class ShareArticle extends Component
{
    public $article_id = '';

    public $article_permalink = '';

    public $article_title = '';

    public $sender_id = '';

    public $sender_email = '';

    public $sender_name = '';

    public $template;

    public $emailerror = '';

    public $emailsuccess = '';

    public $token = '';

    public $current_id = '';

    public $destinee_email = '';

    public function mount($articleid, $permalink, $title, $senderemail, $sendername, $senderid)
    {
        $this->article_permalink = $permalink;
        $this->article_title = $title;
        $this->article_id = $articleid;
        $this->sender_id = $senderid;
        $this->sender_email = $senderemail;
        $this->sender_name = $sendername;
    }

    public function render()
    {
        return view($this->template);
    }

    public function save()
    {
        $this->token = Str::random(32);
        $validated = $this->validate();
        // Generate a unique token
        $this->token = Str::random(60);

        // Search if the user hasn't already sent this article
        $share = Share::where('destinee_email', $this->destinee_email)
            ->where('article_id', $this->article_id)
            ->where('sender_id', $this->sender_id)
            ->first();
        if ($share) {
            $this->emailerror = __('site.emailerror');
            $this->emailsuccess = '';
        } else {
            $entry = Entry::query()
                ->where('collection', 'entre_les_lignes')
                ->where('id', $this->article_id)
                ->first();
            if ($entry) {
                $this->emailerror = '';
                $this->emailsuccess = __('site.emailsuccess');
                $validated['token'] = $this->token;
                $validated['article_id'] = $this->article_id;
                $validated['article_permalink'] = $this->article_permalink;
                $validated['sender_id'] = $this->sender_id;
                $validated['email'] = $this->destinee_email;
                $validated['sender_name'] = $this->sender_name;
                // Save token, email and article_id in shares table
                Share::create($validated);
                $validated['image'] = $entry->main_visual->url;
                $validated['tokenurl'] = url('/lirearticle/'.$this->token);
                $validated['article_title'] = $entry->title;
                // Send an email
                Mail::to($this->destinee_email)->send(new ShareArticleMail($validated));
            } else {
                $this->emailerror = __('site.problem');
            }

        }
    }

    protected function rules()
    {
        return [
            'destinee_email' => 'required|email', // This line sets the 'email' attribute as required.
            'article_id' => 'required|string',
            'token' => 'required|string',
            'sender_id' => 'string',
        ];
    }

    protected function messages()
    {
        return [
            'destinee_email.required' => __('site.emailrequired'),
            'token.string' => __('site.tokenstring'),
            'article_id.required' => __('site.articlerequired'),
        ];
    }
}
