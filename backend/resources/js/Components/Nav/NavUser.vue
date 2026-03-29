<script setup>
// NavUser.vue
// Path: resources/js/Components/Nav/NavUser.vue
//
// Uses Inertia's <Link> component instead of <a href> for all internal navigation.
// This gives the full SPA "no full-page reload" experience — Inertia intercepts
// the click, fetches only the new page's props via XHR, and swaps the component
// without a browser reload.
//
// usePage().url is Inertia's reactive current URL — more reliable than
// window.location.pathname because it updates instantly after navigation
// without needing a page reload for the active class to reflect correctly.

import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'

const page = usePage()

const user = computed(() => page.props.auth.user)

// Inertia's reactive URL — updates immediately after each navigation
const currentUrl = computed(() => page.url)

function isActive(path) {
  return currentUrl.value === path
}

function isActivePrefix(prefix, exclude = null) {
  if (exclude && currentUrl.value === exclude) return false
  return currentUrl.value.startsWith(prefix)
}
</script>

<template>
  <nav>
    <Link href="/user/dashboard" class="logo">LabFix</Link>

    <div class="nav-menu">
      <Link
        href="/user/dashboard"
        class="nav-link"
        :class="{ active: isActive('/user/dashboard') }"
      >
        Dashboard
      </Link>

      <Link
        href="/user/reports/create"
        class="nav-link"
        :class="{ active: isActive('/user/reports/create') }"
      >
        Report Issue
      </Link>

      <Link
        href="/user/reports"
        class="nav-link"
        :class="{ active: isActivePrefix('/user/reports', '/user/reports/create') }"
      >
        My Reports
      </Link>

      <Link
        href="/user/knowledge-base"
        class="nav-link"
        :class="{ active: isActivePrefix('/user/knowledge-base') }"
      >
        Knowledge Base
      </Link>

      <Link
        href="/user/lab-status"
        class="nav-link"
        :class="{ active: isActive('/user/lab-status') }"
      >
        Lab Status
      </Link>

      <!-- Profile avatar — also a Link -->
      <Link href="/profile" class="user-profile">
        <div class="user-avatar">{{ user?.initials ?? 'U' }}</div>
        <span>{{ user?.first_name ?? 'User' }} {{ user?.last_name ?? '' }}</span>
      </Link>
    </div>
  </nav>
</template>