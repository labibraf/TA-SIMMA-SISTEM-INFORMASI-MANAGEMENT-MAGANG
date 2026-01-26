<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use RealRashid\SweetAlert\Facades\Alert;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }

    /**
     * The user has been authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $user
     * @return mixed
     */
    /**
     * Validate login credentials before authentication
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    protected function validateLogin(Request $request)
    {
        $request->validate([
            $this->username() => 'required|string',
            'password' => 'required|string',
        ]);
    }

    /**
     * Attempt to log the user into the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function attemptLogin(Request $request)
    {
        // Cek dulu apakah user ada dan punya role
        $user = \App\Models\User::where($this->username(), $request->input($this->username()))->first();

        if ($user && empty($user->role_id)) {
            // Jangan attempt login jika tidak punya role
            Log::warning('Login attempt ditolak - user tidak memiliki role', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);
            return false;
        }

        // Login normal jika user punya role atau user tidak ditemukan (biar error normal)
        return $this->guard()->attempt(
            $this->credentials($request),
            $request->boolean('remember')
        );
    }

    /**
     * The user has been authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $user
     * @return mixed
     */
    protected function authenticated(Request $request, $user)
    {
        // Log user berhasil login
        Log::info('User berhasil login', [
            'user_id' => $user->id,
            'email' => $user->email,
            'role_id' => $user->role_id
        ]);
    }

    /**
     * Get the failed login response instance.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function sendFailedLoginResponse(Request $request)
    {
        // Cek apakah user ada tapi tidak punya role
        $user = \App\Models\User::where($this->username(), $request->input($this->username()))->first();

        if ($user && empty($user->role_id)) {
            // Log untuk debugging
            Log::warning('Login gagal - user tidak memiliki role', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $request->ip()
            ]);

            // Hanya kirim 1 error message
            throw \Illuminate\Validation\ValidationException::withMessages([
                $this->username() => ['Akun Anda belum memiliki role. Silakan hubungi administrator untuk aktivasi akun.']
            ]);
        }

        throw \Illuminate\Validation\ValidationException::withMessages([
            $this->username() => [trans('auth.failed')],
        ]);
    }
}
