<?php

/**
 * routes/api.php
 *
 * API routes consumed by the LabFix mobile app (Expo).
 * All routes return JSON. Auth uses Laravel Sanctum token guards.
 *
 * ─────────────────────────────────────────────────────────────────────────────
 * ROUTE MAP
 * ─────────────────────────────────────────────────────────────────────────────
 *
 * PUBLIC (no token)
 *   POST   /api/login
 *   POST   /api/register
 *   GET    /api/contact-info
 *   GET    /api/knowledge-base          ← KB index (public, mirrors web)
 *   GET    /api/knowledge-base/{slug}   ← KB article show
 *   POST   /api/knowledge-base/{slug}/helpful
 *   POST   /api/knowledge-base/{slug}/not-helpful
 *
 * PROTECTED — auth:sanctum (any authenticated user)
 *   POST   /api/logout
 *   GET    /api/me
 *   GET    /api/dashboard               ← role-branched stats
 *   GET    /api/labs                    ← active labs + equipment
 *   GET    /api/labs/{id}/equipment
 *   GET    /api/tickets                 ← own tickets (student/staff) or all (IT/admin)
 *   GET    /api/tickets/{id}
 *   POST   /api/tickets                 ← create ticket
 *   PUT    /api/tickets/{id}            ← edit own unassigned ticket (student/staff)
 *   DELETE /api/tickets/{id}            ← cancel own unassigned ticket
 *
 * PROTECTED — auth:sanctum + role:it-support,admin
 *   GET    /api/it/dashboard
 *   GET    /api/it/queue                ← all open tickets with filters
 *   GET    /api/it/queue/{id}           ← single ticket detail
 *   PUT    /api/it/queue/{id}           ← update status/priority + optional self-assign
 *   POST   /api/it/queue/{id}/assign-self
 *   GET    /api/it/assignments          ← tickets assigned to auth user
 *   GET    /api/it/assignments/{id}
 *   PUT    /api/it/assignments/{id}     ← update status/priority only
 *   GET    /api/it/articles             ← all articles (IT management view)
 *   POST   /api/it/articles             ← create article
 *   GET    /api/it/articles/{id}        ← single article with stats
 *   PUT    /api/it/articles/{id}        ← update article
 *   DELETE /api/it/articles/{id}        ← delete article
 * ─────────────────────────────────────────────────────────────────────────────
 */

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use App\Http\Controllers\Api\AuthController;
use App\Models\User;
use App\Models\Setting;

// ═══════════════════════════════════════════════════════════════════════════════
// PUBLIC ROUTES — no token required
// ═══════════════════════════════════════════════════════════════════════════════

/**
 * POST /api/login
 * Body: { email, password }
 * Returns: { message, token, user: { id, name, email, role } }
 */
Route::post('/login', [AuthController::class, 'login']);

/**
 * POST /api/register
 * Body: { first_name, last_name, email, role, password, password_confirmation, terms }
 * Returns: { message, token, user: { id, name, email, role } }
 */
Route::post('/register', function (Request $request) {
    $validated = $request->validate([
        'first_name' => ['required', 'string', 'max:255'],
        'last_name'  => ['required', 'string', 'max:255'],
        'email'      => ['required', 'string', 'email', 'max:255', 'unique:users'],
        'role'       => ['required', 'in:student,staff,it-support'],
        'password'   => ['required', 'confirmed', Password::defaults()],
        'terms'      => ['accepted'],
    ]);

    $user = User::create([
        'first_name' => $validated['first_name'],
        'last_name'  => $validated['last_name'],
        'email'      => $validated['email'],
        'role'       => $validated['role'],
        'password'   => Hash::make($validated['password']),
    ]);

    $user->tokens()->delete();
    $token = $user->createToken('mobile-token')->plainTextToken;

    return response()->json([
        'message' => 'Registration successful',
        'token'   => $token,
        'user'    => [
            'id'    => $user->id,
            'name'  => $user->full_name,
            'email' => $user->email,
            'role'  => $user->role,
        ],
    ], 201);
});

/**
 * GET /api/contact-info
 * Returns live contact settings. Used by Contact screen (public).
 */
Route::get('/contact-info', function () {
    return response()->json([
        'system_name'   => Setting::get('system_name',   'LabFix'),
        'support_email' => Setting::get('support_email', 'support@labfix.edu'),
        'support_phone' => Setting::get('support_phone', ''),
    ]);
});

// ── Knowledge Base (public — mirrors web, no auth needed) ──────────────────

/**
 * GET /api/knowledge-base?search=&category=&page=
 * Returns paginated published articles + popular list + category counts.
 * Used by: app/(tabs)/knowledge-base.tsx
 */
