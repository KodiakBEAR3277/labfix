@extends('layouts.app')

@section('title', $article->title)

@section('navigation')
    <x-nav.user />
@endsection

@section('content')
    <div class="container">
        <a href="{{ route('user.knowledge-base') }}" class="back-btn">← Back to Knowledge Base</a>

        @if(session('success'))
            <div class="alert alert-success" style="background: rgba(16, 185, 129, 0.2); border: 1px solid rgba(16, 185, 129, 0.3); padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; color: #34d399;">
                {{ session('success') }}
            </div>
        @endif

        <div class="content-layout">
            <!-- Main Article Content -->
            <div>
                <!-- Article Header -->
                <div class="card">
                    <div style="margin-bottom: 1rem;">
                        <span class="category-badge">{{ ucfirst($article->category) }}</span>
                    </div>
                    <h1 style="font-size: 2.5rem; color: #ffffff; margin-bottom: 1rem; line-height: 1.2;">
                        {{ $article->title }}
                    </h1>
                    <div style="display: flex; gap: 1.5rem; color: #9ca3af; font-size: 0.9rem; flex-wrap: wrap;">
                        <div>👁️ {{ number_format($article->views) }} views</div>
                        <div>👍 {{ $article->helpfulness_percentage }}% helpful</div>
                        <div>✏️ By {{ $article->author->full_name }}</div>
                        <div>📅 {{ $article->published_at ? $article->published_at->format('M d, Y') : $article->created_at->format('M d, Y') }}</div>
                    </div>
                </div>

                <!-- Article Content -->
                <div class="card" style="margin-top: 1.5rem;">
                    <div style="color: #d1d5db; line-height: 1.8; font-size: 1.05rem;">
                        {!! nl2br(e($article->content)) !!}
                    </div>
                </div>

                <!-- Was this helpful? -->
                <div class="card" style="margin-top: 1.5rem; text-align: center;">
                    <h3 style="color: #ffffff; margin-bottom: 1rem;">Was this article helpful?</h3>
                    <div style="display: flex; gap: 1rem; justify-content: center;">
                        <form action="{{ route('user.knowledge-base.helpful', $article->slug) }}" method="POST" style="display: inline;">
                            @csrf
                            <button type="submit" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 0.5rem;">
                                👍 Yes, this helped
                            </button>
                        </form>
                        <form action="{{ route('user.knowledge-base.not-helpful', $article->slug) }}" method="POST" style="display: inline;">
                            @csrf
                            <button type="submit" class="btn btn-secondary" style="display: inline-flex; align-items: center; gap: 0.5rem;">
                                👎 No, not helpful
                            </button>
                        </form>
                    </div>
                    <p style="color: #9ca3af; margin-top: 1rem; font-size: 0.9rem;">
                        {{ $article->helpful_count + $article->not_helpful_count }} people have rated this article
                    </p>
                </div>
            </div>

            <!-- Sidebar -->
            <div>
                <!-- Article Info -->
                <div class="info-card">
                    <h2 class="card-title">Article Information</h2>
                    <div class="info-list">
                        <div class="info-item">
                            <span class="info-label">Category</span>
                            <span class="info-value">{{ ucfirst($article->category) }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Views</span>
                            <span class="info-value">{{ number_format($article->views) }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Helpfulness</span>
                            <span class="info-value">{{ $article->helpfulness_percentage }}%</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Last Updated</span>
                            <span class="info-value">{{ $article->updated_at->diffForHumans() }}</span>
                        </div>
                    </div>
                </div>

                <!-- Related Articles -->
                @if($relatedArticles->count() > 0)
                    <div class="info-card" style="margin-top: 1.5rem;">
                        <h2 class="card-title">Related Articles</h2>
                        <div style="display: flex; flex-direction: column; gap: 1rem;">
                            @foreach($relatedArticles as $related)
                                <a href="{{ route('user.knowledge-base.show', $related->slug) }}" style="text-decoration: none;">
                                    <div style="padding: 1rem; background: rgba(45, 212, 191, 0.1); border-radius: 8px; border: 1px solid rgba(45, 212, 191, 0.3); transition: all 0.3s;">
                                        <h4 style="color: #2dd4bf; margin-bottom: 0.5rem; font-size: 0.95rem;">{{ $related->title }}</h4>
                                        <div style="color: #9ca3af; font-size: 0.85rem;">
                                            👁️ {{ number_format($related->views) }} views
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Still Need Help? -->
                <div class="info-card" style="margin-top: 1.5rem; text-align: center;">
                    <h2 class="card-title">Still Need Help?</h2>
                    <p style="color: #9ca3af; margin-bottom: 1rem;">
                        Can't find a solution? Submit a support ticket and our IT team will assist you.
                    </p>
                    <a href="{{ route('user.reports.create') }}" class="btn btn-primary">Report an Issue</a>
                </div>
            </div>
        </div>
    </div>
@endsection