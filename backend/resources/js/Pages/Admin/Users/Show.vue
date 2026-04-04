<script setup>
// Pages/Admin/Users/Show.vue
// Path: resources/js/Pages/Admin/Users/Show.vue
// Mirrors: resources/views/admin/users/show.blade.php

import AppLayout from '../../../Layouts/AppLayout.vue'
import NavAdmin from '../../../Components/Nav/NavAdmin.vue'
import { Link, router } from '@inertiajs/vue3'
import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({
  user: Object,
})

const authUser = computed(() => usePage().props.auth.user)

function roleBadgeClass(r) {
  return { student: 'role-student', staff: 'role-staff', 'it-support': 'role-it-support', admin: 'role-admin' }[r] ?? ''
}

function roleLabel(r) {
  return r.replace('-', ' ').replace(/\b\w/g, c => c.toUpperCase())
}

function formatDate(d) {
  return new Date(d).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' })
}

function deleteUser() {
  if (confirm(`Are you sure you want to delete "${props.user.first_name} ${props.user.last_name}"? This cannot be undone!`)) {
    router.delete(`/admin/users/${props.user.id}`)
  }
}
</script>

<template>
  <AppLayout>
    <template #nav><NavAdmin /></template>

    <div class="container">
      <Link href="/admin/users" class="back-btn">← Back to User Management</Link>

      <div class="page-header">
        <h1>User Details</h1>
      </div>

      <div class="content-layout">

        <!-- Left sidebar — avatar card -->
        <div>
          <div class="card" style="text-align:center;">
            <div class="user-avatar-large">
              {{ user.first_name[0] }}{{ user.last_name[0] }}
            </div>
            <div class="user-name">{{ user.first_name }} {{ user.last_name }}</div>
            <div class="user-email">{{ user.email }}</div>
            <span class="role-badge" :class="roleBadgeClass(user.role)">
              {{ roleLabel(user.role) }}
            </span>

            <div class="info-list" style="margin-top:1.5rem;text-align:left;">
              <div class="info-item">
                <span class="info-label">Account Status</span>
                <span class="status-badge" :class="user.is_active ? 'status-active' : 'status-inactive'">
                  {{ user.is_active ? 'Active' : 'Inactive' }}
                </span>
              </div>
              <div class="info-item">
                <span class="info-label">Ticket Submission</span>
                <span class="info-value">{{ user.can_submit_tickets ? 'Enabled' : 'Disabled' }}</span>
              </div>
              <div class="info-item">
                <span class="info-label">Email Notifications</span>
                <span class="info-value">{{ user.email_notifications ? 'Enabled' : 'Disabled' }}</span>
              </div>
              <div class="info-item">
                <span class="info-label">Member Since</span>
                <span class="info-value">{{ formatDate(user.created_at) }}</span>
              </div>
              <div class="info-item">
                <span class="info-label">User ID</span>
                <span class="info-value">#{{ user.id }}</span>
              </div>
              <div class="info-item">
                <span class="info-label">Last Active</span>
                <span class="info-value">{{ new Date(user.updated_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) }}</span>
              </div>
            </div>
          </div>
          <div class="card" style="margin-top:1.5rem;">
            <h3 class="card-title">Quick Actions</h3>
            <div style="display:flex;flex-direction:column;gap:0.75rem;">
              <Link :href="`/admin/users/${user.id}/edit`" class="btn btn-primary" style="text-align:center;">Edit User</Link>
              <button v-if="user.id !== authUser.id" type="button" class="btn btn-danger" style="width:100%;" @click="deleteUser">
                Delete User
              </button>
              <button v-else class="btn btn-danger" style="width:100%;opacity:0.5;" disabled>
                Cannot Delete Your Own Account
              </button>
            </div>
          </div>
        </div>

        <!-- Right — details -->
        <div style="display:flex;flex-direction:column;gap:1.5rem;">

          <div class="card">
            <h2 class="card-title">Personal Information</h2>
            <div class="info-list">
              <div class="info-item">
                <span class="info-label">First Name</span>
                <span class="info-value">{{ user.first_name }}</span>
              </div>
              <div class="info-item">
                <span class="info-label">Last Name</span>
                <span class="info-value">{{ user.last_name }}</span>
              </div>
              <div class="info-item">
                <span class="info-label">Email Address</span>
                <span class="info-value">{{ user.email }}</span>
              </div>
              <div class="info-item">
                <span class="info-label">Phone</span>
                <span class="info-value">{{ user.phone ?? '—' }}</span>
              </div>
              <div class="info-item">
                <span class="info-label">Student / Staff ID</span>
                <span class="info-value">{{ user.student_staff_id ?? '—' }}</span>
              </div>
              <div class="info-item">
                <span class="info-label">Role</span>
                <span class="info-value">
                  <span class="role-badge" :class="roleBadgeClass(user.role)">{{ roleLabel(user.role) }}</span>
                </span>
              </div>
            </div>
          </div>

          <div class="card">
            <h2 class="card-title">Account Settings</h2>
            <div class="info-list">
              <div class="info-item">
                <span class="info-label">Account Active</span>
                <span class="info-value">
                  <span class="status-badge" :class="user.is_active ? 'status-active' : 'status-inactive'">
                    {{ user.is_active ? 'Active' : 'Inactive' }}
                  </span>
                </span>
              </div>
              <div class="info-item">
                <span class="info-label">Can Submit Tickets</span>
                <span class="info-value">{{ user.can_submit_tickets ? '✅ Yes' : '❌ No' }}</span>
              </div>
              <div class="info-item">
                <span class="info-label">Email Notifications</span>
                <span class="info-value">{{ user.email_notifications ? '✅ Enabled' : '❌ Disabled' }}</span>
              </div>
            </div>
          </div>

          <div class="card">
            <h2 class="card-title">Account Timeline</h2>
            <div class="info-list">
              <div class="info-item">
                <span class="info-label">Account Created</span>
                <span class="info-value">{{ new Date(user.created_at).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' }) }}</span>
              </div>
              <div class="info-item">
                <span class="info-label">Last Updated</span>
                <span class="info-value">{{ new Date(user.updated_at).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' }) }}</span>
              </div>
              <div class="info-item">
                <span class="info-label">Email Verified</span>
                <span class="info-value">
                  <span v-if="user.email_verified_at">
                    {{ new Date(user.email_verified_at).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' }) }}
                  </span>
                  <span v-else class="status-badge status-warning">Not Verified</span>
                </span>
              </div>
            </div>
          </div>

          <!-- Danger zone — only shown if not viewing own account -->
          <div v-if="user.id !== authUser.id" class="card" style="border-color:rgba(239,68,68,0.3);">
            <h2 class="card-title" style="color:#ef4444;">Danger Zone</h2>
            <p style="color:#9ca3af;margin-bottom:1rem;font-size:0.9rem;">
              Permanently delete this user account. This action cannot be undone.
            </p>
            <button type="button" class="btn btn-danger" @click="deleteUser">
              Delete User Account
            </button>
          </div>

        </div>
      </div>
    </div>
  </AppLayout>
</template>