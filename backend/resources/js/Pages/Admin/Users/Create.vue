<script setup>
// Pages/Admin/Users/Create.vue
// Path: resources/js/Pages/Admin/Users/Create.vue
// Mirrors: resources/views/admin/users/create.blade.php

import AppLayout from '../../../Layouts/AppLayout.vue'
import NavAdmin from '../../../Components/Nav/NavAdmin.vue'
import { Link, useForm } from '@inertiajs/vue3'

const form = useForm({
  first_name:       '',
  last_name:        '',
  email:            '',
  role:             'student',
  password:         '',
  phone:            '',
  student_staff_id: '',
})

function submit() {
  form.post('/admin/users')
}
</script>

<template>
  <AppLayout>
    <template #nav><NavAdmin /></template>

    <div class="container">
      <Link href="/admin/users" class="back-btn">← Back to User Management</Link>

      <div class="page-header">
        <h1>Add New User</h1>
        <p>Create a new user account</p>
      </div>

      <div class="card" style="max-width:800px;margin:0 auto;">
        <form @submit.prevent="submit">

          <div class="form-section">
            <h3 class="section-title">Personal Information</h3>

            <div class="form-row">
              <div class="form-group">
                <label>First Name *</label>
                <input v-model="form.first_name" type="text" required autocomplete="given-name">
                <span v-if="form.errors.first_name" class="text-danger">{{ form.errors.first_name }}</span>
              </div>
              <div class="form-group">
                <label>Last Name *</label>
                <input v-model="form.last_name" type="text" required autocomplete="family-name">
                <span v-if="form.errors.last_name" class="text-danger">{{ form.errors.last_name }}</span>
              </div>
            </div>

            <div class="form-group">
              <label>Email Address *</label>
              <input v-model="form.email" type="email" required autocomplete="email">
              <span v-if="form.errors.email" class="text-danger">{{ form.errors.email }}</span>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Phone</label>
                <input v-model="form.phone" type="tel" placeholder="Optional">
                <span v-if="form.errors.phone" class="text-danger">{{ form.errors.phone }}</span>
              </div>
              <div class="form-group">
                <label>Student / Staff ID</label>
                <input v-model="form.student_staff_id" type="text" placeholder="Optional">
                <span v-if="form.errors.student_staff_id" class="text-danger">{{ form.errors.student_staff_id }}</span>
              </div>
            </div>
          </div>

          <div class="form-section">
            <h3 class="section-title">Account Settings</h3>

            <div class="form-group">
              <label>Role *</label>
              <select v-model="form.role" required>
                <option value="student">Student</option>
                <option value="staff">Staff</option>
                <option value="it-support">IT Support</option>
                <option value="admin">Admin</option>
              </select>
              <span v-if="form.errors.role" class="text-danger">{{ form.errors.role }}</span>
            </div>

            <div class="form-group">
              <label>Password *</label>
              <input v-model="form.password" type="password" required autocomplete="new-password">
              <p class="help-text">Minimum 8 characters</p>
              <span v-if="form.errors.password" class="text-danger">{{ form.errors.password }}</span>
            </div>
          </div>

          <div class="action-buttons">
            <button type="submit" class="btn btn-primary" :disabled="form.processing">
              {{ form.processing ? 'Creating…' : 'Create User' }}
            </button>
            <Link href="/admin/users" class="btn btn-secondary">Cancel</Link>
          </div>

        </form>
      </div>
    </div>
  </AppLayout>
</template>