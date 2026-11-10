<script setup>
// Pages/SuperAdmin/Users/Index.vue
// Path: resources/js/Pages/SuperAdmin/Users/Index.vue
//
// Platform-wide list of every user across every institution — the superadmin's
// oversight view. Editing happens on SuperAdmin/Users/Edit; the list also has
// an inline activate/deactivate shortcut.

import AppLayout from '../../../Layouts/AppLayout.vue'
import NavSuperAdmin from '../../../Components/Nav/NavSuperAdmin.vue'
import { Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import Icon from '../../../Components/Icon.vue'

const props = defineProps({
  users:        Object,
  stats:        Object,
  filters:      Object,
  institutions: Array,
})

// ── Filters ───────────────────────────────────────────────────────────────────
const search      = ref(props.filters?.search ?? '')
const role        = ref(props.filters?.role ?? 'all')
const status      = ref(props.filters?.status ?? 'all')
const institution = ref(props.filters?.institution ?? 'all')

function applyFilters() {
  router.get('/superadmin/users', {
    search:      search.value || undefined,
    role:        role.value !== 'all' ? role.value : undefined,
    status:      status.value !== 'all' ? status.value : undefined,
    institution: institution.value !== 'all' ? institution.value : undefined,
  }, { preserveState: true, replace: true })
}

let searchTimer = null
watch(search, () => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(applyFilters, 350)
})
watch([role, status, institution], applyFilters)

// ── Helpers ───────────────────────────────────────────────────────────────────
function roleBadgeClass(r) {
  return { student: 'role-student', staff: 'role-staff', 'it-support': 'role-it-support', admin: 'role-admin', superadmin: 'role-admin' }[r] ?? ''
}

function roleLabel(r) {
  if (r === 'superadmin') return 'Superadmin'
  return r.replace('-', ' ').replace(/\b\w/g, c => c.toUpperCase())
}

function formatDate(d) {
  return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}

// ── Inline actions ────────────────────────────────────────────────────────────
function toggleStatus(user) {
  const verb = user.is_active ? 'deactivate' : 'activate'
  if (confirm(`Are you sure you want to ${verb} ${user.first_name} ${user.last_name}?`)) {
    router.post(`/superadmin/users/${user.id}/toggle-status`, {}, { preserveScroll: true })
  }
}
</script>

<template>
  <AppLayout>
    <template #nav><NavSuperAdmin /></template>

    <div class="container">
      <div class="page-header">
        <div>
          <h1>Users</h1>
          <p style="color:#9ca3af;">Every account across every institution on the platform</p>
        </div>
      </div>

      <!-- Stats -->
      <div class="stats-row">
        <div class="stat-card">
          <div class="stat-label">Total Users</div>
          <div class="stat-value">{{ stats.total }}</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Admins</div>
          <div class="stat-value">{{ stats.admins }}</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">IT Support</div>
          <div class="stat-value">{{ stats.it_support }}</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Staff</div>
          <div class="stat-value">{{ stats.staff }}</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Students</div>
          <div class="stat-value">{{ stats.students }}</div>
        </div>
      </div>

      <!-- Filters -->
      <div class="filter-bar">
        <div class="search-box">
          <span class="search-icon"><Icon name="search" /></span>
          <input v-model="search" type="text" placeholder="Search users by name or email...">
        </div>
        <select v-model="institution" style="width:auto;">
          <option value="all">All Institutions</option>
          <option v-for="inst in institutions" :key="inst.id" :value="inst.id">{{ inst.name }}</option>
        </select>
        <select v-model="status" style="width:auto;">
          <option value="all">All Statuses</option>
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
        </select>
      </div>

      <div class="filter-bar">
        <div class="filter-tabs">
          <button type="button" class="tab" :class="{ active: role === 'all' }" @click="role = 'all'">All</button>
          <button type="button" class="tab" :class="{ active: role === 'admin' }" @click="role = 'admin'">Admins</button>
          <button type="button" class="tab" :class="{ active: role === 'it-support' }" @click="role = 'it-support'">IT Support</button>
          <button type="button" class="tab" :class="{ active: role === 'staff' }" @click="role = 'staff'">Staff</button>
          <button type="button" class="tab" :class="{ active: role === 'student' }" @click="role = 'student'">Students</button>
          <button type="button" class="tab" :class="{ active: role === 'superadmin' }" @click="role = 'superadmin'">Superadmins</button>
        </div>
      </div>

      <!-- Table -->
      <div class="table-container">
        <table>
          <thead>
            <tr>
              <th>User</th>
              <th>Institution</th>
              <th>Role</th>
              <th>Status</th>
              <th>Joined</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <template v-if="users.data.length">
              <tr v-for="user in users.data" :key="user.id">
                <td>
                  <div class="user-info">
                    <div class="user-avatar" style="font-size:0.85rem;">
                      {{ user.first_name[0] }}{{ user.last_name[0] }}
                    </div>
                    <div class="user-details">
                      <h4>{{ user.first_name }} {{ user.last_name }}</h4>
                      <p>{{ user.email }}</p>
                    </div>
                  </div>
                </td>
                <td>{{ user.institution?.name ?? '— Platform —' }}</td>
                <td><span class="role-badge" :class="roleBadgeClass(user.role)">{{ roleLabel(user.role) }}</span></td>
                <td><span class="status-badge" :class="user.is_active ? 'status-active' : 'status-inactive'">{{ user.is_active ? 'Active' : 'Inactive' }}</span></td>
                <td>{{ formatDate(user.created_at) }}</td>
                <td>
                  <div class="action-menu">
                    <Link :href="`/superadmin/users/${user.id}/edit`" class="action-btn">Edit</Link>
                    <button
                      v-if="user.role !== 'superadmin'"
                      type="button"
                      class="action-btn"
                      @click="toggleStatus(user)"
                    >
                      {{ user.is_active ? 'Deactivate' : 'Activate' }}
                    </button>
                  </div>
                </td>
              </tr>
            </template>
            <tr v-else>
              <td colspan="6" style="text-align:center;padding:3rem;color:#9ca3af;">
                <div style="font-size:2rem;margin-bottom:1rem;"><Icon name="users" /></div>
                <h3 style="color:#d1d5db;">No users found</h3>
                <p>{{ filters.search || (filters.role && filters.role !== 'all') || (filters.status && filters.status !== 'all') ? 'Try adjusting your filters' : 'No users registered yet' }}</p>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Pagination -->
        <div v-if="users.last_page > 1" class="pagination">
          <div class="page-info">
            Showing {{ users.from ?? 0 }}–{{ users.to ?? 0 }} of {{ users.total }} users
          </div>
          <div class="page-controls">
            <button class="page-btn" :disabled="!users.prev_page_url" @click="router.get(users.prev_page_url)"><Icon name="arrow-left" /> Previous</button>
            <button
              v-for="p in users.last_page" :key="p"
              class="page-btn" :class="{ active: p === users.current_page }"
              @click="router.get(users.path + '?page=' + p)"
            >{{ p }}</button>
            <button class="page-btn" :disabled="!users.next_page_url" @click="router.get(users.next_page_url)">Next <Icon name="arrow-right" /></button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
