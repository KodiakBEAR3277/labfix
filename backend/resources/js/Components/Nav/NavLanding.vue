<script setup>
// NavLanding.vue
// Mirrors: resources/views/components/nav/landing + @auth directive behavior
// Used by: LandingLayout.vue
//
// On mount, fetches GET /auth/status (session-based, no token needed) to
// check if a user is currently logged in via Laravel's session.
//
// If logged in:   shows "Welcome back!" + role-appropriate dashboard link
// If guest:       shows "Don't have an account?" + Sign Up link
//
// This replicates the blade's @auth / @else / @endauth block using v-if.
//
// IMPORTANT: credentials: 'same-origin' is required so the browser sends
// the Laravel session cookie with the fetch — without it Laravel always
// sees the request as a guest regardless of login state.
//
// CSS: uses existing nav classes from navigation.css / landing.css

import { ref, onMounted } from 'vue'

// null = still loading, false = guest, object = authenticated user
const user = ref(null)
const loading = ref(true)

onMounted(async () => {
  try {
    const res = await fetch('/auth/status', {
      credentials: 'same-origin',       // sends the Laravel session cookie
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

// Match Laravel's redirectBasedOnRole() logic
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
    <a href="/" class="logo">LabFix</a>

    <div class="nav-menu">
      <a href="/#features" class="nav-link">Features</a>
      <a href="/#about"    class="nav-link">About</a>
      <a href="/contact"   class="nav-link">Contact</a>
    </div>

    <div class="nav-menu">
      <!-- Still checking session — render nothing to avoid flicker -->
      <template v-if="loading" />

      <!-- @auth equivalent: user is logged in -->
      <template v-else-if="user">
        <span class="nav-link">Welcome back!</span>
        <a :href="dashboardHref(user.role)" class="auth-signup-btn">Dashboard</a>
      </template>

      <!-- @else equivalent: guest -->
      <template v-else>
        <span class="nav-link">Don't have an account?</span>
        <a href="/register" class="auth-signup-btn">Sign Up</a>
      </template>
    </div>
  </nav>
</template>