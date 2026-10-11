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

function openAddEquipment(labId) {
  equipmentModalTitle.value   = 'Add Equipment'
  equipmentActionUrl.value    = '/admin/equipment'
  currentEquipmentId.value    = null
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
  return { computer: '💻', printer: '🖨️', projector: '📽️' }[type] ?? '🖥️'
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
        <a href="/admin/upgrade" class="view-all-link">Upgrade →</a>
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
                <div class="lab-icon">💻</div>
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
                <button
                  type="button"
                  class="btn-add"
                  :disabled="equipmentAtLimit"
                  :title="equipmentAtLimit ? 'Equipment limit reached for your plan — upgrade to add more.' : ''"
                  @click="openAddEquipment(lab.id)"
                >+ Add Equipment</button>
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
                    <div class="equipment-icon">{{ equipmentIcon(eq.type) }}</div>
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
          <div class="empty-icon">🏫</div>
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

  </AppLayout>
</template>