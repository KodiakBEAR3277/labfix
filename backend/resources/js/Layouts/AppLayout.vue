<script setup>
// AppLayout.vue
// Mirrors: resources/views/layouts/app.blade.php
// Used by: All authenticated Inertia pages (User, IT, Admin)
// Path:    resources/js/Layouts/AppLayout.vue
//
// Wraps every authenticated page with the appropriate nav component.
// The nav slot pattern lets each role pass its own nav (NavUser, NavIT, NavAdmin)
// without AppLayout needing to know about roles directly.
//
// Flash messages (success/error) from Laravel session are shown here
// so every page gets them automatically — mirrors how blade's layouts/app.blade.php
// would show session flashes.
//
// CSS: uses layouts/app.blade.php classes — no new styles needed.

import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

const page = usePage()

// Flash messages shared from HandleInertiaRequests
const flash = computed(() => page.props.flash)
</script>

<template>
  <div class="app-layout">

    <!-- Nav slot: each page passes <template #nav><NavUser /></template> -->
    <slot name="nav" />

    <!-- Flash messages — equivalent to @if(session('success')) in blade -->
    <div
      v-if="flash.success"
      class="alert alert-success"
      style="margin: 1rem 2rem 0;"
    >
      {{ flash.success }}
    </div>
    <div
      v-if="flash.error"
      class="alert alert-danger"
      style="margin: 1rem 2rem 0;"
    >
      {{ flash.error }}
    </div>

    <!-- Page content -->
    <main>
      <slot />
    </main>

  </div>
</template>

<style>
/*
  Fix for the broken `body > .container` selector in landing.css.
  That rule was written expecting .container to be a direct child of <body>,
  but Vue wraps everything in .app-shell > .landing-layout first.
  We replicate the same rule here targeting the correct parent instead.
  Not scoped — needs to reach into child components like Landing.vue.
*/
.app-layout .container {
  max-width: 1200px;
  padding: 32px 32px;
  margin: 0 auto;
}
</style>