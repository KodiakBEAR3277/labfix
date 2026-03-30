<script setup>
// Pages/Auth/Login.vue
// Path: resources/js/Pages/Auth/Login.vue
//
// Now a proper Inertia page — served via Inertia::render('Auth/Login').
// All navigation uses Inertia <Link>. No Vue Router imports.
//
// The login form remains a native HTML POST to /login.
// On success Laravel redirects to the dashboard — since everything is now
// in the same Inertia zone, Inertia handles the redirect response natively
// and there is no full-page reload. On validation failure, back()->withErrors()
// also works natively with Inertia and re-renders this page with errors.
//
// NOTE: Inertia error handling via usePage().props.errors is available if you
// want inline validation messages later — that's a separate improvement.

import { Link } from '@inertiajs/vue3'
import AuthLayout from '../../Layouts/AuthLayout.vue'

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? ''
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

        <form action="/login" method="POST">
          <input type="hidden" name="_token" :value="csrfToken">

          <div class="auth-form-group">
            <input
              type="email"
              name="email"
              placeholder="Yourname@gmail.com"
              autocomplete="email"
              required
              autofocus
            >
          </div>

          <div class="auth-form-group">
            <input
              type="password"
              name="password"
              placeholder="Password"
              autocomplete="current-password"
              required
            >
          </div>

          <button type="submit" class="auth-submit-btn">Sign In</button>
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