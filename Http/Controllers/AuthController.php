<?php

namespace Modules\User\Http\Controllers;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Hashing\BcryptHasher;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use Modules\User\Entities\User;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthController extends Controller
{

    /**
     * Display a specific user token
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request, BcryptHasher $hash)
    {
        // Validate user credentials
        try {
            $request->validate([
                'email' => 'required|string|email',
                'password' => 'required|string|min:6',
            ]);
        } catch (ValidationException $e) {
            return json_response()->unauthorized('You are using incorrect email address or password.');
        }

        // Get the user
        try {
            $user = User::withTrashed()->where($request->only('email'))->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return json_response()->unauthorized('You are using incorrect email address or password.');
        }

        // Check password
        if (! $hash->check($request->input('password'), $user->password)) {
            return json_response()->unauthorized('11You are using incorrect email address or password.');
        }

        // Rehash password
        if ($hash->needsRehash($user->password)) {
            $user->password = $hash->make($request->input('password'));
            $user->save();
        }

        // Determine if user has been soft-deleted
        if ($user->trashed()) {
            return json_response()->forbidden('Your account is suspended, please contact account administrator.');
        } elseif (! $user->is_active) {
            return json_response()->forbidden('Before you can login, you must active your account with the code sent to your email address. If you did not receive this email, please check your junk/spam folder.');
        }

        try {
            // create a token for the user
            $token = app('auth')->fromUser($user);
        } catch (JWTException $e) {
            // something went wrong whilst attempting to encode the token
            return json_response()->internalServerError();
        }

        return json_response()->success([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
            'resource' => User::find($user->id),
        ]);
    }
}
