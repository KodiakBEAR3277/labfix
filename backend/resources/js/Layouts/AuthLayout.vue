<template>
  <div class="auth-layout">
    <main class="auth-content">
      <slot />
    </main>
  </div>
</template>

<script setup>
</script>

<style scoped>
.auth-layout {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  background-color: #f3f4f6;
}

.auth-content {
  width: 100%;
  max-width: 400px;
}
</style>
