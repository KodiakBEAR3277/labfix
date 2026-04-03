<script setup>
// Pages/User/Reports/Create.vue
// Path: resources/js/Pages/User/Reports/Create.vue
//
// useForm() replaces the native <form> POST — fixes the 419 CSRF expiry bug
// that occurred because the native form's token could drift from the Inertia
// session. useForm() sends CSRF via the X-XSRF-TOKEN header automatically.
//
// File attachments are collected into form.attachments as File objects.
// useForm() detects File objects and serialises as multipart automatically.
//
// Step navigation is purely local state — no useForm involvement until submit.
// The equipment fetch in step 2 stays as a plain fetch() — it's a GET for
// dropdown data, not a form submission, so useForm is not needed there.

import AppLayout from '../../../Layouts/AppLayout.vue'
import NavUser from '../../../Components/Nav/NavUser.vue'
import { Link, useForm } from '@inertiajs/vue3'
import { ref, computed } from 'vue'

const props = defineProps({
  labs:        Array,
  maintenance: Object,
})

// ── Step state ────────────────────────────────────────────────────────────────
const currentStep = ref(1)
const totalSteps  = 5
const progressWidth = computed(() => `${((currentStep.value - 1) / (totalSteps - 1)) * 100}%`)

function nextStep() { if (currentStep.value < totalSteps) currentStep.value++ }
function prevStep()  { if (currentStep.value > 1) currentStep.value-- }

// ── UI state — drives the review step display ─────────────────────────────────
const selectedLabName       = ref('')
const equipment             = ref([])
const equipmentLoading      = ref(false)
const selectedEquipmentName = ref("General lab issue / Don't know")
const files                 = ref([])   // File objects for the review display

// ── useForm — all submission data lives here ──────────────────────────────────
const form = useForm({
  lab_id:       null,
  equipment_id: '',
  category:     '',
  title:        '',
  description:  '',
  attachments:  [],   // File objects — useForm serialises as multipart
})

// ── Lab selection ─────────────────────────────────────────────────────────────
function selectLab(lab) {
  form.lab_id             = lab.id
  selectedLabName.value   = lab.name
  form.equipment_id       = ''
  selectedEquipmentName.value = "General lab issue / Don't know"
  equipment.value         = []

  equipmentLoading.value = true
  fetch(`/api/labs/${lab.id}/equipment`)
    .then(r => r.json())
    .then(data => { equipment.value = data })
    .catch(() => { equipment.value = [] })
    .finally(() => { equipmentLoading.value = false })
}

function selectEquipment(e) {
  const opt = e.target.options[e.target.selectedIndex]
  form.equipment_id = e.target.value
  selectedEquipmentName.value = e.target.value
    ? opt.text
    : "General lab issue / Don't know"
}

// ── File handling ─────────────────────────────────────────────────────────────
const fileInput = ref(null)

function handleFiles(e) {
  const selected = Array.from(e.target.files ?? [])
  files.value         = selected   // for review display
  form.attachments    = selected   // for submission
}

// ── Submit ────────────────────────────────────────────────────────────────────
function submit() {
  form.post('/user/reports')
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
        <Link href="/user/dashboard" class="btn btn-primary">Back to Dashboard</Link>
      </div>

      <template v-else>

        <!-- Progress steps -->
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

        <!-- Single form element — @submit.prevent calls submit() which uses useForm -->
        <form @submit.prevent="submit">
          <div class="form-container">

            <!-- Step 1: Lab -->
            <div class="form-step" :class="{ active: currentStep === 1 }">
              <h2>Select Lab Location</h2>
              <div class="lab-grid">
                <div
                  v-for="lab in labs"
                  :key="lab.id"
                  class="lab-option"
                  :class="{ selected: form.lab_id === lab.id }"
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
                  :disabled="!form.lab_id"
                  @click="nextStep"
                >Next Step</button>
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
                  :class="{ selected: form.category === cat.value }"
                  @click="form.category = cat.value"
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
                  :disabled="!form.category"
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
                  v-model="form.title"
                  type="text"
                  placeholder="Brief description of the problem"
                  required
                >
                <span v-if="form.errors.title" class="text-danger">{{ form.errors.title }}</span>
              </div>
              <div class="form-group">
                <label for="issue-desc">Detailed Description *</label>
                <textarea
                  id="issue-desc"
                  v-model="form.description"
                  placeholder="Please provide as much detail as possible..."
                  required
                ></textarea>
                <p class="help-text">Minimum 10 characters.</p>
                <span v-if="form.errors.description" class="text-danger">{{ form.errors.description }}</span>
              </div>
              <div class="form-group">
                <label>Attach Screenshots (Optional)</label>
                <div class="file-upload" @click="fileInput.click()">
                  <input
                    ref="fileInput"
                    type="file"
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
                  :disabled="!form.title || form.description.length < 10"
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
                  {{ form.category ? form.category.charAt(0).toUpperCase() + form.category.slice(1) : '—' }}
                </div>
              </div>
              <div class="review-item">
                <div class="review-label">Issue Title</div>
                <div class="review-value">{{ form.title || '—' }}</div>
              </div>
              <div class="review-item">
                <div class="review-label">Description</div>
                <div class="review-value">{{ form.description || '—' }}</div>
              </div>
              <div class="review-item">
                <div class="review-label">Attachments</div>
                <div class="review-value">
                  {{ files.length ? `${files.length} file(s) attached` : 'None' }}
                </div>
              </div>
              <div class="button-group">
                <button type="button" class="btn btn-secondary" @click="prevStep">Back</button>
                <button
                  type="submit"
                  class="btn btn-primary"
                  :disabled="form.processing"
                >
                  {{ form.processing ? 'Submitting…' : 'Submit Report' }}
                </button>
              </div>
            </div>

          </div>
        </form>
      </template>
    </div>
  </AppLayout>
</template>