<script setup>
// Pages/User/Reports/Show.vue
// Mirrors: resources/views/user/reports/show.blade.php
// Path:    resources/js/Pages/User/Reports/Show.vue

import AppLayout from '../../../Layouts/AppLayout.vue'
import NavUser from '../../../Components/Nav/NavUser.vue'
import { Link, router } from '@inertiajs/vue3'

const props = defineProps({
  report: Object,
})

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

function transactionIcon(action) {
  const map = {
    'created':          '🎫',
    'status_changed':   '🔄',
    'assigned':         '👤',
    'priority_changed': '⚠️',
    'updated':          '✏️',
    'deleted':          '🗑️',
    'restored':         '♻️',
  }
  return map[action] ?? '📌'
}

function transactionTitle(action) {
  const map = {
    'created':          'Ticket Created',
    'status_changed':   'Status Updated',
    'assigned':         'Assigned',
    'priority_changed': 'Priority Changed',
    'updated':          'Ticket Updated',
    'deleted':          'Ticket Cancelled',
    'restored':         'Ticket Restored',
  }
  return map[action] ?? 'Activity'
}

function formatDate(dateStr) {
  return new Date(dateStr).toLocaleDateString('en-US', {
    month: 'short', day: 'numeric', year: 'numeric',
    hour: 'numeric', minute: '2-digit',
  })
}

function cancelReport() {
  if (confirm('Are you sure you want to cancel this ticket? Admins can restore it later if needed.')) {
    router.delete(`/user/reports/${props.report.id}`)
  }
}
</script>

