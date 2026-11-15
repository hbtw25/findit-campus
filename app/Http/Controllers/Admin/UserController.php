<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function index()
    {
        return view('admin.users.index');
    }

    // Sumber data JSON untuk DataTables
    public function data()
    {
        $users = User::withCount('reports', 'claims')->orderBy('name')->get();

        return response()->json([
            'data' => $users->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'nim' => $u->nim,
                'email' => $u->email,
                'role' => $u->role,
                'reports' => $u->reports_count,
                'claims' => $u->claims_count,
                'is_active' => $u->is_active,
                'is_me' => $u->id === Auth::id(),
                'toggle_url' => route('admin.users.toggleActive', $u),
            ]),
        ], 200);
    }

    public function toggleActive(User $user)
    {
        abort_if($user->id === Auth::id(), 403, 'Kamu tidak bisa menonaktifkan akunmu sendiri.');

        $user->update(['is_active' => ! $user->is_active]);

        return back()->with('success', $user->is_active
            ? "Akun {$user->name} diaktifkan kembali."
            : "Akun {$user->name} dinonaktifkan.");
    }
}
