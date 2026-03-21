<script setup>
// NavAdmin.vue
// Mirrors: resources/views/components/nav/admin.blade.php
// Path:    resources/js/Components/Nav/NavAdmin.vue

import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

const user = computed(() => usePage().props.auth.user)

const currentPath = computed(() => window.location.pathname)

function isActive(path) {
  return currentPath.value === path
}

function isActivePrefix(prefix) {
  return currentPath.value.startsWith(prefix)
}
</script>

<template>
  <nav>
    <div class="logo">LabFix</div>
    <div class="nav-menu">
      <a href="/admin/dashboard" class="nav-link" :class="{ active: isActive('/admin/dashboard') }">Dashboard</a>
      <a href="/admin/tickets"   class="nav-link" :class="{ active: isActivePrefix('/admin/tickets') }">Ticket Management</a>
      <a href="/admin/users"     class="nav-link" :class="{ active: isActivePrefix('/admin/users') }">User Management</a>
      <a href="/admin/labs"      class="nav-link" :class="{ active: isActivePrefix('/admin/labs') }">Lab Configuration</a>
      <a href="/admin/settings"  class="nav-link" :class="{ active: isActive('/admin/settings') }">System Settings</a>

      <a href="/profile" class="user-profile">
        <div class="user-avatar">{{ user?.initials ?? 'AD' }}</div>
        <span>{{ user?.first_name ?? 'Admin' }} {{ user?.last_name ?? '' }}</span>
      </a>
    </div>
  </nav>
</template>