<template>
  <AppLayout>
    <template #nav><NavUser /></template>

    <div id="user-report-details" class="container">
      <Link href="/user/reports" class="back-btn">← Back to My Reports</Link>

      <!-- Ticket header -->
      <div class="ticket-header">
        <div class="ticket-title-row">
          <div>
            <h1 class="ticket-title">{{ report.title }}</h1>
            <div class="ticket-id">Ticket {{ report.ticket_number }}</div>
          </div>
          <span class="status-badge" :class="`status-${statusColor(report.status)}`">
            {{ statusLabel(report.status) }}
          </span>
        </div>

        <div class="ticket-meta">
          <div class="meta-item"><div class="meta-label">Reported By</div><div class="meta-value">{{ report.reporter?.full_name }}</div></div>
          <div class="meta-item"><div class="meta-label">Lab Location</div><div class="meta-value">{{ report.equipment?.lab?.name }}</div></div>
          <div class="meta-item"><div class="meta-label">Equipment</div><div class="meta-value">{{ report.equipment?.equipment_code }}</div></div>
          <div class="meta-item"><div class="meta-label">Category</div><div class="meta-value">{{ report.category.charAt(0).toUpperCase() + report.category.slice(1) }}</div></div>
          <div class="meta-item"><div class="meta-label">Priority</div><div class="meta-value" :class="`priority-${report.priority}`">{{ report.priority.charAt(0).toUpperCase() + report.priority.slice(1) }}</div></div>
          <div class="meta-item"><div class="meta-label">Submitted</div><div class="meta-value">{{ formatDate(report.created_at) }}</div></div>
          <div class="meta-item" v-if="report.assigned_to"><div class="meta-label">Assigned To</div><div class="meta-value">{{ report.assigned_to_user?.full_name }}</div></div>
        </div>
      </div>

      <div class="detail-layout">
        <!-- Main content -->
        <div class="main-content">

          <div class="content-card">
            <h2 class="card-title">Problem Description</h2>
            <p class="description-text">{{ report.description }}</p>
          </div>

          <div class="content-card">
            <h2 class="card-title">Equipment Information</h2>
            <div class="info-list">
              <div class="info-item"><div class="info-label">Equipment Code</div><div class="info-value">{{ report.equipment?.equipment_code }}</div></div>
              <div class="info-item"><div class="info-label">Equipment Type</div><div class="info-value">{{ report.equipment?.type?.charAt(0).toUpperCase() + report.equipment?.type?.slice(1) }}</div></div>
              <div class="info-item">
                <div class="info-label">Current Status</div>
                <div class="info-value">
                  <span class="status-badge" :class="report.equipment?.status === 'operational' ? 'status-active' : 'status-warning'">
                    {{ statusLabel(report.equipment?.status ?? '') }}
                  </span>
                </div>
              </div>
              <div class="info-item"><div class="info-label">Lab Location</div><div class="info-value">{{ report.equipment?.lab?.name }}</div></div>
              <div class="info-item" v-if="report.equipment?.lab?.location"><div class="info-label">Building/Floor</div><div class="info-value">{{ report.equipment.lab.location }}</div></div>
            </div>
          </div>

          <!-- Activity timeline -->
          <div class="card">
            <h2 class="card-title">Activity Timeline</h2>
            <div class="timeline">
              <template v-if="report.transactions?.length">
                <div v-for="tx in report.transactions" :key="tx.id" class="timeline-item">
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
                    <div style="margin-top:0.25rem;font-size:0.85rem;color:#9ca3af;">by {{ tx.user?.full_name }}</div>
                  </div>
                </div>
              </template>
              <template v-else>
                <div class="timeline-item">
                  <div class="timeline-dot"></div>
                  <div class="timeline-content">
                    <div class="timeline-header">
                      <span class="timeline-title">Ticket Created</span>
                      <span class="timeline-time">{{ formatDate(report.created_at) }}</span>
                    </div>
                    <p class="timeline-text">Report submitted by {{ report.reporter?.full_name }}</p>
                  </div>
                </div>
              </template>
            </div>
          </div>

        </div>

        <!-- Sidebar -->
        <div class="sidebar">

          <div class="info-card">
            <h2 class="card-title">Quick Information</h2>
            <div class="info-row"><span class="info-label">Status</span><span class="info-value">{{ statusLabel(report.status) }}</span></div>
            <div class="info-row"><span class="info-label">Priority</span><span class="info-value" :class="`priority-${report.priority}`">{{ report.priority.charAt(0).toUpperCase() + report.priority.slice(1) }}</span></div>
            <div class="info-row" v-if="report.assigned_at"><span class="info-label">Response Time</span><span class="info-value">—</span></div>
            <div class="info-row"><span class="info-label">Age</span><span class="info-value">{{ report.created_at_human }}</span></div>
          </div>

          <!-- Manage card -->
          <div v-if="!report.assigned_to" class="card">
            <h2 class="card-title">Manage Report</h2>
            <div class="action-buttons">
              <Link :href="`/user/reports/${report.id}/edit`" class="btn btn-primary">Edit Report</Link>
              <button type="button" class="btn btn-danger" @click="cancelReport">Cancel Ticket</button>
            </div>
            <p style="color:#9ca3af;font-size:0.85rem;margin-top:1rem;text-align:center;">Available until ticket is assigned</p>
          </div>
          <div v-else class="card" style="background:rgba(45,212,191,0.1);border-color:rgba(45,212,191,0.3);">
            <h2 class="card-title" style="color:#2dd4bf;">✓ Ticket Assigned</h2>
            <p style="color:#d1d5db;font-size:0.9rem;line-height:1.6;">This ticket has been assigned and can no longer be edited.</p>
          </div>

          <!-- Assigned technician -->
          <div v-if="report.assigned_to" class="info-card">
            <h2 class="card-title">Assigned Technician</h2>
            <div style="text-align:center;padding:1rem 0;">
              <div style="width:60px;height:60px;background:linear-gradient(135deg,#2dd4bf,#14b8a6);border-radius:50%;margin:0 auto 1rem;display:flex;align-items:center;justify-content:center;font-size:1.5rem;font-weight:bold;">
                {{ report.assigned_to_user?.initials }}
              </div>
              <div style="color:#fff;font-weight:600;margin-bottom:0.25rem;">{{ report.assigned_to_user?.full_name }}</div>
              <div style="color:#9ca3af;font-size:0.9rem;">{{ report.assigned_to_user?.role?.replace('-', ' ') }}</div>
            </div>
          </div>
          <div v-else class="info-card">
            <h2 class="card-title">Status</h2>
            <div style="text-align:center;padding:1rem 0;color:#9ca3af;">
              <div style="font-size:2rem;margin-bottom:0.5rem;">⏳</div>
              <p>Awaiting assignment to a technician</p>
            </div>
          </div>

          <div class="info-card">
            <h2 class="card-title">Lab Status</h2>
            <div class="info-row"><span class="info-label">Lab</span><span class="info-value">{{ report.equipment?.lab?.name }}</span></div>
            <div class="info-row"><span class="info-label">Operational Equipment</span><span class="info-value">{{ report.equipment?.lab?.operational_count }}/{{ report.equipment?.lab?.capacity }}</span></div>
          </div>

        </div>
      </div>
    </div>
  </AppLayout>
</template>