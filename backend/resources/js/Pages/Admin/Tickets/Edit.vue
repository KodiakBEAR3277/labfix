<script setup>
// Pages/Admin/Tickets/Edit.vue
// Path: resources/js/Pages/Admin/Tickets/Edit.vue
// Mirrors: resources/views/admin/tickets/edit.blade.php
//
// Main form + quick-status action buttons all use useForm() with _method:PUT.
// Delete button uses router.delete().

import AppLayout from '../../../Layouts/AppLayout.vue'
import NavAdmin from '../../../Components/Nav/NavAdmin.vue'
import { Link, useForm, router } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({
  ticket:  Object,
  itStaff: Array,
})

// ── Main edit form ─────────────────────────────────────────────────────────────
const form = useForm({
  _method:     'PUT',
  status:      props.ticket.status,
  priority:    props.ticket.priority,
  assigned_to: props.ticket.assigned_to ?? '',
})

function submit() {
  form.post(`/admin/tickets/${props.ticket.id}`)
}

// ── Quick-action helper — creates a separate form per action so each button
//    submits independently without touching the main form's state.
function quickUpdate(status) {
  useForm({
    _method:     'PUT',
    status:      status,
    priority:    props.ticket.priority,
    assigned_to: props.ticket.assigned_to ?? '',
  }).post(`/admin/tickets/${props.ticket.id}`)
}

