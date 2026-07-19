<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    private const ROLE_MAP = [
        'Super Admin' => 'super_admin',
        'Gestionnaire' => 'fleet_manager',
        'Chauffeur' => 'driver',
        'Garagiste' => 'garage',
        'Auditeur' => 'auditor',
    ];

    private const ROLE_LABELS = [
        'super_admin' => 'Super Admin',
        'fleet_manager' => 'Gestionnaire',
        'driver' => 'Chauffeur',
        'garage' => 'Garagiste',
        'auditor' => 'Auditeur',
    ];

    public function index()
    {
        $users = User::with('roles')->orderBy('name')->get();

        return response()->json(
            $users->map(fn (User $user) => $this->formatUser($user))
        );
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'nullable|string|min:6',
            'role' => 'required|string',
            'active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $backendRole = $this->mapRole($request->role);
        if (!$backendRole) {
            return response()->json(['message' => 'Rôle invalide'], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password ?: 'password'),
            'is_active' => $request->boolean('active', true),
        ]);

        $user->assignRole($backendRole);

        return response()->json([
            'message' => 'Utilisateur créé',
            'user' => $this->formatUser($user->load('roles')),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $user = User::with('roles')->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:6',
            'role' => 'sometimes|required|string',
            'active' => 'boolean',
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
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }
        if ($request->has('active')) {
            $user->is_active = $request->boolean('active');
        }

        $user->save();

        if ($request->has('role')) {
            $backendRole = $this->mapRole($request->role);
            if (!$backendRole) {
                return response()->json(['message' => 'Rôle invalide'], 422);
            }
            $user->syncRoles([$backendRole]);
        }

        return response()->json([
            'message' => 'Utilisateur mis à jour',
            'user' => $this->formatUser($user->fresh('roles')),
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $user = User::findOrFail($id);

        if ((int) $request->user()->id === (int) $user->id) {
            return response()->json(['message' => 'Impossible de supprimer votre propre compte'], 422);
        }

        $user->tokens()->delete();
        $user->delete();

        return response()->json(['message' => 'Utilisateur supprimé']);
    }

    private function mapRole(string $role): ?string
    {
        if (isset(self::ROLE_MAP[$role])) {
            return self::ROLE_MAP[$role];
        }

        if (in_array($role, self::ROLE_LABELS, true)) {
            return array_search($role, self::ROLE_LABELS, true) ?: null;
        }

        return in_array($role, array_keys(self::ROLE_LABELS), true) ? $role : null;
    }

    private function formatUser(User $user): array
    {
        $roleName = $user->roles->first()?->name;

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'wallet_address' => $user->wallet_address,
            'role' => self::ROLE_LABELS[$roleName] ?? $roleName,
            'active' => (bool) ($user->is_active ?? true),
            'avatar_url' => $user->avatar_url,
            'created_at' => $user->created_at,
            'roles' => $user->roles,
        ];
    }
}
