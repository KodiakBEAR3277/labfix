<script setup>
// Pages/SuperAdmin/Institutions/Index.vue
// Path: resources/js/Pages/SuperAdmin/Institutions/Index.vue
//
// Minimal superadmin landing page — a list of institutions with basic
// stats, deliberately not a merged view of everyone's tickets/labs/users.
// Toggling status is the only action here for now; stepping into a
// specific institution's normal admin view is a natural next step,
// not built yet.

import AppLayout from '../../../Layouts/AppLayout.vue'
import { router, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

defineProps({
  institutions: Array,
})

const user = computed(() => usePage().props.auth.user)

function toggleStatus(institution) {
  router.post(`/superadmin/institutions/${institution.id}/toggle-status`)
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
        <div style="display:flex;align-items:center;gap:1rem;">
          <span style="color:#9ca3af;font-size:0.9rem;">{{ user?.first_name }} {{ user?.last_name }}</span>
          <button type="button" class="btn btn-secondary" @click="logout">Sign Out</button>
        </div>
      </nav>
    </template>

    <div class="container">
      <div class="page-header">
        <h1>Institutions</h1>
        <p style="color:#9ca3af;">Every institution registered on the platform</p>
      </div>

      <div class="table-container">
        <table>
          <thead>
            <tr>
              <th>Name</th>
              <th>Users</th>
              <th>Labs</th>
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
                  <span class="status-badge" :class="institution.is_active ? 'status-active' : 'status-inactive'">
                    {{ institution.is_active ? 'Active' : 'Inactive' }}
                  </span>
                </td>
                <td>{{ institution.created_at }}</td>
                <td>
                  <button type="button" class="action-btn" @click="toggleStatus(institution)">
                    {{ institution.is_active ? 'Deactivate' : 'Activate' }}
                  </button>
                </td>
              </tr>
            </template>
            <tr v-else>
              <td colspan="6" style="text-align:center;padding:3rem;color:#9ca3af;">
                No institutions registered yet.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppLayout>
</template>