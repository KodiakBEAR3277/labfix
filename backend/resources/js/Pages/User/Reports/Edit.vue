<script setup>
// Pages/User/Reports/Edit.vue
// Mirrors: resources/views/user/reports/edit.blade.php
// Path:    resources/js/Pages/User/Reports/Edit.vue

import AppLayout from '../../../Layouts/AppLayout.vue'
import NavUser from '../../../Components/Nav/NavUser.vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
  report: Object,
})

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? ''
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
        <form :action="`/user/reports/${report.id}`" method="POST" enctype="multipart/form-data">
          <input type="hidden" name="_token"  :value="csrfToken">
          <input type="hidden" name="_method" value="PUT">

          <div class="form-section">
            <h3 class="section-title">Report Details</h3>

            <div class="form-group">
              <label>Issue Title *</label>
              <input type="text" name="title" :value="report.title" required>
            </div>

            <div class="form-group">
              <label>Category *</label>
              <select name="category" required>
                <option value="hardware" :selected="report.category === 'hardware'">Hardware</option>
                <option value="software" :selected="report.category === 'software'">Software</option>
                <option value="network"  :selected="report.category === 'network'">Network</option>
                <option value="other"    :selected="report.category === 'other'">Other</option>
              </select>
            </div>

            <div class="form-group">
              <label>Description *</label>
              <textarea name="description" rows="8" required>{{ report.description }}</textarea>
              <p class="help-text">Minimum 10 characters. Be as detailed as possible.</p>
            </div>

            <!-- Existing attachments -->
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
              <input type="file" name="attachments[]" accept="image/*,application/pdf" multiple>
              <p class="help-text">PNG, JPG, PDF up to 5MB each. New files will be added to existing attachments.</p>
            </div>
          </div>

          <div class="action-buttons">
            <button type="submit" class="btn btn-primary">Update Report</button>
            <!-- Link for cancel — no form submission, just navigate back -->
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