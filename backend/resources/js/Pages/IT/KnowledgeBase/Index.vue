<script setup>
// Pages/IT/KnowledgeBase/Index.vue
// Path: resources/js/Pages/IT/KnowledgeBase/Index.vue
// Mirrors: resources/views/it/knowledge-base.blade.php

import AppLayout from '../../../Layouts/AppLayout.vue'
import NavIT from '../../../Components/Nav/NavIT.vue'
import { Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'

const props = defineProps({
  articles: Object,
  stats:    Object,
  filters:  Object,
})

// ── Filters ───────────────────────────────────────────────────────────────────
const search   = ref(props.filters?.search   ?? '')
const status   = ref(props.filters?.status   ?? '')
const category = ref(props.filters?.category ?? 'all')

function applyFilters() {
  router.get('/it/knowledge-base', {
    search:   search.value   || undefined,
    status:   status.value   || undefined,
    category: category.value !== 'all' ? category.value : undefined,
  }, { preserveState: true, replace: true })
}

let searchTimer = null
watch(search, () => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(applyFilters, 350)
})
watch([status, category], applyFilters)

// ── Helpers ───────────────────────────────────────────────────────────────────
function deleteArticle(id) {
  if (confirm('Are you sure you want to delete this article?')) {
    router.delete(`/it/knowledge-base/${id}`)
  }
}

function diffForHumans(d) {
  if (!d) return '—'
  const seconds = Math.floor((Date.now() - new Date(d)) / 1000)
  if (seconds < 60)    return `${seconds}s ago`
  if (seconds < 3600)  return `${Math.floor(seconds / 60)}m ago`
  if (seconds < 86400) return `${Math.floor(seconds / 3600)}h ago`
  return `${Math.floor(seconds / 86400)}d ago`
}

function formatViews(n) {
  return n >= 1000 ? (n / 1000).toFixed(1) + 'K' : n
}
</script>

<template>
  <AppLayout>
    <template #nav><NavIT /></template>

    <div class="container">

      <div class="page-header">
        <div>
          <h1>Knowledge Base Management</h1>
          <p style="color:#9ca3af;">Create and manage troubleshooting articles</p>
        </div>
        <div class="header-actions">
          <Link href="/it/knowledge-base/create" class="btn btn-primary">+ Create New Article</Link>
        </div>
      </div>

      <!-- Stats -->
      <div class="stats-row">
        <div class="stat-card">
          <div class="stat-label">Total Articles</div>
          <div class="stat-value">{{ stats.total }}</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Published</div>
          <div class="stat-value">{{ stats.published }}</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Drafts</div>
          <div class="stat-value">{{ stats.drafts }}</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Total Views</div>
          <div class="stat-value">{{ formatViews(stats.total_views) }}</div>
        </div>
      </div>

      <!-- Filters -->
      <div class="filter-bar">
        <div class="search-box">
          <span class="search-icon">🔍</span>
          <input v-model="search" type="text" placeholder="Search articles...">
        </div>
        <div class="filter-tabs">
          <button type="button" class="tab" :class="{ active: !status && category === 'all' }" @click="status = ''; category = 'all'">All</button>
          <button type="button" class="tab" :class="{ active: status === 'published' }" @click="status = 'published'; category = 'all'">Published</button>
          <button type="button" class="tab" :class="{ active: status === 'draft' }" @click="status = 'draft'; category = 'all'">Drafts</button>
          <button type="button" class="tab" :class="{ active: category === 'hardware' }" @click="status = ''; category = 'hardware'">Hardware</button>
          <button type="button" class="tab" :class="{ active: category === 'software' }" @click="status = ''; category = 'software'">Software</button>
          <button type="button" class="tab" :class="{ active: category === 'network' }" @click="status = ''; category = 'network'">Network</button>
        </div>
      </div>

      <!-- Articles grid -->
      <div class="articles-grid">
        <template v-if="articles.data.length">
          <div v-for="article in articles.data" :key="article.id" class="article-card">
            <div class="article-content">
              <div class="article-header">
                <div>
                  <h3 class="article-title">{{ article.title }}</h3>
                  <span class="status-badge" :class="article.status === 'published' ? 'status-active' : 'status-warning'">
                    {{ article.status.charAt(0).toUpperCase() + article.status.slice(1) }}
                  </span>
                </div>
              </div>
              <div class="article-meta">
                <div class="meta-item"><span>📁</span> {{ article.category.charAt(0).toUpperCase() + article.category.slice(1) }}</div>
                <div class="meta-item"><span>👁️</span> {{ article.views.toLocaleString() }} views</div>
                <div class="meta-item"><span>👍</span> {{ article.helpfulness_percentage }}% helpful</div>
                <div class="meta-item"><span>✏️</span> {{ article.author?.first_name }} {{ article.author?.last_name }}</div>
                <div class="meta-item"><span>📅</span> {{ diffForHumans(article.updated_at) }}</div>
              </div>
            </div>
            <div class="article-actions">
              <Link :href="`/it/knowledge-base/${article.id}/edit`" class="action-btn">Edit</Link>
              <Link :href="`/it/knowledge-base/${article.id}`" class="action-btn">
                {{ article.status === 'draft' ? 'Preview' : 'View' }}
              </Link>
              <button type="button" class="action-btn btn-danger" @click="deleteArticle(article.id)">Delete</button>
            </div>
          </div>
        </template>

        <div v-else class="empty-state">
          <div class="empty-icon">📚</div>
          <h3>No articles yet</h3>
          <p>Create your first knowledge base article</p>
          <Link href="/it/knowledge-base/create" class="btn btn-primary" style="margin-top:1rem;">+ Create Article</Link>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="articles.last_page > 1" class="pagination">
        <div class="page-info">
          Showing {{ articles.from ?? 0 }}–{{ articles.to ?? 0 }} of {{ articles.total }} articles
        </div>
        <div class="page-controls">
          <button class="page-btn" :disabled="!articles.prev_page_url" @click="router.get(articles.prev_page_url)">← Previous</button>
          <button
            v-for="p in articles.last_page" :key="p"
            class="page-btn" :class="{ active: p === articles.current_page }"
            @click="router.get(articles.path + '?page=' + p)"
          >{{ p }}</button>
          <button class="page-btn" :disabled="!articles.next_page_url" @click="router.get(articles.next_page_url)">Next →</button>
        </div>
      </div>

    </div>
  </AppLayout>
</template>