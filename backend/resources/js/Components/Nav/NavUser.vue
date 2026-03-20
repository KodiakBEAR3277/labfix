<script setup>
// NavUser.vue
// Mirrors: resources/views/components/nav/user.blade.php
// Used by: All User role Inertia pages via AppLayout.vue
// Path:    resources/js/Components/Nav/NavUser.vue
//
// Active link detection uses Inertia's usePage() to read the current URL,
// replicating request()->routeIs() from the blade nav.
//
// User name and initials come from the globally shared auth.user prop
// set in HandleInertiaRequests::share() — no fetch needed.
//
// CSS: uses existing navigation.css classes — no new styles needed.

import { computed } from 'vue'
import { usePage, router } from '@inertiajs/vue3'

const page = usePage()

// auth.user is shared globally by HandleInertiaRequests
const user = computed(() => page.props.auth.user)

// Replicate request()->routeIs() using the current URL path
const currentPath = computed(() => window.location.pathname)

function isActive(path) {
  return currentPath.value === path
}

function isActivePrefix(prefix, exclude = null) {
  if (exclude && currentPath.value === exclude) return false
  return currentPath.value.startsWith(prefix)
}

// Logout via Inertia POST (keeps CSRF handling clean)
function logout() {
  router.post('/logout')
}
</script>

<template>
  <nav>
    <div class="logo">LabFix</div>

    <div class="nav-menu">
      <a
        href="/user/dashboard"
        class="nav-link"
        :class="{ active: isActive('/user/dashboard') }"
      >
        Dashboard
      </a>

      <a
        href="/user/reports/create"
        class="nav-link"
        :class="{ active: isActive('/user/reports/create') }"
      >
        Report Issue
      </a>

      <a
        href="/user/reports"
        class="nav-link"
        :class="{ active: isActivePrefix('/user/reports', '/user/reports/create') }"
      >
        My Reports
      </a>

      <a
        href="/user/knowledge-base"
        class="nav-link"
        :class="{ active: isActivePrefix('/user/knowledge-base') }"
      >
        Knowledge Base
      </a>

      <a
        href="/user/lab-status"
        class="nav-link"
        :class="{ active: isActive('/user/lab-status') }"
      >
        Lab Status
      </a>

      <!-- User profile avatar — matches blade's user-profile component -->
      <a href="/profile" class="user-profile">
        <div class="user-avatar">{{ user?.initials ?? 'U' }}</div>
        <span>{{ user?.first_name ?? 'User' }} {{ user?.last_name ?? '' }}</span>
      </a>
    </div>
  </nav>
</template>