<script setup>
// Pages/Auth/Register.vue
// Path: resources/js/Pages/Auth/Register.vue
//
// Two registration paths, chosen via the mode toggle at the top of the form:
//   - "join": search for an existing, active institution — becomes a
//     student account under it.
//   - "create": register a brand-new institution — becomes its first admin.
//
// Submission still goes through useForm() exactly as before; the only
// addition is the institution fields and the search-as-you-type combobox
// for the "join" path. Errors from back()->withErrors() surface the same
// way they already do for every other field.

import { Link, useForm } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import AuthLayout from '../../Layouts/AuthLayout.vue'

const form = useForm({
  mode: 'join',
  institution_id: null,
  institution_name: '',
  institution_contact_email: '',
  first_name: '',
  last_name: '',
  email: '',
  password: '',
  password_confirmation: '',
  terms: false,
})

// ── Institution search (join mode only) ─────────────────────────────────
const institutionSearch = ref('')
const institutionResults = ref([])
const searchLoading = ref(false)
let searchTimer = null

watch(institutionSearch, (value) => {
  form.institution_id = null // editing the text invalidates the previous pick

  clearTimeout(searchTimer)
  if (!value.trim()) {
    institutionResults.value = []
    return
  }

  searchTimer = setTimeout(async () => {
    searchLoading.value = true
    try {
      const res = await fetch(`/api/institutions/search?q=${encodeURIComponent(value)}`)
      institutionResults.value = res.ok ? await res.json() : []
    } catch {
      institutionResults.value = []
    } finally {
      searchLoading.value = false
    }
  }, 300)
})

function selectInstitution(institution) {
  form.institution_id = institution.id
  institutionSearch.value = institution.name
  institutionResults.value = []
}

function switchMode(mode) {
  form.mode = mode
  form.institution_id = null
  form.institution_name = ''
  institutionSearch.value = ''
  institutionResults.value = []
}

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

        <!-- Institution mode toggle — inline styled since this is a new
             structural element with no existing auth.css equivalent -->
        <div style="display:flex;gap:0.75rem;margin-bottom:1.5rem;">
          <button
            type="button"
            @click="switchMode('join')"
            :style="{
              flex: 1, padding: '0.75rem', borderRadius: '8px', cursor: 'pointer',
              border: form.mode === 'join' ? '1.5px solid #2dd4bf' : '1px solid rgba(255,255,255,0.15)',
              background: form.mode === 'join' ? 'rgba(45,212,191,0.1)' : 'transparent',
              color: form.mode === 'join' ? '#2dd4bf' : '#9ca3af',
              fontWeight: 600,
            }"
          >
            I'm joining my school
          </button>
          <button
            type="button"
            @click="switchMode('create')"
            :style="{
              flex: 1, padding: '0.75rem', borderRadius: '8px', cursor: 'pointer',
              border: form.mode === 'create' ? '1.5px solid #2dd4bf' : '1px solid rgba(255,255,255,0.15)',
              background: form.mode === 'create' ? 'rgba(45,212,191,0.1)' : 'transparent',
              color: form.mode === 'create' ? '#2dd4bf' : '#9ca3af',
              fontWeight: 600,
            }"
          >
            My school isn't listed yet
          </button>
        </div>

        <form @submit.prevent="submit">

          <!-- Join an existing institution -->
          <div v-if="form.mode === 'join'" class="auth-form-group" style="position:relative;">
            <label for="institution-search">Your School</label>
            <input
              id="institution-search"
              v-model="institutionSearch"
              type="text"
              placeholder="Start typing your school's name…"
              autocomplete="off"
            >
            <div
              v-if="institutionResults.length"
              style="position:absolute;top:100%;left:0;right:0;background:#1e1e1e;border:1px solid rgba(45,212,191,0.3);border-radius:8px;margin-top:0.25rem;overflow:hidden;z-index:10;"
            >
              <div
                v-for="inst in institutionResults"
                :key="inst.id"
                @click="selectInstitution(inst)"
                style="padding:0.75rem 1rem;cursor:pointer;color:#d1d5db;"
              >
                {{ inst.name }}
              </div>
            </div>
            <p v-if="searchLoading" class="help-text">Searching…</p>
            <p v-else-if="institutionSearch && !institutionResults.length" class="help-text">
              No match yet — keep typing, or switch to "My school isn't listed yet" if it really isn't registered.
            </p>
            <span v-if="form.errors.institution_id" class="text-danger">{{ form.errors.institution_id }}</span>
          </div>

          <!-- Register a new institution -->
          <template v-else>
            <div class="auth-form-group">
              <label for="institution-name">School Name</label>
              <input
                id="institution-name"
                v-model="form.institution_name"
                type="text"
                placeholder="e.g., Surigao del Norte State University"
                required
              >
              <p class="help-text">You'll be set up as this school's administrator, and can add IT support and other admins afterward.</p>
              <span v-if="form.errors.institution_name" class="text-danger">{{ form.errors.institution_name }}</span>
            </div>
            <div class="auth-form-group">
              <label for="institution-contact-email">School Contact Email (optional)</label>
              <input
                id="institution-contact-email"
                v-model="form.institution_contact_email"
                type="email"
                placeholder="Defaults to your own email if left blank"
              >
              <span v-if="form.errors.institution_contact_email" class="text-danger">{{ form.errors.institution_contact_email }}</span>
            </div>
          </template>

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