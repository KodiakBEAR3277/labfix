<script setup>
// Pages/Profile/Show.vue
// Path:    resources/js/Pages/Profile/Show.vue

import AppLayout from '../../Layouts/AppLayout.vue'
import NavUser from '../../Components/Nav/NavUser.vue'
import NavIT from '../../Components/Nav/NavIT.vue'
import NavAdmin from '../../Components/Nav/NavAdmin.vue'
import { Link } from '@inertiajs/vue3'
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

const props = defineProps({
  user: Object,
})

const authUser = computed(() => usePage().props.auth.user)
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? ''

function roleLabel(role) {
  const map = {
    'student':    'Student',
    'staff':      'Staff',
    'it-support': 'IT Support',
    'admin':      'Administrator',
  }
  return map[role] ?? role
}

// Dashboard back-link based on role
const dashboardHref = computed(() => {
  switch (authUser.value?.role) {
    case 'admin':      return '/admin/dashboard'
    case 'it-support': return '/it/dashboard'
    default:           return '/user/dashboard'
  }
})
</script>

<template>
  <AppLayout>
    <template #nav>
      <NavAdmin v-if="authUser.role === 'admin'" />
      <NavIT    v-else-if="authUser.role === 'it-support'" />
      <NavUser  v-else />
    </template>

    <div class="container">
      <!-- Link navigates within Inertia zone — no reload -->
      <Link :href="dashboardHref" class="back-btn">← Back to Dashboard</Link>

      <div class="page-header">
        <h1>My Profile</h1>
        <p style="color:#9ca3af;">Manage your account information and preferences</p>
      </div>

      <div class="content-layout">

        <!-- Left sidebar -->
        <div>
          <div class="card" style="text-align:center;">
            <div class="user-avatar-large">{{ user.initials }}</div>
            <div class="user-name">{{ user.full_name }}</div>
            <div class="user-email">{{ user.email }}</div>
            <span class="role-badge" :class="`role-${user.role === 'it-support' ? 'it-support' : user.role}`">
              {{ roleLabel(user.role) }}
            </span>

            <div class="info-list" style="margin-top:1.5rem;text-align:left;">
              <div class="info-item" v-if="user.phone">
                <span class="info-label">Phone</span>
                <span class="info-value">{{ user.phone }}</span>
              </div>
              <div class="info-item" v-if="user.student_staff_id">
                <span class="info-label">ID Number</span>
                <span class="info-value">{{ user.student_staff_id }}</span>
              </div>
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
            </div>

            <!--
              Logout stays as a native POST form — NOT a Link.
              Inertia's Link only does GET navigations by default.
              Logout requires a POST to /logout so Laravel can invalidate
              the session properly; a GET would bypass the CSRF check.
            -->
            <form action="/logout" method="POST" style="margin-top:1.5rem;">
              <input type="hidden" name="_token" :value="csrfToken">
              <button type="submit" class="btn btn-danger" style="width:100%;">Sign Out</button>
            </form>
          </div>
        </div>

        <!-- Right column: forms -->
        <div style="display:flex;flex-direction:column;gap:1.5rem;">

          <!-- Profile information -->
          <div class="card">
            <h2 class="card-title">Profile Information</h2>
            <form action="/profile/update" method="POST">
              <input type="hidden" name="_token"  :value="csrfToken">
              <input type="hidden" name="_method" value="PUT">
              <div class="form-row">
                <div class="form-group">
                  <label>First Name</label>
                  <input type="text" name="first_name" :value="user.first_name" required>
                </div>
                <div class="form-group">
                  <label>Last Name</label>
                  <input type="text" name="last_name" :value="user.last_name" required>
                </div>
              </div>
              <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" :value="user.email" required>
              </div>
              <div class="form-row">
                <div class="form-group">
                  <label>Phone Number</label>
                  <input type="tel" name="phone" :value="user.phone ?? ''" placeholder="Optional">
                </div>
                <div class="form-group">
                  <label>Student / Staff ID</label>
                  <input type="text" name="student_staff_id" :value="user.student_staff_id ?? ''" placeholder="Optional">
                </div>
              </div>
              <div class="action-buttons">
                <button type="submit" class="btn btn-primary">Update Profile</button>
              </div>
            </form>
          </div>

          <!-- Change password -->
          <div class="card">
            <h2 class="card-title">Change Password</h2>
            <form action="/profile/password" method="POST">
              <input type="hidden" name="_token"  :value="csrfToken">
              <input type="hidden" name="_method" value="PUT">
              <div class="form-group">
                <label>Current Password</label>
                <input type="password" name="current_password" required autocomplete="current-password">
              </div>
              <div class="form-group">
                <label>New Password</label>
                <input type="password" name="password" required autocomplete="new-password">
              </div>
              <div class="form-group">
                <label>Confirm New Password</label>
                <input type="password" name="password_confirmation" required autocomplete="new-password">
              </div>
              <div class="action-buttons">
                <button type="submit" class="btn btn-primary">Update Password</button>
              </div>
            </form>
          </div>

          <!-- Notification preferences -->
          <div class="card">
            <h2 class="card-title">Notification Preferences</h2>
            <form action="/profile/preferences" method="POST">
              <input type="hidden" name="_token"  :value="csrfToken">
              <input type="hidden" name="_method" value="PUT">
              <div class="toggle-item">
                <div class="toggle-info">
                  <h4>Email Notifications</h4>
                  <p>Receive email updates when your ticket status changes</p>
                </div>
                <input
                  type="checkbox"
                  name="email_notifications"
                  :checked="user.email_notifications"
                  style="width:auto;accent-color:#2dd4bf;cursor:pointer;"
                >
              </div>
              <div class="action-buttons" style="margin-top:1rem;">
                <button type="submit" class="btn btn-primary">Save Preferences</button>
              </div>
            </form>
          </div>

          <!-- Danger zone -->
          <div class="card" style="border-color:rgba(239,68,68,0.3);">
            <h2 class="card-title" style="color:#ef4444;">Danger Zone</h2>
            <p style="color:#9ca3af;margin-bottom:1rem;font-size:0.9rem;">
              Sign out of your LabFix account on this device.
            </p>
            <form action="/logout" method="POST">
              <input type="hidden" name="_token" :value="csrfToken">
              <button type="submit" class="btn btn-danger">Sign Out</button>
            </form>
          </div>

        </div>
      </div>
    </div>
  </AppLayout>
</template>