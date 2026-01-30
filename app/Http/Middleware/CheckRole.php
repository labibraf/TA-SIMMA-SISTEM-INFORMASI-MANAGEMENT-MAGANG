<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Role;
use RealRashid\SweetAlert\Facades\Alert;
use Illuminate\Support\Facades\Auth;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles  Array of allowed role names (e.g., 'Admin', 'Mentor', 'Peserta')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // 1. Periksa dulu apakah user sudah login atau belum
        if (!Auth::check()) {
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

        // 3. Ambil role user saat ini
        $userRole = Role::find($role_id);

        if (!$userRole) {
            Alert::error('Error Sistem', 'Role tidak ditemukan. Hubungi administrator.');
            return redirect()->route('home');
        }

        // 4. Cek apakah role user termasuk dalam daftar role yang diizinkan
        // Admin (id 1) selalu diizinkan mengakses semua
        if (Auth::id() == 1) {
            return $next($request);
        }

        // Cek apakah role_name user ada di dalam daftar roles yang diizinkan
        if (!in_array($userRole->role_name, $roles)) {
            Alert::error('Akses Ditolak', 'Anda tidak memiliki izin untuk mengakses halaman ini.');
            return redirect()->route('home');
        }

        return $next($request);
    }
}
