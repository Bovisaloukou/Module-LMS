<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @group Authentication
 *
 * APIs for user authentication.
 */
class AuthController extends Controller
{
    /**
     * Register
     *
     * Create a new user account and return an API token.
     *
     * @unauthenticated
     *
     * @response 201 {
     *   "user": {"id": 1, "name": "John Doe", "email": "john@example.com", "bio": null, "avatar": null, "roles": ["student"], "created_at": "2026-01-01T00:00:00.000000Z"},
     *   "token": "1|abc123..."
     * }
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create($request->validated());

        $user->assignRole('student');

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'user' => new UserResource($user->load('roles')),
            'token' => $token,
        ], 201);
    }

    /**
     * Login
     *
     * Authenticate a user and return an API token.
     *
     * @unauthenticated
     *
     * @response 200 {
     *   "user": {"id": 1, "name": "John Doe", "email": "john@example.com", "bio": null, "avatar": null, "roles": ["student"], "created_at": "2026-01-01T00:00:00.000000Z"},
     *   "token": "1|abc123..."
     * }
     * @response 401 {"message": "Invalid credentials."}
     */
    public function login(LoginRequest $request): JsonResponse
    {
        if (! Auth::attempt($request->validated())) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], 401);
        }

        $user = User::where('email', $request->email)->firstOrFail();

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'user' => new UserResource($user->load('roles')),
            'token' => $token,
        ]);
    }

    /**
     * Logout
     *
     * Revoke the current API token.
     *
     * @authenticated
     *
     * @response 200 {"message": "Logged out successfully."}
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    /**
     * Current User
     *
     * Get the authenticated user's profile.
     *
     * @authenticated
     *
     * @response 200 {"data": {"id": 1, "name": "John Doe", "email": "john@example.com", "bio": null, "avatar": null, "roles": ["student"], "created_at": "2026-01-01T00:00:00.000000Z"}}
     */
    public function user(Request $request): UserResource
    {
        return new UserResource($request->user()->load('roles'));
    }
}
