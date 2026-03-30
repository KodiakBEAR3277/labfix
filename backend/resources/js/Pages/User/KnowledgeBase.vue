<script setup>
// Pages/User/KnowledgeBase.vue
// Path: resources/js/Pages/User/KnowledgeBase.vue

import AppLayout from '../../Layouts/AppLayout.vue'
import NavUser from '../../Components/Nav/NavUser.vue'
import { Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'

const props = defineProps({
  articles:        Object,
  popularArticles: Array,
  categories:      Object,
  filters:         Object,
})

const search   = ref(props.filters?.search   ?? '')
const category = ref(props.filters?.category ?? 'all')

function applyFilters() {
  router.get('/user/knowledge-base', {
    search:   search.value   || undefined,
    category: category.value !== 'all' ? category.value : undefined,
  }, { preserveState: true, replace: true })
}

let searchTimer = null
watch(search, () => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(applyFilters, 350)
})

watch(category, applyFilters)

const categoryIcons = {
  hardware:    '🔧',
  software:    '💾',
  network:     '🌐',
  display:     '🖥️',
  peripherals: '🖱️',
  general:     '📋',
}

function categoryLabel(key) {
  return key.charAt(0).toUpperCase() + key.slice(1)
}

function formatDate(dateStr) {
  return new Date(dateStr).toLocaleDateString('en-US', {
    month: 'short', day: 'numeric', year: 'numeric'
  })
}
</script>

<template>
  <AppLayout>
    <template #nav><NavUser /></template>

    <div class="container">

      <!-- Hero search -->
      <div class="kb-hero">
        <h1>Knowledge Base</h1>
        <p>Find answers to common lab equipment issues before submitting a ticket</p>
        <div class="search-container">
          <span class="search-icon" style="position:absolute;left:1.2rem;top:50%;transform:translateY(-50%);color:#9ca3af;">🔍</span>
          <input
            v-model="search"
            type="text"
            class="search-input"
            placeholder="Search articles..."
          >
        </div>
      </div>

      <!-- Category cards — @click still correct here, these are filter toggles not navigation -->
      <div class="categories-section">
        <h2 class="section-title">Browse by Category</h2>
        <div class="categories-grid">
          <div
            v-for="(count, key) in categories"
            :key="key"
            class="category-card"
            :class="{ selected: category === key }"
            @click="category = category === key ? 'all' : key"
            style="cursor:pointer;"
          >
            <div class="category-icon">{{ categoryIcons[key] ?? '📄' }}</div>
            <div class="category-title">{{ categoryLabel(key) }}</div>
            <div class="category-count">{{ count }} article{{ count !== 1 ? 's' : '' }}</div>
          </div>
        </div>
      </div>

      <div class="content-grid">

        <!-- Articles list -->
        <div>
          <div class="section-header">
            <h2 class="section-title">
              {{ category !== 'all' ? categoryLabel(category) + ' Articles' : 'All Articles' }}
            </h2>
            <button
              v-if="category !== 'all' || search"
              type="button"
              class="view-all"
              @click="search = ''; category = 'all'"
            >
              Clear filters ×
            </button>
          </div>

          <div class="articles-grid">
            <template v-if="articles.data.length">
              <!--
                Link wraps the entire article card — same pattern as Dashboard
                ticket cards. Replaces the <a href> which caused a full reload.
                style="display:block" ensures the Link fills the card area.
              -->
              <Link
                v-for="article in articles.data"
                :key="article.id"
                :href="`/user/knowledge-base/${article.slug}`"
                class="article-card"
                style="display:block;text-decoration:none;"
              >
                <div class="article-content">
                  <div class="article-header">
                    <span class="category-badge">{{ categoryLabel(article.category) }}</span>
                  </div>
                  <div class="article-title">{{ article.title }}</div>
                  <div class="article-meta">
                    <span>👁️ {{ article.views }} views</span>
                    <span>✍️ {{ article.author?.full_name }}</span>
                    <span>📅 {{ formatDate(article.published_at) }}</span>
                  </div>
                  <p v-if="article.excerpt" style="color:#9ca3af;font-size:0.9rem;margin-top:0.5rem;line-height:1.5;">
                    {{ article.excerpt.substring(0, 120) }}{{ article.excerpt.length > 120 ? '…' : '' }}
                  </p>
                </div>
                <div class="article-icon">→</div>
              </Link>
            </template>

            <div v-else style="text-align:center;padding:3rem;color:#9ca3af;">
              <div style="font-size:2rem;margin-bottom:1rem;">🔍</div>
              <h3 style="color:#d1d5db;">No articles found</h3>
              <p>Try a different search term or category</p>
            </div>
          </div>

          <!-- Pagination — router.get() is correct here since these are
               pre-built URLs from Laravel's paginator, not form submissions -->
          <div v-if="articles.last_page > 1" class="pagination" style="margin-top:1.5rem;">
            <div class="page-info">
              Showing {{ articles.from }}–{{ articles.to }} of {{ articles.total }} articles
            </div>
            <div class="page-controls">
              <button
                class="page-btn"
                :disabled="!articles.prev_page_url"
                @click="router.get(articles.prev_page_url)"
              >← Previous</button>
              <button
                v-for="p in articles.last_page"
                :key="p"
                class="page-btn"
                :class="{ active: p === articles.current_page }"
                @click="router.get(articles.path + '?page=' + p)"
              >{{ p }}</button>
              <button
                class="page-btn"
                :disabled="!articles.next_page_url"
                @click="router.get(articles.next_page_url)"
              >Next →</button>
            </div>
          </div>
        </div>

        <!-- Sidebar -->
        <div>
          <div class="section-card">
            <div class="section-header">
              <h2 class="section-title">Most Popular</h2>
            </div>
            <div class="quick-links">
              <div class="quick-link-card">
                <div class="link-list">
                  <Link
                    v-for="article in popularArticles"
                    :key="article.id"
                    :href="`/user/knowledge-base/${article.slug}`"
                    class="link-item"
                  >
                    <span>{{ categoryIcons[article.category] ?? '📄' }}</span>
                    {{ article.title }}
                  </Link>
                </div>
              </div>
            </div>
          </div>

          <div class="help-banner" style="margin-top:1.5rem;">
            <h3>Still need help?</h3>
            <p>Can't find what you're looking for? Submit a support ticket and our IT team will assist you.</p>
            <Link href="/user/reports/create" class="btn-contact">Submit a Ticket</Link>
          </div>
        </div>

      </div>
    </div>
  </AppLayout>
</template>