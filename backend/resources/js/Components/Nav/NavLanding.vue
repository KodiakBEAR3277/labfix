<script setup>
// NavLanding.vue
// Mirrors: resources/views/components/nav/landing (auth.blade.php nav variant)
// Used by: LandingLayout.vue, Landing.vue
//
// Props allow this nav to be reused on auth pages too (login/register)
// by swapping the right-side link — matching how the blade version works
// with $navText, $navLink, $navLinkText variables.

const props = defineProps({
  navText: {
    type: String,
    default: "Don't have an account?"
  },
  navLinkText: {
    type: String,
    default: 'Sign Up'
  },
  navLinkHref: {
    type: String,
    default: '/register'
  },
  navLinkClass: {
    type: String,
    default: 'auth-signup-btn'
  },
  // Pass true on auth pages to use auth-nav styling variant
  authVariant: {
    type: Boolean,
    default: false
  }
})
</script>

<template>
  <nav :class="authVariant ? 'auth-nav' : ''">
    <a href="/" class="logo">LabFix</a>

    <div class="nav-menu">
      <a href="/#features" class="nav-link">Features</a>
      <a href="/#about"    class="nav-link">About</a>
      <a href="/contact"   class="nav-link">Contact</a>
    </div>

    <div :class="authVariant ? 'auth-nav-right' : 'nav-menu'">
      <span :class="authVariant ? 'auth-nav-text' : 'nav-link'">
        {{ navText }}
      </span>
      <a
        :href="navLinkHref"
        :class="navLinkClass"
      >
        {{ navLinkText }}
      </a>
    </div>
  </nav>
</template>