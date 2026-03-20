<script setup>
// Pages/User/KnowledgeBaseShow.vue
// Mirrors: resources/views/user/articles/show.blade.php
// Path:    resources/js/Pages/User/KnowledgeBaseShow.vue

import AppLayout from '../../Layouts/AppLayout.vue'
import NavUser from '../../Components/Nav/NavUser.vue'

const props = defineProps({
  article:        Object,
  relatedArticles: Array,
})

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? ''

function formatDate(dateStr) {
  return new Date(dateStr).toLocaleDateString('en-US', {
    month: 'long', day: 'numeric', year: 'numeric'
  })
}

const categoryIcons = {
  hardware: '🔧', software: '💾', network: '🌐',
  display: '🖥️', peripherals: '🖱️', general: '📋',
}
</script>

<template>
  <AppLayout>
    <template #nav><NavUser /></template>

    <div class="container">
      <a href="/user/knowledge-base" class="back-btn">← Back to Knowledge Base</a>

      <div class="content-grid">

        <!-- Main article content -->
        <div>
          <div class="card">
            <!-- Article header -->
            <div style="margin-bottom:2rem;">
              <div style="display:flex;gap:0.75rem;align-items:center;margin-bottom:1rem;">
                <span class="category-badge">
                  {{ categoryIcons[article.category] ?? '📄' }}
                  {{ article.category.charAt(0).toUpperCase() + article.category.slice(1) }}
                </span>
                <span style="color:#9ca3af;font-size:0.9rem;">{{ article.views }} views</span>
              </div>
              <h1 style="font-size:2rem;color:#ffffff;margin-bottom:1rem;">{{ article.title }}</h1>
              <div style="display:flex;gap:1.5rem;color:#9ca3af;font-size:0.9rem;">
                <span>✍️ {{ article.author?.full_name }}</span>
                <span>📅 {{ formatDate(article.published_at) }}</span>
              </div>
            </div>

            <!-- Article body — whitespace-pre-wrap preserves newlines from the DB -->
            <div style="color:#d1d5db;line-height:1.8;white-space:pre-wrap;">{{ article.content }}</div>

            <!-- Helpfulness rating -->
            <div style="margin-top:2rem;padding-top:2rem;border-top:1px solid rgba(45,212,191,0.2);">
              <p style="color:#9ca3af;margin-bottom:1rem;">Was this article helpful?</p>
              <div style="display:flex;gap:1rem;">
                <!-- Native POST forms — markHelpful/markNotHelpful use back() redirect -->
                <form :action="`/user/knowledge-base/${article.slug}/helpful`" method="POST">
                  <input type="hidden" name="_token" :value="csrfToken">
                  <button type="submit" class="btn btn-secondary">
                    👍 Yes ({{ article.helpful_count }})
                  </button>
                </form>
                <form :action="`/user/knowledge-base/${article.slug}/not-helpful`" method="POST">
                  <input type="hidden" name="_token" :value="csrfToken">
                  <button type="submit" class="btn btn-secondary">
                    👎 No ({{ article.not_helpful_count }})
                  </button>
                </form>
              </div>
              <div v-if="article.helpful_count + article.not_helpful_count > 0" style="margin-top:1rem;color:#9ca3af;font-size:0.9rem;">
                {{ article.helpfulness_percentage }}% of readers found this helpful
              </div>
            </div>
          </div>
        </div>

        <!-- Sidebar -->
        <div>

          <!-- Related articles -->
          <div class="section-card" v-if="relatedArticles.length">
            <div class="section-header">
              <h2 class="section-title">Related Articles</h2>
            </div>
            <div class="link-list">
              <a
                v-for="related in relatedArticles"
                :key="related.id"
                :href="`/user/knowledge-base/${related.slug}`"
                class="link-item"
              >
                <span>{{ categoryIcons[related.category] ?? '📄' }}</span>
                {{ related.title }}
              </a>
            </div>
          </div>

          <!-- Still need help -->
          <div class="help-banner" style="margin-top:1.5rem;">
            <h3>Still having issues?</h3>
            <p>Submit a support ticket and our IT team will help you directly.</p>
            <a href="/user/reports/create" class="btn-contact">Submit a Ticket</a>
          </div>

        </div>
      </div>
    </div>
  </AppLayout>
</template>