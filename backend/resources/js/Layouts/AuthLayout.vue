<script setup>
// AuthLayout.vue
// Mirrors: resources/views/layouts/guest.blade.php
// Used by: Login.vue, Register.vue
//
// Renders the full-page auth shell:
//   - .auth-background  (blurred lab image overlay, defined in auth.css)
//   - <slot name="nav"> (each page passes its own nav since login/register differ)
//   - <slot>            (the form card content)
//
// All classes come from public/assets/css/layouts/auth.css — no scoped styles needed.
</script>

<template>
  <div class="auth-page">

    <!-- Blurred background image (styled entirely in auth.css) -->
    <div class="auth-background"></div>

    <!-- Nav slot: Login passes "Sign Up" link, Register passes "Sign In" link -->
    <slot name="nav" />

    <!-- Main split layout -->
    <div class="auth-container">

      <!-- Left branding panel -->
      <div class="auth-left-section">
        <div class="auth-brand-logo">
          <div class="auth-brand-icon"></div>
          <h1 class="auth-brand-name">LabFix</h1>
        </div>
        <!-- tagline slot so Login says "Sign in" and Register says "Sign Up" -->
        <p class="auth-brand-tagline">
          <slot name="tagline">
            Sign <span class="auth-highlight">in</span>
          </slot>
        </p>
      </div>

      <!-- Right form panel -->
      <slot />

    </div>
  </div>
</template>