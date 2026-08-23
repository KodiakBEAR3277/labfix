<script setup>
// NavUser.vue
// Path: resources/js/Components/Nav/NavUser.vue
//
// Uses Inertia's <Link> component instead of <a href> for all internal navigation.

import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'

const page = usePage()

const user = computed(() => page.props.auth.user)
const institution = computed(() => user.value?.institution)

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
    <Link href="/user/dashboard" class="logo">
      LabFix<span v-if="institution" style="font-size:0.65rem;color:#9ca3af;font-weight:400;margin-left:0.4rem;">· {{ institution.name }}</span>
    </Link>

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

      <Link href="/profile" class="user-profile">
        <div class="user-avatar">{{ user?.initials ?? 'U' }}</div>
        <span>{{ user?.first_name ?? 'User' }} {{ user?.last_name ?? '' }}</span>
      </Link>
    </div>
  </nav>
</template>