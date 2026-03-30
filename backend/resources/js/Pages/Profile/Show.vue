<script setup>
// Pages/Profile/Show.vue
// Path: resources/js/Pages/Profile/Show.vue
//
// Three useForm() instances — one per form section.
// The logout uses router.post() directly since it carries no form data.
// All submissions are XHR — no full-page reloads anywhere on this page.

import AppLayout from '../../Layouts/AppLayout.vue'
import NavUser from '../../Components/Nav/NavUser.vue'
import NavIT from '../../Components/Nav/NavIT.vue'
import NavAdmin from '../../Components/Nav/NavAdmin.vue'
import { Link, useForm, router } from '@inertiajs/vue3'
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

const props = defineProps({
  user: Object,
})

const authUser = computed(() => usePage().props.auth.user)

// ── Profile info form ──────────────────────────────────────────
const profileForm = useForm({
  first_name:      props.user.first_name       ?? '',
  last_name:       props.user.last_name        ?? '',
  email:           props.user.email            ?? '',
  phone:           props.user.phone            ?? '',
  student_staff_id: props.user.student_staff_id ?? '',
})

function submitProfile() {
  profileForm.put('/profile/update')
}

// ── Password form ──────────────────────────────────────────────
const passwordForm = useForm({
  current_password:      '',
  password:              '',
  password_confirmation: '',
})

function submitPassword() {
  passwordForm.put('/profile/password', {
    onSuccess: () => passwordForm.reset(),
    onError:   () => passwordForm.reset('password', 'password_confirmation'),
  })
}

// ── Notification preferences form ─────────────────────────────
const prefsForm = useForm({
  email_notifications: props.user.email_notifications ?? true,
})

function submitPrefs() {
  prefsForm.put('/profile/preferences')
}

// ── Logout ────────────────────────────────────────────────────
// router.post() carries no form data — just fires the POST to /logout.
// Inertia follows Laravel's redirect response client-side.
function logout() {
  router.post('/logout')
}

// ── Helpers ───────────────────────────────────────────────────
function roleLabel(role) {
  const map = {
    'student':    'Student',
    'staff':      'Staff',
    'it-support': 'IT Support',
    'admin':      'Administrator',
  }
  return map[role] ?? role
}

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
      <Link :href="dashboardHref" class="back-btn">← Back to Dashboard</Link>

      <div class="page-header">
        <h1>My Profile</h1>
        <p style="color:#9ca3af;">Manage your account information and preferences</p>
      </div>

      <div class="content-layout">

        <!-- ── Left sidebar ── -->
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

            <!-- Logout — router.post(), no form data needed -->
            <button
              type="button"
              class="btn btn-danger"
              style="width:100%;margin-top:1.5rem;"
              @click="logout"
            >
              Sign Out
            </button>
          </div>
        </div>

        <!-- ── Right column ── -->
        <div style="display:flex;flex-direction:column;gap:1.5rem;">

          <!-- Profile information -->
          <div class="card">
            <h2 class="card-title">Profile Information</h2>
            <form @submit.prevent="submitProfile">
              <div class="form-row">
                <div class="form-group">
                  <label>First Name</label>
                  <input v-model="profileForm.first_name" type="text" required>
                  <span v-if="profileForm.errors.first_name" class="text-danger">{{ profileForm.errors.first_name }}</span>
                </div>
                <div class="form-group">
                  <label>Last Name</label>
                  <input v-model="profileForm.last_name" type="text" required>
                  <span v-if="profileForm.errors.last_name" class="text-danger">{{ profileForm.errors.last_name }}</span>
                </div>
              </div>
              <div class="form-group">
                <label>Email Address</label>
                <input v-model="profileForm.email" type="email" required>
                <span v-if="profileForm.errors.email" class="text-danger">{{ profileForm.errors.email }}</span>
              </div>
              <div class="form-row">
                <div class="form-group">
                  <label>Phone Number</label>
                  <input v-model="profileForm.phone" type="tel" placeholder="Optional">
                </div>
                <div class="form-group">
                  <label>Student / Staff ID</label>
                  <input v-model="profileForm.student_staff_id" type="text" placeholder="Optional">
                </div>
              </div>
              <div class="action-buttons">
                <button
                  type="submit"
                  class="btn btn-primary"
                  :disabled="profileForm.processing"
                >
                  {{ profileForm.processing ? 'Saving…' : 'Update Profile' }}
                </button>
              </div>
            </form>
          </div>

          <!-- Change password -->
          <div class="card">
            <h2 class="card-title">Change Password</h2>
            <form @submit.prevent="submitPassword">
              <div class="form-group">
                <label>Current Password</label>
                <input v-model="passwordForm.current_password" type="password" required autocomplete="current-password">
                <span v-if="passwordForm.errors.current_password" class="text-danger">{{ passwordForm.errors.current_password }}</span>
              </div>
              <div class="form-group">
                <label>New Password</label>
                <input v-model="passwordForm.password" type="password" required autocomplete="new-password">
                <span v-if="passwordForm.errors.password" class="text-danger">{{ passwordForm.errors.password }}</span>
              </div>
              <div class="form-group">
                <label>Confirm New Password</label>
                <input v-model="passwordForm.password_confirmation" type="password" required autocomplete="new-password">
              </div>
              <div class="action-buttons">
                <button
                  type="submit"
                  class="btn btn-primary"
                  :disabled="passwordForm.processing"
                >
                  {{ passwordForm.processing ? 'Updating…' : 'Update Password' }}
                </button>
              </div>
            </form>
          </div>

          <!-- Notification preferences -->
          <div class="card">
            <h2 class="card-title">Notification Preferences</h2>
            <form @submit.prevent="submitPrefs">
              <div class="toggle-item">
                <div class="toggle-info">
                  <h4>Email Notifications</h4>
                  <p>Receive email updates when your ticket status changes</p>
                </div>
                <input
                  v-model="prefsForm.email_notifications"
                  type="checkbox"
                  style="width:auto;accent-color:#2dd4bf;cursor:pointer;"
                >
              </div>
              <div class="action-buttons" style="margin-top:1rem;">
                <button
                  type="submit"
                  class="btn btn-primary"
                  :disabled="prefsForm.processing"
                >
                  {{ prefsForm.processing ? 'Saving…' : 'Save Preferences' }}
                </button>
              </div>
            </form>
          </div>

          <!-- Danger zone -->
          <div class="card" style="border-color:rgba(239,68,68,0.3);">
            <h2 class="card-title" style="color:#ef4444;">Danger Zone</h2>
            <p style="color:#9ca3af;margin-bottom:1rem;font-size:0.9rem;">
              Sign out of your LabFix account on this device.
            </p>
            <button type="button" class="btn btn-danger" @click="logout">
              Sign Out
            </button>
          </div>

        </div>
      </div>
    </div>
  </AppLayout>
</template>