<script setup>
// NavLanding.vue
// Path: resources/js/Components/Nav/NavLanding.vue
//
// This nav lives in the Vue Router public zone (not Inertia), so it uses
// Vue Router's <RouterLink> for navigation within the public SPA — this
// gives the no-reload experience for /, /contact, etc.
//
// Cross-zone links (/login, /register, /user/dashboard etc.) must stay as
// plain <a href> because they cross into the Inertia zone, where a full
// browser navigation is required to boot the Inertia app properly.
// Using RouterLink or Inertia Link across the zone boundary would break routing.
//
// Auth check: fetches GET /auth/status with credentials: 'same-origin' so
// the Laravel session cookie is sent. Without this, Laravel always returns guest.

import { ref, onMounted } from 'vue'
import { RouterLink } from 'vue-router'

const user = ref(null)
const loading = ref(true)

onMounted(async () => {
  try {
    const res = await fetch('/auth/status', {
      credentials: 'same-origin',
      headers: { 'Accept': 'application/json' }
    })
    const data = await res.json()
    user.value = data.user ?? false
  } catch {
    user.value = false
  } finally {
    loading.value = false
  }
})

function dashboardHref(role) {
  switch (role) {
    case 'admin':      return '/admin/dashboard'
    case 'it-support': return '/it/dashboard'
    default:           return '/user/dashboard'
  }
}
</script>

<template>
  <nav>
    <!-- RouterLink within the public SPA zone -->
    <RouterLink to="/" class="logo">LabFix</RouterLink>

    <div class="nav-menu">
      <a href="/#features" class="nav-link">Features</a>
      <a href="/#about"    class="nav-link">About</a>
      <!-- /contact is also in the public SPA zone — use RouterLink -->
      <RouterLink to="/contact" class="nav-link">Contact</RouterLink>
    </div>

    <div class="nav-menu">
      <template v-if="loading" />

      <!-- Authenticated: plain <a> to cross into Inertia zone -->
      <template v-else-if="user">
        <span class="nav-link">Welcome back!</span>
        <a :href="dashboardHref(user.role)" class="auth-signup-btn">Dashboard</a>
      </template>

      <!-- Guest: plain <a> to cross into Inertia zone -->
      <template v-else>
        <span class="nav-link">Don't have an account?</span>
        <a href="/register" class="auth-signup-btn">Sign Up</a>
      </template>
    </div>
  </nav>
</template>