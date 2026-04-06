<script setup>
// Pages/IT/KnowledgeBase/Create.vue
// Path: resources/js/Pages/IT/KnowledgeBase/Create.vue
// Mirrors: resources/views/it/articles/create.blade.php

import AppLayout from '../../../Layouts/AppLayout.vue'
import NavIT from '../../../Components/Nav/NavIT.vue'
import { Link, useForm } from '@inertiajs/vue3'

const form = useForm({
  title:    '',
  category: '',
  excerpt:  '',
  content:  '',
  status:   'draft',
})

function submit() {
  form.post('/it/knowledge-base')
}
</script>

<template>
  <AppLayout>
    <template #nav><NavIT /></template>

    <div class="container">
      <Link href="/it/knowledge-base" class="back-btn">← Back to Knowledge Base</Link>

      <div class="page-header">
        <h1>Create New Article</h1>
        <p>Write a new troubleshooting guide or knowledge base article</p>
      </div>

      <div class="card" style="max-width:900px;margin:0 auto;">
        <form @submit.prevent="submit">

          <div class="form-section">
            <h3 class="section-title">Article Details</h3>

            <div class="form-group">
              <label>Title *</label>
              <input v-model="form.title" type="text" placeholder="e.g., Computer won't turn on - Troubleshooting steps" required>
              <p class="help-text">Choose a clear, descriptive title that users can easily search for</p>
              <span v-if="form.errors.title" class="text-danger">{{ form.errors.title }}</span>
            </div>

            <div class="form-group">
              <label>Category *</label>
              <select v-model="form.category" required>
                <option value="">Select a category</option>
                <option value="hardware">Hardware</option>
                <option value="software">Software</option>
                <option value="network">Network</option>
                <option value="display">Display</option>
                <option value="peripherals">Peripherals</option>
                <option value="general">General Help</option>
              </select>
              <span v-if="form.errors.category" class="text-danger">{{ form.errors.category }}</span>
            </div>

            <div class="form-group">
              <label>Excerpt (Optional)</label>
              <textarea v-model="form.excerpt" rows="3" placeholder="Brief summary of the article (max 500 characters)"></textarea>
              <p class="help-text">If left empty, we'll automatically generate one from your content</p>
              <span v-if="form.errors.excerpt" class="text-danger">{{ form.errors.excerpt }}</span>
            </div>

            <div class="form-group">
              <label>Content *</label>
              <textarea v-model="form.content" rows="15" placeholder="Write your article content here... Include step-by-step instructions, troubleshooting tips, and solutions." required></textarea>
              <p class="help-text">Minimum 50 characters. Use clear language and provide detailed steps.</p>
              <span v-if="form.errors.content" class="text-danger">{{ form.errors.content }}</span>
            </div>

            <div class="form-group">
              <label>Status *</label>
              <select v-model="form.status" required>
                <option value="draft">Save as Draft</option>
                <option value="published">Publish Immediately</option>
              </select>
              <p class="help-text">Drafts are only visible to IT staff. Published articles are visible to all users.</p>
              <span v-if="form.errors.status" class="text-danger">{{ form.errors.status }}</span>
            </div>
          </div>

          <div class="action-buttons">
            <button type="submit" class="btn btn-primary" :disabled="form.processing">
              {{ form.processing ? 'Creating…' : 'Create Article' }}
            </button>
            <Link href="/it/knowledge-base" class="btn btn-secondary">Cancel</Link>
          </div>

        </form>
      </div>
    </div>
  </AppLayout>
</template>