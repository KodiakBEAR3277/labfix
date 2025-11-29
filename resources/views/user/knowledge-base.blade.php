@extends('layouts.app')

@section('title', 'Knowledge Base')

@section('navigation')
    <x-nav.user />
@endsection

@section('content')
    <div class="container">
        <!-- Hero Section -->
        <div class="kb-hero">
            <h1>How can we help you?</h1>
            <p>Search our knowledge base for solutions to common problems</p>
            <div class="search-container">
                <span class="search-icon">🔍</span>
                <input type="text" class="search-input" placeholder="Search for articles, guides, and solutions...">
            </div>
        </div>

        <!-- Categories -->
        <div class="categories-section">
            <h2 class="section-title">Browse by Category</h2>
            <div class="categories-grid">
                <div class="category-card">
                    <div class="category-icon">🔧</div>
                    <div class="category-title">Hardware Issues</div>
                    <div class="category-count">24 articles</div>
                </div>
                <div class="category-card">
                    <div class="category-icon">💾</div>
                    <div class="category-title">Software Problems</div>
                    <div class="category-count">18 articles</div>
                </div>
                <div class="category-card">
                    <div class="category-icon">🌐</div>
                    <div class="category-title">Network & Connectivity</div>
                    <div class="category-count">15 articles</div>
                </div>
                <div class="category-card">
                    <div class="category-icon">🖥️</div>
                    <div class="category-title">Display Issues</div>
                    <div class="category-count">12 articles</div>
                </div>
                <div class="category-card">
                    <div class="category-icon">⌨️</div>
                    <div class="category-title">Peripherals</div>
                    <div class="category-count">10 articles</div>
                </div>
                <div class="category-card">
                    <div class="category-icon">❓</div>
                    <div class="category-title">General Help</div>
                    <div class="category-count">20 articles</div>
                </div>
            </div>
        </div>

        <!-- Popular Articles -->
        <div class="articles-section">
            <h2 class="section-title">Popular Articles</h2>
            <div class="articles-grid">
                <div class="article-card">
                    <div class="article-content">
                        <div class="article-title">Computer won't turn on - Troubleshooting steps</div>
                        <div class="article-meta">
                            <span>👁️ 1,245 views</span>
                            <span>👍 95% helpful</span>
                            <span>Hardware</span>
                        </div>
                    </div>
                    <div class="article-icon">→</div>
                </div>

                <div class="article-card">
                    <div class="article-content">
                        <div class="article-title">How to fix "No Internet Connection" error</div>
                        <div class="article-meta">
                            <span>👁️ 987 views</span>
                            <span>👍 92% helpful</span>
                            <span>Network</span>
                        </div>
                    </div>
                    <div class="article-icon">→</div>
                </div>

                <div class="article-card">
                    <div class="article-content">
                        <div class="article-title">Software installation fails - Common solutions</div>
                        <div class="article-meta">
                            <span>👁️ 856 views</span>
                            <span>👍 88% helpful</span>
                            <span>Software</span>
                        </div>
                    </div>
                    <div class="article-icon">→</div>
                </div>

                <div class="article-card">
                    <div class="article-content">
                        <div class="article-title">Keyboard or mouse not working properly</div>
                        <div class="article-meta">
                            <span>👁️ 734 views</span>
                            <span>👍 90% helpful</span>
                            <span>Peripherals</span>
                        </div>
                    </div>
                    <div class="article-icon">→</div>
                </div>

                <div class="article-card">
                    <div class="article-content">
                        <div class="article-title">Monitor display issues and black screen problems</div>
                        <div class="article-meta">
                            <span>👁️ 612 views</span>
                            <span>👍 85% helpful</span>
                            <span>Display</span>
                        </div>
                    </div>
                    <div class="article-icon">→</div>
                </div>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="articles-section">
            <h2 class="section-title">Quick Links</h2>
            <div class="quick-links">
                <div class="quick-link-card">
                    <div class="quick-link-title">Getting Started</div>
                    <div class="link-list">
                        <a href="#" class="link-item">• How to report an issue</a>
                        <a href="#" class="link-item">• Understanding ticket statuses</a>
                        <a href="#" class="link-item">• Creating your account</a>
                        <a href="#" class="link-item">• Lab equipment locations</a>
                    </div>
                </div>

                <div class="quick-link-card">
                    <div class="quick-link-title">Common Problems</div>
                    <div class="link-list">
                        <a href="#" class="link-item">• Computer won't start</a>
                        <a href="#" class="link-item">• Slow performance</a>
                        <a href="#" class="link-item">• Application crashes</a>
                        <a href="#" class="link-item">• Login issues</a>
                    </div>
                </div>

                <div class="quick-link-card">
                    <div class="quick-link-title">Policies & Guidelines</div>
                    <div class="link-list">
                        <a href="#" class="link-item">• Lab usage policies</a>
                        <a href="#" class="link-item">• Equipment care guidelines</a>
                        <a href="#" class="link-item">• Support response times</a>
                        <a href="#" class="link-item">• Reporting requirements</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Help Banner -->
        <div class="help-banner">
            <h3>Can't find what you're looking for?</h3>
            <p>Our support team is here to help you with any issues</p>
            <button class="btn-contact">Contact IT Support</button>
        </div>
    </div>
@endsection