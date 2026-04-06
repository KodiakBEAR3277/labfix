<script setup>
// Pages/IT/Assignments/Edit.vue
// Path: resources/js/Pages/IT/Assignments/Edit.vue
// Mirrors: resources/views/it/assignments/edit.blade.php
//
// IT staff can only update status and priority on their own assignments.
// No reassignment — that's admin-only via IT/Tickets/Edit.vue.

import AppLayout from '../../../Layouts/AppLayout.vue'
import NavIT from '../../../Components/Nav/NavIT.vue'
import { Link, useForm } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({
  ticket: Object,
})

const form = useForm({
  _method:  'PUT',
  status:   props.ticket.status,
  priority: props.ticket.priority,
})

function submit() {
  form.post(`/it/assignments/${props.ticket.id}`)
}

// Quick one-click actions — separate useForm per action so they don't
// interfere with the main form's state
function quickUpdate(status) {
  useForm({
    _method:  'PUT',
    status:   status,
    priority: props.ticket.priority,
  }).post(`/it/assignments/${props.ticket.id}`)
}

function escalatePriority() {
  useForm({
    _method:  'PUT',
    status:   props.ticket.status,
    priority: 'high',
  }).post(`/it/assignments/${props.ticket.id}`)
}

// ── Computed ──────────────────────────────────────────────────────────────────
const assignedTransaction = computed(() =>
  props.ticket.transactions?.find(tx => tx.action === 'assigned') ?? null
)

// ── Helpers ───────────────────────────────────────────────────────────────────
function statusColor(s) {
  return { new: 'info', assigned: 'assigned', 'in-progress': 'progress', resolved: 'resolved', closed: 'resolved' }[s] ?? 'secondary'
}

function statusLabel(s) {
  return s.replace('-', ' ').replace(/\b\w/g, c => c.toUpperCase())
}

function equipmentStatusClass(s) {
  return s === 'operational' ? 'status-active' : s === 'has-issue' ? 'status-info' : 'status-warning'
}

function formatDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' })
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
      <Link :href="`/it/assignments/${ticket.id}`" class="back-btn">← Back to Assignment</Link>

      <div class="page-header">
        <h1>Update Assignment</h1>
        <p>Update your progress on this ticket</p>
      </div>

      <div class="content-layout">

        <!-- Left column: form -->
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

          <!-- Update form -->
          <div class="card" style="margin-top:1.5rem;">
            <h2 class="card-title">Update Progress</h2>
            <form @submit.prevent="submit">
              <div class="form-section">

                <div class="form-group">
                  <label>Status *</label>
                  <select v-model="form.status" required>
                    <option value="assigned">Assigned</option>
                    <option value="in-progress">In Progress</option>
                    <option value="resolved">Resolved</option>
                  </select>
                  <p class="help-text">Update the current status of your work on this ticket</p>
                  <span v-if="form.errors.status" class="text-danger">{{ form.errors.status }}</span>
                </div>

                <div class="form-group">
                  <label>Priority *</label>
                  <select v-model="form.priority" required>
                    <option value="low">Low</option>
                    <option value="medium">Medium</option>
                    <option value="high">High</option>
                  </select>
                  <p class="help-text">Adjust priority if needed based on your assessment</p>
                  <span v-if="form.errors.priority" class="text-danger">{{ form.errors.priority }}</span>
                </div>

                <div style="background:rgba(45,212,191,0.1);border:1px solid rgba(45,212,191,0.3);padding:1rem;border-radius:8px;margin-top:1rem;">
                  <p style="color:#2dd4bf;margin:0;font-size:0.9rem;">
                    💡 <strong>Note:</strong> The reporter will be automatically notified when you update this ticket.
                  </p>
                </div>

              </div>

              <div class="action-buttons">
                <button type="submit" class="btn btn-primary" :disabled="form.processing">
                  {{ form.processing ? 'Saving…' : 'Save Changes & Notify User' }}
                </button>
                <Link :href="`/it/assignments/${ticket.id}`" class="btn btn-secondary">Cancel</Link>
              </div>
            </form>
          </div>

          <!-- Quick actions -->
          <div class="card" style="margin-top:1.5rem;">
            <h2 class="card-title">Quick Actions</h2>
            <p style="color:#9ca3af;margin-bottom:1rem;">Apply common status changes with one click</p>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;">
              <button
                v-if="ticket.status !== 'in-progress'"
                type="button"
                class="btn btn-secondary"
                style="width:100%;"
                @click="quickUpdate('in-progress')"
              >
                🚀 Start Working
              </button>
              <button
                v-if="ticket.status !== 'resolved'"
                type="button"
                class="btn btn-secondary"
                style="width:100%;"
                @click="quickUpdate('resolved')"
              >
                ✅ Mark Resolved
              </button>
              <button
                v-if="ticket.priority !== 'high' && ticket.status !== 'resolved'"
                type="button"
                class="btn btn-secondary"
                style="width:100%;"
                @click="escalatePriority"
              >
                🔴 Escalate Priority
              </button>
            </div>
          </div>

          <!-- Status guidelines -->
          <div class="card" style="margin-top:1.5rem;background:rgba(59,130,246,0.1);border-color:rgba(59,130,246,0.3);">
            <h2 class="card-title" style="color:#3b82f6;">📋 Status Guidelines</h2>
            <div style="color:#d1d5db;font-size:0.9rem;line-height:1.6;">
              <div style="margin-bottom:1rem;">
                <strong style="color:#60a5fa;">Assigned:</strong> Ticket is assigned but work hasn't started yet
              </div>
              <div style="margin-bottom:1rem;">
                <strong style="color:#60a5fa;">In Progress:</strong> You're actively working on resolving this issue
              </div>
              <div>
                <strong style="color:#60a5fa;">Resolved:</strong> Issue is fixed and verified — reporter will be notified
              </div>
            </div>
          </div>

        </div>

        <!-- Sidebar -->
        <div>

          <!-- Current status -->
          <div class="card">
            <h2 class="card-title">Current Status</h2>
            <div class="info-list">
              <div class="info-item">
                <span class="info-label">Status</span>
                <span class="info-value">
                  <span class="status-badge" :class="`status-${statusColor(ticket.status)}`">
                    {{ statusLabel(ticket.status) }}
                  </span>
                </span>
              </div>
              <div class="info-item">
                <span class="info-label">Priority</span>
                <span class="info-value" :class="`priority-${ticket.priority}`">
                  {{ ticket.priority.charAt(0).toUpperCase() + ticket.priority.slice(1) }}
                </span>
              </div>
              <div class="info-item">
                <span class="info-label">Time on Task</span>
                <span class="info-value">
                  {{ assignedTransaction ? diffForHumans(assignedTransaction.created_at) : 'Just assigned' }}
                </span>
              </div>
              <div class="info-item">
                <span class="info-label">Assigned</span>
                <span class="info-value">
                  {{ assignedTransaction ? formatDate(assignedTransaction.created_at) : 'Recently' }}
                </span>
              </div>
            </div>
          </div>

          <!-- Description preview -->
          <div class="card" style="margin-top:1.5rem;">
            <h2 class="card-title">Problem Description</h2>
            <p style="color:#d1d5db;line-height:1.6;">
              {{ ticket.description?.length > 200 ? ticket.description.substring(0, 200) + '…' : ticket.description }}
            </p>
            <Link :href="`/it/assignments/${ticket.id}`" class="btn btn-secondary" style="margin-top:1rem;">
              View Full Details
            </Link>
          </div>

          <!-- Equipment info -->
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

          <!-- Reporter contact -->
          <div class="card" style="margin-top:1.5rem;">
            <h2 class="card-title">Reporter Contact</h2>
            <div class="reporter-info">
              <div class="reporter-avatar">
                {{ ticket.reporter?.first_name?.[0] }}{{ ticket.reporter?.last_name?.[0] }}
              </div>
              <div class="reporter-name">{{ ticket.reporter?.first_name }} {{ ticket.reporter?.last_name }}</div>
              <div class="reporter-role">{{ statusLabel(ticket.reporter?.role ?? '') }}</div>
              <p v-if="ticket.reporter?.email" style="color:#9ca3af;margin-top:0.5rem;font-size:0.9rem;">
                <a :href="`mailto:${ticket.reporter.email}`" style="color:#2dd4bf;text-decoration:none;">
                  📧 {{ ticket.reporter.email }}
                </a>
              </p>
              <p v-if="ticket.reporter?.phone" style="color:#9ca3af;font-size:0.9rem;">
                <a :href="`tel:${ticket.reporter.phone}`" style="color:#2dd4bf;text-decoration:none;">
                  📞 {{ ticket.reporter.phone }}
                </a>
              </p>
            </div>
          </div>

        </div>
      </div>
    </div>
  </AppLayout>
</template>