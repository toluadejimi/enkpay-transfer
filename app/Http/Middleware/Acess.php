<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class Acess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {


        if($request->header('Authorization') == 'Bearer '.$request->bearerToken() )
        {

            return $next($request);

        }elseif($request->header('Authorization') != 'Bearer '.$request->bearerToken() ){


            abort(response()->json(
                [
                    'status_code' => 401,
                    'status' => false,
                    'message' => 'Token Expired, Please login to continue.',
                ], 401));

        }elseif (Auth::guard('api')->check() != true) {
            abort(response()->json(
                [
                    'status' => false,
                    'message' => 'Unauthorized',
                ], 500));
        }
    }
}
