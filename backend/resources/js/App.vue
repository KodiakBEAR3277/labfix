<script setup>
// App.vue — the permanent shell.
// It stays this simple no matter how many pages are added.
// Pages/layouts are passed in as the default slot from wherever
// this is mounted. Once Vue Router is added later, <RouterView />
// replaces the <slot /> here and nothing else changes.
</script>

<template>
  <div class="app-shell">
    <slot />
  </div>
</template>

<style>
/*
  Global styles that apply across ALL Vue pages go here.
  Right now your existing CSS covers this via the import in app.js,
  so this block stays minimal. Add here only if you find something
  that truly needs to be global and isn't already in main.css.
*/
.app-shell {
  min-height: 100vh;
  background-color: var(--color-bg-primary);
  color: var(--color-text-primary);
  font-family: var(--font-family-base);
}
</style>