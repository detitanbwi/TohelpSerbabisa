<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    /**
     * Login endpoint via Email or Username.
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'login' => 'required|string',
            'password' => 'required|string',
            'device_name' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $loginInput = $request->login;
        $user = User::with('cabang')->where(function ($query) use ($loginInput) {
            $query->where('email', $loginInput)
                  ->orWhere('username', $loginInput);
        })->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Username/Email atau password salah.'
            ], 401);
        }

        $deviceName = $request->device_name ?? 'mobile-flutter-app';
        $token = $user->createToken($deviceName)->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Login berhasil',
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
                'user' => $this->formatUserPayload($user),
            ]
        ]);
    }

    /**
     * Get authenticated user profile.
     */
    public function me(Request $request)
    {
        $user = $request->user()->load('cabang');

        return response()->json([
            'status' => 'success',
            'data' => $this->formatUserPayload($user),
        ]);
    }

    /**
     * Update authenticated user profile (Name, Username, Email, etc.).
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'username' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('users', 'username')->ignore($user->id),
            ],
            'email' => [
                'sometimes',
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $user->update($request->only(['name', 'username', 'email']));
        $user->load('cabang');

        return response()->json([
            'status' => 'success',
            'message' => 'Profil berhasil diperbarui',
            'data' => $this->formatUserPayload($user),
        ]);
    }

    /**
     * Change password.
     */
    public function changePassword(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Password saat ini tidak sesuai'
            ], 400);
        }

        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Password berhasil diubah'
        ]);
    }

    /**
     * Logout and revoke token.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Logout berhasil'
        ]);
    }

    /**
     * Helper to format user response.
     */
    protected function formatUserPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'tipe_karyawan' => $user->tipe_karyawan ?? 'Helpman',
            'is_visible' => (bool) $user->is_visible,
            'cabang' => $user->cabang ? [
                'id' => $user->cabang->id,
                'nama' => $user->cabang->nama,
                'no_wa' => $user->cabang->no_wa ?? '6285695908981',
                'formatted_no_wa' => $user->cabang->formatted_no_wa,
                'lat' => (float) $user->cabang->lat,
                'lng' => (float) ($user->cabang->lng ?? $user->cabang->long),
            ] : null,
            'roles' => $user->getRoleNames(),
            'avatar_url' => $user->avatar_url,
        ];
    }
}
