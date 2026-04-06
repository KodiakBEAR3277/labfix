<script setup>
// Pages/IT/KnowledgeBase/Edit.vue
// Path: resources/js/Pages/IT/KnowledgeBase/Edit.vue
// Mirrors: resources/views/it/articles/edit.blade.php

import AppLayout from '../../../Layouts/AppLayout.vue'
import NavIT from '../../../Components/Nav/NavIT.vue'
import { Link, useForm } from '@inertiajs/vue3'

const props = defineProps({
  article: Object,
})

const form = useForm({
  _method:  'PUT',
  title:    props.article.title    ?? '',
  category: props.article.category ?? '',
  excerpt:  props.article.excerpt  ?? '',
  content:  props.article.content  ?? '',
  status:   props.article.status   ?? 'draft',
})

function submit() {
  form.post(`/it/knowledge-base/${props.article.id}`)
}
</script>

<template>
  <AppLayout>
    <template #nav><NavIT /></template>

    <div class="container">
      <Link href="/it/knowledge-base" class="back-btn">← Back to Knowledge Base</Link>

      <div class="page-header">
        <h1>Edit Article</h1>
        <p>Update your knowledge base article</p>
      </div>

      <div class="card" style="max-width:900px;margin:0 auto;">
        <form @submit.prevent="submit">

          <div class="form-section">
            <h3 class="section-title">Article Details</h3>

            <div class="form-group">
              <label>Title *</label>
              <input v-model="form.title" type="text" required>
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
              <textarea v-model="form.excerpt" rows="3"></textarea>
              <span v-if="form.errors.excerpt" class="text-danger">{{ form.errors.excerpt }}</span>
            </div>

            <div class="form-group">
              <label>Content *</label>
              <textarea v-model="form.content" rows="15" required></textarea>
              <span v-if="form.errors.content" class="text-danger">{{ form.errors.content }}</span>
            </div>

            <div class="form-group">
              <label>Status *</label>
              <select v-model="form.status" required>
                <option value="draft">Draft</option>
                <option value="published">Published</option>
              </select>
              <span v-if="form.errors.status" class="text-danger">{{ form.errors.status }}</span>
            </div>
          </div>

          <div class="action-buttons">
            <button type="submit" class="btn btn-primary" :disabled="form.processing">
              {{ form.processing ? 'Saving…' : 'Update Article' }}
            </button>
            <Link href="/it/knowledge-base" class="btn btn-secondary">Cancel</Link>
          </div>

        </form>
      </div>
    </div>
  </AppLayout>
</template>