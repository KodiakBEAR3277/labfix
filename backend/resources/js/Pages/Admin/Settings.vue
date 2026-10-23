<script setup>
// Pages/Admin/Settings.vue
// Path: resources/js/Pages/Admin/Settings.vue
//
// Per-institution settings. Only settings the system actually acts on are
// shown here. System name and support contact now belong to the platform
// (superadmin settings), and the email notification toggles are gone until
// email sending exists. Values come from Setting::DEFAULTS as one flat map.
//
// Auto-close and auto-escalate are Standard-plan features. On Free they are
// shown locked with an upgrade link, and the server ignores them either way.

import AppLayout from '../../Layouts/AppLayout.vue'
import NavAdmin from '../../Components/Nav/NavAdmin.vue'
import { Link, useForm } from '@inertiajs/vue3'

const props = defineProps({
  values:   Object,   // flat map: { ticket_number_format: 'format_1', ... }
  plan:     String,   // plan label, e.g. "Free"
  features: Object,   // { auto_rules: bool }
})

const form = useForm({
  settings: { ...props.values },
})

function submit() {
  form.post('/admin/settings/update')
}

// Inertia flattens nested validation errors to "settings.<key>"
const err = (key) => form.errors[`settings.${key}`]

// Examples built from today's date so they always look current
const now  = new Date()
const yyyy = now.getFullYear()
const mm   = String(now.getMonth() + 1).padStart(2, '0')
const dd   = String(now.getDate()).padStart(2, '0')

const ticketFormatOptions = [
  { value: 'format_1', label: `TKT-${yyyy}-0001` },
  { value: 'format_2', label: `TKT${yyyy}0001` },
  { value: 'format_3', label: `TKT-${yyyy}${mm}-0001` },
  { value: 'format_4', label: `TKT-${yyyy}${mm}${dd}-0001` },
  { value: 'format_5', label: `TICKET-${yyyy}-0001` },
]
</script>

<template>
  <AppLayout>
    <template #nav><NavAdmin /></template>

    <div class="container">

      <div class="page-header">
        <div>
          <h1>System Settings</h1>
          <p style="color:#9ca3af;">How tickets behave for your institution</p>
        </div>
      </div>

      <form @submit.prevent="submit">

        <div class="settings-grid">

          <!-- ── Ticket Settings ──────────────────────────────────────── -->
          <div class="settings-card">
            <div class="card-header">
              <div class="card-icon">🎫</div>
              <div>
                <h2 class="card-title" style="margin-bottom:0.25rem;">Ticket Settings</h2>
                <p style="color:#9ca3af;font-size:0.9rem;">Numbering, priority and automatic rules</p>
              </div>
            </div>

            <div class="form-group">
              <label>Ticket Number Format</label>
              <select v-model="form.settings.ticket_number_format">
                <option v-for="opt in ticketFormatOptions" :key="opt.value" :value="opt.value">
                  {{ opt.label }}
                </option>
              </select>
              <p class="help-text">Applies to tickets created from now on. Existing ticket numbers do not change.</p>
              <span v-if="err('ticket_number_format')" class="text-danger">{{ err('ticket_number_format') }}</span>
            </div>

            <div class="form-group">
              <label>Default Priority</label>
              <select v-model="form.settings.default_priority">
                <option value="low">Low</option>
                <option value="medium">Medium</option>
                <option value="high">High</option>
              </select>
              <p class="help-text">
                Used for new tickets. A ticket that mentions words like urgent, broken, cannot or critical is always set to High.
              </p>
              <span v-if="err('default_priority')" class="text-danger">{{ err('default_priority') }}</span>
            </div>

            <!-- Locked notice for plans without auto rules -->
            <div
              v-if="!features.auto_rules"
              style="background:rgba(245,158,11,0.1);border:1px solid rgba(245,158,11,0.3);border-radius:8px;padding:0.9rem 1rem;margin-bottom:1rem;font-size:0.9rem;color:var(--color-text-secondary);"
            >
              🔒 Auto-close and auto-escalate are available on the Standard plan and above.
              You are on the <strong>{{ plan }}</strong> plan.
              <Link href="/admin/upgrade" style="color:var(--color-primary-dark);font-weight:600;">See plans →</Link>
            </div>

            <div class="form-group">
              <label>Auto-close Resolved Tickets After (days)</label>
              <input
                v-model="form.settings.auto_close_resolved_after_days"
                type="number" min="0" max="365"
                :disabled="!features.auto_rules"
              >
              <p class="help-text">Closes a ticket this many days after it was resolved. Set to 0 to turn off.</p>
              <span v-if="err('auto_close_resolved_after_days')" class="text-danger">{{ err('auto_close_resolved_after_days') }}</span>
            </div>

            <div class="form-group">
              <label>Auto-escalate Unresolved Tickets After (hours)</label>
              <input
                v-model="form.settings.auto_escalate_after_hours"
                type="number" min="0" max="720"
                :disabled="!features.auto_rules"
              >
              <p class="help-text">Raises the priority of tickets still open after this many hours. Set to 0 to turn off.</p>
              <span v-if="err('auto_escalate_after_hours')" class="text-danger">{{ err('auto_escalate_after_hours') }}</span>
            </div>
          </div>

          <!-- ── Maintenance Mode ─────────────────────────────────────── -->
          <div class="settings-card">
            <div class="card-header">
              <div class="card-icon">🔧</div>
              <div>
                <h2 class="card-title" style="margin-bottom:0.25rem;">Maintenance Mode</h2>
                <p style="color:#9ca3af;font-size:0.9rem;">Temporarily stop new ticket submissions</p>
              </div>
            </div>

            <div class="toggle-item">
              <div class="toggle-info">
                <h4>Enable Maintenance Mode</h4>
                <p>
                  Students and staff cannot submit new tickets, on the website or the mobile app.
                  Everyone can still view existing tickets, and admins are not blocked.
                </p>
              </div>
              <input
                v-model="form.settings.maintenance_mode"
                type="checkbox"
                style="width:auto;accent-color:#2dd4bf;cursor:pointer;width:20px;height:20px;"
              >
            </div>
            <span v-if="err('maintenance_mode')" class="text-danger">{{ err('maintenance_mode') }}</span>

            <div class="form-group" style="margin-top:1rem;">
              <label>Maintenance Message</label>
              <textarea v-model="form.settings.maintenance_message" rows="3" maxlength="500"></textarea>
              <p class="help-text">Shown to users while maintenance mode is on. Leave blank to use a standard message.</p>
              <span v-if="err('maintenance_message')" class="text-danger">{{ err('maintenance_message') }}</span>
            </div>
          </div>

        </div>

        <div style="display:flex;justify-content:flex-end;margin-top:1.5rem;">
          <button type="submit" class="btn btn-primary" :disabled="form.processing">
            {{ form.processing ? 'Saving…' : 'Save Settings' }}
          </button>
        </div>

      </form>
    </div>
  </AppLayout>
</template>