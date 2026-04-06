<script setup>
// Pages/IT/Tickets/Index.vue
// Path: resources/js/Pages/IT/Tickets/Index.vue
// Mirrors: resources/views/it/tickets/index.blade.php

import AppLayout from '../../../Layouts/AppLayout.vue'
import NavIT from '../../../Components/Nav/NavIT.vue'
import { Link, router, useForm, usePage } from '@inertiajs/vue3'
import { ref, watch, computed } from 'vue'

const props = defineProps({
  tickets: Object,
  stats:   Object,
  itStaff: Array,
  labs:    Array,
  filters: Object,
})

const authUser = computed(() => usePage().props.auth.user)

// ── Filters ───────────────────────────────────────────────────────────────────
const search   = ref(props.filters?.search   ?? '')
const status   = ref(props.filters?.status   ?? 'all')
const priority = ref(props.filters?.priority ?? 'all')
const lab      = ref(props.filters?.lab      ?? 'all')

function applyFilters() {
  router.get('/it/tickets', {
    search:   search.value   || undefined,
    status:   status.value   !== 'all' ? status.value   : undefined,
    priority: priority.value !== 'all' ? priority.value : undefined,
    lab:      lab.value      !== 'all' ? lab.value      : undefined,
  }, { preserveState: true, replace: true })
}

let searchTimer = null
watch(search, () => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(applyFilters, 350)
})
watch([status, priority, lab], applyFilters)

