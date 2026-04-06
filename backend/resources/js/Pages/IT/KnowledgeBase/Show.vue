<script setup>
// Pages/IT/KnowledgeBase/Show.vue
// Path: resources/js/Pages/IT/KnowledgeBase/Show.vue
// Mirrors: resources/views/it/articles/show.blade.php

import AppLayout from '../../../Layouts/AppLayout.vue'
import NavIT from '../../../Components/Nav/NavIT.vue'
import { Link, useForm, router } from '@inertiajs/vue3'

const props = defineProps({
  article: Object,
})

// Publish now — sends a PUT with status changed to published
const publishForm = useForm({
  _method:  'PUT',
  title:    props.article.title,
  content:  props.article.content,
  category: props.article.category,
  excerpt:  props.article.excerpt ?? '',
  status:   'published',
})

function publishNow() {
  publishForm.post(`/it/knowledge-base/${props.article.id}`)
}

function deleteArticle() {
  if (confirm('Are you sure you want to delete this article?')) {
    router.delete(`/it/knowledge-base/${props.article.id}`)
  }
}

function formatDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}

function diffForHumans(d) {
  if (!d) return '—'
  const seconds = Math.floor((Date.now() - new Date(d)) / 1000)
  if (seconds < 60)    return `${seconds}s ago`
  if (seconds < 3600)  return `${Math.floor(seconds / 60)}m ago`
  if (seconds < 86400) return `${Math.floor(seconds / 3600)}h ago`
  return `${Math.floor(seconds / 86400)}d ago`
}
</script>

<template>
  <AppLayout>
    <template #nav><NavIT /></template>

    <div class="container">
      <Link href="/it/knowledge-base" class="back-btn">← Back to Knowledge Base</Link>

      <div class="page-header">
        <h1>Article Preview</h1>
        <div class="header-actions">
          <Link :href="`/it/knowledge-base/${article.id}/edit`" class="btn btn-primary">Edit Article</Link>
        </div>
      </div>

      <div class="content-layout">

        <!-- Main content -->
        <div>
          <div class="card">
            <div style="margin-bottom:1rem;">
              <span class="category-badge">{{ article.category.charAt(0).toUpperCase() + article.category.slice(1) }}</span>
              <span
                class="status-badge"
                :class="article.status === 'published' ? 'status-active' : 'status-warning'"
                style="margin-left:0.5rem;"
              >
                {{ article.status.charAt(0).toUpperCase() + article.status.slice(1) }}
              </span>
            </div>
            <h1 style="font-size:2.5rem;color:#ffffff;margin-bottom:1rem;line-height:1.2;">
              {{ article.title }}
            </h1>
            <div style="display:flex;gap:1.5rem;color:#9ca3af;font-size:0.9rem;flex-wrap:wrap;">
              <div>👁️ {{ article.views.toLocaleString() }} views</div>
              <div>👍 {{ article.helpfulness_percentage }}% helpful</div>
              <div>✏️ By {{ article.author?.first_name }} {{ article.author?.last_name }}</div>
              <div>📅 Updated {{ diffForHumans(article.updated_at) }}</div>
            </div>
          </div>

          <div class="card" style="margin-top:1.5rem;">
            <div v-if="article.excerpt" style="background:rgba(45,212,191,0.1);padding:1rem;border-radius:8px;margin-bottom:1.5rem;border-left:4px solid #2dd4bf;">
              <strong style="color:#2dd4bf;">Excerpt:</strong>
              <p style="color:#d1d5db;margin-top:0.5rem;">{{ article.excerpt }}</p>
            </div>
            <div style="color:#d1d5db;line-height:1.8;font-size:1.05rem;white-space:pre-wrap;">{{ article.content }}</div>
          </div>
        </div>

        <!-- Sidebar -->
        <div>

          <div class="info-card">
            <h2 class="card-title">Article Statistics</h2>
            <div class="info-list">
              <div class="info-item">
                <span class="info-label">Status</span>
                <span class="info-value">
                  <span class="status-badge" :class="article.status === 'published' ? 'status-active' : 'status-warning'">
                    {{ article.status.charAt(0).toUpperCase() + article.status.slice(1) }}
                  </span>
                </span>
              </div>
              <div class="info-item"><span class="info-label">Category</span><span class="info-value">{{ article.category.charAt(0).toUpperCase() + article.category.slice(1) }}</span></div>
              <div class="info-item"><span class="info-label">Total Views</span><span class="info-value">{{ article.views.toLocaleString() }}</span></div>
              <div class="info-item"><span class="info-label">Helpful Votes</span><span class="info-value">{{ article.helpful_count }}</span></div>
              <div class="info-item"><span class="info-label">Not Helpful Votes</span><span class="info-value">{{ article.not_helpful_count }}</span></div>
              <div class="info-item"><span class="info-label">Helpfulness</span><span class="info-value">{{ article.helpfulness_percentage }}%</span></div>
              <div class="info-item"><span class="info-label">Created</span><span class="info-value">{{ formatDate(article.created_at) }}</span></div>
              <div class="info-item"><span class="info-label">Last Updated</span><span class="info-value">{{ diffForHumans(article.updated_at) }}</span></div>
            </div>
          </div>

          <div class="info-card" style="margin-top:1.5rem;">
            <h2 class="card-title">Actions</h2>
            <div class="action-buttons" style="flex-direction:column;">
              <Link :href="`/it/knowledge-base/${article.id}/edit`" class="btn btn-primary">Edit Article</Link>
              <button
                v-if="article.status === 'draft'"
                type="button"
                class="btn btn-secondary"
                :disabled="publishForm.processing"
                @click="publishNow"
              >
                {{ publishForm.processing ? 'Publishing…' : 'Publish Now' }}
              </button>
              <button type="button" class="btn btn-danger" @click="deleteArticle">Delete Article</button>
            </div>
          </div>

        </div>
      </div>
    </div>
  </AppLayout>
</template>