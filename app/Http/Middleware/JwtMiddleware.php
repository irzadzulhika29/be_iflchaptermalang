<?php

namespace App\Http\Middleware;

use Closure;
use Tymon\JWTAuth\Facades\JWTAuth;
use Exception;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Http\Middleware\BaseMiddleware;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\URL;

class JwtMiddleware
{
  /**
   * Handle an incoming request.
   *
   * @param  \Illuminate\Http\Request  $request
   * @param  \Closure  $next
   * @return mixed
   */
  public function handle(Request $request, Closure $next)
  {
    try {
      JWTAuth::parseToken()->authenticate();
    } catch (Exception $e) {
      if ($e instanceof TokenInvalidException) {
        return response()->json(['status' => 'Token is Invalid'], Response::HTTP_UNAUTHORIZED);
      } else if ($e instanceof TokenExpiredException) {
        return response()->json(['status' => 'Token is Expired'], Response::HTTP_UNAUTHORIZED);
      } else {
        return response()->json(['status' => 'Authorization Token not found'], Response::HTTP_BAD_REQUEST);
      }
    }
    return $next($request);
  }
}