Route::get('/knowledge-base', function (Request $request) {
    $query = \App\Models\Article::published()->with('author');

    if ($request->filled('search')) {
        $s = $request->search;
        $query->where(function ($q) use ($s) {
            $q->where('title',   'like', "%{$s}%")
              ->orWhere('content', 'like', "%{$s}%")
              ->orWhere('excerpt', 'like', "%{$s}%");
        });
    }

    if ($request->filled('category') && $request->category !== 'all') {
        $query->where('category', $request->category);
    }

    $articles        = $query->latest('published_at')->paginate(10);
    $popularArticles = \App\Models\Article::published()
        ->orderBy('views', 'desc')
        ->take(5)
        ->get(['id', 'title', 'slug', 'category', 'views']);

    $categories = [];
    foreach (['hardware', 'software', 'network', 'display', 'peripherals', 'general'] as $cat) {
        $categories[$cat] = \App\Models\Article::published()->where('category', $cat)->count();
    }

    return response()->json(compact('articles', 'popularArticles', 'categories'));
});

/**
 * GET /api/knowledge-base/{slug}
 * Returns a single published article + related articles.
 * Increments view count on each call (mirrors web behaviour).
 * Used by: app/(tabs)/knowledge-base/[slug].tsx
 */
Route::get('/knowledge-base/{slug}', function ($slug) {
    $article = \App\Models\Article::published()
        ->where('slug', $slug)
        ->with('author')
        ->firstOrFail();

    $article->incrementViews();

    $related = \App\Models\Article::published()
        ->where('category', $article->category)
        ->where('id', '!=', $article->id)
        ->orderBy('views', 'desc')
        ->take(3)
        ->get(['id', 'title', 'slug', 'category']);

    return response()->json(compact('article', 'related'));
});

/**
 * POST /api/knowledge-base/{slug}/helpful
 * Increments helpful_count. No auth required (mirrors web).
 */
Route::post('/knowledge-base/{slug}/helpful', function ($slug) {
    \App\Models\Article::published()->where('slug', $slug)->firstOrFail()->markHelpful();
    return response()->json(['message' => 'Thank you for your feedback!']);
});

/**
 * POST /api/knowledge-base/{slug}/not-helpful
 * Increments not_helpful_count. No auth required (mirrors web).
 */
Route::post('/knowledge-base/{slug}/not-helpful', function ($slug) {
    \App\Models\Article::published()->where('slug', $slug)->firstOrFail()->markNotHelpful();
    return response()->json(['message' => 'Thank you for your feedback!']);
});


// ═══════════════════════════════════════════════════════════════════════════════
// PROTECTED ROUTES — require valid Sanctum token
// ═══════════════════════════════════════════════════════════════════════════════

