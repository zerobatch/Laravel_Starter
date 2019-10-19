<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\User;
use Tymon\JWTAuth\Exceptions\UserNotDefinedException;

class AuthController extends Controller
{
    public function login()
    {
        $result = $this->generateToken();

        return response()->json($result, $result['httpCode']);
    }

    public function logout()
    {
        auth()->logout();

        return response()->json(['success' => true, 'message' => 'Successfully logged out'], 201);
    }

    public function getCurrentUser()
    {
        try {
            $user = auth()->userOrFail();

            return response()->json(['success' => true, 'user' => $user]);
        } catch (UserNotDefinedException $error) {
            return response()->json(['success' => false, 'error' => $error->getMessage()]);
        }
    }

    public function refreshToken(Request $request) {
        $refreshedToken = auth()->refresh();

        return response()->json(['success' => true, 'refreshed_token' => $refreshedToken]);
    }

    protected function generateToken(): array
    {
        $credentials = request(['email', 'password']);

        if (!$token = auth()->attempt($credentials)) {
            return ['success' => false, 'error' => 'The email or password is incorrect', 'httpCode' => 401];
        }

        return [
            'success' => true,
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => auth()->factory()->getTTL() * 60,
            'httpCode' => 201
        ];
    }
}
