<script setup>
// Pages/Admin/Settings.vue
// Path: resources/js/Pages/Admin/Settings.vue
// Mirrors: resources/views/admin/settings.blade.php
//
// Settings are grouped: general, notifications, tickets, maintenance.
// All settings are sent in a single `settings` object keyed by setting key.
// Boolean settings use checkboxes; string/integer use inputs/selects.
// The controller handles normalising booleans from useForm() via $request->boolean().

import AppLayout from '../../Layouts/AppLayout.vue'
import NavAdmin from '../../Components/Nav/NavAdmin.vue'
import { Link, useForm } from '@inertiajs/vue3'
import { reactive } from 'vue'

const props = defineProps({
  settings: Object,   // { general: [...], notifications: [...], tickets: [...], maintenance: [...] }
})

// Build a flat settings object keyed by setting.key with current values
function buildSettingsMap() {
  const map = {}
  Object.values(props.settings).forEach(group => {
    group.forEach(s => {
      // Cast booleans — the model accessor returns them as actual booleans
      map[s.key] = s.type === 'boolean' ? Boolean(s.value) : s.value
    })
  })
  return map
}

const form = useForm({
  settings: buildSettingsMap(),
})

function submit() {
  form.post('/admin/settings/update')
}

// Helper to find a setting object by key (for label/description display)
function getSetting(key) {
  for (const group of Object.values(props.settings)) {
    const found = group.find(s => s.key === key)
    if (found) return found
  }
  return null
}

const ticketFormatOptions = [
  { value: 'format_1', label: 'TKT-2025-0001' },
  { value: 'format_2', label: 'TKT20250001' },
  { value: 'format_3', label: 'TKT-202512-0001' },
  { value: 'format_4', label: 'TKT-20251209-0001' },
  { value: 'format_5', label: 'TICKET-2025-0001' },
]
</script>

