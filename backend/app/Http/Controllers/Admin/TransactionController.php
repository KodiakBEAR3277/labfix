<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TicketTransaction;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = TicketTransaction::with(['ticket', 'user']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('ticket', function($q) use ($search) {
                      $q->where('ticket_number', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%");
                  })
                  ->orWhereHas('user', function($q) use ($search) {
                      $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('action') && $request->action !== 'all') {
            $query->where('action', $request->action);
        }

        if ($request->filled('user') && $request->user !== 'all') {
            $query->where('user_id', $request->user);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $transactions = $query->latest('created_at')->paginate(50)->withQueryString();

        $stats = [
            'total'      => TicketTransaction::count(),
            'today'      => TicketTransaction::whereDate('created_at', today())->count(),
            'this_week'  => TicketTransaction::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'this_month' => TicketTransaction::whereMonth('created_at', now()->month)->count(),
        ];

        $users   = User::orderBy('first_name')->get(['id', 'first_name', 'last_name']);
        $actions = TicketTransaction::select('action')->distinct()->pluck('action');

        return Inertia::render('Admin/Transactions/Index', [
            'transactions' => $transactions,
            'stats'        => $stats,
            'users'        => $users,
            'actions'      => $actions,
            'filters'      => $request->only(['search', 'action', 'user', 'date_from', 'date_to']),
        ]);
    }
}