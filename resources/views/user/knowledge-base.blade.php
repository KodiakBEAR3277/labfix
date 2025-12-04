@extends('layouts.app')

@section('title', 'Knowledge Base')

@section('navigation')
    <x-nav.user />
@endsection

@section('content')
    <div class="container">
        <!-- Hero Section -->
        <div class="kb-hero">
            <h1>How can we help you?</h1>
            <p>Search our knowledge base for solutions to common problems</p>
            <form method="GET" action="{{ route('user.knowledge-base') }}">
                <div class="search-container">
                    <span class="search-icon">🔍</span>
                    <input 
                        type="text" 
                        name="search"
                        class="search-input" 
                        placeholder="Search for articles, guides, and solutions..."
                        value="{{ request('search') }}"
                    >
                </div>
            </form>
        </div>

        <!-- Categories -->
        @isset($categories)
        <div class="categories-section">
            <h2 class="section-title">Browse by Category</h2>
            <div class="categories-grid">
                <a href="{{ route('user.knowledge-base', ['category' => 'hardware']) }}" class="category-card">
                    <div class="category-icon">🔧</div>
                    <div class="category-title">Hardware Issues</div>
                    <div class="category-count">{{ $categories['hardware'] }} articles</div>
                </a>
                <a href="{{ route('user.knowledge-base', ['category' => 'software']) }}" class="category-card">
                    <div class="category-icon">💾</div>
                    <div class="category-title">Software Problems</div>
                    <div class="category-count">{{ $categories['software'] }} articles</div>
                </a>
                <a href="{{ route('user.knowledge-base', ['category' => 'network']) }}" class="category-card">
                    <div class="category-icon">🌐</div>
                    <div class="category-title">Network & Connectivity</div>
                    <div class="category-count">{{ $categories['network'] }} articles</div>
                </a>
                <a href="{{ route('user.knowledge-base', ['category' => 'display']) }}" class="category-card">
                    <div class="category-icon">🖥️</div>
                    <div class="category-title">Display Issues</div>
                    <div class="category-count">{{ $categories['display'] }} articles</div>
                </a>
                <a href="{{ route('user.knowledge-base', ['category' => 'peripherals']) }}" class="category-card">
                    <div class="category-icon">⌨️</div>
                    <div class="category-title">Peripherals</div>
                    <div class="category-count">{{ $categories['peripherals'] }} articles</div>
                </a>
                <a href="{{ route('user.knowledge-base', ['category' => 'general']) }}" class="category-card">
                    <div class="category-icon">❓</div>
                    <div class="category-title">General Help</div>
                    <div class="category-count">{{ $categories['general'] }} articles</div>
                </a>
            </div>
        </div>
        @endisset

        <!-- Popular Articles -->
        @if($popularArticles->count() > 0)
            <div class="articles-section">
                <h2 class="section-title">Popular Articles</h2>
                <div class="articles-grid">
                    @foreach($popularArticles as $article)
                        <a href="{{ route('user.knowledge-base.show', $article->slug) }}" class="article-card">
                            <div class="article-content">
                                <div class="article-title">{{ $article->title }}</div>
                                <div class="article-meta">
                                    <span>👁️ {{ number_format($article->views) }} views</span>
                                    <span>👍 {{ $article->helpfulness_percentage }}% helpful</span>
                                    <span>{{ ucfirst($article->category) }}</span>
                                </div>
                            </div>
                            <div class="article-icon">→</div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- All Articles / Search Results -->
        @if(request('search') || request('category'))
            <div class="articles-section">
                <h2 class="section-title">
                    @if(request('search'))
                        Search Results for "{{ request('search') }}"
                    @elseif(request('category'))
                        {{ ucfirst(request('category')) }} Articles
                    @endif
                </h2>

                @if($articles->count() > 0)
                    <div class="articles-grid">
                        @foreach($articles as $article)
                            <a href="{{ route('user.knowledge-base.show', $article->slug) }}" class="article-card">
                                <div class="article-content">
                                    <div class="article-title">{{ $article->title }}</div>
                                    @if($article->excerpt)
                                        <p style="color: #9ca3af; font-size: 0.9rem; margin-top: 0.5rem;">{{ Str::limit($article->excerpt, 150) }}</p>
                                    @endif
                                    <div class="article-meta">
                                        <span>👁️ {{ number_format($article->views) }} views</span>
                                        <span>👍 {{ $article->helpfulness_percentage }}% helpful</span>
                                        <span>{{ ucfirst($article->category) }}</span>
                                    </div>
                                </div>
                                <div class="article-icon">→</div>
                            </a>
                        @endforeach
                    </div>

                    <!-- Pagination -->
                    @if($articles->hasPages())
                        <div class="pagination" style="margin-top: 2rem;">
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
                @else
                    <div class="empty-state">
                        <div class="empty-icon">🔍</div>
                        <h3>No articles found</h3>
                        <p>Try adjusting your search terms or browse by category</p>
                        <a href="{{ route('user.knowledge-base') }}" class="btn btn-primary" style="margin-top: 1rem;">View All Articles</a>
                    </div>
                @endif
            </div>
        @endif

        <!-- Quick Links -->
        <div class="articles-section">
            <h2 class="section-title">Quick Links</h2>
            <div class="quick-links">
                <div class="quick-link-card">
                    <div class="quick-link-title">Getting Started</div>
                    <div class="link-list">
                        <a href="#" class="link-item">• How to report an issue</a>
                        <a href="#" class="link-item">• Understanding ticket statuses</a>
                        <a href="#" class="link-item">• Creating your account</a>
                        <a href="#" class="link-item">• Lab equipment locations</a>
                    </div>
                </div>

                <div class="quick-link-card">
                    <div class="quick-link-title">Common Problems</div>
                    <div class="link-list">
                        <a href="{{ route('user.knowledge-base', ['search' => 'computer won\'t start']) }}" class="link-item">• Computer won't start</a>
                        <a href="{{ route('user.knowledge-base', ['search' => 'slow performance']) }}" class="link-item">• Slow performance</a>
                        <a href="{{ route('user.knowledge-base', ['search' => 'application crashes']) }}" class="link-item">• Application crashes</a>
                        <a href="{{ route('user.knowledge-base', ['search' => 'login issues']) }}" class="link-item">• Login issues</a>
                    </div>
                </div>

                <div class="quick-link-card">
                    <div class="quick-link-title">Policies & Guidelines</div>
                    <div class="link-list">
                        <a href="#" class="link-item">• Lab usage policies</a>
                        <a href="#" class="link-item">• Equipment care guidelines</a>
                        <a href="#" class="link-item">• Support response times</a>
                        <a href="#" class="link-item">• Reporting requirements</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Help Banner -->
        <div class="help-banner">
            <h3>Can't find what you're looking for?</h3>
            <p>Our support team is here to help you with any issues</p>
            <a href="{{ route('user.reports.create') }}" class="btn-contact">Report an Issue</a>
        </div>
    </div>
@endsection