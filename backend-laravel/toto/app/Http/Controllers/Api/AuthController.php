<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Blockchain\Web3SignatureService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(protected Web3SignatureService $web3Signature)
    {
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'wallet_address' => '0x' . bin2hex(random_bytes(20)),
            'is_active' => true,
        ]);

        $user->assignRole('super_admin');

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'user' => $user->load('roles'),
            'token' => $token,
        ], 201);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if (!Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $user = User::where('email', $request->email)->first();
        
        // Revoke old tokens
        $user->tokens()->delete();

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'user' => $user->load('roles'),
            'token' => $token,
        ]);
    }

    public function web3Login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'wallet_address' => 'required|string',
            'signature' => 'required|string',
            'message' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Verify signature
        if (!$this->web3Signature->verify(
            $request->message,
            $request->signature,
            $request->wallet_address
        )) {
            return response()->json(['message' => 'Signature invalide'], 401);
        }

        $user = User::where('wallet_address', $request->wallet_address)->first();

        if (!$user) {
            // Create new user if wallet not registered
            $user = User::create([
                'name' => 'Web3 User ' . substr($request->wallet_address, 0, 6),
                'email' => $request->wallet_address . '@web3.local',
                'password' => Hash::make(uniqid()),
                'wallet_address' => $request->wallet_address,
            ]);
            
            // Assign default role (auditor)
            $user->assignRole('auditor');
        }

        // Revoke old tokens
        $user->tokens()->delete();

        $token = $user->createToken('web3-auth-token')->plainTextToken;

        return response()->json([
            'user' => $user->load('roles'),
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        
        return response()->json(['message' => 'Logged out successfully']);
    }

    public function me(Request $request)
    {
        return response()->json($request->user()->load('roles'));
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:30',
            'wallet_address' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if ($request->has('name')) {
            $user->name = $request->name;
        }
        if ($request->has('email')) {
            $user->email = $request->email;
        }
        if ($request->has('phone')) {
            $user->phone = $request->phone;
        }
        if ($request->has('wallet_address')) {
            $user->wallet_address = $request->wallet_address;
        }

        $user->save();

        return response()->json([
            'message' => 'Profil mis à jour',
            'user' => $user->fresh()->load('roles'),
        ]);
    }

    public function uploadAvatar(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'avatar' => 'required|image|mimes:jpeg,jpg,png,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = $request->user();
        $file = $request->file('avatar');
        $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $path = $file->storeAs('avatars', $user->id . '.' . $ext, 'public');

        if ($user->avatar_path && $user->avatar_path !== $path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $user->avatar_path = $path;
        $user->save();

        return response()->json([
            'message' => 'Photo de profil mise à jour',
            'user' => $user->fresh()->load('roles'),
        ]);
    }

    public function avatar($id)
    {
        $user = User::findOrFail($id);

        if ($user->avatar_path && Storage::disk('public')->exists($user->avatar_path)) {
            return Storage::disk('public')->response($user->avatar_path);
        }

        // Ephemeral disks (Render) lose uploaded/seeded files after restart —
        // serve a clean initials SVG instead of a broken <img>.
        return $this->initialsAvatarResponse($user);
    }

    private function initialsAvatarResponse(User $user)
    {
        $parts = preg_split('/\s+/', trim((string) $user->name)) ?: ['U'];
        $initials = strtoupper(
            mb_substr($parts[0] ?? 'U', 0, 1) . mb_substr($parts[1] ?? '', 0, 1)
        );
        if ($initials === '') {
            $initials = 'U';
        }

        $palette = ['0ea5e9', '6366f1', '10b981', 'f59e0b', 'ef4444', 'a855f7'];
        $color = $palette[((int) $user->id) % count($palette)];
        $safe = htmlspecialchars($initials, ENT_QUOTES | ENT_XML1, 'UTF-8');

        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="256" height="256" viewBox="0 0 256 256">
  <defs>
    <linearGradient id="g" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="#{$color}"/>
      <stop offset="100%" stop-color="#6366f1"/>
    </linearGradient>
  </defs>
  <rect width="256" height="256" rx="48" fill="url(#g)"/>
  <text x="128" y="128" dy=".36em" text-anchor="middle" fill="#ffffff"
        font-family="system-ui,-apple-system,Segoe UI,sans-serif"
        font-size="96" font-weight="700">{$safe}</text>
</svg>
SVG;

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml; charset=utf-8',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    public function updatePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = $request->user();
        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json(['message' => 'Mot de passe mis à jour']);
    }
}
