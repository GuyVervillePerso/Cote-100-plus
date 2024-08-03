<?php

namespace App\Http\Middleware;

use App\Models\Share;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyToken
{
    protected $token = '';

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $this->token = strip_tags($request->get('token'));
        if ($this->TokenIsValid()) {
            session()->put('canread', true);
        } else {
            session()->put('canread', false);
        }

        return $next($request);
    }

    protected function TokenIsValid($token)
    {
        $share = Share::where('token', $this->token)->first()->toArray();
        ray($share);

    }
}
