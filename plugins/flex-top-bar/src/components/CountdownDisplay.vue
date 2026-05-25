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
  { label: 'days', digits: digitChars(parts.value.days, 2) },
  { label: 'hours', digits: digitChars(parts.value.hours, 2) },
  { label: 'minutes', digits: digitChars(parts.value.minutes, 2) },
  { label: 'seconds', digits: digitChars(parts.value.seconds, 2) },
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

<style scoped>

  .top-bar-countdown-column {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    width: 100%;
    gap: 10px;
    flex-wrap: wrap;
    text-align: center;
    color: var(--top-bar-countdown-text, inherit);
  }

  .top-bar-countdown-column--text-after {
    flex-direction: column-reverse;
  }



  .top-bar-countdown-column--text-after .top-bar-countdown-column__unit {
    flex-direction: column-reverse;
  }


  .top-bar-countdown-column__text {
    color: var(--top-bar-countdown-text, inherit);
    font-weight: 400;
    line-height: 100%;
    white-space: nowrap;
  }

  .top-bar-countdown-column__timer {
    display: flex;
    flex-direction: row;
    align-items: center;
    gap: 8px;
    flex-wrap: nowrap;
    justify-content: center;
  }

  .top-bar-countdown-column__plain {
    color: var(--top-bar-countdown-counter, inherit);
    font-weight: 600;
    font-variant-numeric: tabular-nums;
    letter-spacing: 1px;
    white-space: nowrap;
  }

  .top-bar-countdown-column__unit {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2px;
    flex-shrink: 0;
  }

  .top-bar-countdown-column__digits {
    display: flex;
    flex-direction: row;
    align-items: center;
    gap: 3px;
  }

  .top-bar-countdown-column__digit {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 8px 9px;
    border-radius: 4px;
    background: var(--top-bar-countdown-bg, #1d2327);
    color: var(--top-bar-countdown-counter, #fff);
    font-weight: 700;
    font-variant-numeric: tabular-nums;
    line-height: 1.2;
    position: relative;
  }

  .top-bar-countdown-column__digit:before {
    content:'';
    width: 100%;
    height: 1px;
    background: rgba(255,255,255,0.1);
    position: absolute;
    top:50%;
    margin-top: -1px;
  }

  .top-bar-countdown-column__unit-label {
    font-size: 8px;
    text-transform: uppercase;
    font-weight: 400;
    color: var(--top-bar-countdown-text, inherit);
    line-height: 1;
  }

</style>
