<script setup>
// Components/Icon.vue
// Path: resources/js/Components/Icon.vue
//
// Inline-SVG icon renderer backed by the shared registry in resources/js/icons.
// Filled style, currentColor by default — set `color` on the parent (e.g. a
// button) and the icon follows. Brand entries (google, facebook, …) carry their
// own per-path fills and ignore currentColor.
//
//   <Icon name="ticket" :size="20" />

import { computed } from 'vue'
import { icons } from '../icons'

const props = defineProps({
  name: { type: String, required: true },
  size: { type: [Number, String], default: null },
})

const icon = computed(() => icons[props.name] ?? icons.help)

// Normalise both registry shapes to a list of { d, fill } paths.
const paths = computed(() => {
  const i = icon.value
  return Array.isArray(i) ? i.map((d) => ({ d })) : i.paths
})

const viewBox = computed(() =>
  Array.isArray(icon.value) ? '0 0 24 24' : (icon.value.viewBox ?? '0 0 24 24')
)

// No size prop -> the SVG is 1em and follows the container's font-size
// (just like the emoji it replaces). An explicit size pins it in px.
const dims = computed(() => {
  if (props.size == null) return null
  const v = typeof props.size === 'number' ? `${props.size}px` : props.size
  return { width: v, height: v }
})
</script>

<template>
  <svg
    class="icon"
    :viewBox="viewBox"
    :style="dims"
    fill="currentColor"
    aria-hidden="true"
    focusable="false"
  >
    <path v-for="(p, i) in paths" :key="i" :d="p.d" :fill="p.fill ?? 'currentColor'" />
  </svg>
</template>

<style scoped>
.icon {
  width: 1em;
  height: 1em;
  display: inline-block;
  vertical-align: -0.125em;
  flex-shrink: 0;
}
</style>
