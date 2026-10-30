<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Models\Setting;
use App\Support\ProfileRules;

// Public API routes
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:auth');

Route::get('/contact-info', function () {
    return response()->json([
        'system_name'   => Setting::getPlatform('platform_name', 'LabFix'),
        'support_email' => Setting::getPlatform('platform_contact_email', ''),
        'support_phone' => Setting::getPlatform('platform_contact_phone', ''),
    ]);
});

// Protected API routes (require token)
Route::middleware(['auth:sanctum', 'institution.active'])->group(function () {

    // ── Shared shaping helpers ──────────────────────────────────────────────
    // Laravel serialises the assignedTo relation under the key "assigned_to",
    // which overwrites the assigned_to id column. The mobile screens expect the
    // numeric id under assigned_to and the person under assigned_to_user.
    $shapeTicket = function ($ticket) {
        $data = $ticket->toArray();
        $data['assigned_to_user'] = ($ticket->relationLoaded('assignedTo') && $ticket->assignedTo)
            ? $ticket->assignedTo->toArray()
            : null;
        $data['assigned_to'] = $ticket->getAttribute('assigned_to');
        return $data;
    };

    // Same shape for GET and PUT /profile so the screen can trust either.
    $profileShape = fn ($user) => [
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
    ];
    
    // Auth endpoints
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // ── User dashboard (mobile (tabs)/index.tsx) ────────────────────────────
    Route::get('/dashboard', function () {
        $user = auth()->user();

        $recentTickets = \App\Models\Report::where('user_id', $user->id)
            ->with('equipment.lab')
            ->latest()
            ->take(5)
            ->get();

        $stats = [
            'active' => \App\Models\Report::where('user_id', $user->id)->open()->count(),
            'resolved_this_month' => \App\Models\Report::where('user_id', $user->id)
                ->whereIn('status', ['resolved', 'closed'])
                ->whereHas('transactions', function ($q) {
                    $q->where('action', 'status_changed')
                      ->where('new_value', 'resolved')
                      ->whereMonth('created_at', now()->month)
                      ->whereYear('created_at', now()->year);
                })->count(),
            'total' => \App\Models\Report::where('user_id', $user->id)->count(),
        ];

        return response()->json([
            'role'           => $user->role,
            'stats'          => $stats,
            'recent_tickets' => $recentTickets,
        ]);
    });

    // Maintenance status for the caller's institution. The mobile report screen
    // reads this to show the notice up front. The server also rejects new
    // tickets while it is on, so this is only a convenience.
    Route::get('/maintenance', function () {
        return response()->json([
            'active'  => (bool) Setting::get('maintenance_mode', false),
            'message' => (string) Setting::get('maintenance_message', ''),
        ]);
    });

    // Lab endpoints
    Route::get('/labs', function() {
        return response()->json(\App\Models\Lab::with('equipment')->get());
    });
    
    Route::get('/labs/{lab}/equipment', function($labId) {
        $equipment = \App\Models\Equipment::where('lab_id', $labId)->get();
        return response()->json($equipment);
    });
    
    // Ticket endpoints
    Route::get('/tickets', function (Request $request) use ($shapeTicket) {
        $user = auth()->user();

        // Scope by role (grouped so the OR can't escape other constraints)
        if ($user->role === 'admin') {
            $query = \App\Models\Report::with(['reporter', 'assignedTo', 'equipment.lab']);
        } elseif ($user->role === 'it-support') {
            $query = \App\Models\Report::where(function ($q) use ($user) {
                    $q->where('assigned_to', $user->id)->orWhereNull('assigned_to');
                })
                ->with(['reporter', 'equipment.lab']);
        } else {
            $query = \App\Models\Report::where('user_id', $user->id)
                ->with(['assignedTo', 'equipment.lab']);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%");
            });
        }

        // Mobile filter tabs send: active | resolved | closed
        if ($request->filled('status') && $request->status !== 'all') {
            if ($request->status === 'active') {
                $query->open();
            } else {
                $query->where('status', $request->status);
            }
        }

        // Paginator JSON already has data, current_page, last_page, total,
        // from, to, prev_page_url, next_page_url, which my-reports.tsx expects.
        return response()->json($query->latest()->paginate(10)->withQueryString()->through($shapeTicket));
    });

    Route::get('/tickets/{id}', function ($id) use ($shapeTicket) {
        $user  = auth()->user();
        $query = \App\Models\Report::with(['reporter', 'assignedTo', 'equipment.lab', 'transactions.user', 'voiceNotes']);

        // Students and staff can only open their own tickets
        if (!in_array($user->role, ['admin', 'it-support'])) {
            $query->where('user_id', $user->id);
        }

        return response()->json($shapeTicket($query->findOrFail($id)));
    });
    
    Route::post('/tickets', function (Request $request) {
        $institutionId = auth()->user()->institution_id;

        // exists: alone ignores the institution scope, so an id from another
        // school would pass. Scope both checks to the caller's institution.
        $validated = $request->validate([
            'lab_id'       => ['required', \Illuminate\Validation\Rule::exists('labs', 'id')->where('institution_id', $institutionId)],
            'equipment_id' => ['nullable', \Illuminate\Validation\Rule::exists('equipment', 'id')->where('institution_id', $institutionId)],
            'category'     => ['required', 'in:hardware,software,network,other'],
            'title'        => ['required', 'string', 'max:255'],
            'description'  => ['required', 'string', 'min:10'],
            // Optional voice notes recorded on the mobile report form. m4a/aac is
            // what expo-audio emits natively; the rest are accepted defensively.
            // 5 MB mirrors the image/PDF limit, and easily covers the 60s cap.
            'voice_notes'       => ['nullable', 'array'],
            'voice_notes.*'     => ['nullable', 'file', 'mimes:m4a,mp4,aac,webm,mp3,ogg,wav,caf,3gp', 'max:5120'],
            'voice_durations'   => ['nullable', 'array'],
            'voice_durations.*' => ['nullable', 'integer', 'min:0', 'max:120'],
        ]);

        // "General / Don't know" sends no equipment_id. Attach it to the lab's
        // placeholder equipment, the same way the web form does.
        if (empty($validated['equipment_id'])) {
            $general = \App\Models\Equipment::firstOrCreate(
                ['lab_id' => $validated['lab_id'], 'equipment_code' => 'GENERAL'],
                ['type' => 'other', 'status' => 'operational', 'notes' => 'General lab issues without specific equipment']
            );
            $validated['equipment_id'] = $general->id;
        }

        $ticket = \App\Models\Report::create([
            'ticket_number' => \App\Models\Report::generateTicketNumber(),
            'user_id'       => auth()->id(),
            'equipment_id'  => $validated['equipment_id'],
            'category'      => $validated['category'],
            'title'         => $validated['title'],
            'description'   => $validated['description'],
            'status'        => 'new',
            'priority'      => \App\Models\Report::determinePriority($validated),
        ]);

        \App\Models\TicketTransaction::create([
            'ticket_id'   => $ticket->id,
            'user_id'     => auth()->id(),
            'action'      => 'created',
            'description' => auth()->user()->full_name . ' created this ticket',
        ]);

        // Persist any voice notes that came with the report. The durations
        // arrive as a parallel array so index $i lines up with file $i.
        foreach ($request->file('voice_notes', []) as $i => $file) {
            \App\Models\VoiceNote::create([
                'report_id'        => $ticket->id,
                'user_id'          => auth()->id(),
                'path'             => $file->store('voice-notes', 'public'),
                'mime_type'        => $file->getClientMimeType(),
                'size_bytes'       => $file->getSize(),
                'duration_seconds' => $request->input("voice_durations.{$i}"),
            ]);
        }

        return response()->json([
            'message' => 'Ticket created successfully',
            'ticket'  => $ticket->load(['equipment.lab', 'voiceNotes']),
        ], 201);
    })->middleware(['maintenance.check', 'throttle:tickets']);

    Route::put('/tickets/{id}', function (Request $request, $id) {
        $report = \App\Models\Report::where('user_id', auth()->id())->findOrFail($id);

        if ($report->assigned_to) {
            return response()->json(['message' => 'Cannot edit a ticket that has been assigned to a technician.'], 422);
        }

        $validated = $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'min:10'],
            'category'    => ['required', 'in:hardware,software,network,other'],
        ]);

        $report->update($validated);

        return response()->json([
            'message' => 'Report updated successfully',
            'report'  => $report->load('equipment.lab'),
        ]);
    });

    Route::delete('/tickets/{id}', function ($id) {
        $report = \App\Models\Report::where('user_id', auth()->id())->findOrFail($id);

        if ($report->assigned_to) {
            return response()->json(['message' => 'Cannot cancel a ticket that has been assigned to a technician.'], 422);
        }

        $report->delete();

        return response()->json(['message' => 'Ticket cancelled successfully']);
    });

    // ── Profile ──────────────────────────────────────────────────────────────

    Route::get('/profile', function () use ($profileShape) {
        return response()->json(['user' => $profileShape(auth()->user())]);
    });

    Route::put('/profile', function (Request $request) use ($profileShape) {
        $user = auth()->user();

        ProfileRules::normalize($request);

        $validated = $request->validate([
            'first_name'       => ProfileRules::name(),
            'last_name'        => ProfileRules::name(),
            'email'            => ['required', 'string', 'email', 'max:255', \Illuminate\Validation\Rule::unique('users')->ignore($user->id)],
            'phone'            => ProfileRules::phone(),
            'student_staff_id' => ProfileRules::studentId(),
        ], ProfileRules::messages());

        $user->update($validated);

        // Return the saved user so the screen can refresh without a second request
        return response()->json([
            'message' => 'Profile updated successfully',
            'user'    => $profileShape($user->fresh()),
        ]);
    });

    Route::put('/profile/password', function (Request $request) {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'confirmed', \Illuminate\Validation\Rules\Password::defaults()],
        ]);

        auth()->user()->update([
            'password' => \Illuminate\Support\Facades\Hash::make($validated['password']),
        ]);

        return response()->json(['message' => 'Password updated successfully']);
    });

    Route::put('/profile/preferences', function (Request $request) {
        auth()->user()->update([
            'email_notifications' => $request->boolean('email_notifications'),
        ]);

        return response()->json(['message' => 'Preferences updated successfully']);
    });

    // ── Knowledge base ───────────────────────────────────────────────────────

    Route::get('/knowledge-base', function (Request $request) {
        $query = \App\Models\Article::published()->with('author');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%")
                  ->orWhere('excerpt', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category') && $request->category !== 'all') {
            $query->category($request->category);
        }

        $popularArticles = \App\Models\Article::published()->orderBy('views', 'desc')->take(5)->get();
        $articles = $query->latest('published_at')->paginate(10)->withQueryString();

        $categories = [
            'hardware'    => \App\Models\Article::published()->category('hardware')->count(),
            'software'    => \App\Models\Article::published()->category('software')->count(),
            'network'     => \App\Models\Article::published()->category('network')->count(),
            'display'     => \App\Models\Article::published()->category('display')->count(),
            'peripherals' => \App\Models\Article::published()->category('peripherals')->count(),
            'general'     => \App\Models\Article::published()->category('general')->count(),
        ];

        return response()->json([
            'articles'        => $articles,
            'popularArticles' => $popularArticles,
            'categories'      => $categories,
        ]);
    });

    Route::get('/knowledge-base/{slug}', function ($slug) {
        $article = \App\Models\Article::published()->where('slug', $slug)->with('author')->firstOrFail();
        $article->incrementViews();

        $relatedArticles = \App\Models\Article::published()
            ->where('category', $article->category)
            ->where('id', '!=', $article->id)
            ->orderBy('views', 'desc')
            ->take(3)
            ->get();

        return response()->json(compact('article', 'relatedArticles'));
    });

    Route::post('/knowledge-base/{slug}/{type}', function ($slug, $type) {
        if (!in_array($type, ['helpful', 'not-helpful'])) {
            abort(404);
        }

        $article = \App\Models\Article::published()->where('slug', $slug)->firstOrFail();

        $type === 'helpful' ? $article->markHelpful() : $article->markNotHelpful();

        return response()->json(['message' => 'Thank you for your feedback!']);
    });

    // ── IT zone (role:it-support,admin) ─────────────────────────────────────
    Route::middleware('role:it-support,admin')->prefix('it')->group(function () use ($shapeTicket) {

        // One full ticket, shaped for mobile. $mineOnly limits it to tickets
        // assigned to the caller (used by the assignments screens).
        $itTicket = function ($id, $mineOnly = false) use ($shapeTicket) {
            $query = \App\Models\Report::with(['reporter', 'assignedTo', 'equipment.lab', 'transactions.user', 'voiceNotes']);
            if ($mineOnly) {
                $query->where('assigned_to', auth()->id());
            }
            return $shapeTicket($query->findOrFail($id));
        };

        Route::get('/dashboard', function () {
            $recentTickets = \App\Models\Report::with(['reporter', 'equipment.lab'])->latest()->take(10)->get();

            $priorityAlerts = \App\Models\Report::with(['reporter', 'equipment.lab'])
                ->where('priority', 'high')->open()->latest()->take(5)->get();

            $stats = [
                'open_tickets'    => \App\Models\Report::open()->count(),
                'my_assignments'  => \App\Models\Report::where('assigned_to', auth()->id())->open()->count(),
                'high_priority'   => \App\Models\Report::where('priority', 'high')->open()->count(),
                'resolved_today'  => \App\Models\Report::where('status', 'resolved')
                    ->whereHas('transactions', function ($q) {
                        $q->where('action', 'status_changed')->where('new_value', 'resolved')->whereDate('created_at', today());
                    })->count(),
            ];

            $unassignedCount = \App\Models\Report::whereNull('assigned_to')->open()->count();

            // avg hours from creation to first assignment, last 7 days
            $assignedRows = \App\Models\Report::whereDate('created_at', '>=', now()->subDays(7))
                ->with(['transactions' => fn ($q) => $q->where('action', 'assigned')])
                ->get();
            $hours = [];
            foreach ($assignedRows as $r) {
                $tx = $r->transactions->first();
                if ($tx) $hours[] = $r->created_at->diffInHours($tx->created_at);
            }
            $stats['avg_response_time'] = count($hours)
                ? number_format(array_sum($hours) / count($hours), 1)
                : '0.0';

            // resolved this month / created this month, as a percentage
            $resolvedThisMonth = \App\Models\Report::where('status', 'resolved')
                ->whereHas('transactions', function ($q) {
                    $q->where('action', 'status_changed')->where('new_value', 'resolved')
                      ->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year);
                })->count();
            $createdThisMonth = \App\Models\Report::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)->count();
            $stats['team_satisfaction'] = $createdThisMonth > 0
                ? round(($resolvedThisMonth / $createdThisMonth) * 100)
                : 0;

            // last 3 resolutions: [{ ticket_number, resolved_at }]
            $recentResolved = \App\Models\TicketTransaction::with('ticket')
                ->where('action', 'status_changed')
                ->where('new_value', 'resolved')
                ->latest('created_at')
                ->take(3)
                ->get()
                ->filter(fn ($t) => $t->ticket)
                ->map(fn ($t) => [
                    'ticket_number' => $t->ticket->ticket_number,
                    'resolved_at'   => $t->created_at->diffForHumans(),
                ])
                ->values();

            return response()->json(compact('recentTickets', 'priorityAlerts', 'stats', 'unassignedCount', 'recentResolved'));
        });

        // Ticket queue
        Route::get('/queue', function (Request $request) use ($shapeTicket) {
            $query = \App\Models\Report::with(['reporter', 'assignedTo', 'equipment.lab']);

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('ticket_number', 'like', "%{$search}%")->orWhere('title', 'like', "%{$search}%");
                });
            }
            if ($request->filled('status') && $request->status !== 'all')     $query->where('status', $request->status);
            if ($request->filled('priority') && $request->priority !== 'all') $query->where('priority', $request->priority);
            if ($request->filled('lab') && $request->lab !== 'all') {
                $query->whereHas('equipment.lab', fn ($q) => $q->where('name', $request->lab));
            }

            $tickets = $query->latest()->paginate(10)->withQueryString()->through($shapeTicket);

            $stats = [
                'open'          => \App\Models\Report::open()->count(),
                'assigned'      => \App\Models\Report::where('status', 'assigned')->count(),
                'in_progress'   => \App\Models\Report::where('status', 'in-progress')->count(),
                'high_priority' => \App\Models\Report::where('priority', 'high')->open()->count(),
            ];

            $itStaff = \App\Models\User::whereIn('role', ['it-support', 'admin'])->get(['id', 'first_name', 'last_name', 'role']);
            $labs    = \App\Models\Lab::where('is_active', true)->pluck('name');

            return response()->json(compact('tickets', 'stats', 'itStaff', 'labs'));
        });

        Route::get('/queue/{id}', function ($id) use ($itTicket) {
            return response()->json($itTicket($id));
        });

        Route::put('/queue/{id}', function (Request $request, $id) use ($itTicket) {
            $ticket = \App\Models\Report::findOrFail($id);

            $validated = $request->validate([
                'status'      => ['required', 'in:new,assigned,in-progress,resolved,closed'],
                'priority'    => ['required', 'in:low,medium,high'],
                'assigned_to' => ['nullable', 'exists:users,id'],
            ]);

            $statusChanged     = $ticket->status !== $validated['status'];
            $priorityChanged   = $ticket->priority !== $validated['priority'];
            $assignmentChanged = $ticket->assigned_to !== $validated['assigned_to'];
            $oldStatus = $ticket->status;
            $oldPriority = $ticket->priority;

            $ticket->update([
                'status'      => $validated['status'],
                'priority'    => $validated['priority'],
                'assigned_to' => $validated['assigned_to'],
                'assigned_at' => $validated['assigned_to'] && !$ticket->assigned_at ? now() : $ticket->assigned_at,
            ]);

            if ($statusChanged) {
                \App\Models\TicketTransaction::create([
                    'ticket_id' => $ticket->id, 'user_id' => auth()->id(), 'action' => 'status_changed',
                    'old_value' => $oldStatus, 'new_value' => $validated['status'],
                    'description' => 'Status changed from ' . ucfirst(str_replace('-', ' ', $oldStatus)) . ' to ' . ucfirst(str_replace('-', ' ', $validated['status'])),
                ]);
            }
            if ($priorityChanged) {
                \App\Models\TicketTransaction::create([
                    'ticket_id' => $ticket->id, 'user_id' => auth()->id(), 'action' => 'priority_changed',
                    'old_value' => $oldPriority, 'new_value' => $validated['priority'],
                    'description' => 'Priority changed from ' . ucfirst($oldPriority) . ' to ' . ucfirst($validated['priority']),
                ]);
            }
            if ($assignmentChanged && $validated['assigned_to']) {
                $assignedUser = \App\Models\User::find($validated['assigned_to']);
                \App\Models\TicketTransaction::create([
                    'ticket_id' => $ticket->id, 'user_id' => auth()->id(), 'action' => 'assigned',
                    'old_value' => null, 'new_value' => $validated['assigned_to'],
                    'description' => 'Ticket assigned to ' . $assignedUser->full_name,
                ]);
            }

            return response()->json($itTicket($ticket->id));
        });

        Route::post('/queue/{id}/assign-self', function ($id) use ($itTicket) {
            $ticket = \App\Models\Report::findOrFail($id);
            $ticket->update([
                'assigned_to' => auth()->id(),
                'status'      => 'assigned',
                'assigned_at' => $ticket->assigned_at ?? now(),
            ]);
            return response()->json($itTicket($ticket->id));
        });

        // My assignments
        Route::get('/assignments', function (Request $request) {
            $query = \App\Models\Report::where('assigned_to', auth()->id())->with(['reporter', 'equipment.lab']);

            if ($request->filled('status')) {
                $request->status === 'active' ? $query->open() : $query->where('status', $request->status);
            }

            $tickets = $query->latest()->paginate(10)->withQueryString();

            $stats = [
                'total_assigned'   => \App\Models\Report::where('assigned_to', auth()->id())->count(),
                'in_progress'      => \App\Models\Report::where('assigned_to', auth()->id())->where('status', 'in-progress')->count(),
                'high_priority'    => \App\Models\Report::where('assigned_to', auth()->id())->where('priority', 'high')->open()->count(),
                'completed_today'  => \App\Models\Report::where('assigned_to', auth()->id())->where('status', 'resolved')
                    ->whereHas('transactions', function ($q) {
                        $q->where('action', 'status_changed')->where('new_value', 'resolved')->whereDate('created_at', today());
                    })->count(),
            ];

            return response()->json(compact('tickets', 'stats'));
        });

        Route::get('/assignments/{id}', function ($id) use ($itTicket) {
            return response()->json($itTicket($id, true));
        });

        Route::put('/assignments/{id}', function (Request $request, $id) use ($itTicket) {
            $ticket = \App\Models\Report::where('assigned_to', auth()->id())->findOrFail($id);

            $validated = $request->validate([
                'status'   => ['required', 'in:assigned,in-progress,resolved'],
                'priority' => ['required', 'in:low,medium,high'],
            ]);

            $statusChanged   = $ticket->status !== $validated['status'];
            $priorityChanged = $ticket->priority !== $validated['priority'];
            $oldStatus = $ticket->status;
            $oldPriority = $ticket->priority;

            $ticket->update($validated);

            if ($statusChanged) {
                \App\Models\TicketTransaction::create([
                    'ticket_id' => $ticket->id, 'user_id' => auth()->id(), 'action' => 'status_changed',
                    'old_value' => $oldStatus, 'new_value' => $validated['status'],
                    'description' => 'Status changed from ' . ucfirst(str_replace('-', ' ', $oldStatus)) . ' to ' . ucfirst(str_replace('-', ' ', $validated['status'])),
                ]);
            }
            if ($priorityChanged) {
                \App\Models\TicketTransaction::create([
                    'ticket_id' => $ticket->id, 'user_id' => auth()->id(), 'action' => 'priority_changed',
                    'old_value' => $oldPriority, 'new_value' => $validated['priority'],
                    'description' => 'Priority changed from ' . ucfirst($oldPriority) . ' to ' . ucfirst($validated['priority']),
                ]);
            }

            return response()->json($itTicket($ticket->id, true));
        });

        // Knowledge base management
        Route::get('/articles', function (Request $request) {
            $query = \App\Models\Article::with('author');

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(fn ($q) => $q->where('title', 'like', "%{$search}%")->orWhere('content', 'like', "%{$search}%"));
            }
            if ($request->filled('status')) {
                $request->status === 'published' ? $query->published() : $query->draft();
            }
            if ($request->filled('category') && $request->category !== 'all') $query->category($request->category);

            $articles = $query->latest()->paginate(10)->withQueryString();

            $stats = [
                'total'       => \App\Models\Article::count(),
                'published'   => \App\Models\Article::published()->count(),
                'drafts'      => \App\Models\Article::draft()->count(),
                'total_views' => \App\Models\Article::sum('views'),
            ];

            return response()->json(compact('articles', 'stats'));
        });

        Route::post('/articles', function (Request $request) {
            $validated = $request->validate([
                'title'    => ['required', 'string', 'max:255'],
                'content'  => ['required', 'string', 'min:50'],
                'excerpt'  => ['nullable', 'string', 'max:500'],
                'category' => ['required', 'in:hardware,software,network,display,peripherals,general'],
                'status'   => ['required', 'in:draft,published'],
            ]);

            $article = \App\Models\Article::create([
                'author_id'    => auth()->id(),
                'title'        => $validated['title'],
                'content'      => $validated['content'],
                'excerpt'      => $validated['excerpt'] ?? substr(strip_tags($validated['content']), 0, 200),
                'category'     => $validated['category'],
                'status'       => $validated['status'],
                'published_at' => $validated['status'] === 'published' ? now() : null,
            ]);

            return response()->json(['message' => 'Article created successfully!', 'article' => $article], 201);
        });

        Route::get('/articles/{id}', function ($id) {
            // Unwrapped, not compact('article') — the mobile Article type
            // reads fields straight off the response (article.category, not
            // article.article.category), same as the show and edit screens.
            $article = \App\Models\Article::with('author')->findOrFail($id);
            return response()->json($article);
        });

        Route::put('/articles/{id}', function (Request $request, $id) {
            $article = \App\Models\Article::findOrFail($id);

            $validated = $request->validate([
                'title'    => ['required', 'string', 'max:255'],
                'content'  => ['required', 'string', 'min:50'],
                'excerpt'  => ['nullable', 'string', 'max:500'],
                'category' => ['required', 'in:hardware,software,network,display,peripherals,general'],
                'status'   => ['required', 'in:draft,published'],
            ]);

            if ($article->status === 'draft' && $validated['status'] === 'published') {
                $validated['published_at'] = now();
            }

            $article->update($validated);

            return response()->json(['message' => 'Article updated successfully!']);
        });

        Route::delete('/articles/{id}', function ($id) {
            \App\Models\Article::findOrFail($id)->delete();
            return response()->json(['message' => 'Article deleted successfully!']);
        });
    });
});