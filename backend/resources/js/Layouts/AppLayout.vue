<template>
  <div class="app-layout">
    <nav class="app-nav">
      <!-- Navigation for authenticated pages -->
    </nav>
    <main class="app-content">
      <slot />
    </main>
  </div>
</template>

<script setup>
</script>

<style scoped>
.app-layout {
  display: flex;
  min-height: 100vh;
}

.app-nav {
  width: 250px;
  border-right: 1px solid #e5e7eb;
}

.app-content {
  flex: 1;
  overflow-y: auto;
}
</style>
