<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Role;
use RealRashid\SweetAlert\Facades\Alert;
use Illuminate\Support\Facades\Auth;

class VerifyIsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Periksa dulu apakah user sudah login atau belum
        if (!Auth::check()) {
            // Jika belum login, arahkan ke halaman login
            return redirect()->route('login');
        }

        $user = $request->user();
        $role_id = $user->role_id;

        // 2. Validasi apakah user memiliki role_id
        if (empty($role_id)) {
            Alert::error('Akses Ditolak', 'Akun Anda belum memiliki role. Silakan hubungi administrator.');
            Auth::logout();
            return redirect()->route('login');
        }

        // 3. Cek role Admin
        $adminRole = Role::where('role_name', 'Admin')->first();

        // Jika role Admin tidak ditemukan di database, tangani error
        if (!$adminRole) {
            Alert::error('Error Sistem', 'Role Admin tidak ditemukan. Hubungi administrator.');
            return redirect()->route('home');
        }

        // 4. Cek apakah user adalah admin (id 1 atau memiliki role Admin)
        if (Auth::id() != 1 && $role_id != $adminRole->id) {
            Alert::error('Akses Ditolak', 'Anda tidak memiliki izin.');
            return redirect()->route('home');
        }

        return $next($request);


        // $role_id = $request->user()->role_id;
        // $Adminid = Role::where('role_name', 'Admin')->first()->id;

        // if ($role_id != $Adminid) {
        //     Alert::error('Anda tidak memiliki akses');
        //     return redirect()->route('home');
        // }
        // return $next($request);
    }
}
