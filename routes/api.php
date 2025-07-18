<?php

use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;


Route::get('/auth/google/redirect', function () {
    return Socialite::driver('google')->stateless()->redirect();
});

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return response()->json(auth()->user());
});

Route::post('/auth/register', function (Request $request) {
    $request->validate([
        'email' => 'required|email|unique:users,email',
        'name' => 'required|string|max:255',
        'password' => 'required|min:6',
    ]);

    $user = User::create([
        'email' => $request->email,
        'name' => $request->name,
        'password' => Hash::make($request->password),
    ]);

    $token = JWTAuth::fromUser($user);
    return response()->json(['token' => $token, 'user' => $user]);
});

Route::post('/auth/login', function (Request $request) {
    $credentials = $request->only('email', 'password');

    if (!$token = JWTAuth::attempt($credentials)) {
        return response()->json(['message' => 'Email atau password salah'], 401);
    }

    $user = Auth::user();

    return response()->json([
        'token' => $token,
        'user' => $user
    ]);
});


Route::get('/auth/google/callback', function () {
    $googleUser = Socialite::driver('google')->stateless()->user();

    // Cari user di database
    $user = User::where('email', $googleUser->getEmail())->first();

    // Jika belum ada, redirect ke halaman register frontend
    if (!$user) {
        // GANTI index.html → /
        return redirect()->away("http://localhost:3000/?error=not_registered&email=" . $googleUser->getEmail());
    }

    // Jika user ditemukan, generate token JWT
    $token = JWTAuth::fromUser($user);

    // Redirect ke frontend sambil kirim token dan email
    // GANTI index.html → /
    return redirect()->away("http://localhost:3000/?token=$token&email=" . $user->email);
});
