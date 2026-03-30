<script setup>
// Pages/Auth/Register.vue
// Path: resources/js/Pages/Auth/Register.vue
//
// Now a proper Inertia page — served via Inertia::render('Auth/Register').
// All navigation uses Inertia <Link>. No Vue Router imports.
// Native POST form is kept — same reasoning as Login.vue.

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
          <span class="auth-nav-text">Already have an Account?</span>
          <Link href="/login" class="auth-signin-btn">Sign In</Link>
        </div>
      </nav>
    </template>

    <template #tagline>
      Sign <span class="auth-highlight">Up</span>
    </template>

    <div class="auth-right-section scrollable">
      <div class="auth-registration-container">

        <div class="auth-form-header">
          <h2 class="auth-form-title">Create your account to get Started</h2>
        </div>

        <form action="/register" method="POST">
          <input type="hidden" name="_token" :value="csrfToken">

          <div class="auth-form-row">
            <div class="auth-form-group">
              <label for="first_name">First Name</label>
              <input id="first_name" type="text" name="first_name" autocomplete="given-name" required>
            </div>
            <div class="auth-form-group">
              <label for="last_name">Last Name</label>
              <input id="last_name" type="text" name="last_name" autocomplete="family-name" required>
            </div>
          </div>

          <div class="auth-form-group">
            <label for="email">Email Address</label>
            <input id="email" type="email" name="email" autocomplete="email" required>
          </div>

          <div class="auth-form-group">
            <label for="role">Role</label>
            <select id="role" name="role" required>
              <option value="">Select your role</option>
              <option value="student">Student</option>
              <option value="staff">Staff</option>
              <option value="it-support">IT Support</option>
            </select>
          </div>

          <div class="auth-form-group">
            <label for="password">Password</label>
            <input id="password" type="password" name="password" autocomplete="new-password" required>
          </div>

          <div class="auth-form-group">
            <label for="password_confirmation">Confirm Password</label>
            <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" required>
          </div>

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