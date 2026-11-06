<script setup>
// Pages/Admin/Labs/Index.vue
// Path: resources/js/Pages/Admin/Labs/Index.vue
// Mirrors: resources/views/admin/labs/index.blade.php
//
// Lab edit and equipment edit/add use fetch() to the existing JSON endpoints
// (LabController::edit() and EquipmentController::edit() both return response()->json())
// then submit via useForm(). This keeps the backend untouched.
//
// Equipment modal now also carries hardware detail fields (brand, model,
// specifications, serial number, purchase date, warranty expiry) — all
// optional, matching the nullable columns added to the equipment table.

import AppLayout from '../../../Layouts/AppLayout.vue'
import NavAdmin from '../../../Components/Nav/NavAdmin.vue'
import { Link, useForm, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import QRCode from 'qrcode'
import Icon from '../../../Components/Icon.vue'

const props = defineProps({
  labs:       Array,
  stats:      Object,
  planLimits: Object,
})

// null max = unlimited. Mirrors Institution::canAddLab()/canAddEquipment()
// on the backend — this only controls the button's disabled state and a
// friendlier message; the real guard is server-side.
const labsAtLimit = computed(() => {
  const l = props.planLimits?.labs
  return !!l && l.max !== null && l.current >= l.max
})
const equipmentAtLimit = computed(() => {
  const e = props.planLimits?.equipment
  return !!e && e.max !== null && e.current >= e.max
})

// ── Lab modal ─────────────────────────────────────────────────────────────────
const showLabModal  = ref(false)
const labModalTitle = ref('Add New Lab')

const labForm = useForm({
  _method:     'POST',
  name:        '',
  code:        '',
  location:    '',
  capacity:    '',
  description: '',
})

// labActionUrl tracks where the form POSTs (store or update)
const labActionUrl = ref('/admin/labs')

function openAddLab() {
  labModalTitle.value = 'Add New Lab'
  labActionUrl.value  = '/admin/labs'
  labForm.reset()
  labForm._method = 'POST'
  showLabModal.value  = true
}

function editLab(id) {
  fetch(`/admin/labs/${id}/edit`)
    .then(r => r.json())
    .then(lab => {
      labModalTitle.value  = 'Edit Lab'
      labActionUrl.value   = `/admin/labs/${id}`
      labForm._method      = 'PUT'
      labForm.name         = lab.name
      labForm.code         = lab.code
      labForm.location     = lab.location ?? ''
      labForm.capacity     = lab.capacity
      labForm.description  = lab.description ?? ''
      showLabModal.value   = true
    })
}

function submitLab() {
  labForm.post(labActionUrl.value, {
    onSuccess: () => { showLabModal.value = false },
  })
}

function toggleStatus(id) {
  router.post(`/admin/labs/${id}/toggle-status`)
}

function deleteLab(id) {
  if (confirm('Are you sure you want to delete this lab?')) {
    router.delete(`/admin/labs/${id}`)
  }
}

// ── Equipment modal ───────────────────────────────────────────────────────────
const showEquipmentModal  = ref(false)
const equipmentModalTitle = ref('Add Equipment')
const currentEquipmentId  = ref(null)

const equipmentForm = useForm({
  _method:          'POST',
  lab_id:           '',
  equipment_code:   '',
  type:             'computer',
  status:           'operational',
  notes:            '',
  brand:            '',
  model:            '',
  specifications:   '',
  serial_number:    '',
  purchase_date:    '',
  warranty_expiry:  '',
})

const equipmentActionUrl = ref('/admin/equipment')

// ── QR code + history panels (edit mode only) ────────────────────────────────
// QR is generated when the edit modal opens so the print handler can run
// synchronously inside the user's click (pop-up blockers require that).
const qrDataUrl     = ref('')
const qrEquipment   = ref(null)
const qrError       = ref('')
const historyOpen   = ref(false)
const historyLoading = ref(false)
const historyError  = ref('')
const historyData   = ref(null)

function resetEquipmentPanels() {
  qrDataUrl.value     = ''
  qrEquipment.value   = null
  qrError.value       = ''
  historyOpen.value   = false
  historyLoading.value = false
  historyError.value  = ''
  historyData.value   = null
}

function buildQrCode(equipment) {
  const labName = props.labs?.find(l => l.id === equipment.lab_id)?.name ?? ''
  qrEquipment.value = { ...equipment, lab: { name: labName } }
  qrDataUrl.value   = ''
  qrError.value     = ''

  if (!equipment.qr_payload) {
    qrError.value = 'No QR token is available for this equipment.'
    return
  }

  QRCode.toDataURL(equipment.qr_payload, {
    width: 512,
    margin: 2,
    color: { dark: '#0f172a', light: '#ffffff' },
  })
    .then(url => { qrDataUrl.value = url })
    .catch(() => { qrError.value = 'Could not generate the QR code.' })
}

function escapeHtml(value) {
  return String(value ?? '').replace(/[&<>"']/g, c => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
  }[c]))
}

