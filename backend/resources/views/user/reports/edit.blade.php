@extends('layouts.app')

@section('title', 'Edit Report')

@section('navigation')
    <x-nav.user />
@endsection

@section('content')
    <div class="container">
        <a href="{{ route('user.reports.show', $report->id) }}" class="back-btn">← Back to Report</a>

        <div class="page-header">
            <h1>Edit Report</h1>
            <p>Update your report details before it's assigned to a technician</p>
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

        <div class="card" style="max-width: 800px; margin: 0 auto;">
            <form action="{{ route('user.reports.update', $report->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="form-section">
                    <h3 class="section-title">Report Details</h3>
                    
                    <div class="form-group">
                        <label>Issue Title *</label>
                        <input type="text" name="title" value="{{ old('title', $report->title) }}" required>
                    </div>

                    <div class="form-group">
                        <label>Category *</label>
                        <select name="category" required>
                            <option value="hardware" {{ old('category', $report->category) == 'hardware' ? 'selected' : '' }}>Hardware</option>
                            <option value="software" {{ old('category', $report->category) == 'software' ? 'selected' : '' }}>Software</option>
                            <option value="network" {{ old('category', $report->category) == 'network' ? 'selected' : '' }}>Network</option>
                            <option value="other" {{ old('category', $report->category) == 'other' ? 'selected' : '' }}>Other</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Description *</label>
                        <textarea name="description" rows="8" required>{{ old('description', $report->description) }}</textarea>
                        <p class="help-text">Minimum 10 characters. Be as detailed as possible.</p>
                    </div>

                    <div class="form-group">
                        <label>Current Attachments</label>
                        @if($report->attachments && count($report->attachments) > 0)
                            <div style="display: flex; flex-wrap: wrap; gap: 1rem; margin-bottom: 1rem;">
                                @foreach($report->attachments as $attachment)
                                    <div style="background: rgba(45, 212, 191, 0.1); padding: 0.5rem 1rem; border-radius: 6px; font-size: 0.9rem; color: #2dd4bf;">
                                        📎 {{ basename($attachment) }}
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p style="color: #9ca3af; font-size: 0.9rem;">No attachments</p>
                        @endif
                    </div>

                    <div class="form-group">
                        <label>Add New Attachments (Optional)</label>
                        <input type="file" name="attachments[]" accept="image/*,application/pdf" multiple>
                        <p class="help-text">PNG, JPG, PDF up to 5MB each. New files will be added to existing attachments.</p>
                    </div>
                </div>

                <div class="action-buttons">
                    <button type="submit" class="btn btn-primary">Update Report</button>
                    <a href="{{ route('user.reports.show', $report->id) }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>

        <!-- Info Card -->
        <div class="card" style="max-width: 800px; margin: 1.5rem auto; background: rgba(59, 130, 246, 0.1); border-color: rgba(59, 130, 246, 0.3);">
            <h3 style="color: #3b82f6; margin-bottom: 0.5rem;">ℹ️ Note</h3>
            <p style="color: #d1d5db; font-size: 0.9rem; line-height: 1.6; margin: 0;">
                You can only edit this report before it's assigned to a technician. Once assigned, please contact the assigned technician if you need to make changes.
            </p>
        </div>
    </div>
@endsection