<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class TokenController extends Controller
{
    public function issue(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'name' => ['nullable', 'string', 'max:100'],
        ]);

        $user = \App\Models\User::where('email', $data['email'])->first();
        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['email' => ['Email atau kata sandi tidak cocok.']]);
        }

        $plainTextToken = bin2hex(random_bytes(32));
        $user->apiTokens()->create([
            'name' => $data['name'] ?? 'Ruang Konten integration',
            'token_hash' => hash('sha256', $plainTextToken),
            'expires_at' => now()->addDays(30),
        ]);

        return response()->json([
            'token_type' => 'Bearer',
            'access_token' => $plainTextToken,
            'expires_at' => now()->addDays(30)->toIso8601String(),
            'user' => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email, 'role' => $user->role],
        ], 201);
    }

    public function revoke(Request $request)
    {
        $request->attributes->get('api_token')?->delete();

        return response()->json(['message' => 'Token berhasil dicabut.']);
    }
}
