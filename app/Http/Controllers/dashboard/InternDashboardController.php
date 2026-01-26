<?php

namespace App\Http\Controllers\dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\DashboardService;

class InternDashboardController extends Controller
{
    protected $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $peserta = $user->peserta;

        // Validate peserta data
        if (!$peserta) {
            return redirect()->back()->with('error', 'Data peserta tidak ditemukan');
        }

        // Get dashboard data from service
        $data = $this->dashboardService->getPesertaDashboardData($peserta);

        // Return view with data
        return view('dashboard.peserta', $data);
    }
}
