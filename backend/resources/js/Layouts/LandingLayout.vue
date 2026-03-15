<script setup>
// LandingLayout.vue
// Mirrors: resources/views/layouts/landing.blade.php
// Used by: Landing.vue, Contact.vue (public-facing pages)

import NavLanding from '../Components/Nav/NavLanding.vue'
</script>

<template>
  <div class="landing-layout">
    <NavLanding />

    <main>
      <slot />
    </main>

    <footer v-if="$slots.footer">
      <slot name="footer" />
    </footer>
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
.landing-layout .container {
  max-width: 1200px;
  padding: 32px 32px;
  margin: 0 auto;
}
</style>