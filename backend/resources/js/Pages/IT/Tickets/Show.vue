<script setup>
// Pages/IT/Tickets/Show.vue
// Path: resources/js/Pages/IT/Tickets/Show.vue
// Mirrors: resources/views/it/tickets/show.blade.php

import AppLayout from '../../../Layouts/AppLayout.vue'
import NavIT from '../../../Components/Nav/NavIT.vue'
import { Link, useForm, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({
  ticket: Object,
})

const authUser = computed(() => usePage().props.auth.user)

// ── Quick actions ─────────────────────────────────────────────────────────────
const resolveForm = useForm({
  _method:     'PUT',
  status:      'resolved',
  priority:    props.ticket.priority,
  assigned_to: props.ticket.assigned_to ?? '',
})

function markResolved() {
  resolveForm.post(`/it/tickets/${props.ticket.id}`)
}

function assignToSelf() {
  useForm({}).post(`/it/tickets/${props.ticket.id}/assign-self`)
}

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

function transactionIcon(action) {
  return { created: '🎫', status_changed: '🔄', assigned: '👤', priority_changed: '⚠️', updated: '✏️', deleted: '🗑️', restored: '♻️' }[action] ?? '📌'
}

function transactionTitle(action) {
  return { created: 'Ticket Created', status_changed: 'Status Updated', assigned: 'Assigned', priority_changed: 'Priority Changed', updated: 'Ticket Updated', deleted: 'Ticket Cancelled', restored: 'Ticket Restored' }[action] ?? 'Activity'
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

    <div id="it-ticket-detail" class="container">
      <Link href="/it/tickets" class="back-btn">← Back to Queue</Link>

      <!-- Ticket header -->
      <div class="ticket-header">
        <div class="header-top">
          <div>
            <h1 class="ticket-title">{{ ticket.title }}</h1>
            <div class="ticket-id">Ticket {{ ticket.ticket_number }}</div>
          </div>
          <div style="display:flex;gap:1rem;align-items:center;">
            <span class="priority-badge" :class="`priority-${ticket.priority}`">
              {{ ticket.priority.charAt(0).toUpperCase() + ticket.priority.slice(1) }} Priority
            </span>
            <Link :href="`/it/tickets/${ticket.id}/edit`" class="btn btn-primary">Edit Ticket</Link>
          </div>
        </div>

        <div class="ticket-meta-grid">
          <div class="meta-item"><div class="meta-label">Reporter</div><div class="meta-value">{{ ticket.reporter?.first_name }} {{ ticket.reporter?.last_name }}</div></div>
          <div class="meta-item"><div class="meta-label">Location</div><div class="meta-value">{{ ticket.equipment?.lab?.name }}, {{ ticket.equipment?.equipment_code }}</div></div>
          <div class="meta-item"><div class="meta-label">Category</div><div class="meta-value">{{ ticket.category.charAt(0).toUpperCase() + ticket.category.slice(1) }}</div></div>
          <div class="meta-item">
            <div class="meta-label">Status</div>
            <div class="meta-value"><span class="status-badge" :class="`status-${statusColor(ticket.status)}`">{{ statusLabel(ticket.status) }}</span></div>
          </div>
          <div class="meta-item"><div class="meta-label">Submitted</div><div class="meta-value">{{ formatDate(ticket.created_at) }}</div></div>
          <div class="meta-item"><div class="meta-label">Last Updated</div><div class="meta-value">{{ diffForHumans(ticket.updated_at) }}</div></div>
        </div>
      </div>

      <div class="content-layout">

        <!-- Main content -->
        <div class="content-section">

          <div class="card">
            <h2 class="card-title">Problem Description</h2>
            <p class="description-text">{{ ticket.description }}</p>
          </div>

          <div class="card">
            <h2 class="card-title">Equipment Information</h2>
            <div class="info-list">
              <div class="info-item"><div class="info-label">Equipment Code</div><div class="info-value">{{ ticket.equipment?.equipment_code }}</div></div>
              <div class="info-item"><div class="info-label">Equipment Type</div><div class="info-value">{{ ticket.equipment?.type?.charAt(0).toUpperCase() + ticket.equipment?.type?.slice(1) }}</div></div>
              <div class="info-item">
                <div class="info-label">Current Status</div>
                <div class="info-value">
                  <span class="status-badge" :class="equipmentStatusClass(ticket.equipment?.status)">
                    {{ statusLabel(ticket.equipment?.status ?? '') }}
                  </span>
                </div>
              </div>
              <div class="info-item"><div class="info-label">Lab Location</div><div class="info-value">{{ ticket.equipment?.lab?.name }}</div></div>
              <div v-if="ticket.equipment?.lab?.location" class="info-item">
                <div class="info-label">Building/Floor</div>
                <div class="info-value">{{ ticket.equipment.lab.location }}</div>
              </div>
            </div>
          </div>

          <!-- Attachments -->
          <div v-if="ticket.attachments?.length" class="card">
            <h2 class="card-title">Attachments</h2>
            <div class="attachments-grid">
              <a v-for="att in ticket.attachments" :key="att" :href="`/storage/${att}`" target="_blank" class="attachment-item">
                <div class="attachment-icon">{{ att.endsWith('.pdf') ? '📄' : '🖼️' }}</div>
                <div class="attachment-name">{{ att.split('/').pop() }}</div>
              </a>
            </div>
          </div>

          <!-- Activity Timeline -->
          <div class="card">
            <h2 class="card-title">Activity Timeline</h2>
            <div class="timeline">
              <template v-if="ticket.transactions?.length">
                <div v-for="tx in ticket.transactions" :key="tx.id" class="timeline-item">
                  <div class="timeline-dot"></div>
                  <div class="timeline-content">
                    <div class="timeline-header">
                      <span class="timeline-title">{{ transactionIcon(tx.action) }} {{ transactionTitle(tx.action) }}</span>
                      <span class="timeline-time">{{ formatDate(tx.created_at) }}</span>
                    </div>
                    <p class="timeline-text">{{ tx.description }}</p>
                    <div v-if="tx.old_value || tx.new_value" style="margin-top:0.5rem;padding:0.5rem;background:rgba(45,212,191,0.1);border-radius:4px;font-size:0.85rem;">
                      <span v-if="tx.old_value" style="color:#ef4444;">{{ tx.old_value }}</span>
                      <span v-if="tx.old_value && tx.new_value" style="color:#9ca3af;"> → </span>
                      <span v-if="tx.new_value" style="color:#10b981;">{{ tx.new_value }}</span>
                    </div>
                    <div style="margin-top:0.25rem;font-size:0.85rem;color:#9ca3af;">by {{ tx.user?.first_name }} {{ tx.user?.last_name }}</div>
                  </div>
                </div>
              </template>
              <template v-else>
                <div class="timeline-item">
                  <div class="timeline-dot"></div>
                  <div class="timeline-content">
                    <div class="timeline-header">
                      <span class="timeline-title">Ticket Created</span>
                      <span class="timeline-time">{{ formatDate(ticket.created_at) }}</span>
                    </div>
                    <p class="timeline-text">Report submitted by {{ ticket.reporter?.first_name }} {{ ticket.reporter?.last_name }}</p>
                  </div>
                </div>
              </template>
            </div>
          </div>

        </div>

        <!-- Sidebar -->
        <div class="sidebar">

          <div class="card">
            <h2 class="card-title">Quick Information</h2>
            <div class="info-list">
              <div class="info-item">
                <span class="info-label">Status</span>
                <span class="info-value"><span class="status-badge" :class="`status-${statusColor(ticket.status)}`">{{ statusLabel(ticket.status) }}</span></span>
              </div>
              <div class="info-item">
                <span class="info-label">Priority</span>
                <span class="info-value" :class="`priority-${ticket.priority}`">{{ ticket.priority.charAt(0).toUpperCase() + ticket.priority.slice(1) }}</span>
              </div>
              <div v-if="ticket.assigned_at" class="info-item">
                <span class="info-label">Response Time</span>
                <span class="info-value">{{ diffForHumans(ticket.assigned_at) }}</span>
              </div>
              <div class="info-item">
                <span class="info-label">Age</span>
                <span class="info-value">{{ diffForHumans(ticket.created_at) }}</span>
              </div>
            </div>
          </div>

          <div class="card">
            <h2 class="card-title">Reporter Information</h2>
            <div class="reporter-info">
              <div class="reporter-avatar">{{ ticket.reporter?.first_name?.[0] }}{{ ticket.reporter?.last_name?.[0] }}</div>
              <div class="reporter-name">{{ ticket.reporter?.first_name }} {{ ticket.reporter?.last_name }}</div>
              <div class="reporter-role">{{ statusLabel(ticket.reporter?.role ?? '') }}</div>
              <p v-if="ticket.reporter?.email" style="color:#9ca3af;margin-top:0.5rem;font-size:0.9rem;">{{ ticket.reporter.email }}</p>
            </div>
          </div>

          <!-- Assigned technician -->
          <div v-if="ticket.assigned_to" class="card">
            <h2 class="card-title">Assigned Technician</h2>
            <div class="reporter-info">
              <div class="reporter-avatar">{{ ticket.assigned_to?.first_name?.[0] }}{{ ticket.assigned_to?.last_name?.[0] }}</div>
              <div class="reporter-name">{{ ticket.assigned_to?.first_name }} {{ ticket.assigned_to?.last_name }}</div>
              <div class="reporter-role">{{ statusLabel(ticket.assigned_to?.role ?? '') }}</div>
            </div>
          </div>
          <div v-else class="card">
            <h2 class="card-title">Assignment Status</h2>
            <div style="text-align:center;padding:1rem 0;color:#9ca3af;">
              <div style="font-size:2rem;margin-bottom:0.5rem;">⏳</div>
              <p>Not yet assigned</p>
              <button type="button" class="btn btn-primary" style="margin-top:1rem;" @click="assignToSelf">
                Assign to Me
              </button>
            </div>
          </div>

          <!-- Quick Actions -->
          <div class="card">
            <h2 class="card-title">Quick Actions</h2>
            <div class="action-buttons" style="flex-direction:column;">
              <Link :href="`/it/tickets/${ticket.id}/edit`" class="btn btn-primary">Edit Ticket</Link>
              <button
                v-if="ticket.status !== 'resolved' && ticket.status !== 'closed'"
                type="button"
                class="btn btn-secondary"
                :disabled="resolveForm.processing"
                @click="markResolved"
              >
                {{ resolveForm.processing ? 'Updating…' : 'Mark as Resolved' }}
              </button>
            </div>
          </div>

        </div>
      </div>
    </div>
  </AppLayout>
</template>