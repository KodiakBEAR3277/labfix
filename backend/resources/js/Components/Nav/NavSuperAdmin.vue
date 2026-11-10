<script setup>
// NavSuperAdmin.vue
// Path: resources/js/Components/Nav/NavSuperAdmin.vue
//
// Platform-oversight nav for the superadmin, mirroring the NavAdmin / NavIT /
// NavUser components. The avatar/name button points at /profile, like the
// other navs, so there is no separate "Account" link.

import { computed } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'

const page = usePage()

const user = computed(() => page.props.auth.user)
const currentUrl = computed(() => page.url)

function isActive(path) {
  return currentUrl.value === path
}

function isActivePrefix(prefix) {
  return currentUrl.value.startsWith(prefix)
}

function logout() {
  router.post('/logout')
}
</script>

<template>
  <nav>
    <Link href="/superadmin/institutions" class="logo">
      LabFix<span style="font-size:0.65rem;color:#9ca3af;font-weight:400;margin-left:0.4rem;">· Platform Oversight</span>
    </Link>

    <div class="nav-menu">
      <Link
        href="/superadmin/institutions"
        class="nav-link"
        :class="{ active: isActivePrefix('/superadmin/institutions') }"
      >
        Institutions
      </Link>

      <Link
        href="/superadmin/requests"
        class="nav-link"
        :class="{ active: isActivePrefix('/superadmin/requests') }"
      >
        Requests
      </Link>

      <Link
        href="/superadmin/users"
        class="nav-link"
        :class="{ active: isActivePrefix('/superadmin/users') }"
      >
        Users
      </Link>

      <Link
        href="/superadmin/settings"
        class="nav-link"
        :class="{ active: isActive('/superadmin/settings') }"
      >
        Settings
      </Link>

      <Link href="/profile" class="user-profile">
        <div class="user-avatar">{{ user?.initials ?? 'SA' }}</div>
        <span>{{ user?.first_name ?? 'Superadmin' }} {{ user?.last_name ?? '' }}</span>
      </Link>

      <button type="button" class="btn btn-secondary" @click="logout">Sign Out</button>
    </div>
  </nav>
</template>