<template>
  <AppLayout>
    <template #nav><NavAdmin /></template>

    <div class="container">

      <div class="page-header">
        <div>
          <h1>System Settings</h1>
          <p style="color:#9ca3af;">Configure LabFix system behaviour and notifications</p>
        </div>
      </div>

      <form @submit.prevent="submit">

        <div class="settings-grid">

          <!-- ── General Settings ─────────────────────────────────────── -->
          <div class="settings-card">
            <div class="card-header">
              <div class="card-icon">⚙️</div>
              <div>
                <h2 class="card-title" style="margin-bottom:0.25rem;">General Settings</h2>
                <p style="color:#9ca3af;font-size:0.9rem;">Basic system configuration</p>
              </div>
            </div>

            <div class="form-group">
              <label>System Name</label>
              <input v-model="form.settings.system_name" type="text">
              <p class="help-text">The name displayed across the system</p>
            </div>

            <div class="form-group">
              <label>Support Email</label>
              <input v-model="form.settings.support_email" type="email">
              <p class="help-text">Primary support contact email</p>
            </div>

            <div class="form-group">
              <label>Support Phone</label>
              <input v-model="form.settings.support_phone" type="text">
              <p class="help-text">Support phone number for contact page</p>
            </div>
          </div>

          <!-- ── Notification Settings ────────────────────────────────── -->
          <div class="settings-card">
            <div class="card-header">
              <div class="card-icon">🔔</div>
              <div>
                <h2 class="card-title" style="margin-bottom:0.25rem;">Notification Settings</h2>
                <p style="color:#9ca3af;font-size:0.9rem;">Control email notifications</p>
              </div>
            </div>

            <div class="toggle-item">
              <div class="toggle-info">
                <h4>Notify User on Assignment</h4>
                <p>Email user when ticket is assigned to IT</p>
              </div>
              <input v-model="form.settings.notify_user_on_assignment" type="checkbox" style="width:auto;accent-color:#2dd4bf;cursor:pointer;width:20px;height:20px;">
            </div>

            <div class="toggle-item">
              <div class="toggle-info">
                <h4>Notify User on Status Change</h4>
                <p>Email user when ticket status changes</p>
              </div>
              <input v-model="form.settings.notify_user_on_status_change" type="checkbox" style="width:auto;accent-color:#2dd4bf;cursor:pointer;width:20px;height:20px;">
            </div>

            <div class="toggle-item">
              <div class="toggle-info">
                <h4>Notify User on Resolution</h4>
                <p>Email user when ticket is resolved</p>
              </div>
              <input v-model="form.settings.notify_user_on_resolution" type="checkbox" style="width:auto;accent-color:#2dd4bf;cursor:pointer;width:20px;height:20px;">
            </div>

            <div class="toggle-item">
              <div class="toggle-info">
                <h4>Notify IT on New Ticket</h4>
                <p>Email IT team when new ticket is created</p>
              </div>
              <input v-model="form.settings.notify_it_on_new_ticket" type="checkbox" style="width:auto;accent-color:#2dd4bf;cursor:pointer;width:20px;height:20px;">
            </div>

            <div class="toggle-item">
              <div class="toggle-info">
                <h4>Notify IT on High Priority</h4>
                <p>Email IT team immediately for high priority tickets</p>
              </div>
              <input v-model="form.settings.notify_it_on_high_priority" type="checkbox" style="width:auto;accent-color:#2dd4bf;cursor:pointer;width:20px;height:20px;">
            </div>
          </div>

          <!-- ── Ticket Settings ──────────────────────────────────────── -->
          <div class="settings-card">
            <div class="card-header">
              <div class="card-icon">🎫</div>
              <div>
                <h2 class="card-title" style="margin-bottom:0.25rem;">Ticket Settings</h2>
                <p style="color:#9ca3af;font-size:0.9rem;">Configure ticket behaviour</p>
              </div>
            </div>

            <div class="form-group">
              <label>Ticket Number Format</label>
              <select v-model="form.settings.ticket_number_format">
                <option v-for="opt in ticketFormatOptions" :key="opt.value" :value="opt.value">
                  {{ opt.label }}
                </option>
              </select>
              <p class="help-text">Format used when generating new ticket numbers</p>
            </div>

            <div class="form-group">
              <label>Default Priority</label>
              <select v-model="form.settings.default_priority">
                <option value="low">Low</option>
                <option value="medium">Medium</option>
                <option value="high">High</option>
              </select>
              <p class="help-text">Default priority assigned to new tickets</p>
            </div>

            <div class="form-group">
              <label>Auto-close Resolved Tickets After (days)</label>
              <input v-model="form.settings.auto_close_resolved_after_days" type="number" min="0">
              <p class="help-text">Set to 0 to disable auto-close</p>
            </div>

            <div class="form-group">
              <label>Auto-escalate Unresolved Tickets After (hours)</label>
              <input v-model="form.settings.auto_escalate_after_hours" type="number" min="0">
              <p class="help-text">Set to 0 to disable auto-escalation</p>
            </div>

            <div class="toggle-item">
              <div class="toggle-info">
                <h4>Allow Attachments</h4>
                <p>Allow users to upload files when submitting tickets</p>
              </div>
              <input v-model="form.settings.allow_attachments" type="checkbox" style="width:auto;accent-color:#2dd4bf;cursor:pointer;width:20px;height:20px;">
            </div>
          </div>

          <!-- ── Maintenance Mode ─────────────────────────────────────── -->
          <div class="settings-card">
            <div class="card-header">
              <div class="card-icon">🔧</div>
              <div>
                <h2 class="card-title" style="margin-bottom:0.25rem;">Maintenance Mode</h2>
                <p style="color:#9ca3af;font-size:0.9rem;">Temporarily disable ticket submissions</p>
              </div>
            </div>

            <div class="toggle-item">
              <div class="toggle-info">
                <h4>Enable Maintenance Mode</h4>
                <p>Blocks new ticket creation for students and staff</p>
              </div>
              <input v-model="form.settings.maintenance_mode" type="checkbox" style="width:auto;accent-color:#2dd4bf;cursor:pointer;width:20px;height:20px;">
            </div>

            <div class="form-group" style="margin-top:1rem;">
              <label>Maintenance Message</label>
              <textarea v-model="form.settings.maintenance_message" rows="3"></textarea>
              <p class="help-text">Message shown to users when maintenance mode is active</p>
            </div>
          </div>

        </div>

        <!-- Save button -->
        <div style="display:flex;justify-content:flex-end;margin-top:1.5rem;">
          <button type="submit" class="btn btn-primary" :disabled="form.processing">
            {{ form.processing ? 'Saving…' : 'Save Settings' }}
          </button>
        </div>

      </form>
    </div>
  </AppLayout>
</template>