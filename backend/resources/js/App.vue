<script setup>
// App.vue — the permanent shell.
// <RouterView /> renders whichever Page component matches the current URL.
// Adding new pages never touches this file — just add the route to router/index.js.
</script>

<template>
  <div class="app-shell">
    <RouterView />
  </div>
</template>

<style>
.app-shell {
  min-height: 100vh;
  background-color: var(--color-bg-primary);
  color: var(--color-text-primary);
  font-family: var(--font-family-base);
}
</style>