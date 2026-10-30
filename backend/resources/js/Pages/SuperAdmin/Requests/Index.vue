<script setup>
// Pages/SuperAdmin/Requests/Index.vue
// Path: resources/js/Pages/SuperAdmin/Requests/Index.vue
//
// Pending "create new institution" requests, awaiting review. This is the
// actual gate — approving here is the only path that creates a live
// institution and its first admin account (InstitutionRequest::approve()).
// AuthController::register() never creates one directly anymore.

import AppLayout from '../../../Layouts/AppLayout.vue'
import { Link, useForm, router, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import Icon from '../../../Components/Icon.vue'

const props = defineProps({
  requests: Array,
})

const user = computed(() => usePage().props.auth.user)

function approve(id) {
  if (confirm('Approve this institution? This creates their admin account immediately.')) {
    router.post(`/superadmin/requests/${id}/approve`)
  }
}

// ── Reject modal ─────────────────────────────────────────────────────────
const showRejectModal = ref(false)
const rejectingId = ref(null)

const rejectForm = useForm({
  reason: '',
})

function openReject(id) {
  rejectingId.value = id
  rejectForm.reason = ''
  showRejectModal.value = true
}

function submitReject() {
  rejectForm.post(`/superadmin/requests/${rejectingId.value}/reject`, {
    onSuccess: () => { showRejectModal.value = false },
  })
}

function logout() {
  router.post('/logout')
}

function planLabel(plan) {
  if (!plan) return '—'
  return plan.charAt(0).toUpperCase() + plan.slice(1)
}

function formatDate(d) {
  return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
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
          <Link href="/superadmin/requests" style="color:#2dd4bf;text-decoration:none;font-size:0.9rem;font-weight:600;">Requests</Link>
          <Link href="/superadmin/settings" style="color:#9ca3af;text-decoration:none;font-size:0.9rem;">Settings</Link>
          <span style="color:#9ca3af;font-size:0.9rem;">{{ user?.first_name }} {{ user?.last_name }}</span>
          <button type="button" class="btn btn-secondary" @click="logout">Sign Out</button>
        </div>
      </nav>
    </template>

    <div class="container">
      <div class="page-header">
        <h1>Institution Requests</h1>
        <p style="color:#9ca3af;">New institutions wait here until approved</p>
      </div>

      <div v-if="requests.length" class="table-container">
        <table>
          <thead>
            <tr>
              <th>School</th>
              <th>Requested By</th>
              <th>Plan</th>
              <th>Submitted</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="req in requests" :key="req.id">
              <td>
                <strong>{{ req.institution_name }}</strong><br>
                <small style="color:#9ca3af;">{{ req.institution_contact_email }}</small>
              </td>
              <td>
                {{ req.first_name }} {{ req.last_name }}<br>
                <small style="color:#9ca3af;">{{ req.email }}</small>
              </td>
              <td>{{ planLabel(req.plan) }}</td>
              <td>{{ formatDate(req.created_at) }}</td>
              <td>
                <div class="action-menu">
                  <button type="button" class="action-btn" style="color:#10b981;" @click="approve(req.id)">Approve</button>
                  <button type="button" class="action-btn" style="color:#ef4444;" @click="openReject(req.id)">Reject</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-else class="empty-state">
        <div class="empty-icon"><Icon name="check-circle" /></div>
        <h3>No pending requests</h3>
        <p>New institution sign-ups will show up here for review.</p>
      </div>
    </div>

    <!-- Reject modal -->
    <div v-if="showRejectModal" class="modal-overlay active" @click.self="showRejectModal = false">
      <div class="modal">
        <h2 class="modal-header">Reject Request</h2>
        <form @submit.prevent="submitReject">
          <div class="form-group">
            <label>Reason (internal note — not sent to the requester yet, there's no email in place for that)</label>
            <textarea v-model="rejectForm.reason" placeholder="Why this was rejected"></textarea>
          </div>
          <div class="modal-actions">
            <button type="submit" class="btn btn-danger" :disabled="rejectForm.processing">
              {{ rejectForm.processing ? 'Rejecting…' : 'Confirm Reject' }}
            </button>
            <button type="button" class="btn btn-cancel" @click="showRejectModal = false">Cancel</button>
          </div>
        </form>
      </div>
    </div>

  </AppLayout>
</template>