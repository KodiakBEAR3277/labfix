@extends('layouts.app')

@section('title', 'Lab Status')

@section('navigation')
    <x-nav.user />
@endsection

@section('content')
    <div class="container">
        <div class="page-header">
            <h1>Lab Status Overview</h1>
            <p>Real-time equipment status and lab availability</p>
        </div>

        @php
            $labs = \App\Models\Lab::where('is_active', true)->with('equipment')->get();
            $totalEquipment = $labs->sum(fn($lab) => $lab->equipment->count());
            $totalOperational = $labs->sum(fn($lab) => $lab->operational_count);
            $totalIssues = $labs->sum(fn($lab) => $lab->issues_count);
            $totalMaintenance = $labs->sum(fn($lab) => $lab->maintenance_count);
        @endphp

        <!-- Overview Stats -->
        <div class="overview-stats">
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-label">Total Equipment</div>
                    <div class="stat-icon">💻</div>
                </div>
                <div class="stat-value">{{ $totalEquipment }}</div>
                <div class="stat-progress">
                    <div class="stat-progress-bar" style="width: 100%"></div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-label">Operational</div>
                    <div class="stat-icon">✅</div>
                </div>
                <div class="stat-value">{{ $totalOperational }}</div>
                <div class="stat-progress">
                    <div class="stat-progress-bar" style="width: {{ $totalEquipment > 0 ? ($totalOperational / $totalEquipment * 100) : 0 }}%"></div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-label">Has Issues</div>
                    <div class="stat-icon">🔧</div>
                </div>
                <div class="stat-value">{{ $totalIssues }}</div>
                <div class="stat-progress">
                    <div class="stat-progress-bar" style="width: {{ $totalEquipment > 0 ? ($totalIssues / $totalEquipment * 100) : 0 }}%; background: linear-gradient(90deg, #ef4444, #dc2626);"></div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-label">Maintenance</div>
                    <div class="stat-icon">⚙️</div>
                </div>
                <div class="stat-value">{{ $totalMaintenance }}</div>
                <div class="stat-progress">
                    <div class="stat-progress-bar" style="width: {{ $totalEquipment > 0 ? ($totalMaintenance / $totalEquipment * 100) : 0 }}%; background: linear-gradient(90deg, #f59e0b, #d97706);"></div>
                </div>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="filter-bar">
            <span class="filter-label">Filter Labs:</span>
            <button class="filter-btn active" onclick="filterLabs('all')">All Labs</button>
            <button class="filter-btn" onclick="filterLabs('operational')">Available Only</button>
            <button class="filter-btn" onclick="filterLabs('issues')">With Issues</button>
        </div>

        <!-- Labs -->
        @forelse($labs as $lab)
            @php
                $operationalPercent = $lab->equipment->count() > 0 
                    ? ($lab->operational_count / $lab->equipment->count() * 100) 
                    : 0;
                $isOperational = $operationalPercent >= 50;
            @endphp
            <div class="lab-card" data-operational="{{ $isOperational ? 'true' : 'false' }}" data-issues="{{ $lab->issues_count > 0 ? 'true' : 'false' }}">
                <div class="lab-header">
                    <div>
                        <h2 class="lab-title">{{ $lab->name }}</h2>
                        @if($lab->location)
                            <p style="color: #9ca3af; font-size: 0.9rem; margin-top: 0.25rem;">{{ $lab->location }}</p>
                        @endif
                    </div>
                    <div class="lab-info">
                        <div class="lab-capacity">
                            <span>Capacity: {{ $lab->operational_count }}/{{ $lab->capacity }}</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <div class="status-indicator {{ $isOperational ? 'status-operational' : 'status-limited' }}"></div>
                            <span style="color: {{ $isOperational ? '#10b981' : '#f59e0b' }}; font-weight: 600;">
                                {{ $isOperational ? 'Operational' : 'Limited Availability' }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="equipment-grid">
                    @forelse($lab->equipment as $equipment)
                        @php
                            $statusClass = match($equipment->status) {
                                'operational' => 'equipment-working',
                                'has-issue' => 'equipment-issue',
                                'maintenance' => 'equipment-maintenance',
                                default => 'equipment-working'
                            };
                            $statusText = match($equipment->status) {
                                'operational' => 'Working',
                                'has-issue' => 'Has Issue',
                                'maintenance' => 'Under Maintenance',
                                'retired' => 'Retired',
                                default => 'Unknown'
                            };
                            $icon = match($equipment->type) {
                                'computer' => '💻',
                                'printer' => '🖨️',
                                'projector' => '📽️',
                                default => '🖥️'
                            };
                        @endphp
                        <div class="equipment-item {{ $statusClass }}">
                            <div class="tooltip">{{ $equipment->equipment_code }}: {{ $statusText }}</div>
                            <div class="equipment-icon">{{ $icon }}</div>
                            <div class="equipment-id">{{ $equipment->equipment_code }}</div>
                        </div>
                    @empty
                        <div style="grid-column: 1/-1; text-align: center; padding: 2rem; color: #9ca3af;">
                            No equipment registered for this lab
                        </div>
                    @endforelse
                </div>

                <div class="legend">
                    <div class="legend-item">
                        <div class="legend-color color-working"></div>
                        <span>Working ({{ $lab->operational_count }})</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color color-issue"></div>
                        <span>Has Issues ({{ $lab->issues_count }})</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color color-maintenance"></div>
                        <span>Maintenance ({{ $lab->maintenance_count }})</span>
                    </div>
                </div>
            </div>
        @empty
            <div class="empty-state">
                <div class="empty-icon">🏫</div>
                <h3>No Labs Available</h3>
                <p>No active labs are currently configured in the system</p>
            </div>
        @endforelse
    </div>
@endsection

@push('scripts')
<script>
function filterLabs(filter) {
    const labs = document.querySelectorAll('.lab-card');
    const buttons = document.querySelectorAll('.filter-btn');
    
    // Update active button
    buttons.forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');
    
    // Filter labs
    labs.forEach(lab => {
        if (filter === 'all') {
            lab.style.display = 'block';
        } else if (filter === 'operational') {
            lab.style.display = lab.dataset.operational === 'true' ? 'block' : 'none';
        } else if (filter === 'issues') {
            lab.style.display = lab.dataset.issues === 'true' ? 'block' : 'none';
        }
    });
}
</script>
@endpush