<script setup>
// NavIT.vue
// Mirrors: resources/views/components/nav/it.blade.php
// Path:    resources/js/Components/Nav/NavIT.vue

import { computed } from 'vue'
import { usePage, router } from '@inertiajs/vue3'

const user = computed(() => usePage().props.auth.user)

const currentPath = computed(() => window.location.pathname)

function isActive(path) {
  return currentPath.value === path
}

function isActivePrefix(prefix, exclude = null) {
  if (exclude && currentPath.value === exclude) return false
  return currentPath.value.startsWith(prefix)
}
</script>

<template>
  <nav>
    <div class="logo">LabFix</div>
    <div class="nav-menu">
      <a href="/it/dashboard"    class="nav-link" :class="{ active: isActive('/it/dashboard') }">Dashboard</a>
      <a href="/it/tickets"      class="nav-link" :class="{ active: isActive('/it/tickets') }">Ticket Queue</a>
      <a href="/it/assignments"  class="nav-link" :class="{ active: isActivePrefix('/it/assignments') }">My Assignments</a>
      <a href="/it/knowledge-base" class="nav-link" :class="{ active: isActivePrefix('/it/knowledge-base') }">Knowledge Base</a>

      <a href="/profile" class="user-profile">
        <div class="user-avatar">{{ user?.initials ?? 'IT' }}</div>
        <span>
          {{ user?.first_name ?? 'IT' }} {{ user?.last_name ?? '' }}
          <span v-if="user?.role === 'admin'" style="font-size:0.7rem;color:#ef4444;">(Admin)</span>
        </span>
      </a>
    </div>
  </nav>
</template>