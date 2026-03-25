<template>
  <template v-if="scheduleEnabled">
    <div class="top-bar-grid title">
      <div class="item">
        <label class="check top-bar-life-time-checkbox">
          <input
            v-model="model.scheduled_enabled"
            type="checkbox"
            class="top-bar-toggle-life-time"
            @change="emit('save')"
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
              @blur="emit('save')"
            />
          </label>
        </fieldset>
      </div>
    </div>
  </template>
</template>

<script setup lang="ts">
import type { Bar } from '@/types'
import { __ } from '@wordpress/i18n'

defineProps<{
  scheduleEnabled: boolean
}>()

const model = defineModel<Bar>({ required: true })

const emit = defineEmits<{
  save: []
}>()
</script>
