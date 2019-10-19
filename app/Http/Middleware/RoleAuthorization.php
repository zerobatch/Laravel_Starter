<?php

namespace App\Http\Middleware;

use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

use Closure;

class RoleAuthorization
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, ...$middleware_roles)
    {
        try {
            //Access token from the request        
            $token = JWTAuth::parseToken();
            //Try authenticating user       
            $user = $token->authenticate();

            $user_roles = explode(",", $user->role_names);

            if (isset($user) && count(array_intersect($user_roles, $middleware_roles))) {
                $request->user = $user;
                return $next($request);
            }

            return $this->unauthorized();
        } catch (TokenExpiredException $e) {
            //Thrown if token has expired        
            return $this->unauthorized('Your token has expired. Please, login again.', 'expired');
        } catch (TokenInvalidException $e) {
            //Thrown if token invalid
            return $this->unauthorized('Your token is invalid. Please, login again.', 'invalid');
        } catch (JWTException $e) {
            //Thrown if token was not found in the request.
            return $this->unauthorized('Please, attach a Bearer Token to your request', 'attachment');
        }
    }

    private function unauthorized($message = null, $type = "")
    {
        return response()->json([
            'success' => false,
            'error' => $message ?? 'You are unauthorized to access this resource',
            'type' =>  $type
        ], 401);
    }
}
