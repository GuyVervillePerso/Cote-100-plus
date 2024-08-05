<?php

namespace App\Tags;

use Statamic\Tags\Tags;

class ClearSession extends Tags
{
    /**
     * The {{ clear_session }} tag.
     *
     * @return string|array
     */
    public function index()
    {
        session()->forget('canreadarticle');

    }

    /**
     * The {{ clear_session:example }} tag.
     *
     * @return string|array
     */
    public function example()
    {
        //
    }
}
