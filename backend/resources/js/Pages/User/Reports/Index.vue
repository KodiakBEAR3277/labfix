<script setup>
// Pages/User/Reports/Index.vue
// Mirrors: resources/views/user/reports/index.blade.php
// Path:    resources/js/Pages/User/Reports/Index.vue

import AppLayout from '../../../Layouts/AppLayout.vue'
import NavUser from '../../../Components/Nav/NavUser.vue'
import { Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'

const props = defineProps({
  reports: Object,
  stats:   Object,
  filters: Object,
})

const search = ref(props.filters?.search ?? '')
const status = ref(props.filters?.status ?? 'all')

function applyFilters() {
  router.get('/user/reports', {
    search: search.value || undefined,
    status: status.value !== 'all' ? status.value : undefined,
  }, {
    preserveState: true,
    replace: true,
  })
}

let searchTimer = null
watch(search, () => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(applyFilters, 350)
})

watch(status, applyFilters)

function statusColor(status) {
  const map = {
    'new': 'info', 'assigned': 'assigned',
    'in-progress': 'progress', 'resolved': 'resolved', 'closed': 'resolved',
  }
  return map[status] ?? 'secondary'
}

function statusLabel(s) {
  return s.replace('-', ' ').replace(/\b\w/g, c => c.toUpperCase())
}
</script>

<template>
  <AppLayout>
    <template #nav><NavUser /></template>

    <div class="container">
      <div class="page-header">
        <h1>My Reports</h1>
        <Link href="/user/reports/create" class="new-report-btn">+ New Report</Link>
      </div>

      <!-- Stats -->
      <div class="stats-row">
        <div class="stat-card">
          <div class="stat-label">Total Reports</div>
          <div class="stat-value">{{ stats.total }}</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Active</div>
          <div class="stat-value">{{ stats.active }}</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Resolved</div>
          <div class="stat-value">{{ stats.resolved }}</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Avg. Resolution Time</div>
          <div class="stat-value">{{ stats.avg_resolution_time }}<span style="font-size:1rem;color:#9ca3af;"> hrs</span></div>
        </div>
      </div>

      <!-- Filter bar -->
      <div class="filter-bar">
        <div class="search-box">
          <span class="search-icon">🔍</span>
          <input
            v-model="search"
            type="text"
            placeholder="Search reports..."
          >
        </div>
        <div class="filter-group">
          <button
            v-for="opt in ['all','active','resolved','closed']"
            :key="opt"
            type="button"
            class="filter-btn"
            :class="{ active: status === opt }"
            @click="status = opt"
          >
            {{ opt.charAt(0).toUpperCase() + opt.slice(1) }}
          </button>
        </div>
      </div>

      <!-- Table -->
      <div class="reports-container">
        <table>
          <thead>
            <tr>
              <th>Ticket #</th>
              <th>Issue</th>
              <th>Location</th>
              <th>Equipment</th>
              <th>Status</th>
              <th>Priority</th>
              <th>Date</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <template v-if="reports.data.length">
              <tr v-for="report in reports.data" :key="report.id">
                <td class="ticket-id">{{ report.ticket_number }}</td>
                <td>{{ report.title }}</td>
                <td>{{ report.equipment?.lab?.name }}</td>
                <td>{{ report.equipment?.equipment_code }}</td>
                <td>
                  <span class="status-badge" :class="`status-${statusColor(report.status)}`">
                    {{ statusLabel(report.status) }}
                  </span>
                </td>
                <td>
                  <span class="priority-badge" :class="`priority-${report.priority}`">
                    {{ report.priority === 'high' ? '🔴' : report.priority === 'medium' ? '🟡' : '🟢' }}
                    {{ report.priority.charAt(0).toUpperCase() + report.priority.slice(1) }}
                  </span>
                </td>
                <td>{{ new Date(report.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) }}</td>
                <td>
                  <div class="action-menu">
                    <Link :href="`/user/reports/${report.id}`" class="action-btn">View</Link>
                    <template v-if="!report.assigned_to">
                      <Link :href="`/user/reports/${report.id}/edit`" class="action-btn" style="color:#3b82f6;">Edit</Link>
                      <button
                        type="button"
                        class="action-btn"
                        style="color:#ef4444;"
                        @click="router.delete(`/user/reports/${report.id}`, { onBefore: () => confirm('Cancel this ticket?') })"
                      >Cancel</button>
                    </template>
                    <template v-else>
                      <span class="action-btn" style="opacity:0.5;cursor:not-allowed;" title="Ticket is assigned">🔒 Locked</span>
                    </template>
                  </div>
                </td>
              </tr>
            </template>
            <tr v-else>
              <td colspan="8" style="text-align:center;padding:3rem;color:#9ca3af;">
                <div style="font-size:2rem;margin-bottom:1rem;">📋</div>
                <h3 style="color:#d1d5db;">No reports found</h3>
                <p>{{ search || status !== 'all' ? 'Try adjusting your filters' : 'Report your first issue to get started' }}</p>
                <Link v-if="search || status !== 'all'" href="/user/reports" class="btn btn-primary" style="margin-top:1rem;">Clear Filters</Link>
                <Link v-else href="/user/reports/create" class="btn btn-primary" style="margin-top:1rem;">+ Report Issue</Link>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Pagination -->
        <div v-if="reports.last_page > 1" class="pagination">
          <div class="page-info">
            Showing {{ reports.from ?? 0 }}–{{ reports.to ?? 0 }} of {{ reports.total }} reports
          </div>
          <div class="page-controls">
            <button
              class="page-btn"
              :disabled="!reports.prev_page_url"
              @click="router.get(reports.prev_page_url)"
            >← Previous</button>
            <button
              v-for="p in reports.last_page"
              :key="p"
              class="page-btn"
              :class="{ active: p === reports.current_page }"
              @click="router.get(reports.path + '?page=' + p)"
            >{{ p }}</button>
            <button
              class="page-btn"
              :disabled="!reports.next_page_url"
              @click="router.get(reports.next_page_url)"
            >Next →</button>
          </div>
        </div>
      </div>

      <!-- Info banner -->
      <div style="background:rgba(59,130,246,0.1);border:1px solid rgba(59,130,246,0.3);padding:1rem;border-radius:8px;margin-top:1.5rem;">
        <div style="display:flex;align-items:center;gap:0.75rem;color:#60a5fa;">
          <span style="font-size:1.5rem;">ℹ️</span>
          <div>
            <strong>Note:</strong> You can edit or cancel tickets before they're assigned to a technician.
            Once assigned, contact the technician to make changes.
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>