@extends('layouts.app')

@section('title', 'Preview Article')

@section('navigation')
    @include('components.nav.it')
@endsection

@section('content')
    <div class="container">
        <a href="{{ route('it.knowledge-base.index') }}" class="back-btn">← Back to Knowledge Base</a>

        <div class="page-header">
            <h1>Article Preview</h1>
            <div class="header-actions">
                <a href="{{ route('it.knowledge-base.edit', $article->id) }}" class="btn btn-primary">Edit Article</a>
            </div>
        </div>

        <div class="content-layout">
            <!-- Main Content -->
            <div>
                <!-- Article Header -->
                <div class="card">
                    <div style="margin-bottom: 1rem;">
                        <span class="category-badge">{{ ucfirst($article->category) }}</span>
                        <span class="status-badge status-{{ $article->status }}" style="margin-left: 0.5rem;">{{ ucfirst($article->status) }}</span>
                    </div>
                    <h1 style="font-size: 2.5rem; color: #ffffff; margin-bottom: 1rem; line-height: 1.2;">
                        {{ $article->title }}
                    </h1>
                    <div style="display: flex; gap: 1.5rem; color: #9ca3af; font-size: 0.9rem; flex-wrap: wrap;">
                        <div>👁️ {{ number_format($article->views) }} views</div>
                        <div>👍 {{ $article->helpfulness_percentage }}% helpful</div>
                        <div>✏️ By {{ $article->author->full_name }}</div>
                        <div>📅 Updated {{ $article->updated_at->diffForHumans() }}</div>
                    </div>
                </div>

                <!-- Article Content -->
                <div class="card" style="margin-top: 1.5rem;">
                    @if($article->excerpt)
                        <div style="background: rgba(45, 212, 191, 0.1); padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; border-left: 4px solid #2dd4bf;">
                            <strong style="color: #2dd4bf;">Excerpt:</strong>
                            <p style="color: #d1d5db; margin-top: 0.5rem;">{{ $article->excerpt }}</p>
                        </div>
                    @endif
                    <div style="color: #d1d5db; line-height: 1.8; font-size: 1.05rem;">
                        {!! nl2br(e($article->content)) !!}
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div>
                <!-- Article Stats -->
                <div class="info-card">
                    <h2 class="card-title">Article Statistics</h2>
                    <div class="info-list">
                        <div class="info-item">
                            <span class="info-label">Status</span>
                            <span class="info-value">
                                <span class="status-badge status-{{ $article->status }}">{{ ucfirst($article->status) }}</span>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Category</span>
                            <span class="info-value">{{ ucfirst($article->category) }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Total Views</span>
                            <span class="info-value">{{ number_format($article->views) }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Helpful Votes</span>
                            <span class="info-value">{{ $article->helpful_count }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Not Helpful Votes</span>
                            <span class="info-value">{{ $article->not_helpful_count }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Helpfulness</span>
                            <span class="info-value">{{ $article->helpfulness_percentage }}%</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Created</span>
                            <span class="info-value">{{ $article->created_at->format('M d, Y') }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Last Updated</span>
                            <span class="info-value">{{ $article->updated_at->diffForHumans() }}</span>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="info-card" style="margin-top: 1.5rem;">
                    <h2 class="card-title">Actions</h2>
                    <div class="action-buttons">
                        <a href="{{ route('it.knowledge-base.edit', $article->id) }}" class="btn btn-primary">Edit Article</a>
                        @if($article->status === 'draft')
                            <form action="{{ route('it.knowledge-base.update', $article->id) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="title" value="{{ $article->title }}">
                                <input type="hidden" name="content" value="{{ $article->content }}">
                                <input type="hidden" name="category" value="{{ $article->category }}">
                                <input type="hidden" name="status" value="published">
                                <button type="submit" class="btn btn-secondary">Publish Now</button>
                            </form>
                        @endif
                        <form action="{{ route('it.knowledge-base.destroy', $article->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this article?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">Delete Article</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection