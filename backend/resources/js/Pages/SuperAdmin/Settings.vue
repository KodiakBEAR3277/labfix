<script setup>
// Pages/SuperAdmin/Settings.vue
// Path: resources/js/Pages/SuperAdmin/Settings.vue
//
// Platform-level settings only — the name and contact details shown on
// the public Contact page (web and mobile), via /api/contact-info. This
// is deliberately separate from Admin/Settings.vue, which is per-institution
// and has nothing to do with the platform itself.

import AppLayout from '../../Layouts/AppLayout.vue'
import { Link, useForm, router, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({
  settings: Object,
})

const user = computed(() => usePage().props.auth.user)

const form = useForm({
  platform_name:          props.settings.platform_name          ?? '',
  platform_contact_email: props.settings.platform_contact_email ?? '',
  platform_contact_phone: props.settings.platform_contact_phone ?? '',
})

function submit() {
  form.post('/superadmin/settings/update')
}

function logout() {
  router.post('/logout')
}
</script>

<template>
  <AppLayout>
    <template #nav>
      <nav style="display:flex;justify-content:space-between;align-items:center;padding:1rem 2rem;">
        <span class="logo">
          LabFix<span style="font-size:0.7rem;color:#9ca3af;font-weight:400;margin-left:0.5rem;">· Platform Oversight</span>
        </span>
        <div style="display:flex;align-items:center;gap:1.5rem;">
          <Link href="/superadmin/institutions" style="color:#9ca3af;text-decoration:none;font-size:0.9rem;">Institutions</Link>
          <Link href="/superadmin/requests" style="color:#9ca3af;text-decoration:none;font-size:0.9rem;">Requests</Link>
          <Link href="/superadmin/settings" style="color:#2dd4bf;text-decoration:none;font-size:0.9rem;font-weight:600;">Settings</Link>
          <span style="color:#9ca3af;font-size:0.9rem;">{{ user?.first_name }} {{ user?.last_name }}</span>
          <button type="button" class="btn btn-secondary" @click="logout">Sign Out</button>
        </div>
      </nav>
    </template>

    <div class="container">
      <div class="page-header">
        <h1>Platform Settings</h1>
        <p style="color:#9ca3af;">
          What the public Contact page shows, on both web and mobile. This is about
          LabFix the platform itself, not any one institution.
        </p>
      </div>

      <div class="card" style="max-width:600px;">
        <form @submit.prevent="submit">

          <div class="form-group">
            <label>Platform Name *</label>
            <input v-model="form.platform_name" type="text" placeholder="LabFix" required>
            <span v-if="form.errors.platform_name" class="text-danger">{{ form.errors.platform_name }}</span>
          </div>

          <div class="form-group">
            <label>Contact Email</label>
            <input v-model="form.platform_contact_email" type="email" placeholder="support@labfix.example">
            <span v-if="form.errors.platform_contact_email" class="text-danger">{{ form.errors.platform_contact_email }}</span>
          </div>

          <div class="form-group">
            <label>Contact Phone</label>
            <input v-model="form.platform_contact_phone" type="text" placeholder="Optional">
            <span v-if="form.errors.platform_contact_phone" class="text-danger">{{ form.errors.platform_contact_phone }}</span>
          </div>

          <div class="action-buttons">
            <button type="submit" class="btn btn-primary" :disabled="form.processing">
              {{ form.processing ? 'Saving…' : 'Save Settings' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>