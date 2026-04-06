<script setup>
// Pages/IT/Assignments/Index.vue
// Path: resources/js/Pages/IT/Assignments/Index.vue
// Mirrors: resources/views/it/assignments/index.blade.php

import AppLayout from '../../../Layouts/AppLayout.vue'
import NavIT from '../../../Components/Nav/NavIT.vue'
import { Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'

const props = defineProps({
  tickets: Object,
  stats:   Object,
})

// ── Status filter tabs ────────────────────────────────────────────────────────
const status = ref('')   // '' = all

function applyStatus(val) {
  status.value = val
  router.get('/it/assignments', val ? { status: val } : {}, {
    preserveState: true,
    replace: true,
  })
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function statusColor(s) {
  return { new: 'info', assigned: 'assigned', 'in-progress': 'progress', resolved: 'resolved', closed: 'resolved' }[s] ?? 'secondary'
}

function statusLabel(s) {
  return s.replace('-', ' ').replace(/\b\w/g, c => c.toUpperCase())
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

      <div class="page-header">
        <h1>My Assignments</h1>
        <p>Tickets currently assigned to you</p>
      </div>

      <!-- Stats -->
      <div class="stats-row">
        <div class="stat-card">
          <div class="stat-label">Total Assigned</div>
          <div class="stat-value">{{ stats.total_assigned }}</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">In Progress</div>
          <div class="stat-value">{{ stats.in_progress }}</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">High Priority</div>
          <div class="stat-value">{{ stats.high_priority }}</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Completed Today</div>
          <div class="stat-value">{{ stats.completed_today }}</div>
        </div>
      </div>

      <!-- Filter tabs -->
      <div class="filter-tabs" style="margin-bottom:1.5rem;">
        <button type="button" class="tab" :class="{ active: status === '' }" @click="applyStatus('')">
          All Assignments ({{ stats.total_assigned }})
        </button>
        <button type="button" class="tab" :class="{ active: status === 'in-progress' }" @click="applyStatus('in-progress')">
          In Progress ({{ stats.in_progress }})
        </button>
        <button type="button" class="tab" :class="{ active: status === 'active' }" @click="applyStatus('active')">
          High Priority ({{ stats.high_priority }})
        </button>
      </div>

      <!-- Tickets grid -->
      <div class="tickets-grid">
        <template v-if="tickets.data.length">
          <Link
            v-for="ticket in tickets.data"
            :key="ticket.id"
            :href="`/it/assignments/${ticket.id}`"
            class="ticket-card"
            :class="ticket.priority === 'high' ? 'priority-high' : ''"
            style="display:block;text-decoration:none;"
          >
            <div class="ticket-header">
              <div>
                <div class="ticket-id">{{ ticket.ticket_number }}</div>
                <h3 class="ticket-title">{{ ticket.title }}</h3>
              </div>
              <span class="priority-badge" :class="`priority-${ticket.priority}`">
                {{ ticket.priority === 'high' ? '🔴' : ticket.priority === 'medium' ? '🟡' : '🟢' }}
                {{ ticket.priority.charAt(0).toUpperCase() + ticket.priority.slice(1) }}
              </span>
            </div>

            <div class="ticket-meta">
              <div class="meta-item"><span>👤</span> {{ ticket.reporter?.first_name }} {{ ticket.reporter?.last_name }}</div>
              <div class="meta-item"><span>📍</span> {{ ticket.equipment?.lab?.name }}, {{ ticket.equipment?.equipment_code }}</div>
              <div class="meta-item"><span>🕒</span> {{ diffForHumans(ticket.created_at) }}</div>
              <div class="meta-item"><span>🏷️</span> {{ ticket.category.charAt(0).toUpperCase() + ticket.category.slice(1) }}</div>
            </div>

            <p class="ticket-description" style="color:#9ca3af;font-size:0.9rem;margin:0.75rem 0;line-height:1.5;">
              {{ ticket.description?.substring(0, 150) }}{{ ticket.description?.length > 150 ? '…' : '' }}
            </p>

            <div class="ticket-footer">
              <span class="status-badge" :class="`status-${statusColor(ticket.status)}`">
                {{ statusLabel(ticket.status) }}
              </span>
              <div class="ticket-actions" @click.prevent>
                <Link :href="`/it/assignments/${ticket.id}`" class="action-btn">View Details</Link>
                <Link :href="`/it/assignments/${ticket.id}/edit`" class="action-btn">Update Status</Link>
              </div>
            </div>
          </Link>
        </template>

        <div v-else class="empty-state">
          <div class="empty-icon">📋</div>
          <h3>No assignments yet</h3>
          <p>You don't have any tickets assigned to you at the moment</p>
          <Link href="/it/tickets" class="btn btn-primary" style="margin-top:1rem;">View Ticket Queue</Link>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="tickets.last_page > 1" class="pagination" style="margin-top:1.5rem;">
        <div class="page-info">
          Showing {{ tickets.from ?? 0 }}–{{ tickets.to ?? 0 }} of {{ tickets.total }} tickets
        </div>
        <div class="page-controls">
          <button class="page-btn" :disabled="!tickets.prev_page_url" @click="router.get(tickets.prev_page_url)">← Previous</button>
          <button
            v-for="p in tickets.last_page" :key="p"
            class="page-btn" :class="{ active: p === tickets.current_page }"
            @click="router.get(tickets.path + '?page=' + p)"
          >{{ p }}</button>
          <button class="page-btn" :disabled="!tickets.next_page_url" @click="router.get(tickets.next_page_url)">Next →</button>
        </div>
      </div>

    </div>
  </AppLayout>
</template>