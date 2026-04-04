<script setup>
// Pages/Admin/Transactions/Index.vue
// Path: resources/js/Pages/Admin/Transactions/Index.vue
// Mirrors: resources/views/admin/transactions/index.blade.php

import AppLayout from '../../../Layouts/AppLayout.vue'
import NavAdmin from '../../../Components/Nav/NavAdmin.vue'
import { Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'

const props = defineProps({
  transactions: Object,
  stats:        Object,
  users:        Array,
  actions:      Array,
  filters:      Object,
})

// ── Filters ───────────────────────────────────────────────────────────────────
const search    = ref(props.filters?.search    ?? '')
const action    = ref(props.filters?.action    ?? 'all')
const user      = ref(props.filters?.user      ?? 'all')
const dateFrom  = ref(props.filters?.date_from ?? '')
const dateTo    = ref(props.filters?.date_to   ?? '')

function applyFilters() {
  router.get('/admin/transactions', {
    search:    search.value    || undefined,
    action:    action.value    !== 'all' ? action.value    : undefined,
    user:      user.value      !== 'all' ? user.value      : undefined,
    date_from: dateFrom.value  || undefined,
    date_to:   dateTo.value    || undefined,
  }, { preserveState: true, replace: true })
}

let searchTimer = null
watch(search, () => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(applyFilters, 350)
})
watch([action, user, dateFrom, dateTo], applyFilters)

// ── Helpers ───────────────────────────────────────────────────────────────────
function actionIcon(a) {
  return {
    created:          '🎫',
    status_changed:   '🔄',
    assigned:         '👤',
    priority_changed: '⚠️',
    updated:          '✏️',
    deleted:          '🗑️',
    restored:         '♻️',
  }[a] ?? '📌'
}

function actionLabel(a) {
  return a.replace('_', ' ').replace(/\b\w/g, c => c.toUpperCase())
}

function formatDate(d) {
  return new Date(d).toLocaleDateString('en-US', {
    month: 'short', day: 'numeric', year: 'numeric',
    hour: 'numeric', minute: '2-digit',
  })
}
</script>

<template>
  <AppLayout>
    <template #nav><NavAdmin /></template>

    <div class="container">

      <div class="page-header">
        <div>
          <h1>Ticket History</h1>
          <p style="color:#9ca3af;">Full audit log of all ticket activity</p>
        </div>
        <Link href="/admin/tickets" class="btn btn-secondary">← Back to Tickets</Link>
      </div>

      <!-- Stats -->
      <div class="stats-row">
        <div class="stat-card">
          <div class="stat-label">Total Transactions</div>
          <div class="stat-value">{{ stats.total }}</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Today</div>
          <div class="stat-value">{{ stats.today }}</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">This Week</div>
          <div class="stat-value">{{ stats.this_week }}</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">This Month</div>
          <div class="stat-value">{{ stats.this_month }}</div>
        </div>
      </div>

      <!-- Filters -->
      <div class="filter-bar">
        <div class="filter-row" style="flex-wrap:wrap;">
          <div class="search-box">
            <span class="search-icon">🔍</span>
            <input v-model="search" type="text" placeholder="Search by action, description, ticket, or user...">
          </div>
          <div class="filter-group">
            <span class="filter-label">Action:</span>
            <select v-model="action">
              <option value="all">All Actions</option>
              <option v-for="a in actions" :key="a" :value="a">{{ actionLabel(a) }}</option>
            </select>
          </div>
          <div class="filter-group">
            <span class="filter-label">User:</span>
            <select v-model="user">
              <option value="all">All Users</option>
              <option v-for="u in users" :key="u.id" :value="String(u.id)">
                {{ u.first_name }} {{ u.last_name }}
              </option>
            </select>
          </div>
          <div class="filter-group">
            <span class="filter-label">From:</span>
            <input v-model="dateFrom" type="date" style="padding:0.6rem;background:rgba(255,255,255,0.05);border:1px solid rgba(45,212,191,0.3);border-radius:8px;color:#fff;">
          </div>
          <div class="filter-group">
            <span class="filter-label">To:</span>
            <input v-model="dateTo" type="date" style="padding:0.6rem;background:rgba(255,255,255,0.05);border:1px solid rgba(45,212,191,0.3);border-radius:8px;color:#fff;">
          </div>
        </div>
      </div>

      <!-- Table -->
      <div class="table-container">
        <table>
          <thead>
            <tr>
              <th>Action</th>
              <th>Ticket</th>
              <th>Description</th>
              <th>Performed By</th>
              <th>Change</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody>
            <template v-if="transactions.data.length">
              <tr v-for="tx in transactions.data" :key="tx.id">
                <td>
                  <span style="display:flex;align-items:center;gap:0.5rem;">
                    {{ actionIcon(tx.action) }}
                    <span style="font-size:0.85rem;color:#d1d5db;">{{ actionLabel(tx.action) }}</span>
                  </span>
                </td>
                <td>
                  <Link
                    v-if="tx.ticket"
                    :href="`/admin/tickets/${tx.ticket.id}`"
                    class="ticket-id"
                    style="font-size:0.85rem;"
                  >
                    {{ tx.ticket.ticket_number }}
                  </Link>
                  <span v-else style="color:#9ca3af;">—</span>
                </td>
                <td style="max-width:300px;color:#d1d5db;font-size:0.9rem;">{{ tx.description }}</td>
                <td>
                  <span v-if="tx.user" style="color:#d1d5db;">
                    {{ tx.user.first_name }} {{ tx.user.last_name }}
                  </span>
                  <span v-else style="color:#9ca3af;">—</span>
                </td>
                <td>
                  <div v-if="tx.old_value || tx.new_value" style="font-size:0.85rem;">
                    <span v-if="tx.old_value" style="color:#ef4444;">{{ tx.old_value }}</span>
                    <span v-if="tx.old_value && tx.new_value" style="color:#9ca3af;"> → </span>
                    <span v-if="tx.new_value" style="color:#10b981;">{{ tx.new_value }}</span>
                  </div>
                  <span v-else style="color:#9ca3af;">—</span>
                </td>
                <td style="color:#9ca3af;font-size:0.85rem;white-space:nowrap;">{{ formatDate(tx.created_at) }}</td>
              </tr>
            </template>
            <tr v-else>
              <td colspan="6" style="text-align:center;padding:3rem;color:#9ca3af;">
                <div style="font-size:2rem;margin-bottom:1rem;">📜</div>
                <h3 style="color:#d1d5db;">No transactions found</h3>
                <p>{{ filters.search || filters.action || filters.user ? 'Try adjusting your filters' : 'No activity recorded yet' }}</p>
                <Link v-if="filters.search || filters.action || filters.user" href="/admin/transactions" class="btn btn-primary" style="margin-top:1rem;">
                  Clear Filters
                </Link>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Pagination -->
        <div v-if="transactions.last_page > 1" class="pagination">
          <div class="page-info">
            Showing {{ transactions.from ?? 0 }}–{{ transactions.to ?? 0 }} of {{ transactions.total }} transactions
          </div>
          <div class="page-controls">
            <button class="page-btn" :disabled="!transactions.prev_page_url" @click="router.get(transactions.prev_page_url)">← Previous</button>
            <button
              v-for="p in transactions.last_page" :key="p"
              class="page-btn" :class="{ active: p === transactions.current_page }"
              @click="router.get(transactions.path + '?page=' + p)"
            >{{ p }}</button>
            <button class="page-btn" :disabled="!transactions.next_page_url" @click="router.get(transactions.next_page_url)">Next →</button>
          </div>
        </div>
      </div>

    </div>
  </AppLayout>
</template>