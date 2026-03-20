<script setup>
// Pages/User/Reports/Create.vue
// Mirrors: resources/views/user/reports/create.blade.php
// Path:    resources/js/Pages/User/Reports/Create.vue
//
// Step navigation mirrors the original blade JS exactly:
// each step div gets/loses the 'active' class driven by currentStep ref.
// The CSS already handles display: none / display: block via .form-step / .form-step.active.

import AppLayout from '../../../Layouts/AppLayout.vue'
import NavUser from '../../../Components/Nav/NavUser.vue'
import { ref, computed } from 'vue'

const props = defineProps({
  labs:        Array,
  maintenance: Object,
})

// ── Step state ───────────────────────────────────────────────────────────────
const currentStep = ref(1)
const totalSteps  = 5

const progressWidth = computed(() => `${((currentStep.value - 1) / (totalSteps - 1)) * 100}%`)

// Plain synchronous functions — no async, no awaits here
function nextStep() {
  if (currentStep.value < totalSteps) currentStep.value++
}
function prevStep() {
  if (currentStep.value > 1) currentStep.value--
}

// ── Form data ─────────────────────────────────────────────────────────────────
const selectedLabId         = ref(null)
const selectedLabName       = ref('')
const equipment             = ref([])
const equipmentLoading      = ref(false)
const selectedEquipmentId   = ref('')
const selectedEquipmentName = ref("General lab issue / Don't know")
const category              = ref('')
const title                 = ref('')
const description           = ref('')
const files                 = ref([])

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? ''

// ── Lab selection — fire-and-forget fetch, no awaiting ────────────────────────
function selectLab(lab) {
  selectedLabId.value         = lab.id
  selectedLabName.value       = lab.name
  selectedEquipmentId.value   = ''
  selectedEquipmentName.value = "General lab issue / Don't know"
  equipment.value             = []

  // Fetch equipment in background — result populates the select in step 2
  equipmentLoading.value = true
  fetch(`/api/labs/${lab.id}/equipment`)
    .then(r => r.json())
    .then(data => { equipment.value = data })
    .catch(() => { equipment.value = [] })
    .finally(() => { equipmentLoading.value = false })
}

function selectEquipment(e) {
  const opt = e.target.options[e.target.selectedIndex]
  selectedEquipmentId.value   = e.target.value
  selectedEquipmentName.value = e.target.value
    ? opt.text
    : "General lab issue / Don't know"
}

// ── File handling ─────────────────────────────────────────────────────────────
function handleFiles(e) {
  files.value = Array.from(e.target.files)
}

// ── Category options ──────────────────────────────────────────────────────────
const categories = [
  { value: 'hardware', icon: '🔧', label: 'Hardware', desc: 'Physical components issues' },
  { value: 'software', icon: '💾', label: 'Software', desc: 'Programs and applications'  },
  { value: 'network',  icon: '🌐', label: 'Network',  desc: 'Internet and connectivity'  },
  { value: 'other',    icon: '❓', label: 'Other',    desc: 'Other technical issues'      },
]
</script>

