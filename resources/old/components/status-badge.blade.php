{{-- 
    Status Badge Component
    File: resources/views/components/status-badge.blade.php
    
    Usage:
    <x-status-badge status="new" />
    <x-status-badge status="in progress" />
    <x-status-badge status="resolved" />
    <x-status-badge status="active" />
--}}

@props(['status'])

@php
    // Normalize status to lowercase and remove spaces for CSS class
    $statusClass = strtolower(str_replace(' ', '-', $status));
    
    // Map status to CSS classes
    $statusMap = [
        'new' => 'status-new',
        'assigned' => 'status-assigned',
        'in-progress' => 'status-progress',
        'progress' => 'status-progress',
        'resolved' => 'status-resolved',
        'active' => 'status-active',
        'inactive' => 'status-inactive',
        'published' => 'status-published',
        'draft' => 'status-draft',
        'good' => 'status-good',
        'warning' => 'status-warning',
    ];
    
    $badgeClass = $statusMap[$statusClass] ?? 'status-' . $statusClass;
@endphp

<span class="status-badge {{ $badgeClass }}">
    {{ $status }}
</span>