function printQr() {
  if (!qrDataUrl.value || !qrEquipment.value) return

  const code = escapeHtml(qrEquipment.value.equipment_code)
  const lab  = escapeHtml(qrEquipment.value.lab?.name ?? '')
  const win  = window.open('', '_blank', 'width=420,height=560')

  if (!win) {
    alert('Please allow pop-ups for this site to print the QR code.')
    return
  }

  win.document.write(`<!doctype html>
<html>
  <head>
    <title>LabFix QR — ${code}</title>
    <style>
      body { font-family: system-ui, sans-serif; text-align: center; padding: 32px; color: #0f172a; }
      img { width: 320px; height: 320px; }
      .code { font-size: 22px; font-weight: 700; margin-top: 12px; }
      .lab { font-size: 14px; color: #475569; margin-top: 4px; }
    </style>
  </head>
  <body>
    <img src="${qrDataUrl.value}" alt="QR code for ${code}">
    <div class="code">${code}</div>
    <div class="lab">${lab}</div>
    <script>window.onload = function () { window.print() }<\/script>
  </body>
</html>`)
  win.document.close()
}

function loadHistory() {
  if (!currentEquipmentId.value) return

  if (historyOpen.value) {
    historyOpen.value = false
    return
  }

  historyOpen.value    = true
  historyLoading.value = true
  historyError.value   = ''
  historyData.value    = null

  fetch(`/admin/equipment/${currentEquipmentId.value}/history`)
    .then(r => {
      if (!r.ok) throw new Error('request failed')
      return r.json()
    })
    .then(data => { historyData.value = data })
    .catch(() => { historyError.value = 'Could not load the equipment history.' })
    .finally(() => { historyLoading.value = false })
}

function ticketStatusColor(s) {
  return { new: 'info', assigned: 'assigned', 'in-progress': 'progress', resolved: 'resolved', closed: 'resolved' }[s] ?? 'secondary'
}

function ticketStatusLabel(s) {
  return s.replace('-', ' ').replace(/\b\w/g, c => c.toUpperCase())
}

function formatDate(value) {
  return value ? new Date(value).toLocaleDateString() : ''
}

// Printable sheet for every device in one lab. The window is opened
// synchronously so pop-up blockers see a user gesture; the QR images are
// generated afterwards and written into it.
async function printLabQrSheet(lab) {
  const equipment = (lab.equipment ?? []).filter(eq => eq.qr_payload)

  if (!equipment.length) {
    alert('No scannable equipment in this lab yet.')
    return
  }

  const win = window.open('', '_blank', 'width=900,height=1200')

  if (!win) {
    alert('Please allow pop-ups for this site to print the QR sheet.')
    return
  }

  win.document.write('<p style="font-family:system-ui,sans-serif;padding:24px;">Generating QR codes…</p>')

  try {
    const cards = await Promise.all(equipment.map(async (eq) => {
      const url = await QRCode.toDataURL(eq.qr_payload, {
        width: 480,
        margin: 2,
        color: { dark: '#0f172a', light: '#ffffff' },
      })

      return `<div class="card">
        <img src="${url}" alt="">
        <div class="code">${escapeHtml(eq.equipment_code)}</div>
        <div class="meta">${escapeHtml(eq.type)} · ${escapeHtml(eq.status)}</div>
      </div>`
    }))

    win.document.open()
    win.document.write(`<!doctype html>
<html>
  <head>
    <title>LabFix QR Sheet — ${escapeHtml(lab.name)}</title>
    <style>
      @page { margin: 12mm; }
      * { box-sizing: border-box; }
      body { font-family: system-ui, sans-serif; color: #0f172a; margin: 0; }
      header { display: flex; justify-content: space-between; align-items: baseline; border-bottom: 2px solid #0f172a; padding-bottom: 8px; margin-bottom: 16px; }
      h1 { font-size: 20px; margin: 0; }
      .sub { font-size: 12px; color: #475569; }
      .grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
      .card { border: 1px solid #cbd5e1; border-radius: 10px; padding: 12px; text-align: center; break-inside: avoid; }
      .card img { width: 100%; max-width: 190px; height: auto; }
      .code { font-size: 15px; font-weight: 700; margin-top: 6px; }
      .meta { font-size: 10px; color: #64748b; text-transform: capitalize; }
    </style>
  </head>
  <body>
    <header>
      <div>
        <h1>${escapeHtml(lab.name)}</h1>
        <div class="sub">${escapeHtml(lab.code)} · Equipment QR codes</div>
      </div>
      <div class="sub">${equipment.length} device${equipment.length === 1 ? '' : 's'}</div>
    </header>
    <div class="grid">${cards.join('')}</div>
    <script>window.onload = function () { window.print() }<\/script>
  </body>
</html>`)
    win.document.close()
    win.focus()
  } catch {
    win.document.open()
    win.document.write('<p style="font-family:system-ui,sans-serif;padding:24px;">Could not generate the QR sheet.</p>')
    win.document.close()
  }
}

