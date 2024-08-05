<?php

namespace App\Tags;

use Carbon\Carbon;
use Statamic\Tags\Tags;

class GetDateAgo extends Tags
{
    /**
     * The {{ get_date_ago }} tag.
     *
     * @return string|array
     */
    public function index()
    {
        $date = $this->params->get('date');

        // Compare it with 5 days interval
        ray(Carbon::parse($date)->lt(Carbon::now()->subDays(2)));

        return Carbon::parse($date)->lt(Carbon::now()->subDays(5));
    }

    /**
     * The {{ get_date_ago:example }} tag.
     *
     * @return string|array
     */
    public function example()
    {
        //
    }
}
