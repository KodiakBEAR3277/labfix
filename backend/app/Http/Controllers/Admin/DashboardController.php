<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Report;
use App\Models\Lab;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        // Stats
        $stats = [
            'total_users'       => User::count(),
            'students'          => User::where('role', 'student')->count(),
            'staff'             => User::where('role', 'staff')->count(),
            'it_support'        => User::where('role', 'it-support')->count(),
            'open_tickets'      => Report::open()->count(),
            'high_priority'     => Report::where('priority', 'high')->open()->count(),
            'system_uptime'     => $this->calculateSystemUptime(),
            'total_labs'        => Lab::where('is_active', true)->count(),
            'total_workstations'=> Lab::where('is_active', true)->sum('capacity'),
        ];

        // Recent activity
        $recentActivity = $this->getRecentActivity();

        // System health
        $systemHealth = $this->getSystemHealth();

        return Inertia::render('Admin/Dashboard', compact('stats', 'recentActivity', 'systemHealth'));
    }

    private function calculateSystemUptime()
    {
        // For now, return a high static value
        // TODO: Implement actual uptime tracking
        return 99.8;
    }

    private function getRecentActivity()
    {
        $activities = [];

        // Recent user registrations
        $recentUsers = User::latest()->take(3)->get();
        foreach ($recentUsers as $user) {
            $activities[] = [
                'icon'  => '👤',
                'title' => "New user registered: {$user->full_name} (" . ucfirst($user->role) . ")",
                'time'  => $user->created_at->diffForHumans(),
            ];
        }

        // Recent resolved/closed tickets — use the status scope rather than
        // querying resolved_at directly, which can fail on stale schema caches.
        $recentResolved = Report::closed()
            ->latest('updated_at')
            ->take(2)
            ->get();

        foreach ($recentResolved as $ticket) {
            $activities[] = [
                'icon'  => '✅',
                'title' => "Ticket {$ticket->ticket_number} resolved",
                'time'  => $ticket->updated_at->diffForHumans(),
            ];
        }

        return array_slice($activities, 0, 5);
    }

    private function getSystemHealth()
    {
        $totalTickets    = Report::count();
        $resolvedTickets = Report::closed()->count();

        return [
            'database' => [
                'status'     => 'Operational',
                'percentage' => 95,
                'color'      => 'good',
            ],
            'server_load' => [
                'status'     => 'Normal',
                'percentage' => 45,
                'color'      => 'good',
            ],
            'storage' => [
                'status'     => '75% Used',
                'percentage' => 75,
                'color'      => 'warning',
            ],
            'ticket_system' => [
                'status'     => 'Active',
                'percentage' => $totalTickets > 0
                    ? round(($resolvedTickets / $totalTickets) * 100)
                    : 100,
                'color'      => 'good',
            ],
        ];
    }
}