// ── Bulk add (one lab) ───────────────────────────────────────────────────────
const showBulkModal = ref(false)
const bulkLabId     = ref(null)
const bulkForm = useForm({
  codes:  '',
  type:   'computer',
  status: 'operational',
})

function openBulkAdd(labId) {
  bulkLabId.value = labId
  bulkForm.reset()
  bulkForm.type   = 'computer'
  bulkForm.status = 'operational'
  showBulkModal.value = true
}

function submitBulk() {
  bulkForm.post(`/admin/labs/${bulkLabId.value}/equipment/bulk`, {
    preserveScroll: true,
    onSuccess: () => {
      showBulkModal.value = false
      bulkForm.reset()
    },
  })
}

function openAddEquipment(labId) {
  equipmentModalTitle.value   = 'Add Equipment'
  equipmentActionUrl.value    = '/admin/equipment'
  currentEquipmentId.value    = null
  resetEquipmentPanels()
  equipmentForm.reset()
  equipmentForm._method       = 'POST'
  equipmentForm.lab_id        = labId
  equipmentForm.type          = 'computer'
  equipmentForm.status        = 'operational'
  showEquipmentModal.value    = true
}

function editEquipment(id) {
  fetch(`/admin/equipment/${id}/edit`)
    .then(r => r.json())
    .then(eq => {
      equipmentModalTitle.value    = 'Edit Equipment'
      equipmentActionUrl.value     = `/admin/equipment/${id}`
      currentEquipmentId.value     = id
      resetEquipmentPanels()
      equipmentForm._method        = 'PUT'
      equipmentForm.lab_id         = eq.lab_id
      equipmentForm.equipment_code = eq.equipment_code
      equipmentForm.type           = eq.type
      equipmentForm.status         = eq.status
      equipmentForm.notes          = eq.notes ?? ''
      equipmentForm.brand           = eq.brand ?? ''
      equipmentForm.model           = eq.model ?? ''
      equipmentForm.specifications  = eq.specifications ?? ''
      equipmentForm.serial_number   = eq.serial_number ?? ''
      equipmentForm.purchase_date   = eq.purchase_date ?? ''
      equipmentForm.warranty_expiry = eq.warranty_expiry ?? ''
      showEquipmentModal.value     = true
      buildQrCode(eq)
    })
}

function submitEquipment() {
  equipmentForm.post(equipmentActionUrl.value, {
    onSuccess: () => { showEquipmentModal.value = false },
  })
}

