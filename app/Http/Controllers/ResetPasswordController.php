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

/**
 * Class ResetPasswordController
 * @package App\Http\Controllers
 */
class ResetPasswordController extends Controller
{
    /**
     * Send a reset password email.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendEmail(Request $request)
    {
        if (!$this->validateEmail($request->email)) {
            return $this->failedResponse();
        }

        $this->send($request->email);

        return $this->successResponse();
    }

    /**
     * Send the reset password email.
     *
     * @param string $email
     * @return void
     */
    public function send($email)
    {
        $token = $this->createToken($email);
        Mail::to($email)->send(new ResetPasswordMail($token, $email));
    }

    /**
     * Create a reset password token.
     *
     * @param string $email
     * @return string
     */
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

    /**
     * Save the reset password token to the database.
     *
     * @param string $token
     * @param string $email
     * @return void
     */
    public function saveToken($token, $email)
    {
        PasswordResetToken::create([
            'email' => $email,
            'token' => $token,
            'created_at' => now(),
        ]);
    }

    /**
     * Validate if the given email exists in the database.
     *
     * @param string $email
     * @return bool
     */
    public function validateEmail($email)
    {
        return !!User::where('email', $email)->first();
    }

    /**
     * Response for a failed email validation.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function failedResponse()
    {
        return response()->json([
            'error' => 'Email doesn\'t exist in our database',
        ], Response::HTTP_NOT_FOUND);
    }

    /**
     * Response for a successful email validation.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function successResponse()
    {
        return response()->json([
            'data' => 'Email sent. Please, check your inbox.',
        ], Response::HTTP_OK);
    }

    /**
     * Process the password change based on the provided request.
     *
     * @param ChangePasswordRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function processPasswordChange(ChangePasswordRequest $request)
    {
        return $this->getPasswordResetTableRow($request) ?
            $this->changePassword($request) :
            $this->tokenNotFoundResponse();
    }

    /**
     * Get the password reset token row based on the request.
     *
     * @param Request $request
     * @return mixed
     */
    private function getPasswordResetTableRow($request)
    {
        $tokenRow = PasswordResetToken::where('email', $request->email)
            ->where('token', $request->reset_token)
            ->where('created_at', '>', now()->subHours(1)) // Change '<' to '>'
            ->first();

        return $tokenRow;
    }

    /**
     * Change the user's password.
     *
     * @param ChangePasswordRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
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

        return response()->json(['data' => 'Password changed successfully.'], Response::HTTP_CREATED);
    }

    /**
     * Response for a token not found scenario.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    private function tokenNotFoundResponse()
    {
        return response()->json(['error' => 'Something went wrong. Please, request a new link.'],
        Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
