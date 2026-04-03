<script setup>
// Pages/Admin/Tickets/Index.vue
// Path: resources/js/Pages/Admin/Tickets/Index.vue
// Mirrors: resources/views/admin/tickets/index.blade.php
//
// Props: tickets (paginator), stats, itStaff, labs, filters
// Quick-assign uses useForm() so it goes via Inertia XHR.
// Bulk-actions page navigation is a plain router.visit() with the ids query param.

import AppLayout from '../../../Layouts/AppLayout.vue'
import NavAdmin from '../../../Components/Nav/NavAdmin.vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import { ref, watch } from 'vue'

const props = defineProps({
  tickets: Object,
  stats:   Object,
  itStaff: Array,
  labs:    Array,
  filters: Object,
})

// ── Filters ───────────────────────────────────────────────────────────────────
const search   = ref(props.filters?.search   ?? '')
const status   = ref(props.filters?.status   ?? 'all')
const priority = ref(props.filters?.priority ?? 'all')
const lab      = ref(props.filters?.lab      ?? 'all')
const assigned = ref(props.filters?.assigned ?? 'all')

function applyFilters() {
  router.get('/admin/tickets', {
    search:   search.value   || undefined,
    status:   status.value   !== 'all' ? status.value   : undefined,
    priority: priority.value !== 'all' ? priority.value : undefined,
    lab:      lab.value      !== 'all' ? lab.value      : undefined,
    assigned: assigned.value !== 'all' ? assigned.value : undefined,
  }, { preserveState: true, replace: true })
}

let searchTimer = null
watch(search, () => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(applyFilters, 350)
})
watch([status, priority, lab, assigned], applyFilters)

// ── Row selection for bulk actions ────────────────────────────────────────────
const selectedIds   = ref([])
const selectAll     = ref(false)

function toggleSelectAll() {
  if (selectAll.value) {
    selectedIds.value = props.tickets.data.map(t => t.id)
  } else {
    selectedIds.value = []
  }
}

function updateSelectAll() {
  selectAll.value = props.tickets.data.length > 0 &&
    selectedIds.value.length === props.tickets.data.length
}

function clearSelection() {
  selectedIds.value = []
  selectAll.value   = false
}

function goToBulkActions() {
  if (!selectedIds.value.length) { alert('Please select at least one ticket'); return }
  router.visit('/admin/tickets/bulk?ids=' + selectedIds.value.join(','))
}

// ── Quick-assign modal ────────────────────────────────────────────────────────
const showQuickAssign = ref(false)

const quickAssignForm = useForm({
  action:      'assign',
  ticket_ids:  [],
  assigned_to: '',
})

function openQuickAssign() {
  if (!selectedIds.value.length) { alert('Please select at least one ticket'); return }
  quickAssignForm.ticket_ids  = [...selectedIds.value]
  quickAssignForm.assigned_to = ''
  showQuickAssign.value = true
}

