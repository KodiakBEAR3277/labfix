<script setup>
// Pages/Admin/Tickets/Bulk.vue
// Path: resources/js/Pages/Admin/Tickets/Bulk.vue
// Mirrors: resources/views/admin/tickets/bulk.blade.php
//
// Each operation card has its own useForm() so they submit independently.

import AppLayout from '../../../Layouts/AppLayout.vue'
import NavAdmin from '../../../Components/Nav/NavAdmin.vue'
import { Link, useForm } from '@inertiajs/vue3'

const props = defineProps({
  selectedTickets: Array,
  itStaff:         Array,
})

const ticketIds = props.selectedTickets.map(t => t.id)

// ── One form per operation ─────────────────────────────────────────────────────
const assignForm = useForm({
  ticket_ids:  ticketIds,
  action:      'assign',
  assigned_to: '',
})

const statusForm = useForm({
  ticket_ids: ticketIds,
  action:     'status',
  status:     '',
})

const priorityForm = useForm({
  ticket_ids: ticketIds,
  action:     'priority',
  priority:   '',
})

const closeForm = useForm({
  ticket_ids: ticketIds,
  action:     'close',
})

function submitAssign()   { assignForm.post('/admin/tickets/bulk-update') }
function submitStatus()   { statusForm.post('/admin/tickets/bulk-update') }
function submitPriority() { priorityForm.post('/admin/tickets/bulk-update') }

function submitClose() {
  if (confirm(`Are you sure you want to close these ${props.selectedTickets.length} tickets?`)) {
    closeForm.post('/admin/tickets/bulk-update')
  }
}

function staffFullName(staff) {
  return `${staff.first_name} ${staff.last_name}`
}
</script>

<template>
  <AppLayout>
    <template #nav><NavAdmin /></template>

    <div class="container">

      <div class="page-header">
        <h1>Bulk Operations</h1>
        <p>Apply actions to multiple tickets simultaneously</p>
      </div>

      <!-- Selection bar -->
      <div class="selection-bar">
        <div class="selection-info">
          <div class="selection-count">{{ selectedTickets.length }} Tickets Selected</div>
          <div>Ready for bulk operations</div>
        </div>
        <div class="selection-actions">
          <Link href="/admin/tickets" class="btn btn-outline">Back to Queue</Link>
        </div>
      </div>

      <!-- Operations grid -->
      <div class="operations-grid">

        <!-- Assign -->
        <div class="operation-card">
          <div class="operation-header">
            <div class="operation-icon">👤</div>
            <h3 class="operation-title">Assign Tickets</h3>
          </div>
          <p class="operation-description">Assign all selected tickets to a specific technician</p>
          <form @submit.prevent="submitAssign">
            <div class="form-group">
              <label>Assign To</label>
              <select v-model="assignForm.assigned_to" required>
                <option value="">Select Technician</option>
                <option v-for="staff in itStaff" :key="staff.id" :value="staff.id">
                  {{ staffFullName(staff) }}
                </option>
              </select>
            </div>
            <button type="submit" class="btn btn-primary" :disabled="assignForm.processing">
              {{ assignForm.processing ? 'Assigning…' : 'Assign All' }}
            </button>
          </form>
        </div>

        <!-- Status -->
        <div class="operation-card">
          <div class="operation-header">
            <div class="operation-icon">🔄</div>
            <h3 class="operation-title">Update Status</h3>
          </div>
          <p class="operation-description">Change the status of all selected tickets</p>
          <form @submit.prevent="submitStatus">
            <div class="form-group">
              <label>New Status</label>
              <select v-model="statusForm.status" required>
                <option value="">Select Status</option>
                <option value="assigned">Assigned</option>
                <option value="in-progress">In Progress</option>
                <option value="resolved">Resolved</option>
              </select>
            </div>
            <button type="submit" class="btn btn-primary" :disabled="statusForm.processing">
              {{ statusForm.processing ? 'Updating…' : 'Update All' }}
            </button>
          </form>
        </div>

        <!-- Priority -->
        <div class="operation-card">
          <div class="operation-header">
            <div class="operation-icon">⚠️</div>
            <h3 class="operation-title">Change Priority</h3>
          </div>
          <p class="operation-description">Adjust priority level for all selected tickets</p>
          <form @submit.prevent="submitPriority">
            <div class="form-group">
              <label>Priority Level</label>
              <select v-model="priorityForm.priority" required>
                <option value="">Select Priority</option>
                <option value="low">Low</option>
                <option value="medium">Medium</option>
                <option value="high">High</option>
              </select>
            </div>
            <button type="submit" class="btn btn-primary" :disabled="priorityForm.processing">
              {{ priorityForm.processing ? 'Updating…' : 'Update Priority' }}
            </button>
          </form>
        </div>

        <!-- Close -->
        <div class="operation-card">
          <div class="operation-header">
            <div class="operation-icon">✅</div>
            <h3 class="operation-title">Close Tickets</h3>
          </div>
          <p class="operation-description">Mark all selected tickets as closed</p>
          <button type="button" class="btn btn-primary" :disabled="closeForm.processing" @click="submitClose">
            {{ closeForm.processing ? 'Closing…' : 'Close All' }}
          </button>
        </div>

      </div>

      <!-- Selected tickets preview -->
      <div class="preview-section">
        <h2 class="preview-title">Selected Tickets ({{ selectedTickets.length }})</h2>
        <div class="tickets-list">
          <div v-for="ticket in selectedTickets" :key="ticket.id" class="ticket-preview">
            <div class="ticket-info">
              <h4>{{ ticket.ticket_number }} — {{ ticket.title }}</h4>
              <p>{{ ticket.equipment?.lab?.name }}, {{ ticket.equipment?.equipment_code }} · {{ ticket.priority.charAt(0).toUpperCase() + ticket.priority.slice(1) }} Priority</p>
            </div>
          </div>
        </div>
      </div>

    </div>
  </AppLayout>
</template>