<?php

namespace App\Http\Middleware;

use Closure;
use Benfin\Api\GlobalStorage;
use Illuminate\Http\Response;

class RestrictionMoney
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
        if ((!isset($restrictions['restriction_money'])) || ($restrictions['restriction_money']) ) {
            return Response::error(["Вывод средств заблокирован, обратитесь в техподдержку"], 401);
        }

        return $next($request);
    }

}
