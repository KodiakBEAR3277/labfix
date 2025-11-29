@extends('layouts.app')

@section('title', 'My Reports')

@section('navigation')
    <x-nav.user />
@endsection

@section('content')
    <div class="container">
        <div class="page-header">
            <h1>My Reports</h1>
            <a href="{{ route('user.reports.create') }}" class="new-report-btn">+ New Report</a>
        </div>

        <!-- Stats Row -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-label">Total Reports</div>
                <div class="stat-value">12</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Active</div>
                <div class="stat-value">3</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Resolved</div>
                <div class="stat-value">8</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Avg. Resolution Time</div>
                <div class="stat-value">2.5<span style="font-size: 1rem; color: #9ca3af;"> hrs</span></div>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="filter-bar">
            <div class="search-box">
                <span class="search-icon">🔍</span>
                <input type="text" placeholder="Search reports...">
            </div>
            <div class="filter-group">
                <button class="filter-btn active">All</button>
                <button class="filter-btn">Active</button>
                <button class="filter-btn">Resolved</button>
                <button class="filter-btn">Closed</button>
            </div>
        </div>

        <!-- Reports Table -->
        <div class="reports-container">
            <table>
                <thead>
                    <tr>
                        <th>Ticket ID</th>
                        <th>Issue</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th>Priority</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr onclick="window.location.href='{{ route('user.reports.show', 1) }}'">
                        <td class="ticket-id">#001</td>
                        <td>Computer won't start</td>
                        <td>Lab A, PC-12</td>
                        <td><span class="status-badge status-progress">In Progress</span></td>
                        <td><span class="priority-high">🔴 High</span></td>
                        <td>2 hours ago</td>
                        <td><button class="action-btn" onclick="event.stopPropagation(); window.location.href='{{ route('user.reports.show', 1) }}'">View</button></td>
                    </tr>
                    <tr onclick="window.location.href='{{ route('user.reports.show', 2) }}'">
                        <td class="ticket-id">#002</td>
                        <td>Keyboard keys not working</td>
                        <td>Lab B, PC-05</td>
                        <td><span class="status-badge status-assigned">Assigned</span></td>
                        <td><span class="priority-medium">🟡 Medium</span></td>
                        <td>Yesterday</td>
                        <td><button class="action-btn" onclick="event.stopPropagation()">View</button></td>
                    </tr>
                    <tr onclick="window.location.href='{{ route('user.reports.show', 3) }}'">
                        <td class="ticket-id">#003</td>
                        <td>Software installation error</td>
                        <td>Lab C, PC-20</td>
                        <td><span class="status-badge status-resolved">Resolved</span></td>
                        <td><span class="priority-low">🟢 Low</span></td>
                        <td>3 days ago</td>
                        <td><button class="action-btn" onclick="event.stopPropagation()">View</button></td>
                    </tr>
                    <tr onclick="window.location.href='{{ route('user.reports.show', 4) }}'">
                        <td class="ticket-id">#004</td>
                        <td>Monitor display flickering</td>
                        <td>Lab A, PC-08</td>
                        <td><span class="status-badge status-progress">In Progress</span></td>
                        <td><span class="priority-medium">🟡 Medium</span></td>
                        <td>4 days ago</td>
                        <td><button class="action-btn" onclick="event.stopPropagation()">View</button></td>
                    </tr>
                    <tr onclick="window.location.href='{{ route('user.reports.show', 5) }}'">
                        <td class="ticket-id">#005</td>
                        <td>Network connection problem</td>
                        <td>Lab B, PC-15</td>
                        <td><span class="status-badge status-resolved">Resolved</span></td>
                        <td><span class="priority-high">🔴 High</span></td>
                        <td>5 days ago</td>
                        <td><button class="action-btn" onclick="event.stopPropagation()">View</button></td>
                    </tr>
                    <tr onclick="window.location.href='{{ route('user.reports.show', 6) }}'">
                        <td class="ticket-id">#006</td>
                        <td>Mouse not responding</td>
                        <td>Lab C, PC-03</td>
                        <td><span class="status-badge status-new">New</span></td>
                        <td><span class="priority-low">🟢 Low</span></td>
                        <td>1 week ago</td>
                        <td><button class="action-btn" onclick="event.stopPropagation()">View</button></td>
                    </tr>
                </tbody>
            </table>
            
            <div class="pagination">
                <button class="page-btn">← Previous</button>
                <button class="page-btn active">1</button>
                <button class="page-btn">2</button>
                <button class="page-btn">3</button>
                <button class="page-btn">Next →</button>
            </div>
        </div>
    </div>
@endsection