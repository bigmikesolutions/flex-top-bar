<template>
  <div v-if="visibleBars.length > 0" class="top-bar-container">
    <div
      v-for="bar in visibleBars"
      :key="bar.id"
      :id="`top-bar-${bar.id}`"
      :class="['top-bar', `top-bar--${bar.position}`]"
      :style="getBarStyles(bar)"
      role="banner"
      :data-top-bar-id="bar.id"
      :data-top-bar-position="bar.position"
      :data-top-bar-effect="getColumns(bar)[0]?.effect ?? bar.effect"
      :data-top-bar-hide-on-scroll="bar.hide_on_scroll ? '1' : '0'"
    >
      <div class="top-bar__inner">
        <div class="top-bar__columns">
          <div
            v-for="column in getColumns(bar)"
            :key="column.id"
            class="top-bar__column"
            :class="{ 'top-bar__column--mobile-hidden': !column.messages_mobile_visible }"
            :style="getColumnStyle(column)"
          >
            <template v-if="column.effect === 'none'">
              {{ getConcatenatedMessage(column) }}
            </template>
            <template v-else>
              <transition :name="getTransitionName(column.effect)" mode="out-in">
                <div :key="currentMessageIndex[columnKey(bar.id, column.id)] ?? 0">
                  {{ getCurrentMessage(bar, column) }}
                </div>
              </transition>
            </template>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import type { Bar, BarColumn } from '@/types'

const bars = ref<Bar[]>([])
const currentMessageIndex = ref<Record<string, number>>({})
const intervals = ref<Record<string, number>>({})
const lastScrollY = ref(0)
const hideScrollBars = ref<Set<string>>(new Set())

function getColumns(bar: Bar): BarColumn[] {
  if (bar.columns?.length) {
    return bar.columns
  }
  return [
    {
      id: `${bar.id}-legacy`,
      type: 'text',
      effect: bar.effect,
      messages: bar.messages,
      size_percent: 100,
      messages_mobile_visible: bar.messages_mobile_visible,
    },
  ]
}

function columnKey(barId: string, columnId: string): string {
  return `${barId}:${columnId}`
}

function getColumnStyle(column: BarColumn) {
  return {
    flex: `0 0 ${column.size_percent}%`,
    maxWidth: `${column.size_percent}%`,
  }
}

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

    // Initialize message rotation for columns with effects
    bars.value.forEach(bar => {
      getColumns(bar).forEach(column => {
        if (column.effect !== 'none' && column.messages.length > 1) {
          const key = columnKey(bar.id, column.id)
          currentMessageIndex.value[key] = 0
          startMessageRotation(bar, column)
        }
      })
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

function getConcatenatedMessage(column: BarColumn): string {
  return column.messages.filter(m => m.trim()).join(' ')
}

function getCurrentMessage(bar: Bar, column: BarColumn): string {
  const key = columnKey(bar.id, column.id)
  const index = currentMessageIndex.value[key] || 0
  return column.messages[index] || ''
}

function getTransitionName(effect: string): string {
  if (effect === 'slider') return 'slide'
  if (effect === 'fadein') return 'fade'
  if (effect === 'blink') return 'blink'
  return 'fade'
}

function startMessageRotation(bar: Bar, column: BarColumn) {
  const duration = 5000 // 5 seconds per message
  const key = columnKey(bar.id, column.id)

  intervals.value[key] = window.setInterval(() => {
    const current = currentMessageIndex.value[key] || 0
    currentMessageIndex.value[key] = (current + 1) % column.messages.length
  }, duration)
}

function stopMessageRotation(key: string) {
  if (intervals.value[key]) {
    clearInterval(intervals.value[key])
    delete intervals.value[key]
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
  color: #fff;
  font-size: 16px;
  line-height: 1.5;
}

.top-bar__columns {
  display: flex;
  flex-wrap: wrap;
  width: 100%;
  justify-content: center;
  align-items: center;
  gap: 0;
  text-align: center;
}

.top-bar__column {
  box-sizing: border-box;
  padding: 0 8px;
  min-width: 0;
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

/* Mobile visibility per column */
@media screen and (max-width: 782px) {
  .top-bar__column--mobile-hidden {
    display: none !important;
  }
}
</style>