function deleteTicket() {
  if (confirm('Are you sure you want to delete this ticket? This action cannot be undone!')) {
    router.delete(`/admin/tickets/${props.ticket.id}`)
  }
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

function diffForHumans(d) {
  if (!d) return '—'
  const seconds = Math.floor((Date.now() - new Date(d)) / 1000)
  if (seconds < 60)   return `${seconds}s ago`
  if (seconds < 3600) return `${Math.floor(seconds/60)}m ago`
  if (seconds < 86400) return `${Math.floor(seconds/3600)}h ago`
  return `${Math.floor(seconds/86400)}d ago`
}

const equipmentStatusClass = (s) =>
  s === 'operational' ? 'status-active' : s === 'has-issue' ? 'status-info' : 'status-warning'
</script>

<template>
  <AppLayout>
    <template #nav><NavAdmin /></template>

    <div class="container">
      <Link :href="`/admin/tickets/${ticket.id}`" class="back-btn">← Back to Ticket</Link>

      <div class="page-header">
        <h1>Edit Ticket</h1>
        <p>Update ticket status, priority, and assignment</p>
      </div>

      <div class="content-layout">

        <!-- Left column -->
        <div>

          <!-- Ticket summary (read-only) -->
          <div class="card">
            <h2 class="card-title">Ticket Summary</h2>
            <div class="info-list">
              <div class="info-item"><span class="info-label">Ticket Number</span><span class="info-value">{{ ticket.ticket_number }}</span></div>
              <div class="info-item"><span class="info-label">Title</span><span class="info-value">{{ ticket.title }}</span></div>
              <div class="info-item"><span class="info-label">Reporter</span><span class="info-value">{{ ticket.reporter?.first_name }} {{ ticket.reporter?.last_name }}</span></div>
              <div class="info-item"><span class="info-label">Location</span><span class="info-value">{{ ticket.equipment?.lab?.name }}, {{ ticket.equipment?.equipment_code }}</span></div>
              <div class="info-item"><span class="info-label">Category</span><span class="info-value">{{ ticket.category.charAt(0).toUpperCase() + ticket.category.slice(1) }}</span></div>
            </div>
          </div>

          <!-- Edit form -->
          <div class="card" style="margin-top:1.5rem;">
            <h2 class="card-title">Update Ticket Details</h2>
            <form @submit.prevent="submit">
              <div class="form-section">

                <div class="form-group">
                  <label>Status *</label>
                  <select v-model="form.status" required>
                    <option value="new">New</option>
                    <option value="assigned">Assigned</option>
                    <option value="in-progress">In Progress</option>
                    <option value="resolved">Resolved</option>
                    <option value="closed">Closed</option>
                  </select>
                  <p class="help-text">Update the current status of this ticket</p>
                  <span v-if="form.errors.status" class="text-danger">{{ form.errors.status }}</span>
                </div>

                <div class="form-group">
                  <label>Priority *</label>
                  <select v-model="form.priority" required>
                    <option value="low">Low</option>
                    <option value="medium">Medium</option>
                    <option value="high">High</option>
                  </select>
                  <p class="help-text">Adjust priority based on urgency and impact</p>
                  <span v-if="form.errors.priority" class="text-danger">{{ form.errors.priority }}</span>
                </div>

                <div class="form-group">
                  <label>Assign To</label>
                  <select v-model="form.assigned_to">
                    <option value="">Unassigned</option>
                    <option v-for="staff in itStaff" :key="staff.id" :value="staff.id">
                      {{ staffFullName(staff) }} ({{ roleLabel(staff.role) }})
                    </option>
                  </select>
                  <p class="help-text">Assign this ticket to any IT technician or admin</p>
                  <span v-if="form.errors.assigned_to" class="text-danger">{{ form.errors.assigned_to }}</span>
                </div>

              </div>

              <div class="action-buttons">
                <button type="submit" class="btn btn-primary" :disabled="form.processing">
                  {{ form.processing ? 'Updating…' : 'Update Ticket & Notify User' }}
                </button>
                <Link :href="`/admin/tickets/${ticket.id}`" class="btn btn-secondary">Cancel</Link>
              </div>
            </form>
          </div>

          <!-- Quick actions -->
          <div class="card" style="margin-top:1.5rem;">
            <h2 class="card-title">Quick Actions</h2>
            <p style="color:#9ca3af;margin-bottom:1rem;">Apply common status changes with one click</p>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;">
              <button v-if="ticket.status !== 'assigned' && !ticket.assigned_to" type="button" class="btn btn-secondary" style="width:100%;" @click="quickUpdate('assigned')">
                Assign to Me
              </button>
              <button v-if="ticket.status !== 'in-progress'" type="button" class="btn btn-secondary" style="width:100%;" @click="quickUpdate('in-progress')">
                Mark In Progress
              </button>
              <button v-if="ticket.status !== 'resolved'" type="button" class="btn btn-secondary" style="width:100%;" @click="quickUpdate('resolved')">
                Mark Resolved
              </button>
              <button v-if="ticket.status !== 'closed'" type="button" class="btn btn-secondary" style="width:100%;" @click="quickUpdate('closed')">
                Close Ticket
              </button>
            </div>
          </div>

          <!-- Danger zone -->
          <div class="card" style="margin-top:1.5rem;border-color:rgba(239,68,68,0.3);">
            <h2 class="card-title" style="color:#ef4444;">Danger Zone</h2>
            <p style="color:#9ca3af;margin-bottom:1rem;">Irreversible actions — proceed with caution</p>
            <button type="button" class="btn btn-danger" @click="deleteTicket">
              Delete This Ticket Permanently
            </button>
          </div>

        </div>

        <!-- Sidebar -->
        <div>

          <div class="card">
            <h2 class="card-title">Current Status</h2>
            <div class="info-list">
              <div class="info-item">
                <span class="info-label">Status</span>
                <span class="info-value"><span class="status-badge" :class="`status-${statusColor(ticket.status)}`">{{ statusLabel(ticket.status) }}</span></span>
              </div>
              <div class="info-item">
                <span class="info-label">Priority</span>
                <span class="info-value" :class="`priority-${ticket.priority}`">{{ ticket.priority.charAt(0).toUpperCase() + ticket.priority.slice(1) }}</span>
              </div>
              <div class="info-item">
                <span class="info-label">Assigned To</span>
                <span class="info-value">
                  {{ ticket.assigned_to ? `${ticket.assigned_to.first_name} ${ticket.assigned_to.last_name}` : 'Unassigned' }}
                </span>
              </div>
              <div class="info-item">
                <span class="info-label">Created</span>
                <span class="info-value">{{ diffForHumans(ticket.created_at) }}</span>
              </div>
              <div v-if="ticket.assigned_at" class="info-item">
                <span class="info-label">Assigned</span>
                <span class="info-value">{{ diffForHumans(ticket.assigned_at) }}</span>
              </div>
              <div v-if="ticket.resolved_at" class="info-item">
                <span class="info-label">Resolved</span>
                <span class="info-value">{{ diffForHumans(ticket.resolved_at) }}</span>
              </div>
            </div>
          </div>

          <div class="card" style="margin-top:1.5rem;">
            <h2 class="card-title">Problem Description</h2>
            <p style="color:#d1d5db;line-height:1.6;">
              {{ ticket.description.length > 200 ? ticket.description.substring(0, 200) + '…' : ticket.description }}
            </p>
            <Link :href="`/admin/tickets/${ticket.id}`" class="btn btn-secondary" style="margin-top:1rem;">View Full Details</Link>
          </div>

          <div class="card" style="margin-top:1.5rem;">
            <h2 class="card-title">Equipment Status</h2>
            <div class="info-list">
              <div class="info-item"><span class="info-label">Equipment</span><span class="info-value">{{ ticket.equipment?.equipment_code }}</span></div>
              <div class="info-item"><span class="info-label">Type</span><span class="info-value">{{ ticket.equipment?.type?.charAt(0).toUpperCase() + ticket.equipment?.type?.slice(1) }}</span></div>
              <div class="info-item">
                <span class="info-label">Status</span>
                <span class="info-value">
                  <span class="status-badge" :class="equipmentStatusClass(ticket.equipment?.status)">
                    {{ statusLabel(ticket.equipment?.status ?? '') }}
                  </span>
                </span>
              </div>
              <div class="info-item"><span class="info-label">Lab</span><span class="info-value">{{ ticket.equipment?.lab?.name }}</span></div>
            </div>
          </div>

          <div class="card" style="margin-top:1.5rem;">
            <h2 class="card-title">Reporter</h2>
            <div class="reporter-info">
              <div class="reporter-avatar">{{ ticket.reporter?.first_name?.[0] }}{{ ticket.reporter?.last_name?.[0] }}</div>
              <div class="reporter-name">{{ ticket.reporter?.first_name }} {{ ticket.reporter?.last_name }}</div>
              <div class="reporter-role">{{ statusLabel(ticket.reporter?.role ?? '') }}</div>
              <p v-if="ticket.reporter?.email" style="color:#9ca3af;margin-top:0.5rem;font-size:0.9rem;">{{ ticket.reporter.email }}</p>
            </div>
          </div>

        </div>
      </div>
    </div>
  </AppLayout>
</template>