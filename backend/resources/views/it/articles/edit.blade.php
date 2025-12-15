@extends('layouts.app')

@section('title', 'Edit Article')

@section('navigation')
    @include('components.nav.it')
@endsection

@section('content')
    <div class="container">
        <a href="{{ route('it.knowledge-base.index') }}" class="back-btn">← Back to Knowledge Base</a>

        <div class="page-header">
            <h1>Edit Article</h1>
            <p>Update your knowledge base article</p>
        </div>

        @if($errors->any())
            <div class="alert alert-danger" style="background: rgba(239, 68, 68, 0.2); border: 1px solid rgba(239, 68, 68, 0.3); padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; color: #ef4444;">
                <ul style="margin: 0; padding-left: 1.5rem;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card" style="max-width: 900px; margin: 0 auto;">
            <form action="{{ route('it.knowledge-base.update', $article->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="form-section">
                    <h3 class="section-title">Article Details</h3>
                    
                    <div class="form-group">
                        <label>Title *</label>
                        <input type="text" name="title" value="{{ old('title', $article->title) }}" required>
                    </div>

                    <div class="form-group">
                        <label>Category *</label>
                        <select name="category" required>
                            <option value="">Select a category</option>
                            <option value="hardware" {{ old('category', $article->category) == 'hardware' ? 'selected' : '' }}>Hardware</option>
                            <option value="software" {{ old('category', $article->category) == 'software' ? 'selected' : '' }}>Software</option>
                            <option value="network" {{ old('category', $article->category) == 'network' ? 'selected' : '' }}>Network</option>
                            <option value="display" {{ old('category', $article->category) == 'display' ? 'selected' : '' }}>Display</option>
                            <option value="peripherals" {{ old('category', $article->category) == 'peripherals' ? 'selected' : '' }}>Peripherals</option>
                            <option value="general" {{ old('category', $article->category) == 'general' ? 'selected' : '' }}>General Help</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Excerpt (Optional)</label>
                        <textarea name="excerpt" rows="3">{{ old('excerpt', $article->excerpt) }}</textarea>
                    </div>

                    <div class="form-group">
                        <label>Content *</label>
                        <textarea name="content" rows="15" required>{{ old('content', $article->content) }}</textarea>
                    </div>

                    <div class="form-group">
                        <label>Status *</label>
                        <select name="status" required>
                            <option value="draft" {{ old('status', $article->status) == 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="published" {{ old('status', $article->status) == 'published' ? 'selected' : '' }}>Published</option>
                        </select>
                    </div>
                </div>

                <div class="action-buttons">
                    <button type="submit" class="btn btn-primary">Update Article</button>
                    <a href="{{ route('it.knowledge-base.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection