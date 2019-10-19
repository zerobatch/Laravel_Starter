<?php

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Http\Request;

class SendPasswordResetController extends Controller
{

    use SendsPasswordResetEmails;

    public function forgot(Request $request)
    {
        return $this->sendResetLinkEmail($request);
    }

    /**
     * Overrides for trait SendsPasswordResetEmails
     */
    protected function sendResetLinkResponse($request, $response)
    {
        return response()->json(['success' => true, 'message' => 'Password reset email sent'], 201);
    }

    protected function sendResetLinkFailedResponse($request, $response)
    {
        return response()->json(['success' => false, 'error' => "Email could not be sent to this email address"], 501);
    }

    /**
     * End Overrides
     */
}
