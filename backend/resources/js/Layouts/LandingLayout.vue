<script setup>
// LandingLayout.vue
// Mirrors: resources/views/layouts/landing.blade.php
// Used by: Landing.vue, Contact.vue (public-facing pages)
//
// The <head> meta/title handling is done by entry.blade.php.
// This layout only handles the visible structure: nav + content + footer.

import NavLanding from '../Components/Nav/NavLanding.vue'

const props = defineProps({
  // Lets individual pages customize the nav right-side link
  // (same flexibility as the blade $navText/$navLink variables)
  navText:     { type: String, default: "Don't have an account?" },
  navLinkText: { type: String, default: 'Sign Up' },
  navLinkHref: { type: String, default: '/register' },
})
</script>

<template>
  <div class="landing-layout">
    <NavLanding
      :nav-text="navText"
      :nav-link-text="navLinkText"
      :nav-link-href="navLinkHref"
    />

    <main>
      <!-- Default slot: page content goes here -->
      <slot />
    </main>

    <!-- Footer slot: optional, pages can inject footer content -->
    <footer v-if="$slots.footer">
      <slot name="footer" />
    </footer>
  </div>
</template>