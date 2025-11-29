@extends('layouts.app')

@section('title', 'IT Ticket Management')

@section('navigation')
    @include('components.nav.it')
@endsection

@section('content')
    <div id="it-ticket-detail" class="container">
        <a href="{{ route('it.tickets.index') }}" class="back-btn">← Back to Queue</a>

        <!-- Ticket Header -->
        <div class="ticket-header">
            <div class="header-top">
                <div>
                    <h1 class="ticket-title">Network connectivity issue in Lab C</h1>
                    <div class="ticket-id">Ticket #089</div>
                </div>
                <div class="priority-badge priority-high">High Priority</div>
            </div>

            <div class="ticket-meta-grid">
                <div class="meta-item">
                    <div class="meta-label">Reporter</div>
                    <div class="meta-value">Alice Brown</div>
                </div>
                <div class="meta-item">
                    <div class="meta-label">Location</div>
                    <div class="meta-value">Computer Lab C, All PCs</div>
                </div>
                <div class="meta-item">
                    <div class="meta-label">Category</div>
                    <div class="meta-value">Network</div>
                </div>
                <div class="meta-item">
                    <div class="meta-label">Status</div>
                    <div class="meta-value">In Progress</div>
                </div>
                <div class="meta-item">
                    <div class="meta-label">Submitted</div>
                    <div class="meta-value">Oct 2, 2025 - 1:30 PM</div>
                </div>
                <div class="meta-item">
                    <div class="meta-label">Last Updated</div>
                    <div class="meta-value">30 minutes ago</div>
                </div>
            </div>
        </div>

        <div class="content-layout">
            <!-- Main Content -->
            <div class="content-section">
                <!-- Description -->
                <div class="card">
                    <h2 class="card-title">Problem Description</h2>
                    <p class="description-text">
                        All computers in Lab C are unable to connect to the internet. The network switch appears to be non-responsive. Students are unable to complete their online assignments and class activities. This is affecting approximately 25 workstations. Network cables appear to be properly connected. The issue started suddenly during the morning session.
                    </p>
                </div>

                <!-- Internal Notes -->
                <div class="card">
                    <h2 class="card-title">Internal Notes (IT Team Only)</h2>
                    <div class="notes-list">
                        <div class="note-item">
                            <div class="note-header">
                                <span class="note-author">Mike Chen</span>
                                <span class="note-time">15 minutes ago</span>
                            </div>
                            <p class="note-text">
                                Checked network switch - found power issue. Ordered replacement unit. ETA 2 hours. Setting up temporary wireless access as workaround.
                            </p>
                        </div>
                        <div class="note-item">
                            <div class="note-header">
                                <span class="note-author">Sarah Lee</span>
                                <span class="note-time">45 minutes ago</span>
                            </div>
                            <p class="note-text">
                                Initial assessment complete. Switch is not responding to ping. Will need physical inspection.
                            </p>
                        </div>
                    </div>
                    <textarea class="note-input" placeholder="Add internal note for your team..."></textarea>
                    <button class="btn btn-secondary">Add Note</button>
                </div>

                <!-- Activity Timeline -->
                <div class="card">
                    <h2 class="card-title">Activity Timeline</h2>
                    <div class="timeline">
                        <div class="timeline-item">
                            <div class="timeline-dot"></div>
                            <div class="timeline-content">
                                <div class="timeline-header">
                                    <span class="timeline-title">Status Updated</span>
                                    <span class="timeline-time">15 min ago</span>
                                </div>
                                <p class="timeline-text">Mike Chen updated status to "In Progress"</p>
                            </div>
                        </div>

                        <div class="timeline-item">
                            <div class="timeline-dot"></div>
                            <div class="timeline-content">
                                <div class="timeline-header">
                                    <span class="timeline-title">Ticket Assigned</span>
                                    <span class="timeline-time">30 min ago</span>
                                </div>
                                <p class="timeline-text">Assigned to Mike Chen by System Admin</p>
                            </div>
                        </div>

                        <div class="timeline-item">
                            <div class="timeline-dot"></div>
                            <div class="timeline-content">
                                <div class="timeline-header">
                                    <span class="timeline-title">Priority Changed</span>
                                    <span class="timeline-time">45 min ago</span>
                                </div>
                                <p class="timeline-text">Priority elevated to High (affects multiple users)</p>
                            </div>
                        </div>

                        <div class="timeline-item">
                            <div class="timeline-dot"></div>
                            <div class="timeline-content">
                                <div class="timeline-header">
                                    <span class="timeline-title">Ticket Created</span>
                                    <span class="timeline-time">1 hour ago</span>
                                </div>
                                <p class="timeline-text">Report submitted by Alice Brown</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="sidebar">
                <!-- Ticket Management -->
                <div class="card">
                    <h2 class="card-title">Ticket Management</h2>
                    
                    <div class="control-group">
                        <label class="control-label">Status</label>
                        <select>
                            <option>New</option>
                            <option>Assigned</option>
                            <option selected>In Progress</option>
                            <option>Waiting for Parts</option>
                            <option>Resolved</option>
                            <option>Closed</option>
                        </select>
                    </div>

                    <div class="control-group">
                        <label class="control-label">Priority</label>
                        <select>
                            <option>Low</option>
                            <option>Medium</option>
                            <option selected>High</option>
                            <option>Critical</option>
                        </select>
                    </div>

                    <div class="control-group">
                        <label class="control-label">Assign To</label>
                        <select>
                            <option selected>Mike Chen</option>
                            <option>Sarah Lee</option>
                            <option>Tom Anderson</option>
                            <option>Unassigned</option>
                        </select>
                    </div>

                    <div class="control-group">
                        <label class="control-label">Estimated Resolution Time</label>
                        <input type="text" value="2-3 hours" placeholder="e.g. 2 hours">
                    </div>

                    <div class="action-buttons">
                        <button class="btn btn-primary">Update & Notify User</button>
                        <button class="btn btn-secondary">Save Changes</button>
                    </div>
                </div>

                <!-- Reporter Info -->
                <div class="card">
                    <h2 class="card-title">Reporter Information</h2>
                    <div class="reporter-info">
                        <div class="reporter-avatar">AB</div>
                        <div class="reporter-name">Alice Brown</div>
                        <div class="reporter-role">Student</div>
                    </div>
                    <button class="btn btn-secondary" style="margin-top: 1rem;">Send Message</button>
                </div>

                <!-- Quick Actions -->
                <div class="card">
                    <h2 class="card-title">Quick Actions</h2>
                    <div class="action-buttons">
                        <button class="btn btn-primary">Mark as Resolved</button>
                        <button class="btn btn-secondary">Escalate Issue</button>
                        <button class="btn btn-secondary">Request More Info</button>
                        <button class="btn btn-danger">Close Ticket</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection