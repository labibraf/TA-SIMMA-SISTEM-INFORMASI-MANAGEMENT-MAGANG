<?php

namespace App\Http\Controllers\dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\DashboardService;

class MentorDashboardController extends Controller
{
    protected $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    public function index(Request $request)
    {
        // Validate mentor data
        if (!Auth::user()->mentor) {
            return redirect()->route('home')->with('error', 'Data mentor tidak ditemukan.');
        }

        $mentor = Auth::user()->mentor->load('bagian');
        $mentorId = $mentor->id;

        // Get filter parameters
        $filters = [
            'tahun' => $request->input('tahun'),
            'bulan' => $request->input('bulan'),
        ];

        // Get dashboard data from service
        $data = $this->dashboardService->getMentorDashboardData($mentorId, $filters);

        // Add mentor to data
        $data['mentor'] = $mentor;

        // Return view with data
        return view('dashboard.mentor', $data);
    }
}
