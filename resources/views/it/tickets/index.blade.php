@extends('layouts.app')

@section('title', 'Ticket Queue')

@section('navigation')
    @include('components.nav.it')
@endsection

@section('content')
    <div class="container">
        <div class="page-header">
            <h1>Ticket Queue</h1>
            <div class="header-actions">
                <button class="btn btn-secondary">Export</button>
                <a href="{{ route('it.tickets.bulk') }}" class="btn btn-primary">Bulk Actions</a>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="filter-bar">
            <div class="filter-row">
                <div class="search-box">
                    <span class="search-icon">🔍</span>
                    <input type="text" placeholder="Search tickets by ID, title, or location...">
                </div>
                <div class="filter-group">
                    <span class="filter-label">Status:</span>
                    <select>
                        <option>All Status</option>
                        <option>New</option>
                        <option>Assigned</option>
                        <option>In Progress</option>
                        <option>Resolved</option>
                    </select>
                </div>
                <div class="filter-group">
                    <span class="filter-label">Priority:</span>
                    <select>
                        <option>All Priority</option>
                        <option>High</option>
                        <option>Medium</option>
                        <option>Low</option>
                    </select>
                </div>
                <div class="filter-group">
                    <span class="filter-label">Lab:</span>
                    <select>
                        <option>All Labs</option>
                        <option>Lab A</option>
                        <option>Lab B</option>
                        <option>Lab C</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Bulk Actions Bar (hidden by default) -->
        <div class="bulk-actions" style="display: none;">
            <span class="bulk-text">3 tickets selected</span>
            <button class="btn btn-secondary">Assign to Me</button>
            <button class="btn btn-secondary">Change Priority</button>
            <button class="btn btn-secondary">Update Status</button>
            <button class="btn btn-secondary">Close Tickets</button>
        </div>

        <!-- Tickets Table -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th><input type="checkbox" class="ticket-checkbox"></th>
                        <th>Ticket ID ▼</th>
                        <th>Issue</th>
                        <th>Reporter</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th>Priority</th>
                        <th>Assigned To</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><input type="checkbox" class="ticket-checkbox"></td>
                        <td class="ticket-id">#092</td>
                        <td>Mouse not responding</td>
                        <td>John Doe</td>
                        <td>Lab B, PC-15</td>
                        <td><span class="status-badge status-new">New</span></td>
                        <td><span class="priority-high">High</span></td>
                        <td>Unassigned</td>
                        <td>5 min ago</td>
                        <td>
                            <div class="action-menu">
                                <button class="action-btn">View</button>
                                <button class="action-btn">Assign</button>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td><input type="checkbox" class="ticket-checkbox"></td>
                        <td class="ticket-id">#091</td>
                        <td>Software installation fails</td>
                        <td>Jane Smith</td>
                        <td>Lab C, PC-20</td>
                        <td><span class="status-badge status-assigned">Assigned</span></td>
                        <td><span class="priority-medium">Medium</span></td>
                        <td>Mike Chen</td>
                        <td>12 min ago</td>
                        <td>
                            <div class="action-menu">
                                <button class="action-btn">View</button>
                                <button class="action-btn">Update</button>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td><input type="checkbox" class="ticket-checkbox"></td>
                        <td class="ticket-id">#090</td>
                        <td>Monitor flickering</td>
                        <td>Bob Wilson</td>
                        <td>Lab A, PC-08</td>
                        <td><span class="status-badge status-progress">In Progress</span></td>
                        <td><span class="priority-medium">Medium</span></td>
                        <td>Sarah Lee</td>
                        <td>25 min ago</td>
                        <td>
                            <div class="action-menu">
                                <button class="action-btn">View</button>
                                <button class="action-btn">Update</button>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td><input type="checkbox" class="ticket-checkbox"></td>
                        <td class="ticket-id">#089</td>
                        <td>Network connectivity issue</td>
                        <td>Alice Brown</td>
                        <td>Lab C, All PCs</td>
                        <td><span class="status-badge status-progress">In Progress</span></td>
                        <td><span class="priority-high">High</span></td>
                        <td>Mike Chen</td>
                        <td>1 hour ago</td>
                        <td>
                            <div class="action-menu">
                                <a href="{{ route('it.tickets.show', 89) }}" class="action-btn">View</a>
                                <button class="action-btn">Update</button>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td><input type="checkbox" class="ticket-checkbox"></td>
                        <td class="ticket-id">#088</td>
                        <td>Keyboard keys stuck</td>
                        <td>David Kim</td>
                        <td>Lab A, PC-12</td>
                        <td><span class="status-badge status-assigned">Assigned</span></td>
                        <td><span class="priority-low">Low</span></td>
                        <td>Tom Anderson</td>
                        <td>2 hours ago</td>
                        <td>
                            <div class="action-menu">
                                <button class="action-btn">View</button>
                                <button class="action-btn">Update</button>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td><input type="checkbox" class="ticket-checkbox"></td>
                        <td class="ticket-id">#087</td>
                        <td>Computer won't boot</td>
                        <td>Emma Davis</td>
                        <td>Lab B, PC-05</td>
                        <td><span class="status-badge status-progress">In Progress</span></td>
                        <td><span class="priority-high">High</span></td>
                        <td>Mike Chen</td>
                        <td>3 hours ago</td>
                        <td>
                            <div class="action-menu">
                                <button class="action-btn">View</button>
                                <button class="action-btn">Update</button>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td><input type="checkbox" class="ticket-checkbox"></td>
                        <td class="ticket-id">#086</td>
                        <td>Slow system performance</td>
                        <td>Chris Taylor</td>
                        <td>Lab C, PC-10</td>
                        <td><span class="status-badge status-new">New</span></td>
                        <td><span class="priority-low">Low</span></td>
                        <td>Unassigned</td>
                        <td>4 hours ago</td>
                        <td>
                            <div class="action-menu">
                                <button class="action-btn">View</button>
                                <button class="action-btn">Assign</button>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td><input type="checkbox" class="ticket-checkbox"></td>
                        <td class="ticket-id">#085</td>
                        <td>Audio not working</td>
                        <td>Lisa Garcia</td>
                        <td>Lab A, PC-18</td>
                        <td><span class="status-badge status-resolved">Resolved</span></td>
                        <td><span class="priority-medium">Medium</span></td>
                        <td>Sarah Lee</td>
                        <td>5 hours ago</td>
                        <td>
                            <div class="action-menu">
                                <button class="action-btn">View</button>
                                <button class="action-btn">Close</button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>

            <div class="pagination">
                <div class="page-info">Showing 1-8 of 45 tickets</div>
                <div class="page-controls">
                    <button class="page-btn">← Previous</button>
                    <button class="page-btn active">1</button>
                    <button class="page-btn">2</button>
                    <button class="page-btn">3</button>
                    <button class="page-btn">4</button>
                    <button class="page-btn">5</button>
                    <button class="page-btn">Next →</button>
                </div>
            </div>
        </div>
    </div>
@endsection