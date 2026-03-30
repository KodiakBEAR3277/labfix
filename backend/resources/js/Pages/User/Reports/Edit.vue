<script setup>
// Pages/User/Reports/Edit.vue
// Path: resources/js/Pages/User/Reports/Edit.vue
//
// useForm() replaces the native form POST — no reload on submit or validation error.
//
// File attachments: useForm() supports File objects natively.
// We collect selected files into form.new_attachments (an array) via a file
// input ref, then useForm serialises them as multipart automatically when
// form.post() detects File objects in the payload.
//
// NOTE: Because of the file upload, Inertia will send this as multipart/form-data
// automatically — no enctype attribute needed on the form element.

import AppLayout from '../../../Layouts/AppLayout.vue'
import NavUser from '../../../Components/Nav/NavUser.vue'
import { Link, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'

const props = defineProps({
  report: Object,
})

const form = useForm({
  title:           props.report.title       ?? '',
  category:        props.report.category    ?? 'hardware',
  description:     props.report.description ?? '',
  // new_attachments holds File objects picked by the user.
  // Existing attachments on the server are untouched — the controller
  // appends new ones to the existing array.
  new_attachments: [],
})

// File input ref — we read .files from it on change
const fileInput = ref(null)

function onFilesChange() {
  form.new_attachments = Array.from(fileInput.value?.files ?? [])
}

function submit() {
  // _method spoofing for PUT — useForm handles this via the method option.
  form.post(`/user/reports/${props.report.id}`, {
    // Inertia uses POST + _method:PUT for multipart uploads since browsers
    // don't support PUT with file payloads natively.
    method: 'put',
  })
}
</script>

<template>
  <AppLayout>
    <template #nav><NavUser /></template>

    <div class="container">
      <Link :href="`/user/reports/${report.id}`" class="back-btn">← Back to Report</Link>

      <div class="page-header">
        <h1>Edit Report</h1>
        <p>Update your report details before it's assigned to a technician</p>
      </div>

      <div class="card" style="max-width:800px;margin:0 auto;">
        <form @submit.prevent="submit">

          <div class="form-section">
            <h3 class="section-title">Report Details</h3>

            <div class="form-group">
              <label>Issue Title *</label>
              <input v-model="form.title" type="text" required>
              <span v-if="form.errors.title" class="text-danger">{{ form.errors.title }}</span>
            </div>

            <div class="form-group">
              <label>Category *</label>
              <select v-model="form.category" required>
                <option value="hardware">Hardware</option>
                <option value="software">Software</option>
                <option value="network">Network</option>
                <option value="other">Other</option>
              </select>
              <span v-if="form.errors.category" class="text-danger">{{ form.errors.category }}</span>
            </div>

            <div class="form-group">
              <label>Description *</label>
              <textarea v-model="form.description" rows="8" required></textarea>
              <p class="help-text">Minimum 10 characters. Be as detailed as possible.</p>
              <span v-if="form.errors.description" class="text-danger">{{ form.errors.description }}</span>
            </div>

            <!-- Existing attachments — display only, not re-uploaded -->
            <div class="form-group">
              <label>Current Attachments</label>
              <div v-if="report.attachments?.length" style="display:flex;flex-wrap:wrap;gap:1rem;margin-bottom:1rem;">
                <div
                  v-for="att in report.attachments"
                  :key="att"
                  style="background:rgba(45,212,191,0.1);padding:0.5rem 1rem;border-radius:6px;font-size:0.9rem;color:#2dd4bf;"
                >
                  📎 {{ att.split('/').pop() }}
                </div>
              </div>
              <p v-else style="color:#9ca3af;font-size:0.9rem;">No attachments</p>
            </div>

            <div class="form-group">
              <label>Add New Attachments (Optional)</label>
              <input
                ref="fileInput"
                type="file"
                accept="image/*,application/pdf"
                multiple
                @change="onFilesChange"
              >
              <p class="help-text">PNG, JPG, PDF up to 5MB each. New files will be added to existing attachments.</p>
              <span v-if="form.errors.new_attachments" class="text-danger">{{ form.errors.new_attachments }}</span>
            </div>
          </div>

          <div class="action-buttons">
            <button
              type="submit"
              class="btn btn-primary"
              :disabled="form.processing"
            >
              {{ form.processing ? 'Updating…' : 'Update Report' }}
            </button>
            <Link :href="`/user/reports/${report.id}`" class="btn btn-secondary">Cancel</Link>
          </div>

        </form>
      </div>

      <div class="card" style="max-width:800px;margin:1.5rem auto;background:rgba(59,130,246,0.1);border-color:rgba(59,130,246,0.3);">
        <h3 style="color:#3b82f6;margin-bottom:0.5rem;">ℹ️ Note</h3>
        <p style="color:#d1d5db;font-size:0.9rem;line-height:1.6;margin:0;">
          You can only edit this report before it's assigned to a technician.
          Once assigned, please contact the assigned technician if you need to make changes.
        </p>
      </div>
    </div>
  </AppLayout>
</template>