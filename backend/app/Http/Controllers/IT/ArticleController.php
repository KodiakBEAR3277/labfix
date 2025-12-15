<?php

namespace App\Http\Controllers\IT;

use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ArticleController extends Controller
{
    // Show all articles (IT management view)
    public function index(Request $request)
    {
        $query = Article::with('author');

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($request->filled('status')) {
            if ($request->status === 'published') {
                $query->published();
            } elseif ($request->status === 'draft') {
                $query->draft();
            }
        }

        // Category filter
        if ($request->filled('category') && $request->category !== 'all') {
            $query->category($request->category);
        }

        $articles = $query->latest()->paginate(10)->withQueryString();

        // Stats
        $stats = [
            'total' => Article::count(),
            'published' => Article::published()->count(),
            'drafts' => Article::draft()->count(),
            'total_views' => Article::sum('views'),
        ];

        return view('it.knowledge-base', compact('articles', 'stats'));
    }

    // Show create form
    public function create()
    {
        return view('it.articles.create');
    }

    // Store new article
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'min:50'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'category' => ['required', 'in:hardware,software,network,display,peripherals,general'],
            'status' => ['required', 'in:draft,published'],
        ]);

        $article = Article::create([
            'author_id' => Auth::id(),
            'title' => $validated['title'],
            'content' => $validated['content'],
            'excerpt' => $validated['excerpt'] ?? substr(strip_tags($validated['content']), 0, 200),
            'category' => $validated['category'],
            'status' => $validated['status'],
            'published_at' => $validated['status'] === 'published' ? now() : null,
        ]);

        return redirect()
            ->route('it.knowledge-base.index')
            ->with('success', 'Article created successfully!');
    }

    // Show edit form
    public function edit($id)
    {
        $article = Article::findOrFail($id);
        return view('it.articles.edit', compact('article'));
    }

    // Update article
    public function update(Request $request, $id)
    {
        $article = Article::findOrFail($id);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'min:50'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'category' => ['required', 'in:hardware,software,network,display,peripherals,general'],
            'status' => ['required', 'in:draft,published'],
        ]);

        // If changing from draft to published, set published_at
        if ($article->status === 'draft' && $validated['status'] === 'published') {
            $validated['published_at'] = now();
        }

        $article->update([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'excerpt' => $validated['excerpt'] ?? substr(strip_tags($validated['content']), 0, 200),
            'category' => $validated['category'],
            'status' => $validated['status'],
            'published_at' => $validated['published_at'] ?? $article->published_at,
        ]);

        return redirect()
            ->route('it.knowledge-base.index')
            ->with('success', 'Article updated successfully!');
    }

    // Delete article
    public function destroy($id)
    {
        $article = Article::findOrFail($id);
        $article->delete();

        return redirect()
            ->route('it.knowledge-base.index')
            ->with('success', 'Article deleted successfully!');
    }

    // Show article (preview)
    public function show($id)
    {
        $article = Article::with('author')->findOrFail($id);
        return view('it.articles.show', compact('article'));
    }
}