<template>
  <div class="item-creator grid-3 top-bar-countdown-column-editor">
    <fieldset class="line">
      <legend class="bold">{{ __('Counter style', 'flex-top-bar') }}</legend>
      <label
        v-for="opt in styleOptions"
        :key="opt.value"
        class="radio top-bar-countdown-style-option"
      >
        <input
          type="radio"
          :name="`countdown_style_${barId}_${columnId}`"
          :value="opt.value"
          :checked="column.counter_style === opt.value"
          @change="setCounterStyle(opt.value)"
        />
        <span>{{ opt.label }}</span>
        <CountdownDisplay
          :column="{ ...column, counter_style: opt.value }"
          :preview-parts="previewParts"
        />
      </label>
    </fieldset>

    <fieldset class="line">
      <legend class="bold">{{ __('Count direction', 'flex-top-bar') }}</legend>
      <label class="radio">
        <input
          type="radio"
          :name="`countdown_direction_${barId}_${columnId}`"
          value="down"
          :checked="column.count_direction === 'down'"
          @change="setCountDirection('down')"
        />
        <span>{{ __('Count down until promotion ends', 'flex-top-bar') }}</span>
      </label>
      <label class="radio">
        <input
          type="radio"
          :name="`countdown_direction_${barId}_${columnId}`"
          value="up"
          :checked="column.count_direction === 'up'"
          @change="setCountDirection('up')"
        />
        <span>{{ __('Count up from promotion start', 'flex-top-bar') }}</span>
      </label>
    </fieldset>

    <div class="top-bar-grid bg bg-amber top-bar-lifetime-panel options">
      <div class="item">
        <fieldset class="clear">
          <legend class="bold">{{ __('Count up from (start)', 'flex-top-bar') }}</legend>
          <label>
            <input
              :id="`countup_from_${barId}_${columnId}`"
              type="datetime-local"
              class="top-bar-life-time-datetime"
              :value="column.countup_from_datetime"
              :disabled="column.count_direction !== 'up'"
              @input="onCountupFromInput"
              @click="openPicker"
              @blur="emit('commit')"
            />
          </label>
        </fieldset>
      </div>
      <div class="item">
        <fieldset class="clear">
          <legend class="bold">{{ __('Count down until (end)', 'flex-top-bar') }}</legend>
          <label>
            <input
              :id="`countdown_to_${barId}_${columnId}`"
              type="datetime-local"
              class="top-bar-life-time-datetime"
              :value="column.countdown_to_datetime"
              :disabled="column.count_direction !== 'down'"
              @input="onCountdownToInput"
              @click="openPicker"
              @blur="emit('commit')"
            />
          </label>
        </fieldset>
      </div>
      <div class="item">
        <fieldset class="clear">
          <legend class="bold">{{ __('Timezone', 'flex-top-bar') }}</legend>
          <label :for="`countdown_timezone_${barId}_${columnId}`">
            <TimezoneSelect
              :select-id="`countdown_timezone_${barId}_${columnId}`"
              v-model="timezoneModel"
              @change="emit('commit')"
            />
          </label>
          <p class="xs">
            {{
              __(
                'Counter uses the selected timezone for the dates above (same as bar scheduling).',
                'flex-top-bar',
              )
            }}
          </p>
        </fieldset>
      </div>
    </div>

    <fieldset class="line">
      <legend class="bold">{{ __('Text', 'flex-top-bar') }}</legend>
      <input
        :id="`countdown_text_${barId}_${columnId}`"
        type="text"
        :value="column.text"
        :placeholder="__('Optional label next to the counter', 'flex-top-bar')"
        @input="onTextInput"
        @blur="emit('commit')"
      />
    </fieldset>

    <fieldset class="line">
      <legend class="bold">{{ __('Text position', 'flex-top-bar') }}</legend>
      <label class="radio">
        <input
          type="radio"
          :name="`countdown_text_position_${barId}_${columnId}`"
          value="before"
          :checked="column.text_position === 'before'"
          @change="setTextPosition('before')"
        />
        <span>{{ __('Before counter', 'flex-top-bar') }}</span>
      </label>
      <label class="radio">
        <input
          type="radio"
          :name="`countdown_text_position_${barId}_${columnId}`"
          value="after"
          :checked="column.text_position === 'after'"
          @change="setTextPosition('after')"
        />
        <span>{{ __('After counter', 'flex-top-bar') }}</span>
      </label>
    </fieldset>

    <div class="top-bar-grid options">
      <div v-if="column.counter_style === 'boxed'" class="item">
        <fieldset class="line">
          <legend class="bold">{{ __('Background color', 'flex-top-bar') }}</legend>
          <input
            :id="`countdown_bg_${barId}_${columnId}`"
            type="color"
            :value="column.background_color"
            @input="onBgInput"
            @blur="emit('commit')"
          />
        </fieldset>
      </div>
      <div class="item">
        <fieldset class="line">
          <legend class="bold">{{ __('Counter color', 'flex-top-bar') }}</legend>
          <input
            :id="`countdown_counter_${barId}_${columnId}`"
            type="color"
            :value="column.counter_color"
            @input="onCounterColorInput"
            @blur="emit('commit')"
          />
        </fieldset>
      </div>
      <div class="item">
        <fieldset class="line">
          <legend class="bold">{{ __('Text color', 'flex-top-bar') }}</legend>
          <input
            :id="`countdown_text_color_${barId}_${columnId}`"
            type="color"
            :value="column.text_color"
            @input="onTextColorInput"
            @blur="emit('commit')"
          />
        </fieldset>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { __ } from '@wordpress/i18n'