<template>
  <AppLayout>
    <template #nav><NavUser /></template>

    <div class="container">
      <div class="page-header">
        <h1>Report New Issue</h1>
        <p>Help us fix your problem by providing detailed information</p>
      </div>

      <!-- Maintenance notice -->
      <div v-if="maintenance?.active" class="maintenance-notice">
        <div class="maintenance-icon">🔧</div>
        <h2>System Maintenance</h2>
        <p>{{ maintenance.message }}</p>
        <a href="/user/dashboard" class="btn btn-primary">Back to Dashboard</a>
      </div>

      <template v-else>

        <!-- Progress steps — mirrors the blade's data-step divs -->
        <div class="progress-steps">
          <div class="progress-line" :style="{ width: progressWidth }"></div>
          <div
            v-for="n in totalSteps"
            :key="n"
            class="step"
            :class="{ active: currentStep === n, completed: currentStep > n }"
          >
            <div class="step-circle">{{ n }}</div>
            <div class="step-label">
              {{ ['Lab Location','Equipment','Problem Type','Description','Review'][n-1] }}
            </div>
          </div>
        </div>

        <!-- Native POST form -->
        <form action="/user/reports" method="POST" enctype="multipart/form-data">
          <input type="hidden" name="_token"       :value="csrfToken">
          <input type="hidden" name="lab_id"       :value="selectedLabId">
          <input type="hidden" name="equipment_id" :value="selectedEquipmentId || ''">
          <input type="hidden" name="category"     :value="category">

          <div class="form-container">

            <!-- Step 1: Lab -->
            <div class="form-step" :class="{ active: currentStep === 1 }">
              <h2>Select Lab Location</h2>
              <div class="lab-grid">
                <div
                  v-for="lab in labs"
                  :key="lab.id"
                  class="lab-option"
                  :class="{ selected: selectedLabId === lab.id }"
                  @click="selectLab(lab)"
                  style="cursor:pointer;"
                >
                  <div class="lab-icon">💻</div>
                  <div class="lab-name">{{ lab.name }}</div>
                  <div style="font-size:0.8rem;color:#9ca3af;margin-top:0.25rem;">
                    {{ lab.operational_count ?? 0 }}/{{ lab.capacity }} operational
                  </div>
                </div>
              </div>
              <div class="button-group">
                <button
                  type="button"
                  class="btn btn-primary"
                  :disabled="!selectedLabId"
                  @click="nextStep"
                >
                  Next Step
                </button>
              </div>
            </div>

            <!-- Step 2: Equipment -->
            <div class="form-step" :class="{ active: currentStep === 2 }">
              <h2>Select Equipment</h2>
              <div class="form-group">
                <label for="equipment-select">Equipment/Computer ID</label>
                <select id="equipment-select" @change="selectEquipment">
                  <option value="">
                    {{ equipmentLoading ? 'Loading...' : "General lab issue / Don't know" }}
                  </option>
                  <option
                    v-for="eq in equipment"
                    :key="eq.id"
                    :value="eq.id"
                    :disabled="eq.status === 'retired'"
                  >
                    {{ eq.equipment_code }}{{ eq.status !== 'operational' ? ' (Currently has issues)' : '' }}
                  </option>
                </select>
                <p class="help-text">Select the specific equipment, or leave blank if unsure</p>
              </div>
              <div class="button-group">
                <button type="button" class="btn btn-secondary" @click="prevStep">Back</button>
                <button type="button" class="btn btn-primary" @click="nextStep">Next Step</button>
              </div>
            </div>

            <!-- Step 3: Category -->
            <div class="form-step" :class="{ active: currentStep === 3 }">
              <h2>What type of problem are you experiencing?</h2>
              <div class="category-grid">
                <div
                  v-for="cat in categories"
                  :key="cat.value"
                  class="category-option"
                  :class="{ selected: category === cat.value }"
                  @click="category = cat.value"
                  style="cursor:pointer;"
                >
                  <div class="category-icon">{{ cat.icon }}</div>
                  <div class="category-info">
                    <h3>{{ cat.label }}</h3>
                    <p>{{ cat.desc }}</p>
                  </div>
                </div>
              </div>
              <div class="button-group">
                <button type="button" class="btn btn-secondary" @click="prevStep">Back</button>
                <button
                  type="button"
                  class="btn btn-primary"
                  :disabled="!category"
                  @click="nextStep"
                >Next Step</button>
              </div>
            </div>

            <!-- Step 4: Description -->
            <div class="form-step" :class="{ active: currentStep === 4 }">
              <h2>Describe the Problem</h2>
              <div class="form-group">
                <label for="issue-title">Issue Title *</label>
                <input
                  id="issue-title"
                  v-model="title"
                  type="text"
                  name="title"
                  placeholder="Brief description of the problem"
                  required
                >
              </div>
              <div class="form-group">
                <label for="issue-desc">Detailed Description *</label>
                <textarea
                  id="issue-desc"
                  v-model="description"
                  name="description"
                  placeholder="Please provide as much detail as possible..."
                  required
                ></textarea>
                <p class="help-text">Minimum 10 characters.</p>
              </div>
              <div class="form-group">
                <label>Attach Screenshots (Optional)</label>
                <div class="file-upload" @click="$refs.fileInput.click()">
                  <input
                    ref="fileInput"
                    type="file"
                    name="attachments[]"
                    accept="image/*,application/pdf"
                    multiple
                    style="display:none"
                    @change="handleFiles"
                  >
                  <div class="upload-icon">📷</div>
                  <p style="color:#9ca3af;">Click to upload screenshots or documents</p>
                  <p style="color:#6b7280;font-size:0.85rem;">PNG, JPG, PDF up to 5MB each</p>
                </div>
                <ul v-if="files.length" style="margin-top:1rem;list-style:none;padding:0;">
                  <li
                    v-for="f in files"
                    :key="f.name"
                    style="padding:0.5rem;background:rgba(45,212,191,0.1);margin-bottom:0.5rem;border-radius:6px;color:#2dd4bf;"
                  >
                    📎 {{ f.name }} ({{ (f.size / 1024).toFixed(2) }} KB)
                  </li>
                </ul>
              </div>
              <div class="button-group">
                <button type="button" class="btn btn-secondary" @click="prevStep">Back</button>
                <button
                  type="button"
                  class="btn btn-primary"
                  :disabled="!title || description.length < 10"
                  @click="nextStep"
                >Review & Submit</button>
              </div>
            </div>

            <!-- Step 5: Review -->
            <div class="form-step" :class="{ active: currentStep === 5 }">
              <h2>Review Your Report</h2>
              <div class="review-item">
                <div class="review-label">Lab Location</div>
                <div class="review-value">{{ selectedLabName || '—' }}</div>
              </div>
              <div class="review-item">
                <div class="review-label">Equipment</div>
                <div class="review-value">{{ selectedEquipmentName }}</div>
              </div>
              <div class="review-item">
                <div class="review-label">Problem Category</div>
                <div class="review-value">
                  {{ category ? category.charAt(0).toUpperCase() + category.slice(1) : '—' }}
                </div>
              </div>
              <div class="review-item">
                <div class="review-label">Issue Title</div>
                <div class="review-value">{{ title || '—' }}</div>
              </div>
              <div class="review-item">
                <div class="review-label">Description</div>
                <div class="review-value">{{ description || '—' }}</div>
              </div>
              <div class="review-item">
                <div class="review-label">Attachments</div>
                <div class="review-value">
                  {{ files.length ? `${files.length} file(s) attached` : 'None' }}
                </div>
              </div>
              <div class="button-group">
                <button type="button" class="btn btn-secondary" @click="prevStep">Back</button>
                <button type="submit" class="btn btn-primary">Submit Report</button>
              </div>
            </div>

          </div>
        </form>
      </template>
    </div>
  </AppLayout>
</template>