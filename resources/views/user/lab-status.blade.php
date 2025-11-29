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

        <!-- Overview Stats -->
        <div class="overview-stats">
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-label">Total Equipment</div>
                    <div class="stat-icon">💻</div>
                </div>
                <div class="stat-value">120</div>
                <div class="stat-progress">
                    <div class="stat-progress-bar" style="width: 100%"></div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-label">Operational</div>
                    <div class="stat-icon">✅</div>
                </div>
                <div class="stat-value">102</div>
                <div class="stat-progress">
                    <div class="stat-progress-bar" style="width: 85%"></div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-label">Under Repair</div>
                    <div class="stat-icon">🔧</div>
                </div>
                <div class="stat-value">15</div>
                <div class="stat-progress">
                    <div class="stat-progress-bar" style="width: 12.5%; background: linear-gradient(90deg, #ef4444, #dc2626);"></div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-label">Maintenance</div>
                    <div class="stat-icon">⚙️</div>
                </div>
                <div class="stat-value">3</div>
                <div class="stat-progress">
                    <div class="stat-progress-bar" style="width: 2.5%; background: linear-gradient(90deg, #f59e0b, #d97706);"></div>
                </div>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="filter-bar">
            <span class="filter-label">Filter Labs:</span>
            <button class="filter-btn active">All Labs</button>
            <button class="filter-btn">Available Only</button>
            <button class="filter-btn">With Issues</button>
        </div>

        <!-- Computer Lab A -->
        <div class="lab-card">
            <div class="lab-header">
                <div>
                    <h2 class="lab-title">Computer Lab A</h2>
                </div>
                <div class="lab-info">
                    <div class="lab-capacity">
                        <span>Capacity: 18/20</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <div class="status-indicator status-operational"></div>
                        <span style="color: #10b981; font-weight: 600;">Operational</span>
                    </div>
                </div>
            </div>

            <div class="equipment-grid">
                @for($i = 1; $i <= 20; $i++)
                    <div class="equipment-item equipment-{{ in_array($i, [3, 12]) ? 'issue' : 'working' }}">
                        <div class="tooltip">PC-{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}: {{ in_array($i, [3, 12]) ? 'Hardware Issue' : 'Working' }}</div>
                        <div class="equipment-icon">💻</div>
                        <div class="equipment-id">{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}</div>
                    </div>
                @endfor
            </div>

            <div class="legend">
                <div class="legend-item">
                    <div class="legend-color color-working"></div>
                    <span>Working (18)</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color color-issue"></div>
                    <span>Has Issues (2)</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color color-maintenance"></div>
                    <span>Maintenance (0)</span>
                </div>
            </div>
        </div>

        <!-- Computer Lab B -->
        <div class="lab-card">
            <div class="lab-header">
                <div>
                    <h2 class="lab-title">Computer Lab B</h2>
                </div>
                <div class="lab-info">
                    <div class="lab-capacity">
                        <span>Capacity: 22/25</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <div class="status-indicator status-limited"></div>
                        <span style="color: #f59e0b; font-weight: 600;">Limited Availability</span>
                    </div>
                </div>
            </div>

            <div class="equipment-grid">
                @for($i = 1; $i <= 25; $i++)
                    <div class="equipment-item equipment-{{ in_array($i, [5, 13]) ? 'issue' : (in_array($i, [7]) ? 'maintenance' : 'working') }}">
                        <div class="tooltip">PC-{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}: {{ in_array($i, [5, 13]) ? 'Issue' : (in_array($i, [7]) ? 'Scheduled Maintenance' : 'Working') }}</div>
                        <div class="equipment-icon">💻</div>
                        <div class="equipment-id">{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}</div>
                    </div>
                @endfor
            </div>

            <div class="legend">
                <div class="legend-item">
                    <div class="legend-color color-working"></div>
                    <span>Working (22)</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color color-issue"></div>
                    <span>Has Issues (2)</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color color-maintenance"></div>
                    <span>Maintenance (1)</span>
                </div>
            </div>
        </div>
    </div>
@endsection