// ── Assign to self ────────────────────────────────────────────────────────────
function assignToSelf(id) {
  useForm({}).post(`/it/tickets/${id}/assign-self`)
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
        <h1>Ticket Queue</h1>
        <p>View available tickets and manage your assignments</p>
      </div>

      <!-- Stats -->
      <div class="stats-row">
        <div class="stat-card">
          <div class="stat-label">Open Tickets</div>
          <div class="stat-value">{{ stats.open }}</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Assigned</div>
          <div class="stat-value">{{ stats.assigned }}</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">In Progress</div>
          <div class="stat-value">{{ stats.in_progress }}</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">High Priority</div>
          <div class="stat-value">{{ stats.high_priority }}</div>
        </div>
      </div>

      <!-- Info banner -->
      <div style="background:rgba(59,130,246,0.1);border:1px solid rgba(59,130,246,0.3);padding:1rem;border-radius:8px;margin-bottom:1.5rem;">
        <div style="display:flex;align-items:center;gap:0.75rem;color:#60a5fa;">
          <span style="font-size:1.5rem;">ℹ️</span>
          <div>
            <strong>IT Support View:</strong> You can view all tickets and assign unassigned tickets to yourself.
            <Link href="/it/assignments" style="color:#2dd4bf;text-decoration:none;font-weight:600;"> View My Assignments →</Link>
          </div>
        </div>
      </div>

      <!-- Filters -->
      <div class="filter-bar">
        <div class="filter-row">
          <div class="search-box">
            <span class="search-icon">🔍</span>
            <input v-model="search" type="text" placeholder="Search tickets by ID, title, or location...">
          </div>
          <div class="filter-group">
            <span class="filter-label">Status:</span>
            <select v-model="status">
              <option value="all">All Status</option>
              <option value="new">New</option>
              <option value="assigned">Assigned</option>
              <option value="in-progress">In Progress</option>
              <option value="resolved">Resolved</option>
            </select>
          </div>
          <div class="filter-group">
            <span class="filter-label">Priority:</span>
            <select v-model="priority">
              <option value="all">All Priority</option>
              <option value="high">High</option>
              <option value="medium">Medium</option>
              <option value="low">Low</option>
            </select>
          </div>
          <div class="filter-group">
            <span class="filter-label">Lab:</span>
            <select v-model="lab">
              <option value="all">All Labs</option>
              <option v-for="l in labs" :key="l" :value="l">{{ l }}</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Table -->
      <div class="table-container">
        <table>
          <thead>
            <tr>
              <th>Ticket ID</th>
              <th>Issue</th>
              <th>Reporter</th>
              <th>Location</th>
              <th>Status</th>
              <th>Priority</th>
              <th>Assigned To</th>
              <th>Created</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <template v-if="tickets.data.length">
              <tr v-for="ticket in tickets.data" :key="ticket.id">
                <td class="ticket-id">{{ ticket.ticket_number }}</td>
                <td>
                  <strong>{{ ticket.title }}</strong><br>
                  <small style="color:#9ca3af;">{{ ticket.category.charAt(0).toUpperCase() + ticket.category.slice(1) }}</small>
                </td>
                <td>{{ ticket.reporter?.first_name }} {{ ticket.reporter?.last_name }}</td>
                <td>{{ ticket.equipment?.lab?.name }}, {{ ticket.equipment?.equipment_code }}</td>
                <td>
                  <span class="status-badge" :class="`status-${statusColor(ticket.status)}`">
                    {{ statusLabel(ticket.status) }}
                  </span>
                </td>
                <td>
                  <span class="priority-badge" :class="`priority-${ticket.priority}`">
                    {{ ticket.priority === 'high' ? '🔴' : ticket.priority === 'medium' ? '🟡' : '🟢' }}
                    {{ ticket.priority.charAt(0).toUpperCase() + ticket.priority.slice(1) }}
                  </span>
                </td>
                <td>
                  <span v-if="ticket.assigned_to">
                    <span v-if="ticket.assigned_to === authUser.id" style="color:#2dd4bf;font-weight:600;">You</span>
                    <span v-else>{{ ticket.assigned_to?.first_name }} {{ ticket.assigned_to?.last_name }}</span>
                  </span>
                  <span v-else style="color:#9ca3af;">Unassigned</span>
                </td>
                <td>{{ diffForHumans(ticket.created_at) }}</td>
                <td>
                  <div class="action-menu">
                    <Link :href="`/it/tickets/${ticket.id}`" class="action-btn">View</Link>
                    <template v-if="ticket.assigned_to === authUser.id">
                      <Link :href="`/it/assignments/${ticket.id}/edit`" class="action-btn" style="color:#2dd4bf;">Update</Link>
                    </template>
                    <template v-else-if="!ticket.assigned_to">
                      <button type="button" class="action-btn" style="color:#2dd4bf;" @click="assignToSelf(ticket.id)">
                        Assign to Me
                      </button>
                    </template>
                  </div>
                </td>
              </tr>
            </template>
            <tr v-else>
              <td colspan="9" style="text-align:center;padding:3rem;color:#9ca3af;">
                <template v-if="filters.search || filters.status || filters.priority || filters.lab">
                  <div style="font-size:2rem;margin-bottom:1rem;">🔍</div>
                  <h3 style="color:#d1d5db;">No tickets found</h3>
                  <p>Try adjusting your filters or search terms</p>
                  <Link href="/it/tickets" class="btn btn-primary" style="margin-top:1rem;">Clear Filters</Link>
                </template>
                <template v-else>
                  <div style="font-size:2rem;margin-bottom:1rem;">📋</div>
                  <h3 style="color:#d1d5db;">No tickets yet</h3>
                  <p>All clear! No tickets in the queue.</p>
                </template>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Pagination -->
        <div v-if="tickets.last_page > 1" class="pagination">
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

      <!-- Quick stats panel -->
      <div style="margin-top:2rem;display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:1rem;">
        <div style="background:rgba(45,212,191,0.1);border:1px solid rgba(45,212,191,0.3);padding:1.5rem;border-radius:8px;">
          <h3 style="color:#2dd4bf;margin-bottom:0.5rem;font-size:1rem;">My Active Tickets</h3>
          <div style="font-size:2rem;font-weight:bold;color:#ffffff;">{{ stats.assigned }}</div>
          <Link href="/it/assignments" style="color:#2dd4bf;text-decoration:none;font-size:0.9rem;">View My Assignments →</Link>
        </div>
        <div style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);padding:1.5rem;border-radius:8px;">
          <h3 style="color:#ef4444;margin-bottom:0.5rem;font-size:1rem;">High Priority</h3>
          <div style="font-size:2rem;font-weight:bold;color:#ffffff;">{{ stats.high_priority }}</div>
          <Link href="/it/tickets?priority=high" style="color:#ef4444;text-decoration:none;font-size:0.9rem;">View High Priority →</Link>
        </div>
      </div>

    </div>
  </AppLayout>
</template>