function deleteEquipment() {
  if (!currentEquipmentId.value) return
  if (confirm('Are you sure you want to delete this equipment?')) {
    router.delete(`/admin/equipment/${currentEquipmentId.value}`, {
      onSuccess: () => { showEquipmentModal.value = false },
    })
  }
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function equipmentClass(status) {
  return { operational: 'equipment-working', 'has-issue': 'equipment-issue', maintenance: 'equipment-maintenance' }[status] ?? ''
}

function equipmentIcon(type) {
  return { computer: 'laptop', printer: 'printer', projector: 'videocam' }[type] ?? 'desktop'
}
</script>

<template>
  <AppLayout>
    <template #nav><NavAdmin /></template>

    <div class="container">

      <div v-if="planLimits" class="filter-bar" style="justify-content:space-between;align-items:center;">
        <span style="color:var(--color-text-secondary);font-size:0.9rem;">
          <strong style="color:var(--color-primary-dark);">{{ planLimits.plan }} plan</strong>
          &nbsp;·&nbsp; Labs: {{ planLimits.labs.current }}/{{ planLimits.labs.max ?? '∞' }}
          &nbsp;·&nbsp; Equipment: {{ planLimits.equipment.current }}/{{ planLimits.equipment.max ?? '∞' }}
        </span>
        <a href="/admin/upgrade" class="view-all-link">Upgrade <Icon name="arrow-right" /></a>
      </div>

      <div class="page-header">
        <div>
          <h1>Lab Configuration</h1>
          <p style="color:#9ca3af;">Manage lab layouts and equipment assignments</p>
        </div>
        <button
          type="button"
          class="btn btn-primary"
          :disabled="labsAtLimit"
          :title="labsAtLimit ? 'Lab limit reached for your plan — upgrade to add more.' : ''"
          @click="openAddLab"
        >+ Add New Lab</button>
      </div>

      <!-- Stats -->
      <div class="stats-row">
        <div class="stat-card">
          <div class="stat-label">Total Labs</div>
          <div class="stat-value">{{ stats.total_labs }}</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Total Equipment</div>
          <div class="stat-value">{{ stats.total_equipment }}</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Operational</div>
          <div class="stat-value">{{ stats.total_operational }}</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Under Maintenance</div>
          <div class="stat-value">{{ stats.total_maintenance }}</div>
        </div>
      </div>

      <!-- Labs grid -->
      <div class="labs-grid">
        <template v-if="labs.length">
          <div v-for="lab in labs" :key="lab.id" class="lab-card">

            <div class="lab-header">
              <div class="lab-title-section">
                <div class="lab-icon"><Icon name="laptop" /></div>
                <div>
                  <h2 class="lab-title">{{ lab.name }}</h2>
                  <p style="color:#9ca3af;font-size:0.85rem;">{{ lab.code }}</p>
                </div>
              </div>
              <div class="lab-actions">
                <button type="button" class="action-btn" @click="editLab(lab.id)">Edit</button>
                <button type="button" class="action-btn" @click="toggleStatus(lab.id)">
                  {{ lab.is_active ? 'Deactivate' : 'Activate' }}
                </button>
                <button type="button" class="action-btn btn-danger" @click="deleteLab(lab.id)">Delete</button>
              </div>
            </div>

            <div class="lab-info-grid">
              <div class="info-item">
                <div class="info-label">Location</div>
                <div class="info-value">{{ lab.location ?? 'Not specified' }}</div>
              </div>
              <div class="info-item">
                <div class="info-label">Capacity</div>
                <div class="info-value">{{ lab.capacity }} Workstations</div>
              </div>
              <div class="info-item">
                <div class="info-label">Operational</div>
                <div class="info-value" :style="{ color: lab.operational_count === lab.equipment.length ? '#34d399' : '#fbbf24' }">
                  {{ lab.operational_count }} / {{ lab.equipment.length }}
                </div>
              </div>
              <div class="info-item">
                <div class="info-label">Status</div>
                <div class="info-value">
                  <span class="status-badge" :class="lab.is_active ? 'status-active' : 'status-inactive'">
                    {{ lab.is_active ? 'Active' : 'Inactive' }}
                  </span>
                </div>
              </div>
            </div>

            <p v-if="lab.description" style="color:#9ca3af;font-size:0.9rem;margin:1rem 0;">
              {{ lab.description }}
            </p>

            <!-- Equipment section -->
            <div class="equipment-section">
              <div class="equipment-header">
                <h3 class="section-title">Equipment Layout ({{ lab.equipment.length }} items)</h3>
                <div style="display:flex;gap:0.5rem;flex-wrap:wrap;justify-content:flex-end;">
                  <button
                    type="button"
                    class="btn-add"
                    :disabled="!lab.equipment.length"
                    :title="lab.equipment.length ? 'Print a QR sheet for every device in this lab.' : 'Add equipment first.'"
                    @click="printLabQrSheet(lab)"
                  >QR Sheet</button>
                  <button
                    type="button"
                    class="btn-add"
                    :disabled="equipmentAtLimit"
                    :title="equipmentAtLimit ? 'Equipment limit reached for your plan — upgrade to add more.' : ''"
                    @click="openBulkAdd(lab.id)"
                  >+ Bulk Add</button>
                  <button
                    type="button"
                    class="btn-add"
                    :disabled="equipmentAtLimit"
                    :title="equipmentAtLimit ? 'Equipment limit reached for your plan — upgrade to add more.' : ''"
                    @click="openAddEquipment(lab.id)"
                  >+ Add Equipment</button>
                </div>
              </div>
              <div class="equipment-grid">
                <template v-if="lab.equipment.length">
                  <div
                    v-for="eq in lab.equipment.slice(0, 20)"
                    :key="eq.id"
                    class="equipment-item"
                    :class="equipmentClass(eq.status)"
                    @click="editEquipment(eq.id)"
                  >
                    <div class="tooltip">
                      {{ eq.equipment_code }}: {{ eq.status }}
                      <span v-if="eq.brand || eq.model"><br>{{ eq.brand }} {{ eq.model }}</span>
                      <span v-if="eq.notes"><br>{{ eq.notes.substring(0, 50) }}</span>
                    </div>
                    <div class="equipment-icon"><Icon :name="equipmentIcon(eq.type)" /></div>
                    <div class="equipment-id">{{ eq.equipment_code }}</div>
                  </div>
                </template>
                <div v-else style="grid-column:1/-1;text-align:center;padding:2rem;color:#9ca3af;">
                  <p>No equipment added yet</p>
                  <button
                    type="button"
                    class="btn-add"
                    style="margin-top:1rem;"
                    :disabled="equipmentAtLimit"
                    :title="equipmentAtLimit ? 'Equipment limit reached for your plan — upgrade to add more.' : ''"
                    @click="openAddEquipment(lab.id)"
                  >+ Add Equipment</button>
                </div>
              </div>
              <p v-if="lab.equipment.length > 20" style="text-align:center;margin-top:1rem;color:#9ca3af;font-size:0.9rem;">
                Showing 20 of {{ lab.equipment.length }} items
              </p>
            </div>

          </div>
        </template>

        <div v-else class="empty-state" style="grid-column:1/-1;">
          <div class="empty-icon"><Icon name="school" /></div>
          <h3>No Labs Configured</h3>
          <p>Create your first lab to get started</p>
          <button
            type="button"
            class="btn btn-primary"
            style="margin-top:1rem;"
            :disabled="labsAtLimit"
            :title="labsAtLimit ? 'Lab limit reached for your plan — upgrade to add more.' : ''"
            @click="openAddLab"
          >+ Add New Lab</button>
        </div>
      </div>

    </div>

    <!-- ── Lab Modal ─────────────────────────────────────────────────────── -->
    <div v-if="showLabModal" class="modal-overlay active" @click.self="showLabModal = false">
      <div class="modal">
        <h2 class="modal-header">{{ labModalTitle }}</h2>
        <form @submit.prevent="submitLab">

          <div class="form-group">
            <label>Lab Name *</label>
            <input v-model="labForm.name" type="text" placeholder="e.g., Computer Lab A" required>
            <span v-if="labForm.errors.name" class="text-danger">{{ labForm.errors.name }}</span>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Lab Code *</label>
              <input v-model="labForm.code" type="text" placeholder="e.g., LAB-A" required>
              <span v-if="labForm.errors.code" class="text-danger">{{ labForm.errors.code }}</span>
            </div>
            <div class="form-group">
              <label>Capacity *</label>
              <input v-model="labForm.capacity" type="number" placeholder="Number of workstations" required min="1">
              <span v-if="labForm.errors.capacity" class="text-danger">{{ labForm.errors.capacity }}</span>
            </div>
          </div>

          <div class="form-group">
            <label>Location</label>
            <input v-model="labForm.location" type="text" placeholder="e.g., Building 1, 2nd Floor">
          </div>

          <div class="form-group">
            <label>Description</label>
            <textarea v-model="labForm.description" placeholder="Optional description of the lab"></textarea>
          </div>

          <div class="modal-actions">
            <button type="submit" class="btn btn-save" :disabled="labForm.processing">
              {{ labForm.processing ? 'Saving…' : 'Save Lab' }}
            </button>
            <button type="button" class="btn btn-cancel" @click="showLabModal = false">Cancel</button>
          </div>
        </form>
      </div>
    </div>

    <!-- ── Equipment Modal ───────────────────────────────────────────────── -->
    <div v-if="showEquipmentModal" class="modal-overlay active" @click.self="showEquipmentModal = false">
      <div class="modal">
        <h2 class="modal-header">{{ equipmentModalTitle }}</h2>
        <form @submit.prevent="submitEquipment">

          <div class="form-group">
            <label>Equipment Code *</label>
            <input v-model="equipmentForm.equipment_code" type="text" placeholder="e.g., PC-01" required>
            <span v-if="equipmentForm.errors.equipment_code" class="text-danger">{{ equipmentForm.errors.equipment_code }}</span>
          </div>

          <div class="form-group">
            <label>Type *</label>
            <select v-model="equipmentForm.type" required>
              <option value="computer">Computer</option>
              <option value="printer">Printer</option>
              <option value="projector">Projector</option>
              <option value="other">Other</option>
            </select>
          </div>

          <div class="form-group">
            <label>Status *</label>
            <select v-model="equipmentForm.status" required>
              <option value="operational">Operational</option>
              <option value="has-issue">Has Issue</option>
              <option value="maintenance">Under Maintenance</option>
              <option value="retired">Retired</option>
            </select>
          </div>

          <!-- Hardware details — all optional -->
          <div class="form-row">
            <div class="form-group">
              <label>Brand</label>
              <input v-model="equipmentForm.brand" type="text" placeholder="e.g., Dell, HP">
            </div>
            <div class="form-group">
              <label>Model</label>
              <input v-model="equipmentForm.model" type="text" placeholder="e.g., OptiPlex 7090">
            </div>
          </div>

          <div class="form-group">
            <label>Specifications</label>
            <textarea v-model="equipmentForm.specifications" placeholder="e.g., i5-11500, 8GB RAM, 256GB SSD"></textarea>
          </div>

          <div class="form-group">
            <label>Serial Number</label>
            <input v-model="equipmentForm.serial_number" type="text" placeholder="Manufacturer serial number">
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Purchase Date</label>
              <input v-model="equipmentForm.purchase_date" type="date">
            </div>
            <div class="form-group">
              <label>Warranty Expiry</label>
              <input v-model="equipmentForm.warranty_expiry" type="date">
              <span v-if="equipmentForm.errors.warranty_expiry" class="text-danger">{{ equipmentForm.errors.warranty_expiry }}</span>
            </div>
          </div>

          <div class="form-group">
            <label>Notes</label>
            <textarea v-model="equipmentForm.notes" placeholder="Optional notes about this equipment"></textarea>
          </div>

          <!-- QR code + history — edit mode only (a new device has no id yet) -->
          <template v-if="currentEquipmentId">
            <div class="form-group" style="border-top:1px solid #e2e8f0;padding-top:1.25rem;">
              <label>Equipment QR Code</label>
              <div style="display:flex;gap:1rem;align-items:flex-start;flex-wrap:wrap;">
                <div style="text-align:center;">
                  <img
                    v-if="qrDataUrl"
                    :src="qrDataUrl"
                    alt="Equipment QR code"
                    style="width:140px;height:140px;border:1px solid #e2e8f0;border-radius:8px;background:#fff;"
                  >
                  <div
                    v-else
                    style="width:140px;height:140px;border:1px dashed #cbd5e1;border-radius:8px;display:flex;align-items:center;justify-content:center;text-align:center;padding:0.5rem;color:#94a3b8;font-size:0.8rem;"
                  >{{ qrError || 'Generating…' }}</div>
                  <div style="font-weight:600;font-size:0.85rem;margin-top:0.4rem;">
                    {{ qrEquipment?.equipment_code }}
                  </div>
                </div>

                <div style="flex:1;min-width:180px;font-size:0.85rem;color:#64748b;">
                  <p style="margin:0 0 0.6rem;">
                    Print this code and attach it to the device. Students scan it when reporting an issue.
                  </p>
                  <code style="display:block;word-break:break-all;background:#f1f5f9;border-radius:6px;padding:0.4rem 0.5rem;font-size:0.75rem;color:#334155;">{{ qrEquipment?.qr_payload ?? '—' }}</code>
                  <div style="display:flex;gap:0.5rem;margin-top:0.75rem;flex-wrap:wrap;">
                    <button type="button" class="btn btn-save" :disabled="!qrDataUrl" @click="printQr">Print QR</button>
                    <button type="button" class="btn btn-cancel" @click="loadHistory">
                      {{ historyOpen ? 'Hide History' : 'View History' }}
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <div
              v-if="historyOpen"
              class="form-group"
              style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:1rem;"
            >
              <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.75rem;gap:0.5rem;">
                <strong style="font-size:0.9rem;">Equipment History</strong>
                <span v-if="historyData" style="font-size:0.8rem;color:#64748b;">
                  {{ historyData.counts.open }} open · {{ historyData.counts.total }} total
                </span>
              </div>

              <div v-if="historyLoading" style="color:#64748b;font-size:0.85rem;">Loading history…</div>
              <div v-else-if="historyError" class="text-danger" style="font-size:0.85rem;">{{ historyError }}</div>
              <div v-else-if="historyData && !historyData.reports.length" style="color:#64748b;font-size:0.85rem;">
                No tickets recorded for this equipment yet.
              </div>
              <div v-else-if="historyData" style="display:flex;flex-direction:column;gap:0.5rem;max-height:240px;overflow-y:auto;">
                <Link
                  v-for="r in historyData.reports"
                  :key="r.id"
                  :href="`/admin/tickets/${r.id}`"
                  style="display:flex;justify-content:space-between;align-items:center;gap:0.75rem;text-decoration:none;color:inherit;background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:0.5rem 0.65rem;"
                >
                  <div style="min-width:0;">
                    <div style="font-weight:600;font-size:0.82rem;color:#0f172a;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                      {{ r.ticket_number }} · {{ r.title }}
                    </div>
                    <div style="font-size:0.75rem;color:#94a3b8;">{{ formatDate(r.created_at) }}</div>
                  </div>
                  <span class="status-badge" :class="`status-${ticketStatusColor(r.status)}`" style="white-space:nowrap;">
                    {{ ticketStatusLabel(r.status) }}
                  </span>
                </Link>
              </div>
            </div>
          </template>

          <div class="modal-actions">
            <button type="submit" class="btn btn-save" :disabled="equipmentForm.processing">
              {{ equipmentForm.processing ? 'Saving…' : 'Save Equipment' }}
            </button>
            <button type="button" class="btn btn-cancel" @click="showEquipmentModal = false">Cancel</button>
            <button
              v-if="currentEquipmentId"
              type="button"
              class="btn btn-danger"
              @click="deleteEquipment"
            >Delete</button>
          </div>
        </form>
      </div>
    </div>

    <!-- ── Bulk Add Equipment Modal ──────────────────────────────────────── -->
    <div v-if="showBulkModal" class="modal-overlay active" @click.self="showBulkModal = false">
      <div class="modal">
        <h2 class="modal-header">Bulk Add Equipment</h2>
        <form @submit.prevent="submitBulk">

          <div class="form-group">
            <label>Equipment Codes *</label>
            <textarea
              v-model="bulkForm.codes"
              rows="7"
              placeholder="PC-01&#10;PC-02&#10;PC-03&#10;&#10;or comma-separated: PC-04, PC-05"
              required
            ></textarea>
            <span v-if="bulkForm.errors.codes" class="text-danger">{{ bulkForm.errors.codes }}</span>
            <p style="font-size:0.8rem;color:#64748b;margin-top:0.4rem;">
              One code per line (or comma-separated). Codes already in this lab are skipped.
            </p>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Type *</label>
              <select v-model="bulkForm.type" required>
                <option value="computer">Computer</option>
                <option value="printer">Printer</option>
                <option value="projector">Projector</option>
                <option value="other">Other</option>
              </select>
            </div>
            <div class="form-group">
              <label>Status *</label>
              <select v-model="bulkForm.status" required>
                <option value="operational">Operational</option>
                <option value="has-issue">Has Issue</option>
                <option value="maintenance">Under Maintenance</option>
                <option value="retired">Retired</option>
              </select>
            </div>
          </div>

          <div class="modal-actions">
            <button type="submit" class="btn btn-save" :disabled="bulkForm.processing">
              {{ bulkForm.processing ? 'Adding…' : 'Add Equipment' }}
            </button>
            <button type="button" class="btn btn-cancel" @click="showBulkModal = false">Cancel</button>
          </div>
        </form>
      </div>
    </div>

  </AppLayout>
</template>