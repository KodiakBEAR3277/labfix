<script setup>
// Pages/User/LabStatus.vue
// Mirrors: resources/views/user/lab-status.blade.php
// Path:    resources/js/Pages/User/LabStatus.vue

import AppLayout from '../../Layouts/AppLayout.vue'
import NavUser from '../../Components/Nav/NavUser.vue'
import { ref, computed } from 'vue'

const props = defineProps({
  labs: Array,   // each lab has equipment array with status
})

// Selected lab for the equipment detail view
const selectedLabId = ref(props.labs?.[0]?.id ?? null)

const selectedLab = computed(() =>
  props.labs.find(l => l.id === selectedLabId.value) ?? null
)

function equipmentClass(status) {
  const map = {
    'operational': 'equipment-working',
    'has-issue':   'equipment-issue',
    'maintenance': 'equipment-maintenance',
  }
  return map[status] ?? ''
}

function labStatusClass(lab) {
  const total       = lab.equipment.length
  const operational = lab.equipment.filter(e => e.status === 'operational').length
  if (total === 0) return 'status-inactive'
  return (operational / total) >= 0.5 ? 'status-active' : 'status-warning'
}

function labStatusLabel(lab) {
  const total       = lab.equipment.length
  const operational = lab.equipment.filter(e => e.status === 'operational').length
  if (total === 0) return 'No Equipment'
  return (operational / total) >= 0.5 ? 'Operational' : 'Limited'
}

function operationalCount(lab) {
  return lab.equipment.filter(e => e.status === 'operational').length
}

function issueCount(lab) {
  return lab.equipment.filter(e => e.status === 'has-issue').length
}

function maintenanceCount(lab) {
  return lab.equipment.filter(e => e.status === 'maintenance').length
}

function equipmentIcon(type) {
  const map = { computer: '💻', printer: '🖨️', projector: '📽️' }
  return map[type] ?? '🖥️'
}
</script>

<template>
  <AppLayout>
    <template #nav><NavUser /></template>

    <div class="container">
      <div class="page-header">
        <h1>Lab Status</h1>
        <p style="color:#9ca3af;">Real-time equipment availability across all labs</p>
      </div>

      <!-- Lab selector tabs -->
      <div class="filter-tabs" style="margin-bottom:2rem;flex-wrap:wrap;">
        <button
          v-for="lab in labs"
          :key="lab.id"
          type="button"
          class="tab"
          :class="{ active: selectedLabId === lab.id }"
          @click="selectedLabId = lab.id"
        >
          {{ lab.name }}
          <span
            class="status-badge"
            :class="labStatusClass(lab)"
            style="margin-left:0.5rem;font-size:0.75rem;padding:0.2rem 0.5rem;"
          >
            {{ labStatusLabel(lab) }}
          </span>
        </button>
      </div>

      <!-- Selected lab detail -->
      <template v-if="selectedLab">
        <div class="lab-card">

          <!-- Lab header -->
          <div class="lab-header">
            <div class="lab-title-section">
              <div class="lab-icon">💻</div>
              <div>
                <h2 class="lab-title">{{ selectedLab.name }}</h2>
                <p style="color:#9ca3af;font-size:0.85rem;">{{ selectedLab.location ?? 'Location not specified' }}</p>
              </div>
            </div>
            <span class="status-badge" :class="labStatusClass(selectedLab)">
              {{ labStatusLabel(selectedLab) }}
            </span>
          </div>

          <!-- Stats row -->
          <div class="lab-info-grid">
            <div class="info-item">
              <div class="info-label">Total Equipment</div>
              <div class="info-value">{{ selectedLab.equipment.length }}</div>
            </div>
            <div class="info-item">
              <div class="info-label">Operational</div>
              <div class="info-value" style="color:#34d399;">{{ operationalCount(selectedLab) }}</div>
            </div>
            <div class="info-item">
              <div class="info-label">Has Issues</div>
              <div class="info-value" style="color:#ef4444;">{{ issueCount(selectedLab) }}</div>
            </div>
            <div class="info-item">
              <div class="info-label">Maintenance</div>
              <div class="info-value" style="color:#fbbf24;">{{ maintenanceCount(selectedLab) }}</div>
            </div>
          </div>

          <!-- Equipment grid -->
          <div class="equipment-section">
            <div class="equipment-header">
              <h3 class="section-title">Equipment Layout</h3>
            </div>
            <div class="equipment-grid">
              <div
                v-for="eq in selectedLab.equipment"
                :key="eq.id"
                class="equipment-item"
                :class="equipmentClass(eq.status)"
              >
                <div class="tooltip">
                  {{ eq.equipment_code }}: {{ eq.status.replace('-', ' ') }}
                  <span v-if="eq.notes"><br>{{ eq.notes }}</span>
                </div>
                <div class="equipment-icon">{{ equipmentIcon(eq.type) }}</div>
                <div class="equipment-id">{{ eq.equipment_code }}</div>
              </div>
            </div>

            <!-- Legend -->
            <div class="legend">
              <div class="legend-item">
                <div class="legend-color color-working"></div>
                Operational
              </div>
              <div class="legend-item">
                <div class="legend-color color-issue"></div>
                Has Issue
              </div>
              <div class="legend-item">
                <div class="legend-color color-maintenance"></div>
                Maintenance
              </div>
            </div>
          </div>

        </div>
      </template>

      <div v-else style="text-align:center;padding:3rem;color:#9ca3af;">
        <p>No labs available.</p>
      </div>

    </div>
  </AppLayout>
</template>