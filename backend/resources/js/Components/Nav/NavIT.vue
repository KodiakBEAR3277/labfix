<script setup>
// NavIT.vue
// Path: resources/js/Components/Nav/NavIT.vue
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

function isActivePrefix(prefix, exclude = null) {
  if (exclude && currentUrl.value === exclude) return false
  return currentUrl.value.startsWith(prefix)
}
</script>

<template>
  <nav>
    <Link href="/it/dashboard" class="logo">LabFix</Link>

    <div class="nav-menu">
      <Link
        href="/it/dashboard"
        class="nav-link"
        :class="{ active: isActive('/it/dashboard') }"
      >
        Dashboard
      </Link>

      <Link
        href="/it/tickets"
        class="nav-link"
        :class="{ active: isActive('/it/tickets') }"
      >
        Ticket Queue
      </Link>

      <Link
        href="/it/assignments"
        class="nav-link"
        :class="{ active: isActivePrefix('/it/assignments') }"
      >
        My Assignments
      </Link>

      <Link
        href="/it/knowledge-base"
        class="nav-link"
        :class="{ active: isActivePrefix('/it/knowledge-base') }"
      >
        Knowledge Base
      </Link>

      <Link href="/profile" class="user-profile">
        <div class="user-avatar">{{ user?.initials ?? 'IT' }}</div>
        <span>
          {{ user?.first_name ?? 'IT' }} {{ user?.last_name ?? '' }}
          <span v-if="user?.role === 'admin'" style="font-size:0.7rem;color:#ef4444;">(Admin)</span>
        </span>
      </Link>
    </div>
  </nav>
</template>