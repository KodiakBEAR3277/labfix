<script setup>
// Pages/Admin/Users/Edit.vue
// Path: resources/js/Pages/Admin/Users/Edit.vue
// Mirrors: resources/views/admin/users/edit.blade.php
//
// Layout: two-column content-layout — left sidebar has avatar + danger zone,
// right column has the edit form. Matches the blade exactly.
//
// The three boolean flags (is_active, email_notifications, can_submit_tickets)
// are sent as actual booleans via useForm(). The controller uses
// $request->boolean() to read them, which handles this correctly.

import AppLayout from '../../../Layouts/AppLayout.vue'
import NavAdmin from '../../../Components/Nav/NavAdmin.vue'
import { Link, useForm, router, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({
  user: Object,
})

const authUser = computed(() => usePage().props.auth.user)

const form = useForm({
  _method:               'PUT',
  first_name:            props.user.first_name          ?? '',
  last_name:             props.user.last_name           ?? '',
  email:                 props.user.email               ?? '',
  role:                  props.user.role                ?? 'student',
  phone:                 props.user.phone               ?? '',
  student_staff_id:      props.user.student_staff_id    ?? '',
  is_active:             props.user.is_active           ?? true,
  email_notifications:   props.user.email_notifications ?? true,
  can_submit_tickets:    props.user.can_submit_tickets  ?? true,
  password:              '',
  password_confirmation: '',
})

function submit() {
  form.post(`/admin/users/${props.user.id}`, {
    onError: () => form.reset('password', 'password_confirmation'),
  })
}

function deleteUser() {
  if (confirm(`Are you sure you want to delete ${props.user.first_name} ${props.user.last_name}? This action cannot be undone!`)) {
    router.delete(`/admin/users/${props.user.id}`)
  }
}

function roleBadgeClass(r) {
  return { student: 'role-student', staff: 'role-staff', 'it-support': 'role-it-support', admin: 'role-admin' }[r] ?? ''
}

function roleLabel(r) {
  return r.replace('-', ' ').replace(/\b\w/g, c => c.toUpperCase())
}

function formatDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
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
    <template #nav><NavAdmin /></template>

    <div class="container">
      <Link :href="`/admin/users/${user.id}`" class="back-btn">← Back to User Details</Link>

      <div class="page-header">
        <h1>Edit User</h1>
        <p>Update user information and permissions</p>
      </div>

      <div class="content-layout">

        <!-- ── Left sidebar ─────────────────────────────────────────── -->
        <div>

          <!-- Avatar card -->
          <div class="card" style="text-align:center;">
            <div class="user-avatar-large">
              {{ user.first_name[0] }}{{ user.last_name[0] }}
            </div>
            <h2 class="user-name">{{ user.first_name }} {{ user.last_name }}</h2>
            <p class="user-email">{{ user.email }}</p>
            <span class="role-badge" :class="roleBadgeClass(user.role)">
              {{ roleLabel(user.role) }}
            </span>

            <div class="info-list" style="margin-top:1.5rem;text-align:left;">
              <div class="info-item">
                <span class="info-label">User ID</span>
                <span class="info-value">#{{ user.id }}</span>
              </div>
              <div class="info-item">
                <span class="info-label">Joined</span>
                <span class="info-value">{{ formatDate(user.created_at) }}</span>
              </div>
              <div class="info-item">
                <span class="info-label">Last Active</span>
                <span class="info-value">{{ diffForHumans(user.updated_at) }}</span>
              </div>
            </div>
          </div>

          <!-- Danger zone -->
          <div class="card" style="margin-top:1.5rem;border-color:rgba(239,68,68,0.3);">
            <h3 class="card-title" style="color:#ef4444;">Danger Zone</h3>
            <p style="color:#9ca3af;margin-bottom:1rem;font-size:0.9rem;">
              Once you delete this user, there is no going back. This will permanently
              delete the user and all their data.
            </p>
            <button
              v-if="user.id === authUser.id"
              class="btn btn-danger"
              style="width:100%;opacity:0.5;"
              disabled
            >
              Cannot Delete Your Own Account
            </button>
            <button
              v-else
              type="button"
              class="btn btn-danger"
              style="width:100%;"
              @click="deleteUser"
            >
              Delete User Permanently
            </button>
          </div>

        </div>

        <!-- ── Right: edit form ──────────────────────────────────────── -->
        <div>
          <div class="card">
            <h2 class="card-title">Edit User Information</h2>

            <form @submit.prevent="submit">

              <!-- Personal Information -->
              <div class="form-section">
                <h3 class="section-title">Personal Information</h3>

                <div class="form-row">
                  <div class="form-group">
                    <label>First Name *</label>
                    <input v-model="form.first_name" type="text" required>
                    <span v-if="form.errors.first_name" class="text-danger">{{ form.errors.first_name }}</span>
                  </div>
                  <div class="form-group">
                    <label>Last Name *</label>
                    <input v-model="form.last_name" type="text" required>
                    <span v-if="form.errors.last_name" class="text-danger">{{ form.errors.last_name }}</span>
                  </div>
                </div>

                <div class="form-group">
                  <label>Email Address *</label>
                  <input v-model="form.email" type="email" required>
                  <span v-if="form.errors.email" class="text-danger">{{ form.errors.email }}</span>
                </div>

                <div class="form-row">
                  <div class="form-group">
                    <label>Phone Number</label>
                    <input v-model="form.phone" type="tel" placeholder="Optional">
                    <span v-if="form.errors.phone" class="text-danger">{{ form.errors.phone }}</span>
                  </div>
                  <div class="form-group">
                    <label>Student / Staff ID</label>
                    <input v-model="form.student_staff_id" type="text" placeholder="Optional">
                    <span v-if="form.errors.student_staff_id" class="text-danger">{{ form.errors.student_staff_id }}</span>
                  </div>
                </div>
              </div>

              <!-- Role & Permissions -->
              <div class="form-section">
                <h3 class="section-title">Role & Permissions</h3>

                <div class="form-group">
                  <label>User Role *</label>
                  <select v-model="form.role" required>
                    <option value="student">Student</option>
                    <option value="staff">Staff</option>
                    <option value="it-support">IT Support</option>
                    <option value="admin">Admin</option>
                  </select>
                  <span v-if="form.errors.role" class="text-danger">{{ form.errors.role }}</span>
                </div>

                <div class="toggle-item">
                  <div class="toggle-info">
                    <h4>Account Active</h4>
                    <p>Allow this user to log in to the system</p>
                  </div>
                  <input
                    v-model="form.is_active"
                    type="checkbox"
                    style="width:auto;accent-color:#2dd4bf;cursor:pointer;width:20px;height:20px;"
                  >
                </div>

                <div class="toggle-item">
                  <div class="toggle-info">
                    <h4>Email Notifications</h4>
                    <p>Send email alerts for ticket updates</p>
                  </div>
                  <input
                    v-model="form.email_notifications"
                    type="checkbox"
                    style="width:auto;accent-color:#2dd4bf;cursor:pointer;width:20px;height:20px;"
                  >
                </div>

                <div class="toggle-item">
                  <div class="toggle-info">
                    <h4>Can Submit Tickets</h4>
                    <p>Allow this user to create support tickets</p>
                  </div>
                  <input
                    v-model="form.can_submit_tickets"
                    type="checkbox"
                    style="width:auto;accent-color:#2dd4bf;cursor:pointer;width:20px;height:20px;"
                  >
                </div>
              </div>

              <!-- Change Password -->
              <div class="form-section">
                <h3 class="section-title">
                  Change Password (Optional)
                </h3>
                <p style="color:#9ca3af;margin-bottom:1rem;font-size:0.9rem;">
                  Leave blank to keep the current password
                </p>

                <div class="form-group">
                  <label>New Password</label>
                  <input v-model="form.password" type="password" autocomplete="new-password">
                  <span v-if="form.errors.password" class="text-danger">{{ form.errors.password }}</span>
                  <p class="help-text">Minimum 8 characters</p>
                </div>

                <div class="form-group">
                  <label>Confirm New Password</label>
                  <input v-model="form.password_confirmation" type="password" autocomplete="new-password">
                </div>
              </div>

              <div class="action-buttons">
                <button type="submit" class="btn btn-primary" :disabled="form.processing">
                  {{ form.processing ? 'Saving…' : 'Save Changes' }}
                </button>
                <Link :href="`/admin/users/${user.id}`" class="btn btn-secondary">Cancel</Link>
              </div>

            </form>
          </div>
        </div>

      </div>
    </div>
  </AppLayout>
</template>