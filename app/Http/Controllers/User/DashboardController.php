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
                ->where('status', 'resolved')
                ->whereHas('transactions', function($query) {
                    $query->where('action', 'status_changed')
                          ->where('new_value', 'resolved')
                          ->whereMonth('created_at', now()->month);
                })
                ->count(),
            'avg_resolution_time' => $this->calculateAverageResolutionTime($user->id),
        ];

        return view('user.dashboard', compact('reports', 'stats'));
    }

    private function calculateAverageResolutionTime($userId)
    {
        $resolvedReports = Report::where('user_id', $userId)
            ->whereIn('status', ['resolved', 'closed'])
            ->with(['transactions' => function($query) {
                $query->where('action', 'status_changed')
                      ->where('new_value', 'resolved');
            }])
            ->get();

        if ($resolvedReports->isEmpty()) {
            return '0';
        }

        $totalHours = 0;
        $countWithResolution = 0;
        
        foreach ($resolvedReports as $report) {
            // Find the resolved transaction
            $resolvedTransaction = $report->transactions
                ->where('action', 'status_changed')
                ->where('new_value', 'resolved')
                ->first();
            
            if ($resolvedTransaction) {
                $hours = $report->created_at->diffInHours($resolvedTransaction->created_at);
                $totalHours += $hours;
                $countWithResolution++;
            }
        }

        if ($countWithResolution === 0) {
            return '0';
        }

        $average = $totalHours / $countWithResolution;
        return number_format($average, 1);
    }
}