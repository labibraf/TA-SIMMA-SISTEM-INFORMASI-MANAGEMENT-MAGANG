<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\dashboard\AdminDashboardController;
use App\Http\Controllers\dashboard\MentorDashboardController;
use App\Http\Controllers\dashboard\InternDashboardController;

class DashboardController extends Controller
{
    /**
     * Main dashboard routing based on user role
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Redirect based on user role
        if ($user->role_id == 1) { // Admin
            return app(AdminDashboardController::class)->index($request);
        } elseif ($user->role_id == 2) { // Mentor
            return app(MentorDashboardController::class)->index($request);
        } else { // Peserta/Intern
            return app(InternDashboardController::class)->index($request);
        }
    }
}
