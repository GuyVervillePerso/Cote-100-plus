@component('mail::message')
    # @lang('site.shareHello'),

    {{$details['sender_name']}} @lang('site.shareMessage1')

    ## {{$details['article_title']}}

    @lang('site.shareMessage2')

    @component('mail::button', ['url' => $details['tokenurl']])
        {{__('site:viewarticle')}}
    @endcomponent

    Thank You,<br>
    {{ config('app.name') }}
@endcomponent
