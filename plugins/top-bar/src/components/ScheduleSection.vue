<template>
  <template v-if="scheduleEnabled">
    <div class="top-bar-grid title">
      <div class="item">
        <label
          class="check top-bar-life-time-checkbox"
          :title="sectionTooltip || undefined"
          :aria-label="sectionTooltip || undefined"
        >
          <input
            v-model="model.scheduled_enabled"
            type="checkbox"
            class="top-bar-toggle-life-time"
            @change="onScheduledToggle"
          />
          <span class="lifetime-label">
            <p class="bold lg">{{ __('Scheduled', 'top-bar') }}</p>
          </span>
          <span class="lifetime-description">
            <p class="xs">{{ __('Schedule when the bar should be visible.', 'top-bar') }}</p>
          </span>
        </label>
      </div>
    </div>

    <div
      v-if="model.scheduled_enabled"
      class="top-bar-grid bg bg-amber top-bar-lifetime-panel"
    >
      <div class="item">
        <fieldset class="clear">
          <legend class="bold">{{ __('From', 'top-bar') }}</legend>
          <label>
            <input
              :id="`scheduled_from_${model.id}`"
              v-model="model.scheduled_from_datetime"
              type="datetime-local"
              class="top-bar-life-time-datetime"
              @click="openPicker"
              @blur="emit('save')"
            />
          </label>
        </fieldset>
      </div>
      <div class="item">
        <fieldset class="clear">
          <legend class="bold">{{ __('To', 'top-bar') }}</legend>
          <label>
            <input
              :id="`scheduled_to_${model.id}`"
              v-model="model.scheduled_to_datetime"
              type="datetime-local"
              class="top-bar-life-time-datetime"
              @click="openPicker"
              @blur="emit('save')"
            />
          </label>
        </fieldset>
      </div>
    </div>
  </template>
  <template v-else>
    <div class="top-bar-grid title">
      <div class="item">
        <label
          class="check top-bar-life-time-checkbox top-bar-schedule--disabled"
          :title="sectionTooltip || undefined"
          :aria-label="sectionTooltip || undefined"
        >
          <input type="checkbox" disabled class="top-bar-toggle-life-time" />
          <span class="lifetime-label">
            <p class="bold lg">{{ __('Scheduled', 'top-bar') }}</p>
          </span>
          <span class="lifetime-description">
            <p class="xs">{{ __('Schedule when the bar should be visible.', 'top-bar') }}</p>
          </span>
        </label>
      </div>
    </div>
  </template>
</template>

<script setup lang="ts">
import type { Bar } from '@/types'
import { __ } from '@wordpress/i18n'

withDefaults(
  defineProps<{
    scheduleEnabled: boolean
    /** Plan / limits hint (native tooltip on the Scheduled row). */
    sectionTooltip?: string
  }>(),
  { sectionTooltip: '' },
)

const model = defineModel<Bar>({ required: true })

const emit = defineEmits<{
  save: []
}>()

function onScheduledToggle() {
  // When disabling scheduling, clear any dates before saving. Otherwise the backend
  // normalization will re-enable scheduling if dates are still present.
  if (!model.value.scheduled_enabled) {
    model.value.scheduled_from_datetime = ''
    model.value.scheduled_to_datetime = ''
  }
  emit('save')
}

function openPicker(e: Event) {
  const input = e.target as HTMLInputElement
  // Improves UX in browsers that require clicking the calendar icon.
  input.showPicker?.()
}
</script>

<style scoped>
.top-bar-schedule--disabled {
  opacity: 0.65;
  cursor: not-allowed;
}
</style>
