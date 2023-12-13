<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\PasswordResetToken;

use App\Mail\ResetPasswordMail;
use App\Http\Requests\ChangePasswordRequest;

use Illuminate\Support\Facades\Log;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ResetPasswordController extends Controller
{
    public function sendEmail(Request $request)
    {
        if (!$this->validateEmail($request->email)) {
            return $this->failedResponse();
        }

        $this->send($request->email);

        return $this->successResponse();
    }

    public function send($email)
    {
        $token = $this->createToken($email);
        Mail::to($email)->send(new ResetPasswordMail($token, $email));
    }

    public function createToken($email)
    {
        $oldToken = PasswordResetToken::where('email', $email)->first();

        if ($oldToken) {
            return $oldToken->token;
        }

        $token = Str::random(60);
        $this->saveToken($token, $email);

        return $token;
    }


    public function saveToken($token, $email)
    {
        PasswordResetToken::create([
            'email' => $email,
            'token' => $token,
            'created_at' => now(),
        ]);
    }


    public function validateEmail($email)
    {
        return !!User::where('email', $email)->first();
    }

    public function failedResponse()
    {
        return response()->json([
            'error' => 'Email does\'t exist on our database',
        ], Response::HTTP_NOT_FOUND);
    }


    public function successResponse()
    {
        return response()->json([
            'data' => 'Password reset email sent. Please, check your inbox.',
        ], Response::HTTP_OK);
    }


    public function processPasswordChange(ChangePasswordRequest $request)
    {
        return $this->getPasswordResetTableRow($request) ?
            $this->changePassword($request) :
            $this->tokenNotFoundResponse();
    }


    private function getPasswordResetTableRow($request)
    {
        $tokenRow = PasswordResetToken::where('email', $request->email)
            ->where('token', $request->reset_token)
            ->where('created_at', '>', now()->subHours(1)) // Cambiamos '<' a '>'
            ->first();

        return $tokenRow;
    }


    private function changePassword($request)
    {
        $user = User::whereEmail($request->email)->first();

        if ($user) {
            $user->update(['password' => bcrypt($request->password)]);
        }

        $tokenRow = $this->getPasswordResetTableRow($request);

        if ($tokenRow) {
            $tokenRow->delete();
        }

        return response()->json(['data' => 'Password changed.'], Response::HTTP_CREATED);
    }

    private function tokenNotFoundResponse()
    {
        return response()->json(['error' => 'Token or email not valid.'],
        Response::HTTP_UNPROCESSABLE_ENTITY);
    }

}
