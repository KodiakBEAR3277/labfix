<template>
  <span class="status-badge" :class="`status-${status}`">
    {{ label }}
  </span>
</template>

<script setup>
defineProps({
  status: {
    type: String,
    default: 'default',
    validator: (value) => ['default', 'success', 'warning', 'error', 'info'].includes(value)
  },
  label: {
    type: String,
    required: true
  }
});
</script>

<style scoped>
.status-badge {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.875rem;
  font-weight: 500;
}

.status-default {
  background-color: #f3f4f6;
  color: #374151;
}

.status-success {
  background-color: #dcfce7;
  color: #15803d;
}

.status-warning {
  background-color: #fef3c7;
  color: #b45309;
}

.status-error {
  background-color: #fee2e2;
  color: #991b1b;
}

.status-info {
  background-color: #dbeafe;
  color: #0c4a6e;
}
</style>
