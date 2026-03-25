<template>
  <div v-if="visibleBars.length > 0" class="top-bar-container">
    <div
      v-for="bar in visibleBars"
      :key="bar.id"
      :id="`top-bar-${bar.id}`"
      :class="['top-bar', `top-bar--${bar.position}`, { 'top-bar--messages-mobile-hidden': !bar.messages_mobile_visible }]"
      :style="getBarStyles(bar)"
      role="banner"
      :data-top-bar-id="bar.id"
      :data-top-bar-position="bar.position"
      :data-top-bar-effect="bar.effect"
      :data-top-bar-hide-on-scroll="bar.hide_on_scroll ? '1' : '0'"
    >
      <div class="top-bar__inner">
        <template v-if="bar.effect === 'none'">
          {{ getConcatenatedMessage(bar) }}
        </template>
        <template v-else>
          <transition :name="getTransitionName(bar.effect)" mode="out-in">
            <div :key="currentMessageIndex[bar.id] || 0">
              {{ getCurrentMessage(bar) }}
            </div>
          </transition>
        </template>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import type { Bar } from '@/types'

const bars = ref<Bar[]>([])
const currentMessageIndex = ref<Record<string, number>>({})
const intervals = ref<Record<string, number>>({})
const lastScrollY = ref(0)
const hideScrollBars = ref<Set<string>>(new Set())

// Fetch bars from public API endpoint
async function fetchBars() {
  try {
    // Use public endpoint (no auth required for frontend)
    // Using query string format for compatibility with sites without pretty permalinks
    const response = await fetch('/?rest_route=/top-bar/v1/public-bars')
    if (!response.ok) {
      throw new Error('Failed to fetch bars')
    }
    bars.value = await response.json()

    // Initialize message rotation for bars with effects
    bars.value.forEach(bar => {
      if (bar.effect !== 'none' && bar.messages.length > 1) {
        currentMessageIndex.value[bar.id] = 0
        startMessageRotation(bar)
      }
    })
  } catch (error) {
    console.error('Failed to load top bars:', error)
  }
}

// Filter bars by visibility and scheduling
const visibleBars = computed(() => {
  return bars.value.filter(bar => {
    // Check if visible
    if (!bar.visible) return false

    // Check scheduling
    if (bar.scheduled_enabled && bar.scheduled_from_datetime && bar.scheduled_to_datetime) {
      const now = new Date()
      const from = new Date(bar.scheduled_from_datetime)
      const to = new Date(bar.scheduled_to_datetime)

      if (now < from || now > to) {
        return false
      }
    }

    // Check hide on scroll
    if (bar.hide_on_scroll && hideScrollBars.value.has(bar.id)) {
      return false
    }

    return true
  })
})

function getBarStyles(bar: Bar) {
  const styles: Record<string, string> = {
    backgroundColor: bar.bg_color || '#1d2327',
  }

  if (bar.frame_width > 0 && bar.frame_color) {
    styles.border = `${bar.frame_width}px solid ${bar.frame_color}`
  }

  return styles
}

function getConcatenatedMessage(bar: Bar): string {
  return bar.messages.filter(m => m.trim()).join(' ')
}

function getCurrentMessage(bar: Bar): string {
  const index = currentMessageIndex.value[bar.id] || 0
  return bar.messages[index] || ''
}

function getTransitionName(effect: string): string {
  if (effect === 'slider') return 'slide'
  if (effect === 'fadein') return 'fade'
  if (effect === 'blink') return 'blink'
  return 'fade'
}

function startMessageRotation(bar: Bar) {
  const duration = 5000 // 5 seconds per message

  intervals.value[bar.id] = window.setInterval(() => {
    const current = currentMessageIndex.value[bar.id] || 0
    currentMessageIndex.value[bar.id] = (current + 1) % bar.messages.length
  }, duration)
}

function stopMessageRotation(barId: string) {
  if (intervals.value[barId]) {
    clearInterval(intervals.value[barId])
    delete intervals.value[barId]
  }
}

// Handle scroll hide behavior
function handleScroll() {
  const currentScrollY = window.scrollY
  const threshold = 30

  bars.value.forEach(bar => {
    if (bar.hide_on_scroll) {
      if (currentScrollY > threshold) {
        hideScrollBars.value.add(bar.id)
      } else {
        hideScrollBars.value.delete(bar.id)
      }
    }
  })

  lastScrollY.value = currentScrollY
}

onMounted(() => {
  fetchBars()
  window.addEventListener('scroll', handleScroll, { passive: true })
})

onUnmounted(() => {
  // Clean up intervals
  Object.keys(intervals.value).forEach(stopMessageRotation)
  window.removeEventListener('scroll', handleScroll)
})
</script>

<style scoped>
.top-bar-container {
  position: relative;
  z-index: 99998;
}

.top-bar {
  width: 100%;
  padding: 12px 20px;
  box-sizing: border-box;
  transition: transform 0.3s ease;
  z-index: 99998;
}

.top-bar--top {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
}

/* Adjust for WordPress admin bar (32px on desktop, 46px on mobile) */
body.admin-bar .top-bar--top {
  top: 32px;
}

@media screen and (max-width: 782px) {
  body.admin-bar .top-bar--top {
    top: 46px;
  }
}

.top-bar--bottom {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
}

.top-bar__inner {
  max-width: 1200px;
  margin: 0 auto;
  text-align: center;
  color: #fff;
  font-size: 16px;
  line-height: 1.5;
}

/* Transitions for effects */
.slide-enter-active,
.slide-leave-active {
  transition: transform 0.5s ease;
}

.slide-enter-from {
  transform: translateX(100%);
}

.slide-leave-to {
  transform: translateX(-100%);
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.5s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.blink-enter-active,
.blink-leave-active {
  transition: opacity 0.3s ease;
}

.blink-enter-from,
.blink-leave-to {
  opacity: 0;
}

/* Mobile visibility */
@media screen and (max-width: 782px) {
  .top-bar--messages-mobile-hidden {
    display: none !important;
  }
}
</style>
