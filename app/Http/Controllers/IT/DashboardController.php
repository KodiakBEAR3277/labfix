<?php

namespace App\Http\Controllers\IT;

use App\Http\Controllers\Controller;
use App\Models\Report;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        // Recent tickets (last 10)
        $recentTickets = Report::with(['reporter', 'equipment.lab'])
            ->latest()
            ->take(10)
            ->get();

        // High priority unresolved tickets
        $priorityAlerts = Report::with(['reporter', 'equipment.lab'])
            ->where('priority', 'high')
            ->open()
            ->latest()
            ->take(5)
            ->get();

        // Stats
        $stats = [
            'open_tickets' => Report::open()->count(),
            'my_assignments' => Report::where('assigned_to', Auth::id())->open()->count(),
            'high_priority' => Report::where('priority', 'high')->open()->count(),
            'resolved_today' => Report::where('status', 'resolved')
                ->whereHas('transactions', function($query) {
                    $query->where('action', 'status_changed')
                          ->where('new_value', 'resolved')
                          ->whereDate('created_at', today());
                })
                ->count(),
            'avg_response_time' => $this->calculateAverageResponseTime(),
            'team_satisfaction' => $this->calculateTeamSatisfaction(),
        ];

        return view('it.dashboard', compact('recentTickets', 'priorityAlerts', 'stats'));
    }

    private function calculateAverageResponseTime()
    {
        $recentTickets = Report::whereDate('created_at', '>=', now()->subDays(7))
            ->with(['transactions' => function($query) {
                $query->where('action', 'assigned');
            }])
            ->get();

        if ($recentTickets->isEmpty()) {
            return '0.0';
        }

        $totalHours = 0;
        $countWithAssignment = 0;
        
        foreach ($recentTickets as $ticket) {
            // Find the assignment transaction
            $assignedTransaction = $ticket->transactions->where('action', 'assigned')->first();
            
            if ($assignedTransaction) {
                $hours = $ticket->created_at->diffInHours($assignedTransaction->created_at);
                $totalHours += $hours;
                $countWithAssignment++;
            }
        }

        if ($countWithAssignment === 0) {
            return '0.0';
        }

        $average = $totalHours / $countWithAssignment;
        return number_format($average, 1);
    }

    private function calculateTeamSatisfaction()
    {
        // Get tickets resolved this month
        $resolvedThisMonth = Report::where('status', 'resolved')
            ->whereHas('transactions', function($query) {
                $query->where('action', 'status_changed')
                      ->where('new_value', 'resolved')
                      ->whereMonth('created_at', now()->month);
            })
            ->count();
            
        $totalThisMonth = Report::whereMonth('created_at', now()->month)->count();
        
        if ($totalThisMonth === 0) {
            return 0;
        }
        
        return round(($resolvedThisMonth / $totalThisMonth) * 100);
    }
}