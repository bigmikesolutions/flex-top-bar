<template>
  <div class="item-creator">
    <fieldset>
      <legend class="bold">{{ __('Type', 'flex-top-bar') }}</legend>
      <label v-for="opt in options" :key="opt.value" class="top-bar-column-type-option radio">
        <input
          type="radio"
          :name="`column_type_${groupName}`"
          :value="opt.value"
          :checked="columnType === opt.value"
          @change="onSelect(opt.value)"
        />
        <span>{{ opt.label }}</span>     
       </label>
    </fieldset>
  </div>
</template>

<script setup lang="ts">
import { __ } from '@wordpress/i18n'
import type { ColumnType } from '@/types'

const props = defineProps<{
  /** Unique per column row for radio name grouping. */
  groupName: string
  columnType: ColumnType
}>()

const emit = defineEmits<{
  'update:columnType': [value: ColumnType]
}>()

const options: { value: ColumnType; label: string }[] = [
  { value: 'text', label: __('Text Editor', 'flex-top-bar') },
  { value: 'icon', label: __('Icon + text', 'flex-top-bar') },
  { value: 'social', label: __('Social media', 'flex-top-bar') },
  { value: 'contact', label: __('Contact data', 'flex-top-bar') },
]

function onSelect(value: ColumnType) {
  if (value !== props.columnType) {
    emit('update:columnType', value)
  }
}
</script>

<style scoped>
.top-bar-column-type-option {
  display: block;
  margin-bottom: 6px;
}
</style>
