<?php

if (!function_exists('mockAuthHeaders')) {
    function mockAuthHeaders(App\User $user)
    {
        $headers = ['Accept' => 'application/json'];

        $token = \JWTAuth::fromUser($user);
        \JWTAuth::setToken($token);
        $headers['Authorization'] = 'Bearer ' . $token;


        return compact('headers', 'token');
    }
}
