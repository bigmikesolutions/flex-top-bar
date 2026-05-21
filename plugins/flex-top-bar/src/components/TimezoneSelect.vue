<template>
  <select
    :id="selectId"
    v-model="model"
    class="top-bar-life-time-datetime top-bar-timezone-select"
    @change="emit('change')"
  >
    <option v-for="option in timezoneOptions" :key="option.value" :value="option.value">
      {{ option.label }}
    </option>
  </select>
</template>

<script setup lang="ts">
import { computed, watch } from 'vue'
import { buildTimezoneOptions, getBrowserTimezone, normalizeTimezoneId } from '@/utils/scheduleDateTime'

const props = withDefaults(
  defineProps<{
    selectId?: string
  }>(),
  {
    selectId: '',
  },
)

const model = defineModel<string>({ required: true })

const emit = defineEmits<{
  change: []
}>()

const timezoneOptions = computed(() => buildTimezoneOptions(model.value))

watch(
  () => model.value,
  (value) => {
    const normalized = normalizeTimezoneId(value)
    const resolved = normalized || getBrowserTimezone()
    if (resolved !== value) {
      model.value = resolved
      emit('change')
    }
  },
  { immediate: true },
)
</script>
