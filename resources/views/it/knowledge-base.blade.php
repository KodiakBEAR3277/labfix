@extends('layouts.app')

@section('title', 'Knowledge Base Admin')

@section('navigation')
    @include('components.nav.it')
@endsection

@section('content')
    <div class="container">
        <div class="page-header">
            <div>
                <h1>Knowledge Base Management</h1>
                <p style="color: #9ca3af;">Create and manage troubleshooting articles</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('it.knowledge-base.create') }}" class="btn btn-primary">+ Create New Article</a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success" style="background: rgba(16, 185, 129, 0.2); border: 1px solid rgba(16, 185, 129, 0.3); padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; color: #34d399;">
                {{ session('success') }}
            </div>
        @endif

        <!-- Stats Row -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-label">Total Articles</div>
                <div class="stat-value">{{ $stats['total'] }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Published</div>
                <div class="stat-value">{{ $stats['published'] }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Drafts</div>
                <div class="stat-value">{{ $stats['drafts'] }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Total Views</div>
                <div class="stat-value">{{ number_format($stats['total_views'] / 1000, 1) }}K</div>
            </div>
        </div>

        <!-- Filter Bar -->
        <form method="GET" action="{{ route('it.knowledge-base.index') }}" id="filterForm">
            <div class="filter-bar">
                <div class="search-box">
                    <span class="search-icon">🔍</span>
                    <input 
                        type="text" 
                        name="search"
                        placeholder="Search articles..."
                        value="{{ request('search') }}"
                        onchange="document.getElementById('filterForm').submit()"
                    >
                </div>
                <div class="filter-tabs">
                    <button type="submit" name="status" value="" class="tab {{ !request('status') ? 'active' : '' }}">All</button>
                    <button type="submit" name="status" value="published" class="tab {{ request('status') === 'published' ? 'active' : '' }}">Published</button>
                    <button type="submit" name="status" value="draft" class="tab {{ request('status') === 'draft' ? 'active' : '' }}">Drafts</button>
                    <button type="submit" name="category" value="hardware" class="tab {{ request('category') === 'hardware' ? 'active' : '' }}">Hardware</button>
                    <button type="submit" name="category" value="software" class="tab {{ request('category') === 'software' ? 'active' : '' }}">Software</button>
                    <button type="submit" name="category" value="network" class="tab {{ request('category') === 'network' ? 'active' : '' }}">Network</button>
                </div>
            </div>
        </form>

        <!-- Articles Grid -->
        <div class="articles-grid">
            @forelse($articles as $article)
                <div class="article-card">
                    <div class="article-content">
                        <div class="article-header">
                            <div>
                                <h3 class="article-title">{{ $article->title }}</h3>
                                <span class="status-badge status-{{ $article->status }}">{{ ucfirst($article->status) }}</span>
                            </div>
                        </div>
                        <div class="article-meta">
                            <div class="meta-item">
                                <span>📁</span> {{ ucfirst($article->category) }}
                            </div>
                            <div class="meta-item">
                                <span>👁️</span> {{ number_format($article->views) }} views
                            </div>
                            <div class="meta-item">
                                <span>👍</span> {{ $article->helpfulness_percentage }}% helpful
                            </div>
                            <div class="meta-item">
                                <span>✏️</span> {{ $article->author->full_name }}
                            </div>
                            <div class="meta-item">
                                <span>📅</span> {{ $article->updated_at->diffForHumans() }}
                            </div>
                        </div>
                    </div>
                    <div class="article-actions">
                        <a href="{{ route('it.knowledge-base.edit', $article->id) }}" class="action-btn">Edit</a>
                        <a href="{{ route('it.knowledge-base.show', $article->id) }}" class="action-btn">{{ $article->status === 'draft' ? 'Preview' : 'View' }}</a>
                        <form action="{{ route('it.knowledge-base.destroy', $article->id) }}" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this article?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="action-btn btn-danger">Delete</button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="empty-state">
                    <div class="empty-icon">📚</div>
                    <h3>No articles yet</h3>
                    <p>Create your first knowledge base article</p>
                    <a href="{{ route('it.knowledge-base.create') }}" class="btn btn-primary" style="margin-top: 1rem;">+ Create Article</a>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        @if($articles->hasPages())
            <div class="pagination">
                <div class="page-info">
                    Showing {{ $articles->firstItem() ?? 0 }}-{{ $articles->lastItem() ?? 0 }} of {{ $articles->total() }} articles
                </div>
                <div class="page-controls">
                    @if ($articles->onFirstPage())
                        <button class="page-btn" disabled>← Previous</button>
                    @else
                        <a href="{{ $articles->previousPageUrl() }}" class="page-btn">← Previous</a>
                    @endif

                    @foreach ($articles->getUrlRange(1, $articles->lastPage()) as $page => $url)
                        @if ($page == $articles->currentPage())
                            <button class="page-btn active">{{ $page }}</button>
                        @else
                            <a href="{{ $url }}" class="page-btn">{{ $page }}</a>
                        @endif
                    @endforeach

                    @if ($articles->hasMorePages())
                        <a href="{{ $articles->nextPageUrl() }}" class="page-btn">Next →</a>
                    @else
                        <button class="page-btn" disabled>Next →</button>
                    @endif
                </div>
            </div>
        @endif
    </div>
@endsection