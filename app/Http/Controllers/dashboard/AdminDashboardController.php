<?php

namespace App\Http\Controllers\dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\DashboardService;

class AdminDashboardController extends Controller
{
    protected $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    public function index(Request $request)
    {
        // Get filter parameters
        $filters = [
            'tahun' => $request->input('tahun'),
            'bulan' => $request->input('bulan'),
            'bagian' => $request->input('bagian'),
            'trend_period' => $request->input('trend_period', 6),
        ];

        // Get dashboard data from service
        $data = $this->dashboardService->getAdminDashboardData($filters);

        // Add filter values to data for view
        $data['tahun'] = $filters['tahun'];
        $data['bulan'] = $filters['bulan'];
        $data['bagian'] = $filters['bagian'];

        // Return view with data
        return view('dashboard.admin', $data);
    }
}
