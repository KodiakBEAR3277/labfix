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
            'resolved_today' => Report::whereDate('resolved_at', today())->count(),
            'avg_response_time' => $this->calculateAverageResponseTime(),
            'team_satisfaction' => $this->calculateTeamSatisfaction(),
        ];

        return view('it.dashboard', compact('recentTickets', 'priorityAlerts', 'stats'));
    }

    private function calculateAverageResponseTime()
    {
        $assignedTickets = Report::whereNotNull('assigned_at')
            ->whereNotNull('created_at')
            ->whereDate('created_at', '>=', now()->subDays(7))
            ->get();

        if ($assignedTickets->isEmpty()) {
            return '0.0';
        }

        $totalHours = 0;
        foreach ($assignedTickets as $ticket) {
            $hours = $ticket->created_at->diffInHours($ticket->assigned_at);
            $totalHours += $hours;
        }

        $average = $totalHours / $assignedTickets->count();
        return number_format($average, 1);
    }

    private function calculateTeamSatisfaction()
    {
        // For now, return a static high number
        // TODO: Implement actual satisfaction tracking system
        $resolvedThisMonth = Report::whereMonth('resolved_at', now()->month)->count();
        $totalThisMonth = Report::whereMonth('created_at', now()->month)->count();
        
        if ($totalThisMonth === 0) {
            return 0;
        }
        
        return round(($resolvedThisMonth / $totalThisMonth) * 100);
    }
}