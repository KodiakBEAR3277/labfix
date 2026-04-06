<script setup>
// Pages/IT/Dashboard.vue
// Path: resources/js/Pages/IT/Dashboard.vue
// Mirrors: resources/views/it/dashboard.blade.php

import AppLayout from '../../Layouts/AppLayout.vue'
import NavIT from '../../Components/Nav/NavIT.vue'
import { Link, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({
  recentTickets:   Array,
  priorityAlerts:  Array,
  stats:           Object,
  recentResolved:  Array,
  unassignedCount: Number,
})

const user = computed(() => usePage().props.auth.user)

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

    <div id="it-dashboard" class="container">

      <div class="page-header">
        <h1>IT Support Dashboard</h1>
        <p>Welcome back, {{ user?.first_name }}! Here's your ticket overview</p>
      </div>

      <!-- Stats grid -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-header">
            <span class="stat-label">Open Tickets</span>
            <span class="stat-icon">📋</span>
          </div>
          <div class="stat-value">{{ stats.open_tickets }}</div>
          <div class="stat-change">{{ stats.open_tickets > 20 ? 'High volume' : 'Normal' }}</div>
        </div>

        <div class="stat-card">
          <div class="stat-header">
            <span class="stat-label">My Assignments</span>
            <span class="stat-icon">👤</span>
          </div>
          <div class="stat-value">{{ stats.my_assignments }}</div>
          <div class="stat-change">Active tasks</div>
        </div>

        <div class="stat-card">
          <div class="stat-header">
            <span class="stat-label">High Priority</span>
            <span class="stat-icon">🔴</span>
          </div>
          <div class="stat-value">{{ stats.high_priority }}</div>
          <div class="stat-change" :class="stats.high_priority > 5 ? 'negative' : ''">
            {{ stats.high_priority > 5 ? 'Needs attention' : 'Under control' }}
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-header">
            <span class="stat-label">Resolved Today</span>
            <span class="stat-icon">✅</span>
          </div>
          <div class="stat-value">{{ stats.resolved_today }}</div>
          <div class="stat-change">Great progress!</div>
        </div>

        <div class="stat-card">
          <div class="stat-header">
            <span class="stat-label">Avg Response Time</span>
            <span class="stat-icon">⏱️</span>
          </div>
          <div class="stat-value">{{ stats.avg_response_time }}h</div>
          <div class="stat-change">Last 7 days</div>
        </div>

        <div class="stat-card">
          <div class="stat-header">
            <span class="stat-label">Team Performance</span>
            <span class="stat-icon">📊</span>
          </div>
          <div class="stat-value">{{ stats.team_satisfaction }}%</div>
          <div class="stat-change">Resolution rate</div>
        </div>
      </div>

      <!-- Priority alerts -->
      <div v-if="priorityAlerts.length" class="alerts-section">
        <div class="alerts-header">
          <div class="alerts-title">
            ⚠️ High Priority Alerts
            <span class="alert-badge">{{ priorityAlerts.length }}</span>
          </div>
        </div>
        <div class="alerts-grid">
          <div v-for="alert in priorityAlerts" :key="alert.id" class="alert-item">
            <div class="alert-info">
              <h4>{{ alert.ticket_number }} - {{ alert.title }}</h4>
              <p>Reported {{ diffForHumans(alert.created_at) }} • {{ alert.equipment?.lab?.name }}</p>
            </div>
            <Link :href="`/it/tickets/${alert.id}`" class="alert-action">Take Action</Link>
          </div>
        </div>
      </div>

      <!-- Main content grid -->
      <div class="content-grid">

        <!-- Recent tickets -->
        <div class="section-card">
          <div class="section-header">
            <h2 class="section-title">Recent Tickets</h2>
            <Link href="/it/tickets" class="view-all-link">View All →</Link>
          </div>
          <div class="ticket-list">
            <template v-if="recentTickets.length">
              <div v-for="ticket in recentTickets.slice(0, 5)" :key="ticket.id" class="ticket-item">
                <div class="ticket-header">
                  <span class="ticket-id">{{ ticket.ticket_number }}</span>
                  <span class="status-badge" :class="`status-${statusColor(ticket.status)}`">
                    {{ statusLabel(ticket.status) }}
                  </span>
                </div>
                <div class="ticket-title">{{ ticket.title }}</div>
                <div class="ticket-meta">
                  <span>{{ ticket.equipment?.lab?.name }}</span>
                  <span>{{ diffForHumans(ticket.created_at) }}</span>
                  <span :class="`priority-${ticket.priority}`">
                    {{ ticket.priority.charAt(0).toUpperCase() + ticket.priority.slice(1) }} Priority
                  </span>
                </div>
              </div>
            </template>
            <p v-else style="text-align:center;color:#9ca3af;padding:2rem;">No recent tickets</p>
          </div>
        </div>

        <!-- Sidebar -->
        <div style="display:flex;flex-direction:column;gap:2rem;">

          <!-- Quick Actions -->
          <div class="section-card">
            <div class="section-header">
              <h2 class="section-title">Quick Actions</h2>
            </div>
            <div class="quick-actions">
              <Link href="/it/tickets?status=new" class="activity-item" style="text-decoration:none;">
                <span class="activity-icon">🔥</span>
                <div class="activity-time">
                  <h4 class="activity-title">View Unassigned</h4>
                  <p class="activity-desc">{{ unassignedCount }} tickets waiting</p>
                </div>
              </Link>
              <Link href="/it/assignments" class="activity-item" style="text-decoration:none;">
                <span class="activity-icon">👤</span>
                <div class="activity-time">
                  <h4 class="activity-title">My Assignments</h4>
                  <p class="activity-desc">{{ stats.my_assignments }} active</p>
                </div>
              </Link>
              <Link href="/it/knowledge-base" class="activity-item" style="text-decoration:none;">
                <span class="activity-icon">📚</span>
                <div class="activity-time">
                  <h4 class="activity-title">Knowledge Base</h4>
                  <p class="activity-desc">Manage articles</p>
                </div>
              </Link>
            </div>
          </div>

          <!-- Recent Activity -->
          <div class="section-card">
            <div class="section-header">
              <h2 class="section-title">Recent Activity</h2>
            </div>
            <div class="activity-feed">
              <template v-if="recentResolved.length">
                <div v-for="(resolved, i) in recentResolved" :key="i" class="activity-item">
                  <div class="activity-icon">✅</div>
                  <div class="activity-content">
                    <div class="activity-title">Ticket {{ resolved.ticket_number }} resolved</div>
                    <div class="activity-time">{{ resolved.resolved_at }}</div>
                  </div>
                </div>
              </template>
              <p v-else style="text-align:center;color:#9ca3af;padding:1rem;">No recent activity</p>
            </div>
          </div>

        </div>
      </div>

    </div>
  </AppLayout>
</template>