<script setup>
// Pages/IT/Tickets/Edit.vue
// Path: resources/js/Pages/IT/Tickets/Edit.vue
// Mirrors: resources/views/it/tickets/edit.blade.php
//
// IT staff can assign to themselves only; admins can assign to anyone.

import AppLayout from '../../../Layouts/AppLayout.vue'
import NavIT from '../../../Components/Nav/NavIT.vue'
import { Link, useForm, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({
  ticket:  Object,
  itStaff: Array,
})

const authUser = computed(() => usePage().props.auth.user)
const isAdmin  = computed(() => authUser.value?.role === 'admin')

const form = useForm({
  _method:     'PUT',
  status:      props.ticket.status,
  priority:    props.ticket.priority,
  assigned_to: props.ticket.assigned_to ?? '',
})

function submit() {
  form.post(`/it/tickets/${props.ticket.id}`)
}

function quickUpdate(status) {
  useForm({
    _method:     'PUT',
    status:      status,
    priority:    props.ticket.priority,
    assigned_to: props.ticket.assigned_to ?? '',
  }).post(`/it/tickets/${props.ticket.id}`)
}

function assignToSelf() {
  useForm({}).post(`/it/tickets/${props.ticket.id}/assign-self`)
}

function statusColor(s) {
  return { new: 'info', assigned: 'assigned', 'in-progress': 'progress', resolved: 'resolved', closed: 'resolved' }[s] ?? 'secondary'
}

function statusLabel(s) {
  return s.replace('-', ' ').replace(/\b\w/g, c => c.toUpperCase())
}

function roleLabel(r) {
  return r.replace('-', ' ').replace(/\b\w/g, c => c.toUpperCase())
}

function equipmentStatusClass(s) {
  return s === 'operational' ? 'status-active' : s === 'has-issue' ? 'status-info' : 'status-warning'
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
      <Link :href="`/it/tickets/${ticket.id}`" class="back-btn">← Back to Ticket</Link>

      <div class="page-header">
        <h1>Edit Ticket</h1>
        <p>Update ticket status, priority, and assignment</p>
      </div>

      <div class="content-layout">

        <!-- Left: form -->
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
                    <!-- Admins can assign to anyone; IT staff can only assign to themselves -->
                    <template v-if="isAdmin">
                      <option v-for="staff in itStaff" :key="staff.id" :value="staff.id">
                        {{ staff.first_name }} {{ staff.last_name }} ({{ roleLabel(staff.role) }})
                      </option>
                    </template>
                    <template v-else>
                      <option :value="authUser.id">
                        Myself ({{ authUser.first_name }} {{ authUser.last_name }})
                      </option>
                    </template>
                  </select>
                  <p class="help-text">
                    {{ isAdmin ? 'Assign this ticket to an IT technician' : 'You can assign this ticket to yourself' }}
                  </p>
                  <span v-if="form.errors.assigned_to" class="text-danger">{{ form.errors.assigned_to }}</span>
                </div>

              </div>

              <div class="action-buttons">
                <button type="submit" class="btn btn-primary" :disabled="form.processing">
                  {{ form.processing ? 'Updating…' : 'Update Ticket & Notify User' }}
                </button>
                <Link :href="`/it/tickets/${ticket.id}`" class="btn btn-secondary">Cancel</Link>
              </div>
            </form>
          </div>

          <!-- Quick actions -->
          <div class="card" style="margin-top:1.5rem;">
            <h2 class="card-title">Quick Actions</h2>
            <p style="color:#9ca3af;margin-bottom:1rem;">Apply common status changes with one click</p>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;">
              <button v-if="ticket.status !== 'in-progress'" type="button" class="btn btn-secondary" style="width:100%;" @click="quickUpdate('in-progress')">
                Mark In Progress
              </button>
              <button v-if="ticket.status !== 'resolved'" type="button" class="btn btn-secondary" style="width:100%;" @click="quickUpdate('resolved')">
                Mark Resolved
              </button>
              <button
                v-if="!ticket.assigned_to || ticket.assigned_to !== authUser.id"
                type="button"
                class="btn btn-secondary"
                style="width:100%;"
                @click="assignToSelf"
              >
                Assign to Me
              </button>
            </div>
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
                <span class="info-value">{{ ticket.assigned_to ? `${ticket.assigned_to.first_name} ${ticket.assigned_to.last_name}` : 'Unassigned' }}</span>
              </div>
              <div class="info-item">
                <span class="info-label">Created</span>
                <span class="info-value">{{ diffForHumans(ticket.created_at) }}</span>
              </div>
            </div>
          </div>

          <div class="card" style="margin-top:1.5rem;">
            <h2 class="card-title">Problem Description</h2>
            <p style="color:#d1d5db;line-height:1.6;">
              {{ ticket.description.length > 200 ? ticket.description.substring(0, 200) + '…' : ticket.description }}
            </p>
            <Link :href="`/it/tickets/${ticket.id}`" class="btn btn-secondary" style="margin-top:1rem;">View Full Details</Link>
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
            </div>
          </div>

        </div>
      </div>
    </div>
  </AppLayout>
</template>