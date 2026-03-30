<script setup>
// Pages/Auth/Register.vue
// Path: resources/js/Pages/Auth/Register.vue
//
// useForm() replaces the native <form> POST — no full-page reload on
// success or validation failure. Errors from back()->withErrors() surface
// in form.errors.field_name inline next to each input.

import { Link, useForm } from '@inertiajs/vue3'
import AuthLayout from '../../Layouts/AuthLayout.vue'

const form = useForm({
  first_name:            '',
  last_name:             '',
  email:                 '',
  role:                  '',
  password:              '',
  password_confirmation: '',
  terms:                 false,
})

function submit() {
  form.post('/register', {
    onError: () => form.reset('password', 'password_confirmation'),
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

        <form @submit.prevent="submit">

          <div class="auth-form-row">
            <div class="auth-form-group">
              <label for="first_name">First Name</label>
              <input
                id="first_name"
                v-model="form.first_name"
                type="text"
                autocomplete="given-name"
                required
              >
              <span v-if="form.errors.first_name" class="text-danger">{{ form.errors.first_name }}</span>
            </div>
            <div class="auth-form-group">
              <label for="last_name">Last Name</label>
              <input
                id="last_name"
                v-model="form.last_name"
                type="text"
                autocomplete="family-name"
                required
              >
              <span v-if="form.errors.last_name" class="text-danger">{{ form.errors.last_name }}</span>
            </div>
          </div>

          <div class="auth-form-group">
            <label for="email">Email Address</label>
            <input
              id="email"
              v-model="form.email"
              type="email"
              autocomplete="email"
              required
            >
            <span v-if="form.errors.email" class="text-danger">{{ form.errors.email }}</span>
          </div>

          <div class="auth-form-group">
            <label for="role">Role</label>
            <select id="role" v-model="form.role" required>
              <option value="">Select your role</option>
              <option value="student">Student</option>
              <option value="staff">Staff</option>
              <option value="it-support">IT Support</option>
            </select>
            <span v-if="form.errors.role" class="text-danger">{{ form.errors.role }}</span>
          </div>

          <div class="auth-form-group">
            <label for="password">Password</label>
            <input
              id="password"
              v-model="form.password"
              type="password"
              autocomplete="new-password"
              required
            >
            <span v-if="form.errors.password" class="text-danger">{{ form.errors.password }}</span>
          </div>

          <div class="auth-form-group">
            <label for="password_confirmation">Confirm Password</label>
            <input
              id="password_confirmation"
              v-model="form.password_confirmation"
              type="password"
              autocomplete="new-password"
              required
            >
          </div>

          <div class="auth-checkbox-group">
            <input id="terms" v-model="form.terms" type="checkbox" required>
            <label for="terms">I have read the terms and conditions</label>
            <span v-if="form.errors.terms" class="text-danger" style="display:block;">{{ form.errors.terms }}</span>
          </div>

          <button
            type="submit"
            class="auth-submit-btn"
            :disabled="form.processing"
            :style="form.processing ? 'opacity:0.7;cursor:not-allowed;' : ''"
          >
            {{ form.processing ? 'Creating account…' : 'Create Account' }}
          </button>

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