<script setup>
// Pages/Auth/Login.vue
// Path: resources/js/Pages/Auth/Login.vue
//
// useForm() from @inertiajs/vue3 replaces the native <form> POST.
// This means Inertia intercepts the submission as XHR — no full-page reload
// on either success or failure.
//
// On success:  Laravel redirects to the dashboard; Inertia follows it client-side.
// On failure:  back()->withErrors() sends errors back; Inertia populates
//              form.errors without a reload, preserving the email field value.
//
// form.processing is true while the request is in-flight — used to disable
// the submit button and prevent double-submissions.

import { Link, useForm } from '@inertiajs/vue3'
import AuthLayout from '../../Layouts/AuthLayout.vue'

const form = useForm({
  email:    '',
  password: '',
})

function submit() {
  form.post('/login', {
    // Clear the password field on any error so the user re-types it
    onError: () => form.reset('password'),
  })
}
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
          <span class="auth-nav-text">Don't have an account?</span>
          <Link href="/register" class="auth-signup-btn">Sign Up</Link>
        </div>
      </nav>
    </template>

    <template #tagline>
      Sign <span class="auth-highlight">in</span>
    </template>

    <div class="auth-right-section">
      <div class="auth-login-container">

        <div class="auth-login-header">
          <h2 class="auth-login-title">SIGN<span class="auth-highlight">IN</span></h2>
          <p class="auth-login-subtitle">Sign in with email address</p>
        </div>

        <!-- Inline error banner — shown when Laravel sends back withErrors() -->
        <div
          v-if="form.errors.email"
          class="alert alert-danger"
          style="margin-bottom:1rem;"
        >
          {{ form.errors.email }}
        </div>

        <form @submit.prevent="submit">

          <div class="auth-form-group">
            <input
              v-model="form.email"
              type="email"
              placeholder="Yourname@gmail.com"
              autocomplete="email"
              required
              autofocus
              :class="{ 'is-invalid': form.errors.email }"
            >
          </div>

          <div class="auth-form-group">
            <input
              v-model="form.password"
              type="password"
              placeholder="Password"
              autocomplete="current-password"
              required
              :class="{ 'is-invalid': form.errors.password }"
            >
          </div>

          <button
            type="submit"
            class="auth-submit-btn"
            :disabled="form.processing"
            :style="form.processing ? 'opacity:0.7;cursor:not-allowed;' : ''"
          >
            {{ form.processing ? 'Signing in…' : 'Sign In' }}
          </button>

        </form>

        <div class="auth-divider">Or continue with</div>

        <div class="auth-social-buttons">
          <a href="#" class="auth-social-btn">
            <div class="auth-social-icon auth-google-icon"></div>
            Google
          </a>
          <a href="#" class="auth-social-btn">
            <div class="auth-social-icon auth-facebook-icon"></div>
            Facebook
          </a>
        </div>

        <div class="auth-footer-text">
          By signing in you agree with our <a href="#">Terms and Conditions</a>
        </div>

      </div>
    </div>

  </AuthLayout>
</template>