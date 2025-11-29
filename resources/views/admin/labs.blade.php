@extends('layouts.app')

@section('title', 'System Settings')

@section('navigation')
    <x-nav.admin />
@endsection

@section('content')
    <div class="container">
        <div class="page-header">
            <div>
                <h1>Lab Configuration</h1>
                <p style="color: #9ca3af;">Manage lab layouts and equipment assignments</p>
            </div>
            <button class="btn-primary">+ Add New Lab</button>
        </div>

        <!-- Stats Row -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-label">Total Labs</div>
                <div class="stat-value">6</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Total Equipment</div>
                <div class="stat-value">120</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Operational</div>
                <div class="stat-value">102</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Under Maintenance</div>
                <div class="stat-value">18</div>
            </div>
        </div>

        <!-- Labs Grid -->
        <div class="labs-grid">
            <!-- Computer Lab A -->
            <div class="lab-card">
                <div class="lab-header">
                    <div class="lab-title-section">
                        <div class="lab-icon">💻</div>
                        <h2 class="lab-title">Computer Lab A</h2>
                    </div>
                    <div class="lab-actions">
                        <button class="action-btn">Edit</button>
                        <button class="action-btn">View Status</button>
                    </div>
                </div>

                <div class="lab-info-grid">
                    <div class="info-item">
                        <div class="info-label">Location</div>
                        <div class="info-value">Building 1, 2nd Floor</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Capacity</div>
                        <div class="info-value">20 Workstations</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Operational</div>
                        <div class="info-value" style="color: #34d399;">18 / 20</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Lab Manager</div>
                        <div class="info-value">Prof. Martinez</div>
                    </div>
                </div>

                <div class="equipment-section">
                    <div class="equipment-header">
                        <h3 class="section-title">Equipment Layout</h3>
                        <button class="btn-add">+ Add Equipment</button>
                    </div>
                    <div class="equipment-grid">
                        <div class="equipment-item">
                            <div class="equipment-icon">💻</div>
                            <div class="equipment-id">PC-01</div>
                        </div>
                        <div class="equipment-item">
                            <div class="equipment-icon">💻</div>
                            <div class="equipment-id">PC-02</div>
                        </div>
                        <div class="equipment-item">
                            <div class="equipment-icon">💻</div>
                            <div class="equipment-id">PC-03</div>
                        </div>
                        <div class="equipment-item">
                            <div class="equipment-icon">💻</div>
                            <div class="equipment-id">PC-04</div>
                        </div>
                        <div class="equipment-item">
                            <div class="equipment-icon">💻</div>
                            <div class="equipment-id">PC-05</div>
                        </div>
                        <div class="equipment-item">
                            <div class="equipment-icon">💻</div>
                            <div class="equipment-id">PC-06</div>
                        </div>
                        <div class="equipment-item">
                            <div class="equipment-icon">💻</div>
                            <div class="equipment-id">PC-07</div>
                        </div>
                        <div class="equipment-item">
                            <div class="equipment-icon">💻</div>
                            <div class="equipment-id">PC-08</div>
                        </div>
                        <div class="equipment-item">
                            <div class="equipment-icon">💻</div>
                            <div class="equipment-id">PC-09</div>
                        </div>
                        <div class="equipment-item">
                            <div class="equipment-icon">💻</div>
                            <div class="equipment-id">PC-10</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Computer Lab B -->
            <div class="lab-card">
                <div class="lab-header">
                    <div class="lab-title-section">
                        <div class="lab-icon">💻</div>
                        <h2 class="lab-title">Computer Lab B</h2>
                    </div>
                    <div class="lab-actions">
                        <button class="action-btn">Edit</button>
                        <button class="action-btn">View Status</button>
                    </div>
                </div>

                <div class="lab-info-grid">
                    <div class="info-item">
                        <div class="info-label">Location</div>
                        <div class="info-value">Building 2, 1st Floor</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Capacity</div>
                        <div class="info-value">25 Workstations</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Operational</div>
                        <div class="info-value" style="color: #fbbf24;">22 / 25</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Lab Manager</div>
                        <div class="info-value">Prof. Chen</div>
                    </div>
                </div>

                <div class="equipment-section">
                    <div class="equipment-header">
                        <h3 class="section-title">Equipment Layout</h3>
                        <button class="btn-add">+ Add Equipment</button>
                    </div>
                    <div class="equipment-grid">
                        <div class="equipment-item">
                            <div class="equipment-icon">💻</div>
                            <div class="equipment-id">PC-01</div>
                        </div>
                        <div class="equipment-item">
                            <div class="equipment-icon">💻</div>
                            <div class="equipment-id">PC-02</div>
                        </div>
                        <div class="equipment-item">
                            <div class="equipment-icon">💻</div>
                            <div class="equipment-id">PC-03</div>
                        </div>
                        <div class="equipment-item">
                            <div class="equipment-icon">💻</div>
                            <div class="equipment-id">PC-04</div>
                        </div>
                        <div class="equipment-item">
                            <div class="equipment-icon">💻</div>
                            <div class="equipment-id">PC-05</div>
                        </div>
                        <div class="equipment-item">
                            <div class="equipment-icon">💻</div>
                            <div class="equipment-id">PC-06</div>
                        </div>
                        <div class="equipment-item">
                            <div class="equipment-icon">💻</div>
                            <div class="equipment-id">PC-07</div>
                        </div>
                        <div class="equipment-item">
                            <div class="equipment-icon">💻</div>
                            <div class="equipment-id">PC-08</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Lab Modal -->
    <div class="modal-overlay">
        <div class="modal">
            <h2 class="modal-header">Add New Lab</h2>
            <form>
                <div class="form-group">
                    <label>Lab Name</label>
                    <input type="text" placeholder="e.g., Computer Lab A">
                </div>
                <div class="form-group">
                    <label>Location</label>
                    <input type="text" placeholder="e.g., Building 1, 2nd Floor">
                </div>
                <div class="form-group">
                    <label>Capacity</label>
                    <input type="number" placeholder="Number of workstations">
                </div>
                <div class="form-group">
                    <label>Lab Manager</label>
                    <input type="text" placeholder="Name of lab manager">
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-save">Save Lab</button>
                    <button type="button" class="btn btn-cancel">Cancel</button>
                </div>
            </form>
        </div>
    </div>
@endsection