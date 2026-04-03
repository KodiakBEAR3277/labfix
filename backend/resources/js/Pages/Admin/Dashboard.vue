<script setup>
// Pages/Admin/Dashboard.vue
// Path: resources/js/Pages/Admin/Dashboard.vue
// Mirrors: resources/views/admin/dashboard.blade.php
//
// Controller passes: stats (object), recentActivity (array), systemHealth (object)
// All internal links use Inertia <Link>. No <a href> for route navigation.

import AppLayout from '../../Layouts/AppLayout.vue'
import NavAdmin from '../../Components/Nav/NavAdmin.vue'
import { Link } from '@inertiajs/vue3'
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

const props = defineProps({
  stats:          Object,
  recentActivity: Array,
  systemHealth:   Object,
})

const user = computed(() => usePage().props.auth.user)

// Map health color key → CSS class suffix used in badges/utilities
function healthBadgeClass(color) {
  return color === 'good' ? 'status-active' : 'status-warning'
}
</script>

<template>
  <AppLayout>
    <template #nav>
      <NavAdmin />
    </template>

    <div class="container">

      <div class="page-header">
        <div>
          <h1>Admin Dashboard</h1>
          <p style="color:#9ca3af;">
            Welcome back, {{ user?.first_name }}! Here's your system overview
          </p>
        </div>
      </div>

      <!-- ── System Stats ───────────────────────────────────────────── -->
      <div class="system-stats">

        <div class="stat-card">
          <div class="stat-header">
            <span class="stat-label">Total Users</span>
            <span class="stat-icon">👥</span>
          </div>
          <div class="stat-value">{{ stats.total_users }}</div>
          <div class="stat-detail">
            {{ stats.students }} students · {{ stats.staff }} staff · {{ stats.it_support }} IT
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-header">
            <span class="stat-label">Open Tickets</span>
            <span class="stat-icon">🎫</span>
          </div>
          <div class="stat-value">{{ stats.open_tickets }}</div>
          <div class="stat-detail">Across all labs</div>
        </div>

        <div class="stat-card">
          <div class="stat-header">
            <span class="stat-label">High Priority</span>
            <span class="stat-icon">🔴</span>
          </div>
          <div class="stat-value">{{ stats.high_priority }}</div>
          <div class="stat-detail" :class="stats.high_priority > 5 ? 'stat-change negative' : 'stat-change'">
            {{ stats.high_priority > 5 ? 'Needs attention' : 'Under control' }}
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-header">
            <span class="stat-label">System Uptime</span>
            <span class="stat-icon">⚡</span>
          </div>
          <div class="stat-value">{{ stats.system_uptime }}%</div>
          <div class="stat-detail">Last 30 days</div>
        </div>

        <div class="stat-card">
          <div class="stat-header">
            <span class="stat-label">Active Labs</span>
            <span class="stat-icon">🏫</span>
          </div>
          <div class="stat-value">{{ stats.total_labs }}</div>
          <div class="stat-detail">{{ stats.total_workstations }} total workstations</div>
        </div>

      </div>

      <!-- ── Main content grid ──────────────────────────────────────── -->
      <div class="content-grid">

        <!-- Recent Activity -->
        <div class="section-card">
          <div class="section-header">
            <h2 class="section-title">Recent Activity</h2>
            <Link href="/admin/tickets" class="view-all-link">View Tickets →</Link>
          </div>

          <div class="activity-feed">
            <template v-if="recentActivity.length">
              <div
                v-for="(activity, index) in recentActivity"
                :key="index"
                class="activity-item"
              >
                <div class="activity-icon">{{ activity.icon }}</div>
                <div class="activity-content">
                  <div class="activity-title">{{ activity.title }}</div>
                  <div class="activity-time">{{ activity.time }}</div>
                </div>
              </div>
            </template>
            <p v-else style="text-align:center;color:#9ca3af;padding:2rem;">
              No recent activity
            </p>
          </div>
        </div>

        <!-- Sidebar -->
        <div style="display:flex;flex-direction:column;gap:2rem;">

          <!-- Quick Actions -->
          <div class="section-card">
            <div class="section-header">
              <h2 class="section-title">Quick Actions</h2>
            </div>
            <div class="activity-feed">
              <Link href="/admin/tickets" class="activity-item" style="text-decoration:none;">
                <div class="activity-icon">🎫</div>
                <div class="activity-content">
                  <div class="activity-title">Ticket Management</div>
                  <div class="activity-desc">{{ stats.open_tickets }} open tickets</div>
                </div>
              </Link>
              <Link href="/admin/users" class="activity-item" style="text-decoration:none;">
                <div class="activity-icon">👥</div>
                <div class="activity-content">
                  <div class="activity-title">User Management</div>
                  <div class="activity-desc">{{ stats.total_users }} registered users</div>
                </div>
              </Link>
              <Link href="/admin/labs" class="activity-item" style="text-decoration:none;">
                <div class="activity-icon">🏫</div>
                <div class="activity-content">
                  <div class="activity-title">Lab Configuration</div>
                  <div class="activity-desc">{{ stats.total_labs }} active labs</div>
                </div>
              </Link>
              <Link href="/admin/settings" class="activity-item" style="text-decoration:none;">
                <div class="activity-icon">⚙️</div>
                <div class="activity-content">
                  <div class="activity-title">System Settings</div>
                  <div class="activity-desc">Configure LabFix</div>
                </div>
              </Link>
              <Link href="/admin/transactions" class="activity-item" style="text-decoration:none;">
                <div class="activity-icon">📜</div>
                <div class="activity-content">
                  <div class="activity-title">Ticket History</div>
                  <div class="activity-desc">View all transactions</div>
                </div>
              </Link>
            </div>
          </div>

          <!-- System Health -->
          <div class="section-card">
            <div class="section-header">
              <h2 class="section-title">System Health</h2>
            </div>
            <div class="health-items">
              <div
                v-for="(item, key) in systemHealth"
                :key="key"
                class="health-item"
              >
                <div class="health-header">
                  <span class="health-label">
                    {{ key.replace('_', ' ').replace(/\b\w/g, c => c.toUpperCase()) }}
                  </span>
                  <span class="status-badge" :class="healthBadgeClass(item.color)">
                    {{ item.status }}
                  </span>
                </div>
                <div class="health-bar">
                  <div
                    class="health-bar-fill"
                    :style="{ width: item.percentage + '%' }"
                  ></div>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>

      <!-- ── User breakdown ─────────────────────────────────────────── -->
      <div class="section-card" style="margin-top:2rem;">
        <div class="section-header">
          <h2 class="section-title">User Breakdown</h2>
          <Link href="/admin/users" class="view-all-link">Manage Users →</Link>
        </div>
        <div class="system-stats" style="margin-bottom:0;">
          <div class="stat-card">
            <div class="stat-label">Students</div>
            <div class="stat-value">{{ stats.students }}</div>
          </div>
          <div class="stat-card">
            <div class="stat-label">Staff</div>
            <div class="stat-value">{{ stats.staff }}</div>
          </div>
          <div class="stat-card">
            <div class="stat-label">IT Support</div>
            <div class="stat-value">{{ stats.it_support }}</div>
          </div>
          <div class="stat-card">
            <div class="stat-label">Admins</div>
            <div class="stat-value">
              {{ stats.total_users - stats.students - stats.staff - stats.it_support }}
            </div>
          </div>
        </div>
      </div>

    </div>
  </AppLayout>
</template>