Route::middleware('auth:sanctum')->group(function () {

    // ── Auth ─────────────────────────────────────────────────────────────────

    /** POST /api/logout — deletes current token */
    Route::post('/logout', [AuthController::class, 'logout']);

    /** GET /api/me — returns authenticated user */
    Route::get('/me', [AuthController::class, 'me']);

    // ── Profile ─────────────────────────────────────────────────────

    /**
     * GET /api/profile
     * Returns the full authenticated user object for the profile screen.
     */
    Route::get('/profile', function (Request $request) {
        $user = $request->user();
        return response()->json([
            'user' => [
                'id'                  => $user->id,
                'first_name'          => $user->first_name,
                'last_name'           => $user->last_name,
                'full_name'           => $user->full_name,
                'initials'            => $user->initials,
                'email'               => $user->email,
                'role'                => $user->role,
                'phone'               => $user->phone,
                'student_staff_id'    => $user->student_staff_id,
                'email_notifications' => $user->email_notifications,
                'is_active'           => $user->is_active,
                'can_submit_tickets'  => $user->can_submit_tickets,
                'created_at'          => $user->created_at,
            ],
        ]);
    });

    /**
     * PUT /api/profile
     * Body: { first_name, last_name, email, phone?, student_staff_id? }
     * Updates basic profile info.
     */
    Route::put('/profile', function (Request $request) {
        $user = $request->user();

        $validated = $request->validate([
            'first_name'       => ['required', 'string', 'max:255'],
            'last_name'        => ['required', 'string', 'max:255'],
            'email'            => ['required', 'string', 'email', 'max:255',
                                   \Illuminate\Validation\Rule::unique('users')->ignore($user->id)],
            'phone'            => ['nullable', 'string', 'max:20'],
            'student_staff_id' => ['nullable', 'string', 'max:50'],
        ]);

        $user->update($validated);

        return response()->json([
            'message' => 'Profile updated successfully!',
            'user' => [
                'id'               => $user->id,
                'first_name'       => $user->first_name,
                'last_name'        => $user->last_name,
                'full_name'        => $user->full_name,
                'initials'         => $user->initials,
                'email'            => $user->email,
                'role'             => $user->role,
                'phone'            => $user->phone,
                'student_staff_id' => $user->student_staff_id,
            ],
        ]);
    });

    /**
     * PUT /api/profile/password
     * Body: { current_password, password, password_confirmation }
     * Updates the authenticated user's password.
     */
    Route::put('/profile/password', function (Request $request) {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'confirmed', \Illuminate\Validation\Rules\Password::defaults()],
        ]);

        $request->user()->update([
            'password' => \Illuminate\Support\Facades\Hash::make($request->password),
        ]);

        return response()->json(['message' => 'Password updated successfully!']);
    });

    /**
     * PUT /api/profile/preferences
     * Body: { email_notifications: bool }
     * Updates notification preferences.
     */
    Route::put('/profile/preferences', function (Request $request) {
        $request->user()->update([
            'email_notifications' => $request->boolean('email_notifications'),
        ]);

        return response()->json(['message' => 'Preferences updated successfully!']);
    });
    
    // ── Labs ─────────────────────────────────────────────────────────────────

    /**
     * GET /api/labs
     * All active labs with their full equipment list.
     * Used by: Lab Status screen + Report Create step 1.
     */
    Route::get('/labs', function () {
        return response()->json(
            \App\Models\Lab::where('is_active', true)
                ->with('equipment')
                ->get()
        );
    });

    /**
     * GET /api/labs/{id}/equipment
     * Equipment for a specific lab (ordered by code).
     * Used by: Report Create step 2 dropdown.
     */
    Route::get('/labs/{lab}/equipment', function ($labId) {
        return response()->json(
            \App\Models\Equipment::where('lab_id', $labId)
                ->orderBy('equipment_code')
                ->get(['id', 'equipment_code', 'type', 'status'])
        );
    });

    // ── Dashboard (role-branched) ─────────────────────────────────────────────

    /**
     * GET /api/dashboard
     * Returns stats appropriate to the authenticated user's role.
     *
     * student/staff → own ticket counts + 3 recent tickets
     * it-support    → redirected to /api/it/dashboard (or use that directly)
     * admin         → system-wide counts
     */
    Route::get('/dashboard', function () {
        $user = auth()->user();

        if ($user->role === 'admin') {
            return response()->json([
                'role'  => 'admin',
                'stats' => [
                    'total_users'   => \App\Models\User::count(),
                    'open_tickets'  => \App\Models\Report::open()->count(),
                    'high_priority' => \App\Models\Report::where('priority', 'high')->open()->count(),
                    'total_labs'    => \App\Models\Lab::where('is_active', true)->count(),
                ],
            ]);
        }

        if ($user->role === 'it-support') {
            return response()->json([
                'role'  => 'it-support',
                'stats' => [
                    'open_tickets'   => \App\Models\Report::open()->count(),
                    'my_assignments' => \App\Models\Report::where('assigned_to', $user->id)->open()->count(),
                    'high_priority'  => \App\Models\Report::where('priority', 'high')->open()->count(),
                    'resolved_today' => \App\Models\Report::where('status', 'resolved')
                        ->whereHas('transactions', fn($q) => $q
                            ->where('action', 'status_changed')
                            ->where('new_value', 'resolved')
                            ->whereDate('created_at', today()))
                        ->count(),
                ],
            ]);
        }

        // Student / Staff
        return response()->json([
            'role'  => $user->role,
            'stats' => [
                'active'              => \App\Models\Report::where('user_id', $user->id)->open()->count(),
                'resolved_this_month' => \App\Models\Report::where('user_id', $user->id)
                    ->where('status', 'resolved')
                    ->whereHas('transactions', fn($q) => $q
                        ->where('action', 'status_changed')
                        ->where('new_value', 'resolved')
                        ->whereMonth('created_at', now()->month))
                    ->count(),
                'total' => \App\Models\Report::where('user_id', $user->id)->count(),
            ],
            'recent_tickets' => \App\Models\Report::where('user_id', $user->id)
                ->with(['equipment.lab', 'assignedTo'])
                ->latest()
                ->take(3)
                ->get(),
        ]);
    });

    // ── User Tickets ──────────────────────────────────────────────────────────

    /**
     * GET /api/tickets?search=&status=&page=
     * student/staff → own tickets only
     * it-support/admin → all tickets
     */
    Route::get('/tickets', function (Request $request) {
        $user  = auth()->user();
        $query = \App\Models\Report::with(['reporter', 'assignedTo', 'equipment.lab']);

        if (!in_array($user->role, ['admin', 'it-support'])) {
            $query->where('user_id', $user->id);
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) => $q
                ->where('ticket_number', 'like', "%{$s}%")
                ->orWhere('title', 'like', "%{$s}%"));
        }

        return response()->json($query->latest()->paginate(15));
    });

    /**
     * GET /api/tickets/{id}
     * student/staff can only view their own; IT/admin can view any.
     */
    Route::get('/tickets/{id}', function ($id) {
        $user   = auth()->user();
        $ticket = \App\Models\Report::with([
            'reporter', 'assignedTo', 'equipment.lab', 'transactions.user',
        ])->findOrFail($id);

        if (!in_array($user->role, ['admin', 'it-support']) && $ticket->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        return response()->json($ticket);
    });

    /**
     * POST /api/tickets
     * Create a new ticket. Any authenticated user may submit.
     * Mirrors User\ReportController::store().
     */
    Route::post('/tickets', function (Request $request) {
        $user = auth()->user();

        $validated = $request->validate([
            'lab_id'       => ['required', 'exists:labs,id'],
            'equipment_id' => ['nullable', 'exists:equipment,id'],
            'category'     => ['required', 'in:hardware,software,network,other'],
            'title'        => ['required', 'string', 'max:255'],
            'description'  => ['required', 'string', 'min:10'],
        ]);

        if (empty($validated['equipment_id'])) {
            $lab     = \App\Models\Lab::findOrFail($validated['lab_id']);
            $general = \App\Models\Equipment::firstOrCreate(
                ['lab_id' => $lab->id, 'equipment_code' => 'GENERAL'],
                ['type' => 'other', 'status' => 'operational',
                 'notes' => 'General lab issues without specific equipment']
            );
            $validated['equipment_id'] = $general->id;
        }

        $keywords = ['not working', 'broken', 'urgent', 'critical', 'cannot', "can't", 'multiple', 'all'];
        $text     = strtolower($validated['title'] . ' ' . $validated['description']);
        $priority = collect($keywords)->first(fn($kw) => str_contains($text, $kw)) ? 'high' : 'medium';

        $ticket = \App\Models\Report::create([
            'ticket_number' => \App\Models\Report::generateTicketNumber(),
            'user_id'       => $user->id,
            'equipment_id'  => $validated['equipment_id'],
            'category'      => $validated['category'],
            'title'         => $validated['title'],
            'description'   => $validated['description'],
            'status'        => 'new',
            'priority'      => $priority,
        ]);

        \App\Models\TicketTransaction::create([
            'ticket_id'   => $ticket->id,
            'user_id'     => $user->id,
            'action'      => 'created',
            'description' => $user->full_name . ' created this ticket',
        ]);

        $ticket->equipment->updateStatusFromReports();

        return response()->json($ticket->load(['equipment.lab', 'transactions']), 201);
    });

    /**
     * PUT /api/tickets/{id}
     * Edit title, category, description of an unassigned ticket.
     * Only the ticket owner may edit; only before assignment.
     * Mirrors User\ReportController::update().
     */
    Route::put('/tickets/{id}', function (Request $request, $id) {
        $user   = auth()->user();
        $ticket = \App\Models\Report::where('user_id', $user->id)->findOrFail($id);

        if ($ticket->assigned_to) {
            return response()->json(['message' => 'Cannot edit an assigned ticket.'], 403);
        }

        $validated = $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'min:10'],
            'category'    => ['required', 'in:hardware,software,network,other'],
        ]);

        $ticket->update($validated);

        \App\Models\TicketTransaction::create([
            'ticket_id'   => $ticket->id,
            'user_id'     => $user->id,
            'action'      => 'updated',
            'description' => $user->full_name . ' updated ticket details',
        ]);

        return response()->json($ticket->load(['equipment.lab', 'transactions.user']));
    });

    /**
     * DELETE /api/tickets/{id}
     * Cancel (soft-delete) an unassigned ticket. Owner only.
     */
    Route::delete('/tickets/{id}', function ($id) {
        $user   = auth()->user();
        $ticket = \App\Models\Report::where('user_id', $user->id)->findOrFail($id);

        if ($ticket->assigned_to) {
            return response()->json(['message' => 'Cannot delete an assigned ticket.'], 403);
        }

        \App\Models\TicketTransaction::create([
            'ticket_id'   => $ticket->id,
            'user_id'     => $user->id,
            'action'      => 'deleted',
            'description' => $user->full_name . ' cancelled this ticket',
        ]);

        $ticket->delete();

        return response()->json(['message' => 'Ticket cancelled successfully.']);
    });


    // ═══════════════════════════════════════════════════════════════════════════
    // IT ZONE — role:it-support,admin only
    // ═══════════════════════════════════════════════════════════════════════════

    Route::middleware('role:it-support,admin')->prefix('it')->group(function () {

        // ── IT Dashboard ──────────────────────────────────────────────────────

        /**
         * GET /api/it/dashboard
         * Returns full IT dashboard data:
         *   stats        — 6 stat cards (mirrors IT/Dashboard.vue)
         *   recentTickets    — last 10 open tickets
         *   priorityAlerts   — up to 5 high-priority open tickets
         *   recentResolved   — last 3 tickets resolved (with human time)
         *   unassignedCount  — count of unassigned open tickets
         *
         * Used by: app/(it)/dashboard.tsx
         */
        Route::get('/dashboard', function () {
            $user = auth()->user();

            // Average response time (hours from created_at to first 'assigned' transaction)
            $recentTickets7d = \App\Models\Report::whereDate('created_at', '>=', now()->subDays(7))
                ->with(['transactions' => fn($q) => $q->where('action', 'assigned')])
                ->get();

            $totalHours = 0;
            $countWithAssignment = 0;
            foreach ($recentTickets7d as $t) {
                $tx = $t->transactions->where('action', 'assigned')->first();
                if ($tx) {
                    $totalHours += $t->created_at->diffInHours($tx->created_at);
                    $countWithAssignment++;
                }
            }
            $avgResponseTime = $countWithAssignment > 0
                ? number_format($totalHours / $countWithAssignment, 1)
                : '0.0';

            // Team satisfaction — resolution rate this month
            $totalThisMonth    = \App\Models\Report::whereMonth('created_at', now()->month)->count();
            $resolvedThisMonth = \App\Models\Report::where('status', 'resolved')
                ->whereHas('transactions', fn($q) => $q
                    ->where('action', 'status_changed')
                    ->where('new_value', 'resolved')
                    ->whereMonth('created_at', now()->month))
                ->count();
            $teamSatisfaction = $totalThisMonth > 0
                ? round(($resolvedThisMonth / $totalThisMonth) * 100)
                : 0;

            $stats = [
                'open_tickets'     => \App\Models\Report::open()->count(),
                'my_assignments'   => \App\Models\Report::where('assigned_to', $user->id)->open()->count(),
                'high_priority'    => \App\Models\Report::where('priority', 'high')->open()->count(),
                'resolved_today'   => \App\Models\Report::where('status', 'resolved')
                    ->whereHas('transactions', fn($q) => $q
                        ->where('action', 'status_changed')
                        ->where('new_value', 'resolved')
                        ->whereDate('created_at', today()))
                    ->count(),
                'avg_response_time' => $avgResponseTime,
                'team_satisfaction' => $teamSatisfaction,
            ];

            $recentTickets = \App\Models\Report::with(['reporter', 'equipment.lab'])
                ->latest()
                ->take(10)
                ->get();

            $priorityAlerts = \App\Models\Report::with(['reporter', 'equipment.lab'])
                ->where('priority', 'high')
                ->open()
                ->latest()
                ->take(5)
                ->get();

            $unassignedCount = \App\Models\Report::whereNull('assigned_to')->open()->count();

            // Recent resolved — ticket_number + human-readable resolved time
            $recentResolved = \App\Models\Report::with(['transactions' => fn($q) => $q
                    ->where('action', 'status_changed')
                    ->where('new_value', 'resolved')
                    ->latest('created_at')
                    ->take(1)])
                ->get()
                ->filter(fn($t) => $t->transactions->isNotEmpty())
                ->take(3)
                ->map(fn($t) => [
                    'ticket_number' => $t->ticket_number,
                    'resolved_at'   => $t->transactions->first()->created_at->diffForHumans(),
                ])
                ->values();

            return response()->json(compact(
                'stats',
                'recentTickets',
                'priorityAlerts',
                'unassignedCount',
                'recentResolved'
            ));
        });

        // ── IT Ticket Queue ───────────────────────────────────────────────────

        /**
         * GET /api/it/queue?search=&status=&priority=&lab=&page=
         * All tickets visible to IT — full queue with filters.
         * Mirrors IT\TicketController::index().
         * Used by: app/(it)/queue/index.tsx
         */
        Route::get('/queue', function (Request $request) {
            $query = \App\Models\Report::with(['reporter', 'assignedTo', 'equipment.lab']);

            if ($request->filled('search')) {
                $s = $request->search;
                $query->where(fn($q) => $q
                    ->where('ticket_number', 'like', "%{$s}%")
                    ->orWhere('title', 'like', "%{$s}%")
                    ->orWhereHas('reporter', fn($q) => $q
                        ->where('first_name', 'like', "%{$s}%")
                        ->orWhere('last_name', 'like', "%{$s}%"))
                    ->orWhereHas('equipment', fn($q) => $q
                        ->where('equipment_code', 'like', "%{$s}%")
                        ->orWhereHas('lab', fn($q) => $q->where('name', 'like', "%{$s}%"))));
            }

            if ($request->filled('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            if ($request->filled('priority') && $request->priority !== 'all') {
                $query->where('priority', $request->priority);
            }

            if ($request->filled('lab') && $request->lab !== 'all') {
                $query->whereHas('equipment.lab', fn($q) => $q->where('name', $request->lab));
            }

            $tickets = $query->latest()->paginate(15);

            $stats = [
                'open'          => \App\Models\Report::open()->count(),
                'assigned'      => \App\Models\Report::where('status', 'assigned')->count(),
                'in_progress'   => \App\Models\Report::where('status', 'in-progress')->count(),
                'high_priority' => \App\Models\Report::where('priority', 'high')->open()->count(),
                'unassigned'    => \App\Models\Report::whereNull('assigned_to')->open()->count(),
            ];

            $labs = \App\Models\Lab::where('is_active', true)->pluck('name');

            return response()->json(compact('tickets', 'stats', 'labs'));
        });

        /**
         * GET /api/it/queue/{id}
         * Single ticket detail — IT can view any ticket.
         * Mirrors IT\TicketController::show().
         * Used by: app/(it)/queue/[id].tsx
         */
        Route::get('/queue/{id}', function ($id) {
            $ticket = \App\Models\Report::with([
                'reporter', 'assignedTo', 'equipment.lab', 'transactions.user',
            ])->findOrFail($id);

            return response()->json($ticket);
        });

        /**
         * PUT /api/it/queue/{id}
         * Update status, priority, and optionally assigned_to.
         * IT staff can only assign to themselves; admins can assign to anyone.
         * Mirrors IT\TicketController::update().
         * Used by: app/(it)/queue/[id]/edit.tsx
         */
        Route::put('/queue/{id}', function (Request $request, $id) {
            $user   = auth()->user();
            $ticket = \App\Models\Report::findOrFail($id);

            $validated = $request->validate([
                'status'      => ['required', 'in:new,assigned,in-progress,resolved,closed'],
                'priority'    => ['required', 'in:low,medium,high'],
                'assigned_to' => ['nullable', 'exists:users,id'],
            ]);

            // IT staff can only assign to themselves, not to others
            if ($validated['assigned_to'] && $user->role === 'it-support'
                && $validated['assigned_to'] != $user->id) {
                return response()->json([
                    'message' => 'IT Support can only assign tickets to themselves.',
                ], 403);
            }

            $oldStatus   = $ticket->status;
            $oldPriority = $ticket->priority;

            $ticket->update([
                'status'      => $validated['status'],
                'priority'    => $validated['priority'],
                'assigned_to' => $validated['assigned_to'] ?? $ticket->assigned_to,
                'assigned_at' => ($validated['assigned_to'] && !$ticket->assigned_at) ? now() : $ticket->assigned_at,
                'resolved_at' => ($validated['status'] === 'resolved' && $ticket->status !== 'resolved') ? now() : $ticket->resolved_at,
            ]);

            // Log status change
            if ($ticket->wasChanged('status')) {
                \App\Models\TicketTransaction::create([
                    'ticket_id'   => $ticket->id,
                    'user_id'     => $user->id,
                    'action'      => 'status_changed',
                    'old_value'   => $oldStatus,
                    'new_value'   => $validated['status'],
                    'description' => 'Status changed from ' . ucfirst(str_replace('-', ' ', $oldStatus))
                                   . ' to ' . ucfirst(str_replace('-', ' ', $validated['status'])),
                ]);
            }

            // Log priority change
            if ($ticket->wasChanged('priority')) {
                \App\Models\TicketTransaction::create([
                    'ticket_id'   => $ticket->id,
                    'user_id'     => $user->id,
                    'action'      => 'priority_changed',
                    'old_value'   => $oldPriority,
                    'new_value'   => $validated['priority'],
                    'description' => 'Priority changed from ' . ucfirst($oldPriority)
                                   . ' to ' . ucfirst($validated['priority']),
                ]);
            }

            // Log assignment
            if ($ticket->wasChanged('assigned_to') && $validated['assigned_to']) {
                $assignee = \App\Models\User::find($validated['assigned_to']);
                \App\Models\TicketTransaction::create([
                    'ticket_id'   => $ticket->id,
                    'user_id'     => $user->id,
                    'action'      => 'assigned',
                    'new_value'   => $validated['assigned_to'],
                    'description' => 'Ticket assigned to ' . $assignee?->full_name,
                ]);
            }

            return response()->json(
                $ticket->fresh(['reporter', 'assignedTo', 'equipment.lab', 'transactions.user'])
            );
        });

        /**
         * POST /api/it/queue/{id}/assign-self
         * One-tap self-assignment. Sets assigned_to = auth user, status = assigned.
         * Mirrors IT\TicketController::assignToSelf().
         * Used by: app/(it)/queue/[id].tsx and queue/index.tsx
         */
        Route::post('/queue/{id}/assign-self', function ($id) {
            $user   = auth()->user();
            $ticket = \App\Models\Report::findOrFail($id);

            $ticket->update([
                'assigned_to' => $user->id,
                'status'      => 'assigned',
                'assigned_at' => $ticket->assigned_at ?? now(),
            ]);

            \App\Models\TicketTransaction::create([
                'ticket_id'   => $ticket->id,
                'user_id'     => $user->id,
                'action'      => 'assigned',
                'new_value'   => $user->id,
                'description' => 'Ticket assigned to ' . $user->full_name,
            ]);

            return response()->json(
                $ticket->fresh(['reporter', 'assignedTo', 'equipment.lab', 'transactions.user'])
            );
        });

        // ── IT Assignments (my tickets) ───────────────────────────────────────

        /**
         * GET /api/it/assignments?status=&page=
         * Tickets assigned to the authenticated IT user.
         * Mirrors IT\AssignmentController::index().
         * Used by: app/(it)/assignments/index.tsx
         */
        Route::get('/assignments', function (Request $request) {
            $user  = auth()->user();
            $query = \App\Models\Report::where('assigned_to', $user->id)
                ->with(['reporter', 'equipment.lab']);

            if ($request->filled('status')) {
                if ($request->status === 'active') {
                    $query->open();
                } else {
                    $query->where('status', $request->status);
                }
            }

            $tickets = $query->latest()->paginate(10);

            $stats = [
                'total_assigned'   => \App\Models\Report::where('assigned_to', $user->id)->count(),
                'in_progress'      => \App\Models\Report::where('assigned_to', $user->id)->where('status', 'in-progress')->count(),
                'high_priority'    => \App\Models\Report::where('assigned_to', $user->id)->where('priority', 'high')->open()->count(),
                'completed_today'  => \App\Models\Report::where('assigned_to', $user->id)
                    ->where('status', 'resolved')
                    ->whereHas('transactions', fn($q) => $q
                        ->where('action', 'status_changed')
                        ->where('new_value', 'resolved')
                        ->whereDate('created_at', today()))
                    ->count(),
            ];

            return response()->json(compact('tickets', 'stats'));
        });

        /**
         * GET /api/it/assignments/{id}
         * Single assignment detail. The ticket must be assigned to the auth user.
         * Mirrors IT\AssignmentController::show().
         * Used by: app/(it)/assignments/[id].tsx
         */
        Route::get('/assignments/{id}', function ($id) {
            $user   = auth()->user();
            $ticket = \App\Models\Report::where('assigned_to', $user->id)
                ->with(['reporter', 'equipment.lab', 'transactions.user'])
                ->findOrFail($id);

            return response()->json($ticket);
        });

        /**
         * PUT /api/it/assignments/{id}
         * Update status and priority only — no reassignment allowed here.
         * Mirrors IT\AssignmentController::update().
         * Used by: app/(it)/assignments/[id]/edit.tsx
         */
        Route::put('/assignments/{id}', function (Request $request, $id) {
            $user   = auth()->user();
            $ticket = \App\Models\Report::where('assigned_to', $user->id)->findOrFail($id);

            $validated = $request->validate([
                'status'   => ['required', 'in:assigned,in-progress,resolved'],
                'priority' => ['required', 'in:low,medium,high'],
            ]);

            $oldStatus   = $ticket->status;
            $oldPriority = $ticket->priority;

            $ticket->update([
                'status'      => $validated['status'],
                'priority'    => $validated['priority'],
                'resolved_at' => ($validated['status'] === 'resolved' && $ticket->status !== 'resolved')
                    ? now() : $ticket->resolved_at,
            ]);

            if ($ticket->wasChanged('status')) {
                \App\Models\TicketTransaction::create([
                    'ticket_id'   => $ticket->id,
                    'user_id'     => $user->id,
                    'action'      => 'status_changed',
                    'old_value'   => $oldStatus,
                    'new_value'   => $validated['status'],
                    'description' => 'Status changed from ' . ucfirst(str_replace('-', ' ', $oldStatus))
                                   . ' to ' . ucfirst(str_replace('-', ' ', $validated['status'])),
                ]);
            }

            if ($ticket->wasChanged('priority')) {
                \App\Models\TicketTransaction::create([
                    'ticket_id'   => $ticket->id,
                    'user_id'     => $user->id,
                    'action'      => 'priority_changed',
                    'old_value'   => $oldPriority,
                    'new_value'   => $validated['priority'],
                    'description' => 'Priority changed from ' . ucfirst($oldPriority)
                                   . ' to ' . ucfirst($validated['priority']),
                ]);
            }

            return response()->json(
                $ticket->fresh(['reporter', 'equipment.lab', 'transactions.user'])
            );
        });

        // ── IT Knowledge Base Management ──────────────────────────────────────

        /**
         * GET /api/it/articles?search=&status=&category=&page=
         * All articles (published + drafts) — IT management view.
         * Mirrors IT\ArticleController::index().
         * Used by: app/(it)/knowledge-base/index.tsx
         */
        Route::get('/articles', function (Request $request) {
            $query = \App\Models\Article::with('author');

            if ($request->filled('search')) {
                $s = $request->search;
                $query->where(fn($q) => $q
                    ->where('title',   'like', "%{$s}%")
                    ->orWhere('content', 'like', "%{$s}%"));
            }

            if ($request->filled('status')) {
                if ($request->status === 'published') $query->published();
                elseif ($request->status === 'draft')  $query->draft();
            }

            if ($request->filled('category') && $request->category !== 'all') {
                $query->where('category', $request->category);
            }

            $articles = $query->latest()->paginate(10);

            $stats = [
                'total'       => \App\Models\Article::count(),
                'published'   => \App\Models\Article::published()->count(),
                'drafts'      => \App\Models\Article::draft()->count(),
                'total_views' => \App\Models\Article::sum('views'),
            ];

            return response()->json(compact('articles', 'stats'));
        });

        /**
         * POST /api/it/articles
         * Create a new article (draft or published).
         * Mirrors IT\ArticleController::store().
         * Used by: app/(it)/knowledge-base/create.tsx
         */
        Route::post('/articles', function (Request $request) {
            $user = auth()->user();

            $validated = $request->validate([
                'title'    => ['required', 'string', 'max:255'],
                'content'  => ['required', 'string', 'min:50'],
                'excerpt'  => ['nullable', 'string', 'max:500'],
                'category' => ['required', 'in:hardware,software,network,display,peripherals,general'],
                'status'   => ['required', 'in:draft,published'],
            ]);

            $article = \App\Models\Article::create([
                'author_id'    => $user->id,
                'title'        => $validated['title'],
                'content'      => $validated['content'],
                'excerpt'      => $validated['excerpt'] ?? substr(strip_tags($validated['content']), 0, 200),
                'category'     => $validated['category'],
                'status'       => $validated['status'],
                'published_at' => $validated['status'] === 'published' ? now() : null,
            ]);

            return response()->json($article->load('author'), 201);
        });

        /**
         * GET /api/it/articles/{id}
         * Single article with full stats — used by article preview screen.
         * Mirrors IT\ArticleController::show().
         * Used by: app/(it)/knowledge-base/[id]/show.tsx
         */
        Route::get('/articles/{id}', function ($id) {
            $article = \App\Models\Article::with('author')->findOrFail($id);
            return response()->json($article);
        });

        /**
         * PUT /api/it/articles/{id}
         * Update article fields. Handles draft → published transition.
         * Mirrors IT\ArticleController::update().
         * Used by: app/(it)/knowledge-base/[id]/edit.tsx
         */
        Route::put('/articles/{id}', function (Request $request, $id) {
            $article = \App\Models\Article::findOrFail($id);

            $validated = $request->validate([
                'title'    => ['required', 'string', 'max:255'],
                'content'  => ['required', 'string', 'min:50'],
                'excerpt'  => ['nullable', 'string', 'max:500'],
                'category' => ['required', 'in:hardware,software,network,display,peripherals,general'],
                'status'   => ['required', 'in:draft,published'],
            ]);

            // Set published_at when transitioning from draft → published
            if ($article->status === 'draft' && $validated['status'] === 'published') {
                $validated['published_at'] = now();
            }

            $article->update([
                'title'        => $validated['title'],
                'content'      => $validated['content'],
                'excerpt'      => $validated['excerpt'] ?? substr(strip_tags($validated['content']), 0, 200),
                'category'     => $validated['category'],
                'status'       => $validated['status'],
                'published_at' => $validated['published_at'] ?? $article->published_at,
            ]);

            return response()->json($article->fresh('author'));
        });

        /**
         * DELETE /api/it/articles/{id}
         * Delete an article. No soft delete — mirrors IT\ArticleController::destroy().
         * Used by: app/(it)/knowledge-base/index.tsx (swipe-to-delete or confirm dialog)
         */
        Route::delete('/articles/{id}', function ($id) {
            $article = \App\Models\Article::findOrFail($id);
            $article->delete();
            return response()->json(['message' => 'Article deleted successfully.']);
        });

    }); // end IT prefix group

}); // end auth:sanctum group