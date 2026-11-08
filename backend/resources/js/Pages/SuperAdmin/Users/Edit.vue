<script setup>
// Pages/SuperAdmin/Users/Edit.vue
// Path: resources/js/Pages/SuperAdmin/Users/Edit.vue
//
// Superadmin-only edit form for any user on the platform. Mirrors
// Admin/Users/Edit.vue but without the danger zone: the superadmin cannot
// delete users here, and cannot change a superadmin's role or deactivate a
// superadmin (including themselves) — the server enforces the same rule.

import AppLayout from '../../../Layouts/AppLayout.vue'
import NavSuperAdmin from '../../../Components/Nav/NavSuperAdmin.vue'
import { Link, useForm } from '@inertiajs/vue3'
import { computed } from 'vue'
import Icon from '../../../Components/Icon.vue'

const props = defineProps({
  user: Object,
})

const isSuperadminTarget = computed(() => props.user.role === 'superadmin')

const form = useForm({
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
  form.put(`/superadmin/users/${props.user.id}`, {
    onError: () => form.reset('password', 'password_confirmation'),
  })
}

function roleLabel(r) {
  if (r === 'superadmin') return 'Superadmin'
  return r.replace('-', ' ').replace(/\b\w/g, c => c.toUpperCase())
}

function formatDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}
</script>

<template>
  <AppLayout>
    <template #nav><NavSuperAdmin /></template>

    <div class="container">
      <Link href="/superadmin/users" class="back-btn"><Icon name="arrow-left" /> Back to Users</Link>

      <div class="page-header">
        <h1>Edit User</h1>
        <p>Update this account's details and permissions</p>
      </div>

      <div class="content-layout">

        <!-- ── Left sidebar ─────────────────────────────────────────── -->
        <div>
          <div class="card" style="text-align:center;">
            <div class="user-avatar-large">
              {{ user.first_name[0] }}{{ user.last_name[0] }}
            </div>
            <h2 class="user-name">{{ user.first_name }} {{ user.last_name }}</h2>
            <p class="user-email">{{ user.email }}</p>
            <span class="role-badge" :class="user.role === 'superadmin' ? 'role-admin' : `role-${user.role}`">
              {{ roleLabel(user.role) }}
            </span>

            <div class="info-list" style="margin-top:1.5rem;text-align:left;">
              <div class="info-item">
                <span class="info-label">User ID</span>
                <span class="info-value">#{{ user.id }}</span>
              </div>
              <div class="info-item">
                <span class="info-label">Institution</span>
                <span class="info-value">{{ user.institution?.name ?? '— Platform —' }}</span>
              </div>
              <div class="info-item">
                <span class="info-label">Joined</span>
                <span class="info-value">{{ formatDate(user.created_at) }}</span>
              </div>
            </div>
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
                    <label>First Name <span class="required">*</span></label>
                    <input v-model="form.first_name" type="text" required>
                    <span v-if="form.errors.first_name" class="text-danger">{{ form.errors.first_name }}</span>
                  </div>
                  <div class="form-group">
                    <label>Last Name <span class="required">*</span></label>
                    <input v-model="form.last_name" type="text" required>
                    <span v-if="form.errors.last_name" class="text-danger">{{ form.errors.last_name }}</span>
                  </div>
                </div>

                <div class="form-group">
                  <label>Email Address <span class="required">*</span></label>
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
                  <label>User Role <span class="required">*</span></label>
                  <select v-model="form.role" :disabled="isSuperadminTarget" required>
                    <option v-if="isSuperadminTarget" value="superadmin">Superadmin</option>
                    <option value="student">Student</option>
                    <option value="staff">Staff</option>
                    <option value="it-support">IT Support</option>
                    <option value="admin">Admin</option>
                  </select>
                  <p v-if="isSuperadminTarget" class="help-text">
                    The platform superadmin role can't be changed here.
                  </p>
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
                    :disabled="isSuperadminTarget"
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
                <h3 class="section-title">Change Password (Optional)</h3>
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
                <Link href="/superadmin/users" class="btn btn-secondary">Cancel</Link>
              </div>

            </form>
          </div>
        </div>

      </div>
    </div>
  </AppLayout>
</template>
