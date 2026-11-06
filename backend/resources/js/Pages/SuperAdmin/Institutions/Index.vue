<script setup>
// Pages/SuperAdmin/Institutions/Index.vue
// Path: resources/js/Pages/SuperAdmin/Institutions/Index.vue
//
// Superadmin institution management: create (with its first admin), edit,
// activate/deactivate, change plan, and delete when the institution is empty.
// This is platform oversight only — it never shows a merged view of another
// institution's tickets or labs.

import AppLayout from '../../../Layouts/AppLayout.vue'
import NavSuperAdmin from '../../../Components/Nav/NavSuperAdmin.vue'
import { router, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'

defineProps({
  institutions: Array,
})

// ── Create / edit modal ───────────────────────────────────────────────────────
const showModal = ref(false)
const editingId = ref(null)

const form = useForm({
  name:                     '',
  contact_email:            '',
  address:                  '',
  plan:                     'free',
  is_active:                true,
  admin_first_name:         '',
  admin_last_name:          '',
  admin_email:              '',
  admin_password:           '',
  admin_password_confirmation: '',
})

function openCreate() {
  editingId.value = null
  form.reset()
  form.clearErrors()
  form.plan = 'free'
  form.is_active = true
  showModal.value = true
}

function openEdit(institution) {
  editingId.value = institution.id
  form.clearErrors()
  form.name = institution.name
  form.contact_email = institution.contact_email ?? ''
  form.address = institution.address ?? ''
  form.plan = institution.plan
  form.is_active = institution.is_active
  showModal.value = true
}

function submit() {
  const options = {
    preserveScroll: true,
    onSuccess: () => { showModal.value = false },
  }

  if (editingId.value) {
    form.put(`/superadmin/institutions/${editingId.value}`, options)
    return
  }

  form.post('/superadmin/institutions', options)
}

function destroyInstitution(institution) {
  if (confirm(`Delete "${institution.name}"? This only works if it has no users, labs or equipment.`)) {
    router.delete(`/superadmin/institutions/${institution.id}`, { preserveScroll: true })
  }
}

// ── Inline actions ────────────────────────────────────────────────────────────
function toggleStatus(institution) {
  router.post(`/superadmin/institutions/${institution.id}/toggle-status`)
}

function changePlan(institution, plan) {
  if (plan === institution.plan) return
  router.post(`/superadmin/institutions/${institution.id}/plan`, { plan }, { preserveScroll: true })
}
</script>

<template>
  <AppLayout>
    <template #nav><NavSuperAdmin /></template>

    <div class="container">
      <div class="page-header">
        <div>
          <h1>Institutions</h1>
          <p style="color:#9ca3af;">Every institution registered on the platform</p>
        </div>
        <button type="button" class="btn btn-primary" @click="openCreate">+ Add Institution</button>
      </div>

      <div class="table-container">
        <table>
          <thead>
            <tr>
              <th>Name</th>
              <th>Users</th>
              <th>Labs</th>
              <th>Plan</th>
              <th>Status</th>
              <th>Registered</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <template v-if="institutions.length">
              <tr v-for="institution in institutions" :key="institution.id">
                <td><strong>{{ institution.name }}</strong></td>
                <td>{{ institution.user_count }}</td>
                <td>{{ institution.lab_count }}</td>
                <td>
                  <select :value="institution.plan" style="padding:0.4rem 2rem 0.4rem 0.6rem;width:auto;" @change="changePlan(institution, $event.target.value)">
                    <option value="free">Free</option>
                    <option value="standard">Standard</option>
                    <option value="pro">Pro</option>
                    <option value="enterprise">Enterprise</option>
                  </select>
                </td>
                <td>
                  <span class="status-badge" :class="institution.is_active ? 'status-active' : 'status-inactive'">
                    {{ institution.is_active ? 'Active' : 'Inactive' }}
                  </span>
                </td>
                <td>{{ institution.created_at }}</td>
                <td>
                  <div class="action-menu">
                    <button type="button" class="action-btn" @click="openEdit(institution)">Edit</button>
                    <button type="button" class="action-btn" @click="toggleStatus(institution)">
                      {{ institution.is_active ? 'Deactivate' : 'Activate' }}
                    </button>
                    <button type="button" class="action-btn btn-danger" @click="destroyInstitution(institution)">Delete</button>
                  </div>
                </td>
              </tr>
            </template>
            <tr v-else>
              <td colspan="7" style="text-align:center;padding:3rem;color:#9ca3af;">
                No institutions registered yet.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ── Create / Edit Institution Modal ───────────────────────────────── -->
    <div v-if="showModal" class="modal-overlay active" @click.self="showModal = false">
      <div class="modal">
        <h2 class="modal-header">{{ editingId ? 'Edit Institution' : 'Add Institution' }}</h2>
        <form @submit.prevent="submit">

          <div class="form-group">
            <label>Institution Name *</label>
            <input v-model="form.name" type="text" maxlength="255" placeholder="e.g., State University" required>
            <span v-if="form.errors.name" class="text-danger">{{ form.errors.name }}</span>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Contact Email</label>
              <input v-model="form.contact_email" type="email" maxlength="255" placeholder="e.g., it@university.edu">
              <span v-if="form.errors.contact_email" class="text-danger">{{ form.errors.contact_email }}</span>
            </div>
            <div class="form-group">
              <label>Plan *</label>
              <select v-model="form.plan" required>
                <option value="free">Free</option>
                <option value="standard">Standard</option>
                <option value="pro">Pro</option>
                <option value="enterprise">Enterprise</option>
              </select>
              <span v-if="form.errors.plan" class="text-danger">{{ form.errors.plan }}</span>
            </div>
          </div>

          <div class="form-group">
            <label>Address</label>
            <input v-model="form.address" type="text" maxlength="255" placeholder="e.g., Main Campus, Building A">
            <span v-if="form.errors.address" class="text-danger">{{ form.errors.address }}</span>
          </div>

          <div class="toggle-item" style="margin-bottom:1rem;">
            <div class="toggle-info">
              <h4>Active</h4>
              <p>Inactive institutions cannot sign in or submit tickets.</p>
            </div>
            <input
              v-model="form.is_active"
              type="checkbox"
              style="width:20px;height:20px;accent-color:#2dd4bf;cursor:pointer;"
            >
          </div>

          <!-- The first admin is only needed when creating a new institution. -->
          <template v-if="!editingId">
            <h3 style="font-size:0.95rem;color:var(--color-primary-dark);margin:0.5rem 0 0.9rem;border-top:1px solid #e2e8f0;padding-top:1rem;">
              First Admin Account
            </h3>

            <div class="form-row">
              <div class="form-group">
                <label>First Name *</label>
                <input v-model="form.admin_first_name" type="text" maxlength="255" required>
                <span v-if="form.errors.admin_first_name" class="text-danger">{{ form.errors.admin_first_name }}</span>
              </div>
              <div class="form-group">
                <label>Last Name *</label>
                <input v-model="form.admin_last_name" type="text" maxlength="255" required>
                <span v-if="form.errors.admin_last_name" class="text-danger">{{ form.errors.admin_last_name }}</span>
              </div>
            </div>

            <div class="form-group">
              <label>Admin Email *</label>
              <input v-model="form.admin_email" type="email" maxlength="255" required>
              <span v-if="form.errors.admin_email" class="text-danger">{{ form.errors.admin_email }}</span>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Password *</label>
                <input v-model="form.admin_password" type="password" autocomplete="new-password" required>
                <span v-if="form.errors.admin_password" class="text-danger">{{ form.errors.admin_password }}</span>
              </div>
              <div class="form-group">
                <label>Confirm Password *</label>
                <input v-model="form.admin_password_confirmation" type="password" autocomplete="new-password" required>
              </div>
            </div>
          </template>

          <div class="modal-actions">
            <button type="submit" class="btn btn-save" :disabled="form.processing">
              {{ form.processing ? 'Saving…' : (editingId ? 'Save Changes' : 'Create Institution') }}
            </button>
            <button type="button" class="btn btn-cancel" @click="showModal = false">Cancel</button>
          </div>
        </form>
      </div>
    </div>

  </AppLayout>
</template>