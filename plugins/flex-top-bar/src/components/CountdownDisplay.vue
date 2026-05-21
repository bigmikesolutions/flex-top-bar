<template>
  <div
    class="top-bar-countdown-column"
    :class="[
      `top-bar-countdown-column--${column.counter_style}`,
      `top-bar-countdown-column--text-${column.text_position}`,
    ]"
    :style="columnStyle"
  >
    <span v-if="column.text" class="top-bar-countdown-column__text">{{ column.text }}</span>
    <div class="top-bar-countdown-column__timer" aria-live="polite">
      <template v-if="column.counter_style === 'plain'">
        <span class="top-bar-countdown-column__plain">{{ plainLabel }}</span>
      </template>
      <template v-else>
        <div
          v-for="unit in boxedUnits"
          :key="unit.label"
          class="top-bar-countdown-column__unit"
        >
          <span class="top-bar-countdown-column__digits">
            <span
              v-for="(digit, digitIndex) in unit.digits"
              :key="`${unit.label}-${digitIndex}`"
              class="top-bar-countdown-column__digit"
            >{{ digit }}</span>
          </span>
          <span class="top-bar-countdown-column__unit-label">{{ unit.label }}</span>
        </div>
      </template>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue'
import type { CountdownBarColumn } from '@/types'
import {
  digitChars,
  formatPlainCountdown,
  getCountdownRemainingMs,
  splitCountdownMs,
} from '@/utils/countdown'

const props = withDefaults(
  defineProps<{
    column: CountdownBarColumn
    /** Fixed parts for admin style preview (skips live ticker). */
    previewParts?: { days: number; hours: number; minutes: number; seconds: number }
  }>(),
  { previewParts: undefined },
)

const nowMs = ref(Date.now())
let timerId: ReturnType<typeof setInterval> | undefined

const parts = computed(() => {
  if (props.previewParts) {
    return props.previewParts
  }
  const remaining = getCountdownRemainingMs(
    props.column.count_direction,
    props.column.countdown_to_datetime,
    props.column.countup_from_datetime,
    props.column.countdown_timezone,
    nowMs.value,
  )
  return splitCountdownMs(remaining)
})

const plainLabel = computed(() => formatPlainCountdown(parts.value))

const boxedUnits = computed(() => [
  { label: 'd', digits: digitChars(parts.value.days, 2) },
  { label: 'h', digits: digitChars(parts.value.hours, 2) },
  { label: 'm', digits: digitChars(parts.value.minutes, 2) },
  { label: 's', digits: digitChars(parts.value.seconds, 2) },
])

const columnStyle = computed(() => {
  const style: Record<string, string> = {
    '--top-bar-countdown-text': props.column.text_color,
    '--top-bar-countdown-counter': props.column.counter_color,
  }
  if (props.column.counter_style === 'boxed') {
    style['--top-bar-countdown-bg'] = props.column.background_color
  }
  return style
})

onMounted(() => {
  if (props.previewParts) {
    return
  }
  timerId = setInterval(() => {
    nowMs.value = Date.now()
  }, 1000)
})

onUnmounted(() => {
  if (timerId !== undefined) {
    clearInterval(timerId)
  }
})
</script>