function submitQuickAssign() {
  quickAssignForm.post('/admin/tickets/bulk-update', {
    onSuccess: () => { showQuickAssign.value = false; clearSelection() },
  })
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function statusColor(s) {
  return { new: 'info', assigned: 'assigned', 'in-progress': 'progress', resolved: 'resolved', closed: 'resolved' }[s] ?? 'secondary'
}

function statusLabel(s) {
  return s.replace('-', ' ').replace(/\b\w/g, c => c.toUpperCase())
}

function roleLabel(role) {
  return role.replace('-', ' ').replace(/\b\w/g, c => c.toUpperCase())
}

function staffFullName(staff) {
  return `${staff.first_name} ${staff.last_name}`
}
</script>

<template>
  <AppLayout>
    <template #nav><NavAdmin /></template>

    <div class="container">

      <!-- Header -->
      <div class="page-header">
        <div>
          <h1>Ticket Management</h1>
          <p style="color:#9ca3af;">Oversee all support tickets and manage assignments</p>
        </div>
        <div class="header-actions">
          <Link href="/admin/transactions" class="btn btn-secondary">📜 Ticket History</Link>
          <button type="button" class="btn btn-primary" @click="goToBulkActions">⚡ Bulk Actions</button>
        </div>
      </div>

      <!-- Stats -->
      <div class="system-stats">
        <div class="stat-card">
          <div class="stat-header"><span class="stat-label">Total Tickets</span><span class="stat-icon">📋</span></div>
          <div class="stat-value">{{ stats.total }}</div>
          <div class="stat-detail">All time</div>
        </div>
        <div class="stat-card">
          <div class="stat-header"><span class="stat-label">Open Tickets</span><span class="stat-icon">🔔</span></div>
          <div class="stat-value">{{ stats.open }}</div>
          <div class="stat-detail">Needs attention</div>
        </div>
        <div class="stat-card">
          <div class="stat-header"><span class="stat-label">Unassigned</span><span class="stat-icon">⏳</span></div>
          <div class="stat-value">{{ stats.unassigned }}</div>
          <div class="stat-detail">Waiting for assignment</div>
        </div>
        <div class="stat-card">
          <div class="stat-header"><span class="stat-label">In Progress</span><span class="stat-icon">⚙️</span></div>
          <div class="stat-value">{{ stats.in_progress }}</div>
          <div class="stat-detail">Being worked on</div>
        </div>
        <div class="stat-card">
          <div class="stat-header"><span class="stat-label">High Priority</span><span class="stat-icon">🔴</span></div>
          <div class="stat-value">{{ stats.high_priority }}</div>
          <div class="stat-detail">Urgent attention needed</div>
        </div>
        <div class="stat-card">
          <div class="stat-header"><span class="stat-label">Resolved</span><span class="stat-icon">✅</span></div>
          <div class="stat-value">{{ stats.resolved }}</div>
          <div class="stat-detail">Completed tickets</div>
        </div>
      </div>

      <!-- Filters -->
      <div class="filter-bar">
        <div class="filter-row">
          <div class="search-box">
            <span class="search-icon">🔍</span>
            <input v-model="search" type="text" placeholder="Search by ticket ID, title, reporter, or location...">
          </div>
          <div class="filter-group">
            <span class="filter-label">Status:</span>
            <select v-model="status">
              <option value="all">All Status</option>
              <option value="new">New</option>
              <option value="assigned">Assigned</option>
              <option value="in-progress">In Progress</option>
              <option value="resolved">Resolved</option>
              <option value="closed">Closed</option>
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
            <span class="filter-label">Assigned:</span>
            <select v-model="assigned">
              <option value="all">All</option>
              <option value="unassigned">Unassigned</option>
              <option v-for="staff in itStaff" :key="staff.id" :value="String(staff.id)">
                {{ staffFullName(staff) }}
              </option>
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

      <!-- Bulk actions bar -->
      <div v-if="selectedIds.length" class="bulk-actions">
        <span class="bulk-text">{{ selectedIds.length }} ticket{{ selectedIds.length !== 1 ? 's' : '' }} selected</span>
        <button type="button" class="btn btn-white" @click="openQuickAssign">Quick Assign</button>
        <button type="button" class="btn btn-white" @click="goToBulkActions">More Actions</button>
        <button type="button" class="btn btn-secondary" @click="clearSelection">Clear</button>
      </div>

      <!-- Table -->
      <div class="table-container">
        <table>
          <thead>
            <tr>
              <th style="width:40px;">
                <input
                  type="checkbox"
                  v-model="selectAll"
                  @change="toggleSelectAll"
                  class="ticket-checkbox"
                >
              </th>
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
                <td>
                  <input
                    type="checkbox"
                    class="ticket-checkbox ticket-select"
                    :value="ticket.id"
                    v-model="selectedIds"
                    @change="updateSelectAll"
                  >
                </td>
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
                    {{ ticket.assigned_to?.first_name }} {{ ticket.assigned_to?.last_name }}
                  </span>
                  <span v-else style="color:#9ca3af;">Unassigned</span>
                </td>
                <td>{{ new Date(ticket.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) }}</td>
                <td>
                  <div class="action-menu">
                    <Link :href="`/admin/tickets/${ticket.id}`" class="action-btn">View</Link>
                    <Link :href="`/admin/tickets/${ticket.id}/edit`" class="action-btn">Edit</Link>
                  </div>
                </td>
              </tr>
            </template>
            <tr v-else>
              <td colspan="10" style="text-align:center;padding:3rem;color:#9ca3af;">
                <template v-if="filters.search || filters.status || filters.priority || filters.lab || filters.assigned">
                  <div style="font-size:2rem;margin-bottom:1rem;">🔍</div>
                  <h3 style="color:#d1d5db;">No tickets found</h3>
                  <p>Try adjusting your filters or search terms</p>
                  <Link href="/admin/tickets" class="btn btn-primary" style="margin-top:1rem;">Clear Filters</Link>
                </template>
                <template v-else>
                  <div style="font-size:2rem;margin-bottom:1rem;">📋</div>
                  <h3 style="color:#d1d5db;">No tickets yet</h3>
                  <p>All clear! No tickets in the system.</p>
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

    </div>

    <!-- Quick Assign Modal -->
    <div v-if="showQuickAssign" class="modal-overlay active" @click.self="showQuickAssign = false">
      <div class="modal">
        <h2 class="modal-header">Quick Assign</h2>
        <form @submit.prevent="submitQuickAssign">
          <div class="form-group">
            <label>Assign To *</label>
            <select v-model="quickAssignForm.assigned_to" required>
              <option value="">Select Technician</option>
              <option v-for="staff in itStaff" :key="staff.id" :value="staff.id">
                {{ staffFullName(staff) }} ({{ roleLabel(staff.role) }})
              </option>
            </select>
          </div>
          <div class="modal-actions">
            <button type="submit" class="btn btn-primary" :disabled="quickAssignForm.processing">
              {{ quickAssignForm.processing ? 'Assigning…' : 'Assign Selected Tickets' }}
            </button>
            <button type="button" class="btn btn-cancel" @click="showQuickAssign = false">Cancel</button>
          </div>
        </form>
      </div>
    </div>

  </AppLayout>
</template>