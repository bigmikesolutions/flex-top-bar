<template>
  <div class="top-bar-grid title">
    <div class="item">
      <label class="check top-bar-life-time-checkbox">
        <input
          v-model="localEnabled"
          type="checkbox"
          @change="handleEnabledChange"
        />
        <span class="lifetime-label">
          <p class="bold lg">{{ __('Scheduled', 'flex-top-bar') }}</p>
          <p class="xs">{{ __('Schedule when the bar should be visible', 'flex-top-bar') }}</p>
        </span>
      </label>
    </div>
  </div>

  <div v-if="localEnabled" class="top-bar-lifetime-panel">
    <div class="top-bar-grid">
      <div class="item">
        <label :for="`scheduled_from_${barId}`">
          <p class="bold">{{ __('From', 'flex-top-bar') }}</p>
          <p class="xs">{{ __('Start date and time', 'flex-top-bar') }}</p>
        </label>
      </div>
      <div class="item">
        <input
          :id="`scheduled_from_${barId}`"
          v-model="localFrom"
          type="datetime-local"
          class="regular-text"
          @blur="handleUpdate"
        />
      </div>
    </div>

    <div class="top-bar-grid">
      <div class="item">
        <label :for="`scheduled_to_${barId}`">
          <p class="bold">{{ __('To', 'flex-top-bar') }}</p>
          <p class="xs">{{ __('End date and time', 'flex-top-bar') }}</p>
        </label>
      </div>
      <div class="item">
        <input
          :id="`scheduled_to_${barId}`"
          v-model="localTo"
          type="datetime-local"
          class="regular-text"
          @blur="handleUpdate"
        />
      </div>
    </div>

    <div class="top-bar-grid">
      <div class="item">
        <label :for="`scheduled_timezone_${barId}`">
          <p class="bold">{{ __('Timezone', 'flex-top-bar') }}</p>
          <p class="xs">{{ __('Schedule times use the selected timezone', 'flex-top-bar') }}</p>
        </label>
      </div>
      <div class="item">
        <TimezoneSelect
          :select-id="`scheduled_timezone_${barId}`"
          v-model="localTimezone"
          @change="handleUpdate"
        />
      </div>
    </div>

    <div v-if="validationError" class="top-bar-grid">
      <div class="item"></div>
      <div class="item">
        <p class="description" style="color: #d63638;">
          {{ validationError }}
        </p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { __ } from '@wordpress/i18n'
import TimezoneSelect from '@/components/TimezoneSelect.vue'
import {
  fromDatetimeLocalValue,
  getDefaultScheduleTimezone,
  resolveScheduleTimezone,
  toDatetimeLocalValue,
  wallClockToTimestamp,
} from '@/utils/scheduleDateTime'

const props = defineProps<{
  enabled: boolean
  from: string
  to: string
  timezone: string
  barId: string
}>()

const emit = defineEmits<{
  'update:enabled': [value: boolean]
  'update:from': [value: string]
  'update:to': [value: string]
  'update:timezone': [value: string]
  update: []
}>()

const localEnabled = ref(props.enabled)
const localFrom = ref(toDatetimeLocalValue(props.from))
const localTo = ref(toDatetimeLocalValue(props.to))
const localTimezone = ref(getDefaultScheduleTimezone(props.timezone))

watch(() => props.enabled, (newVal) => {
  localEnabled.value = newVal
})

watch(() => props.from, (newVal) => {
  localFrom.value = toDatetimeLocalValue(newVal)
})

watch(() => props.to, (newVal) => {
  localTo.value = toDatetimeLocalValue(newVal)
})

watch(() => props.timezone, (newVal) => {
  localTimezone.value = getDefaultScheduleTimezone(newVal)
})

const validationError = computed(() => {
  if (!localEnabled.value) return ''

  if (!localFrom.value || !localTo.value) {
    return __('Both start and end dates are required', 'flex-top-bar')
  }

  const timeZone = resolveScheduleTimezone(localTimezone.value)
  const from = wallClockToTimestamp(fromDatetimeLocalValue(localFrom.value), timeZone)
  const to = wallClockToTimestamp(fromDatetimeLocalValue(localTo.value), timeZone)

  if (to <= from) {
    return __('End date must be after start date', 'flex-top-bar')
  }

  return ''
})

function handleEnabledChange() {
  if (!localEnabled.value) {
    emit('update:timezone', '')
  } else {
    emit('update:timezone', getDefaultScheduleTimezone(localTimezone.value))
  }
  emit('update:enabled', localEnabled.value)
  emit('update')
}

function handleUpdate() {
  if (validationError.value) {
    return
  }

  emit('update:from', fromDatetimeLocalValue(localFrom.value))
  emit('update:to', fromDatetimeLocalValue(localTo.value))
  emit('update:timezone', resolveScheduleTimezone(localTimezone.value))
  emit('update')
}
</script>

<style scoped>
.top-bar-lifetime-panel {
  padding-left: 2em;
  margin-top: 1em;
  border-left: 2px solid #dcdcde;
}

.lifetime-label {
  display: inline-block;
}
</style>
