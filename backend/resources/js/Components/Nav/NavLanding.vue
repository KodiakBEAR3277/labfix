<script setup>
// NavLanding.vue
// Path: resources/js/Components/Nav/NavLanding.vue
//
// Now that Landing and Contact are Inertia pages, auth.user is shared
// globally by HandleInertiaRequests — no fetch('/auth/status') needed.
// The /auth/status endpoint has been removed from web.php entirely.
//
// All links use Inertia's <Link> — there is no longer a Vue Router zone
// to cross. The only plain <a> remaining are hash-scroll anchors (#features,
// #about) which are not route navigations.

import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'

const page = usePage()

// auth.user is null for guests, object for authenticated users
const user = computed(() => page.props.auth.user)

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
    <Link href="/" class="logo">LabFix</Link>

    <div class="nav-menu">
      <a href="/#features" class="nav-link">Features</a>
      <a href="/#about"    class="nav-link">About</a>
      <Link href="/contact" class="nav-link">Contact</Link>
    </div>

    <div class="nav-menu">
      <!-- Authenticated -->
      <template v-if="user">
        <span class="nav-link">Welcome back!</span>
        <Link :href="dashboardHref(user.role)" class="auth-signup-btn">Dashboard</Link>
      </template>

      <!-- Guest -->
      <template v-else>
        <span class="nav-link">Don't have an account?</span>
        <Link href="/register" class="auth-signup-btn">Sign Up</Link>
      </template>
    </div>
  </nav>
</template>