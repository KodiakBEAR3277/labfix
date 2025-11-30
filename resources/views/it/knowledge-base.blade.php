@extends('layouts.app')

@section('title', 'Knowledge Base Admin')

@section('navigation')
    @include('components.nav.it')
@endsection

@section('content')
    <div class="container">
        <div class="page-header">
            <div>
                <h1>Knowledge Base Management</h1>
                <p style="color: #9ca3af;">Create and manage troubleshooting articles</p>
            </div>
            <div class="header-actions">
                <button class="btn btn-primary">+ Create New Article</button>
            </div>
        </div>

        <!-- Stats Row -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-label">Total Articles</div>
                <div class="stat-value">89</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Published</div>
                <div class="stat-value">72</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Drafts</div>
                <div class="stat-value">17</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Total Views</div>
                <div class="stat-value">12.5K</div>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="filter-bar">
            <div class="search-box">
                <span class="search-icon">🔍</span>
                <input type="text" placeholder="Search articles...">
            </div>
            <div class="filter-tabs">
                <button class="tab active">All</button>
                <button class="tab">Published</button>
                <button class="tab">Drafts</button>
                <button class="tab">Hardware</button>
                <button class="tab">Software</button>
                <button class="tab">Network</button>
            </div>
        </div>

        <!-- Articles Grid -->
        <div class="articles-grid">
            <div class="article-card">
                <div class="article-content">
                    <div class="article-header">
                        <div>
                            <h3 class="article-title">Computer won't turn on - Troubleshooting steps</h3>
                            <span class="status-badge status-published">Published</span>
                        </div>
                    </div>
                    <div class="article-meta">
                        <div class="meta-item">
                            <span>📁</span> Hardware
                        </div>
                        <div class="meta-item">
                            <span>👁️</span> 1,245 views
                        </div>
                        <div class="meta-item">
                            <span>👍</span> 95% helpful
                        </div>
                        <div class="meta-item">
                            <span>✏️</span> Mike Chen
                        </div>
                        <div class="meta-item">
                            <span>📅</span> Updated 2 days ago
                        </div>
                    </div>
                </div>
                <div class="article-actions">
                    <button class="action-btn">Edit</button>
                    <button class="action-btn">View</button>
                    <button class="action-btn btn-danger">Delete</button>
                </div>
            </div>

            <div class="article-card">
                <div class="article-content">
                    <div class="article-header">
                        <div>
                            <h3 class="article-title">How to fix "No Internet Connection" error</h3>
                            <span class="status-badge status-published">Published</span>
                        </div>
                    </div>
                    <div class="article-meta">
                        <div class="meta-item">
                            <span>📁</span> Network
                        </div>
                        <div class="meta-item">
                            <span>👁️</span> 987 views
                        </div>
                        <div class="meta-item">
                            <span>👍</span> 92% helpful
                        </div>
                        <div class="meta-item">
                            <span>✏️</span> Sarah Lee
                        </div>
                        <div class="meta-item">
                            <span>📅</span> Updated 5 days ago
                        </div>
                    </div>
                </div>
                <div class="article-actions">
                    <button class="action-btn">Edit</button>
                    <button class="action-btn">View</button>
                    <button class="action-btn btn-danger">Delete</button>
                </div>
            </div>

            <div class="article-card">
                <div class="article-content">
                    <div class="article-header">
                        <div>
                            <h3 class="article-title">Software installation fails - Common solutions</h3>
                            <span class="status-badge status-published">Published</span>
                        </div>
                    </div>
                    <div class="article-meta">
                        <div class="meta-item">
                            <span>📁</span> Software
                        </div>
                        <div class="meta-item">
                            <span>👁️</span> 856 views
                        </div>
                        <div class="meta-item">
                            <span>👍</span> 88% helpful
                        </div>
                        <div class="meta-item">
                            <span>✏️</span> Tom Anderson
                        </div>
                        <div class="meta-item">
                            <span>📅</span> Updated 1 week ago
                        </div>
                    </div>
                </div>
                <div class="article-actions">
                    <button class="action-btn">Edit</button>
                    <button class="action-btn">View</button>
                    <button class="action-btn btn-danger">Delete</button>
                </div>
            </div>

            <div class="article-card">
                <div class="article-content">
                    <div class="article-header">
                        <div>
                            <h3 class="article-title">Printer troubleshooting guide</h3>
                            <span class="status-badge status-draft">Draft</span>
                        </div>
                    </div>
                    <div class="article-meta">
                        <div class="meta-item">
                            <span>📁</span> Hardware
                        </div>
                        <div class="meta-item">
                            <span>👁️</span> 0 views
                        </div>
                        <div class="meta-item">
                            <span>👍</span> N/A
                        </div>
                        <div class="meta-item">
                            <span>✏️</span> Mike Chen
                        </div>
                        <div class="meta-item">
                            <span>📅</span> Created today
                        </div>
                    </div>
                </div>
                <div class="article-actions">
                    <button class="action-btn">Edit</button>
                    <button class="action-btn">Preview</button>
                    <button class="action-btn btn-danger">Delete</button>
                </div>
            </div>

            <div class="article-card">
                <div class="article-content">
                    <div class="article-header">
                        <div>
                            <h3 class="article-title">Keyboard and mouse not working properly</h3>
                            <span class="status-badge status-published">Published</span>
                        </div>
                    </div>
                    <div class="article-meta">
                        <div class="meta-item">
                            <span>📁</span> Peripherals
                        </div>
                        <div class="meta-item">
                            <span>👁️</span> 734 views
                        </div>
                        <div class="meta-item">
                            <span>👍</span> 90% helpful
                        </div>
                        <div class="meta-item">
                            <span>✏️</span> Sarah Lee
                        </div>
                        <div class="meta-item">
                            <span>📅</span> Updated 2 weeks ago
                        </div>
                    </div>
                </div>
                <div class="article-actions">
                    <button class="action-btn">Edit</button>
                    <button class="action-btn">View</button>
                    <button class="action-btn btn-danger">Delete</button>
                </div>
            </div>

            <div class="article-card">
                <div class="article-content">
                    <div class="article-header">
                        <div>
                            <h3 class="article-title">Monitor display issues and black screen problems</h3>
                            <span class="status-badge status-published">Published</span>
                        </div>
                    </div>
                    <div class="article-meta">
                        <div class="meta-item">
                            <span>📁</span> Display
                        </div>
                        <div class="meta-item">
                            <span>👁️</span> 612 views
                        </div>
                        <div class="meta-item">
                            <span>👍</span> 85% helpful
                        </div>
                        <div class="meta-item">
                            <span>✏️</span> Mike Chen
                        </div>
                        <div class="meta-item">
                            <span>📅</span> Updated 3 weeks ago
                        </div>
                    </div>
                </div>
                <div class="article-actions">
                    <button class="action-btn">Edit</button>
                    <button class="action-btn">View</button>
                    <button class="action-btn btn-danger">Delete</button>
                </div>
            </div>

            <div class="article-card">
                <div class="article-content">
                    <div class="article-header">
                        <div>
                            <h3 class="article-title">Windows update troubleshooting</h3>
                            <span class="status-badge status-draft">Draft</span>
                        </div>
                    </div>
                    <div class="article-meta">
                        <div class="meta-item">
                            <span>📁</span> Software
                        </div>
                        <div class="meta-item">
                            <span>👁️</span> 0 views
                        </div>
                        <div class="meta-item">
                            <span>👍</span> N/A
                        </div>
                        <div class="meta-item">
                            <span>✏️</span> Tom Anderson
                        </div>
                        <div class="meta-item">
                            <span>📅</span> Created 3 days ago
                        </div>
                    </div>
                </div>
                <div class="article-actions">
                    <button class="action-btn">Edit</button>
                    <button class="action-btn">Preview</button>
                    <button class="action-btn btn-danger">Delete</button>
                </div>
            </div>
        </div>
    </div>
@endsection