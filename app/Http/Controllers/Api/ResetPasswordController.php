<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Http\Request;

class ResetPasswordController extends Controller
{
    use ResetsPasswords;


    public function resetUserPassword(Request $request)
    {
        return $this->reset($request);
    }

    /**
     * Overrides for ResetPassword trait
     */

    protected function resetPassword($user, $password)
    {
        $user->password = bcrypt($password);
        $user->setRememberToken(\Str::random(60));
        $user->save();

        event(new PasswordReset($user));
    }

    protected function sendResetResponse($request, $response)
    {
        return response()->json(['success' => true, 'message' => 'Password reset done, now you can login with this new one'], 201);
    }

    protected function sendResetFailedResponse($request, $response)
    {
        return response()->json(['success' => false, 'error' => "Token invalid or expired"], 501);
    }
    /**
     * End Overrides
     */
}
