<script setup>
// Pages/Admin/Users/Index.vue
// Path: resources/js/Pages/Admin/Users/Index.vue
// Mirrors: resources/views/admin/users/index.blade.php

import AppLayout from '../../../Layouts/AppLayout.vue'
import NavAdmin from '../../../Components/Nav/NavAdmin.vue'
import { Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'

const props = defineProps({
  users:   Object,
  stats:   Object,
  filters: Object,
})

// ── Filters ───────────────────────────────────────────────────────────────────
const search = ref(props.filters?.search ?? '')
const role   = ref(props.filters?.role   ?? 'all')
const status = ref(props.filters?.status ?? 'all')

function applyFilters() {
  router.get('/admin/users', {
    search: search.value || undefined,
    role:   role.value   !== 'all' ? role.value   : undefined,
    status: status.value !== 'all' ? status.value : undefined,
  }, { preserveState: true, replace: true })
}

let searchTimer = null
watch(search, () => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(applyFilters, 350)
})
watch([role, status], applyFilters)

// ── Helpers ───────────────────────────────────────────────────────────────────
function roleBadgeClass(r) {
  return { student: 'role-student', staff: 'role-staff', 'it-support': 'role-it-support', admin: 'role-admin' }[r] ?? ''
}

function roleLabel(r) {
  return r.replace('-', ' ').replace(/\b\w/g, c => c.toUpperCase())
}

// function deleteUser(id, name) {
//   if (confirm(`Are you sure you want to delete "${name}"? This action cannot be undone!`)) {
//     router.delete(`/admin/users/${id}`)
//   }
// }
</script>

<template>
  <AppLayout>
    <template #nav><NavAdmin /></template>

    <div class="container">

      <div class="page-header">
        <div>
          <h1>User Management</h1>
          <p style="color:#9ca3af;">Manage all registered users and their roles</p>
        </div>
        <Link href="/admin/users/create" class="btn btn-primary">+ Add New User</Link>
      </div>

      <!-- Stats -->
      <div class="stats-row">
        <div class="stat-card">
          <div class="stat-label">Total Users</div>
          <div class="stat-value">{{ stats.total }}</div>
        </div>
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
          <div class="stat-value">{{ stats.admins }}</div>
        </div>
      </div>

      <!-- Filters -->
      <div class="filter-bar">
        <div class="search-box">
          <span class="search-icon">🔍</span>
          <input v-model="search" type="text" placeholder="Search users by name or email...">
        </div>
        <div class="filter-tabs">
          <button type="button" class="tab" :class="{ active: role === 'all' }" @click="role = 'all'">All Users</button>
          <button type="button" class="tab" :class="{ active: role === 'student' }" @click="role = 'student'">Students</button>
          <button type="button" class="tab" :class="{ active: role === 'staff' }" @click="role = 'staff'">Staff</button>
          <button type="button" class="tab" :class="{ active: role === 'it-support' }" @click="role = 'it-support'">IT Support</button>
          <button type="button" class="tab" :class="{ active: role === 'admin' }" @click="role = 'admin'">Admins</button>
        </div>
      </div>

      <!-- Active filter badges -->
      <div v-if="filters.search || (filters.role && filters.role !== 'all')" style="display:flex;gap:0.5rem;align-items:center;margin-bottom:1rem;flex-wrap:wrap;">
        <span style="color:#9ca3af;font-size:0.9rem;">Active filters:</span>
        <span v-if="filters.search" class="badge">
          Search: "{{ filters.search }}"
          <a @click.prevent="search = ''" href="#" style="color:inherit;font-weight:bold;">×</a>
        </span>
        <span v-if="filters.role && filters.role !== 'all'" class="badge">
          Role: {{ roleLabel(filters.role) }}
          <a @click.prevent="role = 'all'" href="#" style="color:inherit;font-weight:bold;">×</a>
        </span>
        <Link href="/admin/users" class="btn btn-secondary" style="padding:0.4rem 0.8rem;font-size:0.85rem;">Clear All</Link>
      </div>

      <!-- Table -->
      <div class="table-container">
        <table>
          <thead>
            <tr>
              <th>User</th>
              <th>Role</th>
              <th>Status</th>
              <th>Joined</th>
              <th>Last Active</th>
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
                <td><span class="role-badge" :class="roleBadgeClass(user.role)">{{ roleLabel(user.role) }}</span></td>
                <td><span class="status-badge" :class="user.is_active ? 'status-active' : 'status-inactive'">{{ user.is_active ? 'Active' : 'Inactive' }}</span></td>
                <td>{{ new Date(user.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) }}</td>
                <td>{{ new Date(user.updated_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) }}</td>
                <td>
                  <div class="action-menu">
                    <Link :href="`/admin/users/${user.id}/edit`" class="action-btn">Edit</Link>
                    <Link :href="`/admin/users/${user.id}`" class="action-btn">View</Link>
                  </div>
                </td>
              </tr>
            </template>
            <tr v-else>
              <td colspan="6" style="text-align:center;padding:3rem;color:#9ca3af;">
                <div style="font-size:2rem;margin-bottom:1rem;">👥</div>
                <h3 style="color:#d1d5db;">No users found</h3>
                <p>{{ filters.search || filters.role || filters.status ? 'Try adjusting your filters' : 'No users registered yet' }}</p>
                <Link v-if="filters.search || filters.role || filters.status" href="/admin/users" class="btn btn-primary" style="margin-top:1rem;">
                  Clear Filters
                </Link>
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
            <button class="page-btn" :disabled="!users.prev_page_url" @click="router.get(users.prev_page_url)">← Previous</button>
            <button
              v-for="p in users.last_page" :key="p"
              class="page-btn" :class="{ active: p === users.current_page }"
              @click="router.get(users.path + '?page=' + p)"
            >{{ p }}</button>
            <button class="page-btn" :disabled="!users.next_page_url" @click="router.get(users.next_page_url)">Next →</button>
          </div>
        </div>
      </div>

    </div>
  </AppLayout>
</template>