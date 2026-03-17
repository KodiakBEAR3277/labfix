<script setup>
// Register.vue
// Mirrors: resources/views/auth/registration.blade.php
// Layout:  AuthLayout.vue
// Path:    resources/js/Pages/Auth/Register.vue
//
// Uses a native <form> POST to /register — no fetch, no JSON.
// Laravel's session-based AuthController needs zero changes:
//   - back()->withErrors() on failed registration → full-page reload back to /register
//   - redirectBasedOnRole() on success → full-page redirect to dashboard
//
// CSRF token is bound from <meta name="csrf-token"> which entry.blade.php outputs.
//
// CSS: All classes from public/css/layouts/auth.css — no new styles needed.
// Note: .auth-right-section.scrollable handles overflow for this taller form.

import AuthLayout from '../../Layouts/AuthLayout.vue'

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? ''
</script>

<template>
  <AuthLayout>

    <!-- Nav: "Already have an Account? Sign In" -->
    <template #nav>
      <nav class="auth-nav">
        <a href="/" class="auth-logo">
          <div class="auth-logo-icon"></div>
          <span>LabFix</span>
        </a>
        <div class="auth-nav-right">
          <span class="auth-nav-text">Already have an Account?</span>
          <a href="/login" class="auth-signin-btn">Sign In</a>
        </div>
      </nav>
    </template>

    <!-- Left panel tagline -->
    <template #tagline>
      Sign <span class="auth-highlight">Up</span>
    </template>

    <!-- Right panel: registration card (scrollable for taller form) -->
    <div class="auth-right-section scrollable">
      <div class="auth-registration-container">

        <div class="auth-form-header">
          <h2 class="auth-form-title">Create your account to get Started</h2>
        </div>

        <!-- Native POST — Laravel handles CSRF, validation, and redirect -->
        <form action="/register" method="POST">
          <input type="hidden" name="_token" :value="csrfToken">

          <!-- First / Last name row -->
          <div class="auth-form-row">
            <div class="auth-form-group">
              <label for="first_name">First Name</label>
              <input
                id="first_name"
                type="text"
                name="first_name"
                autocomplete="given-name"
                required
              >
            </div>
            <div class="auth-form-group">
              <label for="last_name">Last Name</label>
              <input
                id="last_name"
                type="text"
                name="last_name"
                autocomplete="family-name"
                required
              >
            </div>
          </div>

          <!-- Email -->
          <div class="auth-form-group">
            <label for="email">Email Address</label>
            <input
              id="email"
              type="email"
              name="email"
              autocomplete="email"
              required
            >
          </div>

          <!-- Role -->
          <div class="auth-form-group">
            <label for="role">Role</label>
            <select id="role" name="role" required>
              <option value="">Select your role</option>
              <option value="student">Student</option>
              <option value="staff">Staff</option>
              <option value="it-support">IT Support</option>
            </select>
          </div>

          <!-- Password -->
          <div class="auth-form-group">
            <label for="password">Password</label>
            <input
              id="password"
              type="password"
              name="password"
              autocomplete="new-password"
              required
            >
          </div>

          <!-- Confirm Password -->
          <div class="auth-form-group">
            <label for="password_confirmation">Confirm Password</label>
            <input
              id="password_confirmation"
              type="password"
              name="password_confirmation"
              autocomplete="new-password"
              required
            >
          </div>

          <!-- Terms checkbox -->
          <div class="auth-checkbox-group">
            <input id="terms" type="checkbox" name="terms" required>
            <label for="terms">I have read the terms and conditions</label>
          </div>

          <button type="submit" class="auth-submit-btn">Create Account</button>
        </form>

        <div class="auth-divider">or continue with</div>

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

      </div>
    </div>

  </AuthLayout>
</template>