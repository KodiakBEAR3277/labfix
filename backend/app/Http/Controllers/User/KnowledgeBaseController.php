<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\Http\Request;
use Inertia\Inertia;

class KnowledgeBaseController extends Controller
{
    // Show knowledge base home
    public function index(Request $request)
    {
        $query = Article::published()->with('author');
    
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                ->orWhere('content', 'like', "%{$search}%")
                ->orWhere('excerpt', 'like', "%{$search}%");
            });
        }
    
        if ($request->filled('category') && $request->category !== 'all') {
            $query->category($request->category);
        }
    
        $popularArticles = Article::published()->orderBy('views', 'desc')->take(5)->get();
        $articles        = $query->latest('published_at')->paginate(10)->withQueryString();
    
        $categories = [
            'hardware'    => Article::published()->category('hardware')->count(),
            'software'    => Article::published()->category('software')->count(),
            'network'     => Article::published()->category('network')->count(),
            'display'     => Article::published()->category('display')->count(),
            'peripherals' => Article::published()->category('peripherals')->count(),
            'general'     => Article::published()->category('general')->count(),
        ];
    
        return Inertia::render('User/KnowledgeBase', [
            'articles'        => $articles,
            'popularArticles' => $popularArticles,
            'categories'      => $categories,
            'filters'         => $request->only(['search', 'category']),
        ]);
    }

    // Show single article
    public function show($slug)
    {
        $article = Article::published()->where('slug', $slug)->with('author')->firstOrFail();
        $article->incrementViews();
    
        $relatedArticles = Article::published()
            ->where('category', $article->category)
            ->where('id', '!=', $article->id)
            ->orderBy('views', 'desc')
            ->take(3)
            ->get();
    
        return Inertia::render('User/KnowledgeBaseShow', compact('article', 'relatedArticles'));
    }

    // Mark article as helpful
    public function markHelpful($slug)
    {
        $article = Article::published()->where('slug', $slug)->firstOrFail();
        $article->markHelpful();

        return back()->with('success', 'Thank you for your feedback!');
    }

    // Mark article as not helpful
    public function markNotHelpful($slug)
    {
        $article = Article::published()->where('slug', $slug)->firstOrFail();
        $article->markNotHelpful();

        return back()->with('success', 'Thank you for your feedback!');
    }
}