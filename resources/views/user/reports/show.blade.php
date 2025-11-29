@extends('layouts.app')

@section('title', 'Report Detail')

@section('navigation')
    <x-nav.user />
@endsection

@section('content')
    <div id="user-report-details" class="container">
        <a href="{{ route('user.reports.index') }}" class="back-btn">← Back to My Reports</a>

        <!-- Ticket Header -->
        <div class="ticket-header">
            <div class="ticket-title-row">
                <div>
                    <h1 class="ticket-title">Computer won't start</h1>
                    <div class="ticket-id">Ticket #001</div>
                </div>
                <span class="status-badge status-progress">In Progress</span>
            </div>

            <div class="ticket-meta">
                <div class="meta-item">
                    <div class="meta-label">Reported By</div>
                    <div class="meta-value">John Doe</div>
                </div>
                <div class="meta-item">
                    <div class="meta-label">Location</div>
                    <div class="meta-value">Computer Lab A, PC-12</div>
                </div>
                <div class="meta-item">
                    <div class="meta-label">Category</div>
                    <div class="meta-value">Hardware</div>
                </div>
                <div class="meta-item">
                    <div class="meta-label">Priority</div>
                    <div class="meta-value priority-high">High</div>
                </div>
                <div class="meta-item">
                    <div class="meta-label">Submitted</div>
                    <div class="meta-value">Oct 1, 2025 - 2:30 PM</div>
                </div>
                <div class="meta-item">
                    <div class="meta-label">Assigned To</div>
                    <div class="meta-value">Tech Support - Mike Chen</div>
                </div>
            </div>
        </div>

        <div class="detail-layout">
            <!-- Main Content -->
            <div class="main-content">
                <!-- Description -->
                <div class="content-card">
                    <h2 class="card-title">Problem Description</h2>
                    <p class="description-text">
                        The computer shows a black screen when powered on. No POST beep sound detected. 
                        I've tried pressing the power button multiple times, but nothing happens. 
                        The monitor is working fine as tested with another computer. The power LED on the 
                        case lights up, but no display appears on the monitor.
                    </p>
                </div>

                <!-- Attachments -->
                <div class="content-card">
                    <h2 class="card-title">Attachments</h2>
                    <div class="attachments-grid">
                        <div class="attachment-item">
                            <div class="attachment-icon">🖼️</div>
                            <div class="attachment-name">screenshot_1.png</div>
                        </div>
                        <div class="attachment-item">
                            <div class="attachment-icon">🖼️</div>
                            <div class="attachment-name">error_photo.jpg</div>
                        </div>
                    </div>
                </div>

                <!-- Activity Timeline -->
                <div class="content-card">
                    <h2 class="card-title">Activity Timeline</h2>
                    <div class="timeline">
                        <div class="timeline-item">
                            <div class="timeline-dot"></div>
                            <div class="timeline-content">
                                <div class="timeline-header">
                                    <span class="timeline-title">Ticket Assigned</span>
                                    <span class="timeline-time">2 hours ago</span>
                                </div>
                                <p class="timeline-text">
                                    Assigned to Mike Chen from Tech Support Team
                                </p>
                            </div>
                        </div>

                        <div class="timeline-item">
                            <div class="timeline-dot"></div>
                            <div class="timeline-content">
                                <div class="timeline-header">
                                    <span class="timeline-title">Status Updated</span>
                                    <span class="timeline-time">3 hours ago</span>
                                </div>
                                <p class="timeline-text">
                                    Status changed from "New" to "In Progress"
                                </p>
                            </div>
                        </div>

                        <div class="timeline-item">
                            <div class="timeline-dot"></div>
                            <div class="timeline-content">
                                <div class="timeline-header">
                                    <span class="timeline-title">Ticket Created</span>
                                    <span class="timeline-time">4 hours ago</span>
                                </div>
                                <p class="timeline-text">
                                    Report submitted by John Doe
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="sidebar">
                <!-- Quick Info -->
                <div class="info-card">
                    <h2 class="card-title">Quick Information</h2>
                    <div class="info-row">
                        <span class="info-label">Status</span>
                        <span class="info-value">In Progress</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Priority</span>
                        <span class="info-value priority-high">High</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Response Time</span>
                        <span class="info-value">1 hour</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Estimated Fix</span>
                        <span class="info-value">2-4 hours</span>
                    </div>
                </div>

                <!-- Actions -->
                <div class="info-card">
                    <h2 class="card-title">Actions</h2>
                    <div class="action-buttons">
                        <button class="action-btn btn-primary">Add Comment</button>
                        <button class="action-btn btn-secondary">Upload More Files</button>
                        <button class="action-btn btn-secondary">Cancel Request</button>
                    </div>
                </div>

                <!-- Contact -->
                <div class="info-card">
                    <h2 class="card-title">Assigned Technician</h2>
                    <div style="text-align: center; padding: 1rem 0;">
                        <div style="width: 60px; height: 60px; background: linear-gradient(135deg, #2dd4bf, #14b8a6); border-radius: 50%; margin: 0 auto 1rem; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: bold;">MC</div>
                        <div style="color: #ffffff; font-weight: 600; margin-bottom: 0.25rem;">Mike Chen</div>
                        <div style="color: #9ca3af; font-size: 0.9rem;">Tech Support Team</div>
                    </div>
                    <button class="action-btn btn-secondary" style="margin-top: 1rem;">Send Message</button>
                </div>
            </div>
        </div>
    </div>
@endsection