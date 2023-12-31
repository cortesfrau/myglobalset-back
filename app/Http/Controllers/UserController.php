<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    /**
     * UserController constructor.
     * Set up middleware to ensure authentication for API requests.
     */
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * Update the user profile.
     *
     * @param Request $request - HTTP request containing user profile data.
     * @return \Illuminate\Http\JsonResponse - JSON response indicating success or failure.
     */
    public function update(Request $request)
    {
        // Get the authenticated user
        $authenticatedUser = auth()->user();

        // Find the user by their ID
        $user = User::find($request->input('id'));

        // Check if the user exists
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        // Verify that the authenticated user is the owner of the profile
        if ($authenticatedUser->id !== $user->id) {
            return response()->json(['error' => 'You do not have permission to update this profile'], 403);
        }

        // Validate the form data
        $request->validate([
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required|email',
            'password' => 'sometimes|nullable|confirmed|min:8',
        ]);

        // Update user fields
        $user->first_name = $request->input('first_name');
        $user->last_name = $request->input('last_name');
        $user->email = $request->input('email');

        // If a new password is provided, update it
        if ($request->filled('password')) {
            $user->password = bcrypt($request->input('password'));
        }

        // Save changes to the database
        $user->save();

        // Successful response
        return response()->json(['message' => 'User updated successfully'], 200);
    }
}
