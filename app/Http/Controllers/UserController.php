<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;
use RealRashid\SweetAlert\Facades\Alert;

class UserController extends Controller
{
    public function index()
    {
        // Urutkan dari data terbaru ke lama dengan eager loading bagian
        $users = User::with(['role', 'peserta.bagian', 'mentor.bagian'])
                    ->orderBy('created_at', 'desc')
                    ->get();
        $roles = Role::all();
        return view('users.index', compact('users', 'roles'));
    }

    public function updateRole(Request $request)
    {
        $request->validate([
            'user_id' => 'required',
            'role_id' => 'required',
        ]);

        $userID = $request->user_id;
        $roleID = $request->role_id;

        $user = User::findOrFail($userID);
        $user->role_id = $roleID;
        $user->save();

        Alert::success('Success', 'Role berhasil diubah');
        return redirect()->route('users.index');
    }

    public function edit($id)
    {
        $user = User::with(['role', 'peserta', 'mentor'])->findOrFail($id);
        return view('users.edit', compact('user'));
    }

    public function update(Request $request, $id)
    {
        $user = User::with(['peserta', 'mentor'])->findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
        ]);

        // Update user - sinkronisasi otomatis dihandle oleh boot() method di User model
        $user->update([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        Alert::success('Success', 'Data user berhasil diperbarui');
        return redirect()->route('users.index');
    }
}
