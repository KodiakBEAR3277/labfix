<script setup>
// Pages/SuperAdmin/Settings.vue
// Path: resources/js/Pages/SuperAdmin/Settings.vue
//
// Platform-level settings only — the name and contact details shown on
// the public Contact page (web and mobile), via /api/contact-info. This
// is deliberately separate from Admin/Settings.vue, which is per-institution
// and has nothing to do with the platform itself.

import AppLayout from '../../Layouts/AppLayout.vue'
import NavSuperAdmin from '../../Components/Nav/NavSuperAdmin.vue'
import { useForm } from '@inertiajs/vue3'

const props = defineProps({
  settings: Object,
})

const form = useForm({
  platform_name:          props.settings.platform_name          ?? '',
  platform_contact_email: props.settings.platform_contact_email ?? '',
  platform_contact_phone: props.settings.platform_contact_phone ?? '',
})

function submit() {
  form.post('/superadmin/settings/update')
}
</script>

<template>
  <AppLayout>
    <template #nav><NavSuperAdmin /></template>

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