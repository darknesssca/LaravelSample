<?php

namespace App\Http\Middleware;

use Closure;
use Benfin\Api\GlobalStorage;

class RestrictionPolicy
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $restrictions = GlobalStorage::getRestrictions();
        if ((!isset($restrictions['restriction_policy'])) || ($restrictions['restriction_policy']) ) {
            return Response::error(["Оформление полисов заблокировано, обратитесь в техподдержку"], 401);
        }

        return $next($request);
    }

}
