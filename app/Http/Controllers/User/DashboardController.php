<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Report;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        // Get user's reports
        $reports = Report::where('user_id', $user->id)
            ->with(['equipment.lab', 'assignedTo'])
            ->latest()
            ->take(3)
            ->get();

        // Calculate stats
        $stats = [
            'active' => Report::where('user_id', $user->id)->open()->count(),
            'resolved_this_month' => Report::where('user_id', $user->id)
                ->closed()
                ->whereMonth('resolved_at', now()->month)
                ->count(),
            'avg_resolution_time' => $this->calculateAverageResolutionTime($user->id),
        ];

        return view('user.dashboard', compact('reports', 'stats'));
    }

    private function calculateAverageResolutionTime($userId)
    {
        $resolvedReports = Report::where('user_id', $userId)
            ->whereNotNull('resolved_at')
            ->whereNotNull('created_at')
            ->get();

        if ($resolvedReports->isEmpty()) {
            return '0';
        }

        $totalHours = 0;
        foreach ($resolvedReports as $report) {
            $hours = $report->created_at->diffInHours($report->resolved_at);
            $totalHours += $hours;
        }

        $average = $totalHours / $resolvedReports->count();
        
        return number_format($average, 1);
    }
}