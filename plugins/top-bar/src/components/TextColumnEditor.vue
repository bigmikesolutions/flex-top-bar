<template>
  <div class="item-creator grid-3">
    <fieldset class="line">
      <legend class="bold">{{ __('Effect', 'top-bar') }}</legend>
      <label>
        <select
          :id="`effect_${barId}_${columnId}`"
          :value="effect"
          @change="onEffectChange"
        >
          <option value="none">{{ __('None', 'top-bar') }}</option>
          <option value="slider">{{ __('Slider', 'top-bar') }}</option>
          <option value="fadein">{{ __('Fade In', 'top-bar') }}</option>
          <option value="blink">{{ __('Blink', 'top-bar') }}</option>
        </select>
      </label>
    </fieldset>
    <fieldset class="line">
      <legend class="bold">{{ __('Add multi fields', 'top-bar') }}</legend>

      <div class="top-bar-message-list">
        <div
          v-for="(_message, index) in messages"
          :key="index"
          class="top-bar-column-creator-grid"
        >
          <div class="item-creator no">
            <p class="bold md">{{ index + 1 }}</p>
          </div>
          <div class="item-creator grid-2">
            <textarea
              :value="messages[index]"
              :name="messages[index]"
              :placeholder="index === 0 ? __('Welcome!', 'top-bar') : ''"
              rows="2"
              @input="onMessageInput(index, $event)"
              @blur="emit('commit')"
            ></textarea>
          </div>
          <div class="item-creator center">
            <button
              v-if="messages.length > 1"
              type="button"
              class="top-bar-btn top-bar-icons delete mask black remove empty"
              @click="removeMessage(index)"
            >
              Remove
            </button>
          </div>
        </div>
      </div>
    </fieldset>

    <div class="top-bar-row rt">
      <button
        v-if="messages.length < maxMessages"
        type="button"
        class="top-bar-btn amber sm right"
        @click="addMessage"
      >
        {{ __('Add new text', 'top-bar') }}
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { __ } from '@wordpress/i18n'
import type { Bar, TextBarColumn } from '@/types'

const props = defineProps<{
  barId: string
  columnId: string
  effect: Bar['effect']
  messages: string[]
  maxMessages: number
}>()

const emit = defineEmits<{
  patch: [updates: Partial<Pick<TextBarColumn, 'messages'>>]
  commit: []
  update: [updates: Partial<Pick<TextBarColumn, 'effect' | 'messages'>>]
}>()

function onEffectChange(e: Event) {
  const value = (e.target as HTMLSelectElement).value as Bar['effect']
  emit('update', { effect: value })
}

function onMessageInput(index: number, e: Event) {
  const value = (e.target as HTMLTextAreaElement).value
  const next = [...props.messages]
  next[index] = value
  emit('patch', { messages: next })
}

function addMessage() {
  if (props.messages.length < props.maxMessages) {
    emit('update', { messages: [...props.messages, ''] })
  }
}

function removeMessage(index: number) {
  if (props.messages.length > 1 && index > 0) {
    const next = props.messages.filter((_, i) => i !== index)
    emit('update', { messages: next })
  }
}
</script>
