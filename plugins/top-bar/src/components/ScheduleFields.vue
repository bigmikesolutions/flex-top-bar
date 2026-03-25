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
          <p class="bold lg">{{ __('Scheduled', 'top-bar') }}</p>
          <p class="xs">{{ __('Schedule when the bar should be visible', 'top-bar') }}</p>
        </span>
      </label>
    </div>
  </div>

  <div v-if="localEnabled" class="top-bar-lifetime-panel">
    <div class="top-bar-grid">
      <div class="item">
        <label :for="`scheduled_from_${barId}`">
          <p class="bold">{{ __('From', 'top-bar') }}</p>
          <p class="xs">{{ __('Start date and time', 'top-bar') }}</p>
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
          <p class="bold">{{ __('To', 'top-bar') }}</p>
          <p class="xs">{{ __('End date and time', 'top-bar') }}</p>
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

const props = defineProps<{
  enabled: boolean
  from: string
  to: string
  barId: string
}>()

const emit = defineEmits<{
  'update:enabled': [value: boolean]
  'update:from': [value: string]
  'update:to': [value: string]
  update: []
}>()

const localEnabled = ref(props.enabled)
const localFrom = ref(convertToDatetimeLocal(props.from))
const localTo = ref(convertToDatetimeLocal(props.to))

watch(() => props.enabled, (newVal) => {
  localEnabled.value = newVal
})

watch(() => props.from, (newVal) => {
  localFrom.value = convertToDatetimeLocal(newVal)
})

watch(() => props.to, (newVal) => {
  localTo.value = convertToDatetimeLocal(newVal)
})

const validationError = computed(() => {
  if (!localEnabled.value) return ''

  if (!localFrom.value || !localTo.value) {
    return __('Both start and end dates are required', 'top-bar')
  }

  const from = new Date(localFrom.value)
  const to = new Date(localTo.value)

  if (to <= from) {
    return __('End date must be after start date', 'top-bar')
  }

  return ''
})

function convertToDatetimeLocal(isoString: string): string {
  if (!isoString) return ''

  // Convert from ISO 8601 format (YYYY-MM-DDTHH:mm) to datetime-local format
  // ISO format from backend: "2026-03-25T10:30"
  // datetime-local format: "2026-03-25T10:30"
  // They're the same, but let's ensure it's properly formatted
  return isoString.slice(0, 16)
}

function convertFromDatetimeLocal(datetimeLocal: string): string {
  if (!datetimeLocal) return ''

  // Convert to ISO 8601 format for backend
  // datetime-local format: "2026-03-25T10:30"
  // Backend expects: "2026-03-25T10:30"
  return datetimeLocal.slice(0, 16)
}

function handleEnabledChange() {
  emit('update:enabled', localEnabled.value)
  emit('update')
}

function handleUpdate() {
  if (validationError.value) {
    return
  }

  emit('update:from', convertFromDatetimeLocal(localFrom.value))
  emit('update:to', convertFromDatetimeLocal(localTo.value))
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
