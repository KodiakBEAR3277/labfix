<script setup>
// NavAdmin.vue
// Path: resources/js/Components/Nav/NavAdmin.vue
//
// Uses Inertia's <Link> component for all internal navigation.
// page.url is reactive and updates immediately after each Inertia navigation,
// so active link detection always reflects the current page without a reload.

import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'

const page = usePage()

const user = computed(() => page.props.auth.user)

const currentUrl = computed(() => page.url)

function isActive(path) {
  return currentUrl.value === path
}

function isActivePrefix(prefix) {
  return currentUrl.value.startsWith(prefix)
}
</script>

<template>
  <nav>
    <Link href="/admin/dashboard" class="logo">LabFix</Link>

    <div class="nav-menu">
      <Link
        href="/admin/dashboard"
        class="nav-link"
        :class="{ active: isActive('/admin/dashboard') }"
      >
        Dashboard
      </Link>

      <Link
        href="/admin/tickets"
        class="nav-link"
        :class="{ active: isActivePrefix('/admin/tickets') }"
      >
        Ticket Management
      </Link>

      <Link
        href="/admin/users"
        class="nav-link"
        :class="{ active: isActivePrefix('/admin/users') }"
      >
        User Management
      </Link>

      <Link
        href="/admin/labs"
        class="nav-link"
        :class="{ active: isActivePrefix('/admin/labs') }"
      >
        Lab Configuration
      </Link>

      <Link
        href="/admin/settings"
        class="nav-link"
        :class="{ active: isActive('/admin/settings') }"
      >
        System Settings
      </Link>

      <Link href="/profile" class="user-profile">
        <div class="user-avatar">{{ user?.initials ?? 'AD' }}</div>
        <span>{{ user?.first_name ?? 'Admin' }} {{ user?.last_name ?? '' }}</span>
      </Link>
    </div>
  </nav>
</template>