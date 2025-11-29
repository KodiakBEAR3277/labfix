@extends('layouts.app')

@section('title', 'System Settings')

@section('navigation')
    <x-nav.admin />
@endsection

@section('content')
    <div class="container">
        <div class="page-header">
            <h1>System Settings</h1>
            <p>Configure system-wide settings and preferences</p>
        </div>

        <div class="settings-grid">
            <!-- General Settings -->
            <div class="settings-card">
                <div class="card-header">
                    <div class="card-icon">⚙️</div>
                    <h2 class="card-title">General Settings</h2>
                </div>
                
                <div class="form-group">
                    <label>System Name</label>
                    <input type="text" value="LabFix - Computer Lab Management">
                    <p class="help-text">This will be displayed throughout the application</p>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Default Language</label>
                        <select>
                            <option>English</option>
                            <option>Filipino</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Timezone</label>
                        <select>
                            <option>Asia/Manila (PHT)</option>
                            <option>UTC</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Support Email</label>
                    <input type="email" value="support@labfix.edu">
                    <p class="help-text">Email address for system notifications and support</p>
                </div>
            </div>

            <!-- Notification Settings -->
            <div class="settings-card">
                <div class="card-header">
                    <div class="card-icon">🔔</div>
                    <h2 class="card-title">Notification Settings</h2>
                </div>

                <div class="toggle-item">
                    <div class="toggle-info">
                        <h4>Email Notifications</h4>
                        <p>Send email alerts for new tickets and updates</p>
                    </div>
                    <div class="toggle-switch active" onclick="this.classList.toggle('active')">
                        <div class="toggle-slider"></div>
                    </div>
                </div>

                <div class="toggle-item">
                    <div class="toggle-info">
                        <h4>Notify Users on Status Change</h4>
                        <p>Automatically notify users when ticket status changes</p>
                    </div>
                    <div class="toggle-switch active" onclick="this.classList.toggle('active')">
                        <div class="toggle-slider"></div>
                    </div>
                </div>

                <div class="toggle-item">
                    <div class="toggle-info">
                        <h4>Notify IT on New Tickets</h4>
                        <p>Alert IT support team when new tickets are submitted</p>
                    </div>
                    <div class="toggle-switch active" onclick="this.classList.toggle('active')">
                        <div class="toggle-slider"></div>
                    </div>
                </div>

                <div class="toggle-item">
                    <div class="toggle-info">
                        <h4>Daily Summary Reports</h4>
                        <p>Send daily summary to administrators</p>
                    </div>
                    <div class="toggle-switch" onclick="this.classList.toggle('active')">
                        <div class="toggle-slider"></div>
                    </div>
                </div>
            </div>

            <!-- Ticket Settings -->
            <div class="settings-card">
                <div class="card-header">
                    <div class="card-icon">📋</div>
                    <h2 class="card-title">Ticket Settings</h2>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Auto-Close Resolved Tickets After</label>
                        <select>
                            <option>Never</option>
                            <option>24 hours</option>
                            <option>3 days</option>
                            <option selected>7 days</option>
                            <option>14 days</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Default Priority</label>
                        <select>
                            <option>Low</option>
                            <option selected>Medium</option>
                            <option>High</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Ticket ID Format</label>
                    <input type="text" value="TKT-{YEAR}-{NUMBER}" disabled>
                    <p class="help-text">Example: TKT-2025-001</p>
                </div>

                <div class="toggle-item">
                    <div class="toggle-info">
                        <h4>Allow Attachments</h4>
                        <p>Users can upload screenshots and files</p>
                    </div>
                    <div class="toggle-switch active" onclick="this.classList.toggle('active')">
                        <div class="toggle-slider"></div>
                    </div>
                </div>
            </div>

            <!-- Priority Rules -->
            <div class="settings-card">
                <div class="card-header">
                    <div class="card-icon">⚠️</div>
                    <h2 class="card-title">Priority Rules</h2>
                </div>

                <div class="priority-rules">
                    <div class="priority-rule">
                        <div class="priority-icon">🔴</div>
                        <div class="priority-info">
                            <h4>High Priority</h4>
                            <p>Critical issues affecting multiple users or lab operations</p>
                        </div>
                    </div>

                    <div class="priority-rule">
                        <div class="priority-icon">🟡</div>
                        <div class="priority-info">
                            <h4>Medium Priority</h4>
                            <p>Standard issues affecting single workstation functionality</p>
                        </div>
                    </div>

                    <div class="priority-rule">
                        <div class="priority-icon">🟢</div>
                        <div class="priority-info">
                            <h4>Low Priority</h4>
                            <p>Minor issues or enhancement requests</p>
                        </div>
                    </div>
                </div>

                <div class="form-group" style="margin-top: 1.5rem;">
                    <label>Auto-Escalate After</label>
                    <select>
                        <option>Never</option>
                        <option>4 hours</option>
                        <option selected>8 hours</option>
                        <option>24 hours</option>
                    </select>
                    <p class="help-text">Automatically increase priority if ticket is unresolved</p>
                </div>
            </div>

            <!-- Maintenance Mode -->
            <div class="settings-card">
                <div class="card-header">
                    <div class="card-icon">🔧</div>
                    <h2 class="card-title">Maintenance Mode</h2>
                </div>

                <div class="toggle-item">
                    <div class="toggle-info">
                        <h4>Enable Maintenance Mode</h4>
                        <p>Temporarily disable ticket submissions for system maintenance</p>
                    </div>
                    <div class="toggle-switch" onclick="this.classList.toggle('active')">
                        <div class="toggle-slider"></div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Maintenance Message</label>
                    <textarea placeholder="The system is currently undergoing maintenance. Please try again later.">The system is currently undergoing maintenance. Please try again later.</textarea>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <button class="btn btn-primary">Save All Changes</button>
            <button class="btn btn-secondary">Reset to Defaults</button>
        </div>
    </div>
@endsection