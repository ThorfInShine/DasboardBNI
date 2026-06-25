<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        $users = User::select('id', 'name', 'npp', 'role', 'created_at')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json($users);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'npp' => 'required|string|unique:users,npp',
            'password' => ['required', 'confirmed', Password::min(8)],
            'role' => 'required|in:admin,user',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'npp' => $validated['npp'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
        ]);

        return response()->json([
            'message' => 'User created successfully',
            'user' => $user,
        ], 201);
    }

    public function destroy(Request $request, string $id)
    {
        if ($request->user()->id == $id) {
            return response()->json([
                'message' => 'You cannot delete your own account',
            ], 403);
        }

        $user = User::findOrFail($id);
        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully',
        ]);
    }

    public function resetPassword(Request $request, string $id)
    {
        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = User::findOrFail($id);
        $user->password = Hash::make($validated['password']);
        $user->save();

        return response()->json([
            'message' => 'Password reset successfully',
        ]);
    }
}
