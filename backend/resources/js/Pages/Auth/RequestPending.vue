<script setup>
// Pages/Auth/RequestPending.vue
// Path: resources/js/Pages/Auth/RequestPending.vue
//
// Shown after someone submits a "create new institution" registration.
// No live account exists yet at this point — the request sits pending
// until a superadmin reviews it, so there is nothing to log into here.

import { Link } from '@inertiajs/vue3'
import AuthLayout from '../../Layouts/AuthLayout.vue'
import Icon from '../../Components/Icon.vue'
</script>

<template>
  <AuthLayout>

    <template #nav>
      <nav class="auth-nav">
        <Link href="/" class="auth-logo">
          <div class="auth-logo-icon"></div>
          <span>LabFix</span>
        </Link>
        <div class="auth-nav-right">
          <span class="auth-nav-text">Already have an Account?</span>
          <Link href="/login" class="auth-signin-btn">Sign In</Link>
        </div>
      </nav>
    </template>

    <template #tagline>
      Almost <span class="auth-highlight">there</span>
    </template>

    <div class="auth-right-section">
      <div class="auth-registration-container" style="text-align:center;">
        <div style="font-size:3rem;margin-bottom:1rem;"><Icon name="edit" /></div>
        <h2 class="auth-form-title">Request submitted</h2>
        <p style="color:#9ca3af;line-height:1.6;margin-top:1rem;">
          Thanks for signing up your school. A LabFix administrator will review your
          request shortly. Once approved, you'll be able to sign in with the email
          and password you just provided.
        </p>
        <Link
          href="/"
          class="auth-submit-btn"
          style="display:inline-block;text-decoration:none;margin-top:2rem;"
        >
          Back to Home
        </Link>
      </div>
    </div>

  </AuthLayout>
</template>