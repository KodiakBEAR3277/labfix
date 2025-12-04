@extends('layouts.app')

@section('title', 'Create Article')

@section('navigation')
    @include('components.nav.it')
@endsection

@section('content')
    <div class="container">
        <a href="{{ route('it.knowledge-base.index') }}" class="back-btn">← Back to Knowledge Base</a>

        <div class="page-header">
            <h1>Create New Article</h1>
            <p>Write a new troubleshooting guide or knowledge base article</p>
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
            <form action="{{ route('it.knowledge-base.store') }}" method="POST">
                @csrf

                <div class="form-section">
                    <h3 class="section-title">Article Details</h3>
                    
                    <div class="form-group">
                        <label>Title *</label>
                        <input type="text" name="title" value="{{ old('title') }}" placeholder="e.g., Computer won't turn on - Troubleshooting steps" required>
                        <p class="help-text">Choose a clear, descriptive title that users can easily search for</p>
                    </div>

                    <div class="form-group">
                        <label>Category *</label>
                        <select name="category" required>
                            <option value="">Select a category</option>
                            <option value="hardware" {{ old('category') == 'hardware' ? 'selected' : '' }}>Hardware</option>
                            <option value="software" {{ old('category') == 'software' ? 'selected' : '' }}>Software</option>
                            <option value="network" {{ old('category') == 'network' ? 'selected' : '' }}>Network</option>
                            <option value="display" {{ old('category') == 'display' ? 'selected' : '' }}>Display</option>
                            <option value="peripherals" {{ old('category') == 'peripherals' ? 'selected' : '' }}>Peripherals</option>
                            <option value="general" {{ old('category') == 'general' ? 'selected' : '' }}>General Help</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Excerpt (Optional)</label>
                        <textarea name="excerpt" rows="3" placeholder="Brief summary of the article (max 500 characters)">{{ old('excerpt') }}</textarea>
                        <p class="help-text">If left empty, we'll automatically generate one from your content</p>
                    </div>

                    <div class="form-group">
                        <label>Content *</label>
                        <textarea name="content" rows="15" placeholder="Write your article content here... Include step-by-step instructions, troubleshooting tips, and solutions." required>{{ old('content') }}</textarea>
                        <p class="help-text">Minimum 50 characters. Use clear language and provide detailed steps.</p>
                    </div>

                    <div class="form-group">
                        <label>Status *</label>
                        <select name="status" required>
                            <option value="draft" {{ old('status') == 'draft' ? 'selected' : '' }}>Save as Draft</option>
                            <option value="published" {{ old('status') == 'published' ? 'selected' : '' }}>Publish Immediately</option>
                        </select>
                        <p class="help-text">Drafts are only visible to IT staff. Published articles are visible to all users.</p>
                    </div>
                </div>

                <div class="action-buttons">
                    <button type="submit" class="btn btn-primary">Create Article</button>
                    <a href="{{ route('it.knowledge-base.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection