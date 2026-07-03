<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Kreait\Laravel\Firebase\Facades\Firebase;
use Kreait\Firebase\Exception\Auth\FailedToVerifyToken;
use Kreait\Firebase\Exception\Auth\UserNotFound;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function firebaseLogin(Request $request)
    {
        $validated = $request->validate([
            'firebase_token' => ['required', 'string'],
        ]);

        try {
            $auth = Firebase::auth();
            $verifiedIdToken = $auth->verifyIdToken($validated['firebase_token']);
            $uid = $verifiedIdToken->claims()->get('sub');
            $firebaseUser = $auth->getUser($uid);

            $user = User::firstOrCreate(
                ['email' => $firebaseUser->email],
                [
                    'name' => $firebaseUser->displayName ?? 'User',
                    'email_verified_at' => $firebaseUser->emailVerified ? now() : null,
                    'allowed_ratings' => ['general'],
                ]
            );

            Auth::login($user, true);

            return response()->json([
                'success' => true,
                'redirect' => route('index'),
            ]);
        } catch (FailedToVerifyToken|UserNotFound $e) {
            return response()->json([
                'success' => false,
                'message' => 'The authentication token is invalid or has expired.',
            ], 401);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while logging in.',
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}