import CountdownDisplay from '@/components/CountdownDisplay.vue'
import TimezoneSelect from '@/components/TimezoneSelect.vue'
import type { CountdownBarColumn, CountdownDirection, CountdownStyle, CountdownTextPosition } from '@/types'
import { fromDatetimeLocalValue, getDefaultScheduleTimezone } from '@/utils/scheduleDateTime'

const props = defineProps<{
  barId: string
  columnId: string
  column: CountdownBarColumn
}>()

const emit = defineEmits<{
  patch: [updates: Partial<CountdownBarColumn>]
  commit: []
}>()

const previewParts = { days: 1, hours: 2, minutes: 3, seconds: 4 }

const styleOptions: { value: CountdownStyle; label: string }[] = [
  { value: 'plain', label: __('Plain text', 'flex-top-bar') },
  { value: 'boxed', label: __('Digits with background', 'flex-top-bar') },
]

const timezoneModel = computed({
  get: () => props.column.countdown_timezone,
  set: (value: string) => {
    emit('patch', { countdown_timezone: value })
  },
})

onMounted(() => {
  if (!props.column.countdown_timezone.trim()) {
    emit('patch', { countdown_timezone: getDefaultScheduleTimezone() })
  }
})

function patch(updates: Partial<CountdownBarColumn>) {
  emit('patch', updates)
}

function setCounterStyle(style: CountdownStyle) {
  patch({ counter_style: style })
  emit('commit')
}

function setCountDirection(direction: CountdownDirection) {
  patch({ count_direction: direction })
  emit('commit')
}

function setTextPosition(position: CountdownTextPosition) {
  patch({ text_position: position })
  emit('commit')
}

function onCountupFromInput(e: Event) {
  patch({ countup_from_datetime: fromDatetimeLocalValue((e.target as HTMLInputElement).value) })
}

function onCountdownToInput(e: Event) {
  patch({ countdown_to_datetime: fromDatetimeLocalValue((e.target as HTMLInputElement).value) })
}

function onTextInput(e: Event) {
  patch({ text: (e.target as HTMLInputElement).value })
}

function onBgInput(e: Event) {
  patch({ background_color: (e.target as HTMLInputElement).value })
}

function onCounterColorInput(e: Event) {
  patch({ counter_color: (e.target as HTMLInputElement).value })
}

function onTextColorInput(e: Event) {
  patch({ text_color: (e.target as HTMLInputElement).value })
}

function openPicker(e: MouseEvent) {
  const input = e.currentTarget as HTMLInputElement | null
  input?.showPicker?.()
}
</script>

<style scoped>
.top-bar-countdown-style-option {
  display: block;
  margin-bottom: 12px;
}

.top-bar-countdown-style-option :deep(.top-bar-countdown-column) {
  margin-top: 8px;
  pointer-events: none;
}
</style>
