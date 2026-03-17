<script setup>
// Login.vue
// Mirrors: resources/views/auth/login.blade.php
// Layout:  AuthLayout.vue
// Path:    resources/js/Pages/Auth/Login.vue
//
// Uses a native <form> POST to /login — no fetch, no JSON.
// Laravel's session-based AuthController needs zero changes:
//   - back()->withErrors() on failed login → full-page reload back to /login
//   - redirectBasedOnRole() on success → full-page redirect to dashboard
//
// CSRF token is bound from <meta name="csrf-token"> which entry.blade.php outputs.
//
// CSS: All classes from public/css/layouts/auth.css — no new styles needed.

import AuthLayout from '../../Layouts/AuthLayout.vue'

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? ''
</script>

<template>
  <AuthLayout>

    <!-- Nav: "Don't have an account? Sign Up" -->
    <template #nav>
      <nav class="auth-nav">
        <a href="/" class="auth-logo">
          <div class="auth-logo-icon"></div>
          <span>LabFix</span>
        </a>
        <div class="auth-nav-right">
          <span class="auth-nav-text">Don't have an account?</span>
          <a href="/register" class="auth-signup-btn">Sign Up</a>
        </div>
      </nav>
    </template>

    <!-- Left panel tagline -->
    <template #tagline>
      Sign <span class="auth-highlight">in</span>
    </template>

    <!-- Right panel: login card -->
    <div class="auth-right-section">
      <div class="auth-login-container">

        <div class="auth-login-header">
          <h2 class="auth-login-title">SIGN<span class="auth-highlight">IN</span></h2>
          <p class="auth-login-subtitle">Sign in with email address</p>
        </div>

        <!-- Native POST — Laravel handles CSRF, validation, and redirect -->
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