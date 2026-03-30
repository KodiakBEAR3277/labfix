<script setup>
// Pages/User/Dashboard.vue
// Path: resources/js/Pages/User/Dashboard.vue

import AppLayout from '../../Layouts/AppLayout.vue'
import NavUser from '../../Components/Nav/NavUser.vue'
import { Link, router } from '@inertiajs/vue3'
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

const props = defineProps({
  reports: Array,
  stats:   Object,
})

const user = computed(() => usePage().props.auth.user)

function statusColor(status) {
  const map = {
    'new':         'info',
    'assigned':    'assigned',
    'in-progress': 'progress',
    'resolved':    'resolved',
    'closed':      'resolved',
  }
  return map[status] ?? 'secondary'
}

function statusLabel(status) {
  return status.replace('-', ' ').replace(/\b\w/g, c => c.toUpperCase())
}
</script>

<template>
  <AppLayout>
    <template #nav>
      <NavUser />
    </template>

    <div class="container">

      <!-- Welcome section -->
      <div class="welcome-section">
        <h1>Welcome back, {{ user?.first_name }}!</h1>
        <p>Here's an overview of your submitted reports</p>
      </div>

      <!-- Stats row -->
      <div class="stats-row">
        <div class="stat-card">
          <div class="stat-label">Active Tickets</div>
          <div class="stat-value">{{ stats.active }}</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Resolved This Month</div>
          <div class="stat-value">{{ stats.resolved_this_month }}</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Avg. Resolution Time</div>
          <div class="stat-value">
            {{ stats.avg_resolution_time }}
            <span style="font-size:1rem;color:#9ca3af;">hrs</span>
          </div>
        </div>
      </div>

      <!-- Quick actions — Link wraps the whole card so the entire area is clickable -->
      <div class="quick-actions" style="margin-top:2rem;">
        <Link href="/user/reports/create" class="action-card">
          <div class="action-icon">🎫</div>
          <div class="action-title">Report Issue</div>
          <div class="action-description">Submit a new equipment issue</div>
        </Link>
        <Link href="/user/reports" class="action-card">
          <div class="action-icon">📋</div>
          <div class="action-title">My Reports</div>
          <div class="action-description">View all your submitted tickets</div>
        </Link>
        <Link href="/user/knowledge-base" class="action-card">
          <div class="action-icon">📚</div>
          <div class="action-title">Knowledge Base</div>
          <div class="action-description">Browse self-help articles</div>
        </Link>
        <Link href="/user/lab-status" class="action-card">
          <div class="action-icon">🖥️</div>
          <div class="action-title">Lab Status</div>
          <div class="action-description">Check which labs are operational</div>
        </Link>
      </div>

      <!-- Recent reports -->
      <div class="section-card">
        <div class="section-header">
          <h2 class="section-title">Recent Reports</h2>
          <Link href="/user/reports" class="view-all-link">View All →</Link>
        </div>

        <div class="reports-grid">
          <template v-if="reports.length">
            <!--
              Each ticket card is wrapped in a <Link> so the entire card
              is a client-side navigation — replaces the old $inertia.visit()
              @click approach, which used a deprecated Inertia API and required
              an extra div wrapper with cursor:pointer.
            -->
            <Link
              v-for="report in reports"
              :key="report.id"
              :href="`/user/reports/${report.id}`"
              class="ticket-card"
              style="display:block;text-decoration:none;"
            >
              <div class="ticket-header">
                <span class="ticket-id">{{ report.ticket_number }}</span>
                <span class="status-badge" :class="`status-${statusColor(report.status)}`">
                  {{ statusLabel(report.status) }}
                </span>
              </div>
              <div class="ticket-title">{{ report.title }}</div>
              <div class="ticket-meta">
                <span>{{ report.equipment?.lab?.name }}</span>
                <span>{{ report.equipment?.equipment_code }}</span>
                <span class="priority-badge" :class="`priority-${report.priority}`">
                  {{ report.priority.charAt(0).toUpperCase() + report.priority.slice(1) }}
                </span>
              </div>
            </Link>
          </template>

          <div v-else style="text-align:center;padding:3rem;color:#9ca3af;">
            <div style="font-size:2rem;margin-bottom:1rem;">📋</div>
            <p>
              No reports yet.
              <Link href="/user/reports/create" style="color:#2dd4bf;">Report your first issue →</Link>
            </p>
          </div>
        </div>
      </div>

    </div>
  </AppLayout>
</template>