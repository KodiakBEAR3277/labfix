<script setup>
// Components/VoiceMessage.vue
// Path: resources/js/Components/VoiceMessage.vue
//
// Messenger-style voice note player with a YouTube-like draggable scrubber.
// Wraps the browser's native <audio> element — the recording itself was made
// on mobile, so this is playback-only.
//
// props:
//   src      — relative storage path, e.g. "voice-notes/abc.m4a" (parent
//              builds it as `/storage/${path}`)
//   duration — seconds stored with the note; used for the total until the
//              real metadata loads (and as a fallback if it never does)
//
// Only one note plays at a time. Each instance fires a `labfix:voice-play`
// DOM event on play; every other instance hears it and pauses itself.

import { ref, computed, onMounted, onBeforeUnmount } from 'vue'

const props = defineProps({
  src:      { type: String, required: true },
  duration: { type: Number, default: 0 },
})

const audioEl   = ref(null)
const playing   = ref(false)
const current   = ref(0)
const metaDur   = ref(0)
const scrubbing = ref(false)

const instanceId = Math.random().toString(36).slice(2)

const total = computed(() => {
  const d = metaDur.value || props.duration || 0
  return Number.isFinite(d) && d > 0 ? d : 0
})
const progress = computed(() =>
  total.value > 0 ? Math.min(current.value / total.value, 1) : 0
)

// Deterministic decorative waveform — avoids decoding audio just to draw it.
const BARS = 28
const PATTERN = [8, 14, 20, 11, 24, 16, 9, 19, 26, 13, 10, 22, 15, 18, 12, 25, 9, 17, 21, 14, 11, 23, 16, 12]
const barHeight = (i) => PATTERN[i % PATTERN.length]

function fmt(seconds) {
  if (!Number.isFinite(seconds) || seconds < 0) seconds = 0
  const m = Math.floor(seconds / 60)
  const s = Math.floor(seconds % 60)
  return `${m}:${String(s).padStart(2, '0')}`
}

function toggle() {
  const el = audioEl.value
  if (!el) return
  if (el.paused) {
    document.dispatchEvent(new CustomEvent('labfix:voice-play', { detail: { id: instanceId } }))
    el.play().catch(() => {})
  } else {
    el.pause()
  }
}

function onPlay()  { playing.value = true }
function onPause() { playing.value = false }
function onTime()  { if (!scrubbing.value) current.value = audioEl.value?.currentTime ?? 0 }
function onMeta()  { metaDur.value = audioEl.value?.duration ?? 0 }
function onEnded() {
  playing.value = false
  current.value = 0
  if (audioEl.value) audioEl.value.currentTime = 0
}

function onOtherPlay(e) {
  if (e.detail?.id !== instanceId) audioEl.value?.pause()
}

// ── Draggable scrubber ────────────────────────────────────────────────────────
function seekToClientX(clientX) {
  const track = document.getElementById(`vm-track-${instanceId}`)
  const el = audioEl.value
  if (!track || !el || !total.value) return
  const rect = track.getBoundingClientRect()
  const ratio = Math.max(0, Math.min(1, (clientX - rect.left) / rect.width))
  el.currentTime = ratio * total.value
  current.value = el.currentTime
}

function onPointerDown(e) {
  scrubbing.value = true
  seekToClientX(e.clientX)
  window.addEventListener('pointermove', onPointerMove)
  window.addEventListener('pointerup', onPointerUp)
}
function onPointerMove(e) {
  if (scrubbing.value) seekToClientX(e.clientX)
}
function onPointerUp() {
  scrubbing.value = false
  window.removeEventListener('pointermove', onPointerMove)
  window.removeEventListener('pointerup', onPointerUp)
}

onMounted(() => document.addEventListener('labfix:voice-play', onOtherPlay))
onBeforeUnmount(() => {
  document.removeEventListener('labfix:voice-play', onOtherPlay)
  window.removeEventListener('pointermove', onPointerMove)
  window.removeEventListener('pointerup', onPointerUp)
})
</script>

<template>
  <div class="voice-message">
    <button
      type="button"
      class="vm-play"
      :aria-label="playing ? 'Pause voice note' : 'Play voice note'"
      @click="toggle"
    >
      <svg
        v-if="playing"
        class="vm-icon"
        viewBox="0 0 24 24"
        width="14"
        height="14"
        fill="currentColor"
        aria-hidden="true"
      >
        <path d="M6 5h4v14H6zM14 5h4v14h-4z" />
      </svg>
      <svg
        v-else
        class="vm-icon"
        viewBox="0 0 24 24"
        width="14"
        height="14"
        fill="currentColor"
        aria-hidden="true"
      >
        <path d="M8 5v14l11-7z" />
      </svg>
    </button>

    <div class="vm-body">
      <div
        :id="`vm-track-${instanceId}`"
        class="vm-track"
        @pointerdown="onPointerDown"
      >
        <div class="vm-bars">
          <span
            v-for="i in BARS"
            :key="i"
            class="vm-bar"
            :class="{ 'vm-bar--filled': (i - 1) / BARS < progress }"
            :style="{ height: `${barHeight(i - 1)}px` }"
          ></span>
        </div>
        <div class="vm-thumb" :style="{ left: `calc(${progress * 100}% - 6px)` }"></div>
      </div>

      <div class="vm-time">{{ fmt(current) }} / {{ fmt(total) }}</div>
    </div>

    <audio
      ref="audioEl"
      :src="src"
      preload="metadata"
      @play="onPlay"
      @pause="onPause"
      @timeupdate="onTime"
      @loadedmetadata="onMeta"
      @durationchange="onMeta"
      @ended="onEnded"
    ></audio>
  </div>
</template>
