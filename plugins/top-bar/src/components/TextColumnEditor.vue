<template>
  <div class="item-creator lg">
    <fieldset class="line">
      <legend class="bold">{{ __('Effect', 'top-bar') }}</legend>
      <label>
        <select
          :id="`effect_${barId}`"
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
            <button
              v-if="messages.length > 1"
              type="button"
              class="top-bar-btn amber sm"
              @click="removeMessage(index)"
            >
              X
            </button>
          </div>
          <div class="item-creator">
            <textarea
              :value="messages[index]"
              :placeholder="index === 0 ? __('Welcome!', 'top-bar') : ''"
              rows="2"
              @input="onMessageInput(index, $event)"
              @blur="emit('commit')"
            ></textarea>
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
import type { Bar } from '@/types'

const props = defineProps<{
  barId: string
  effect: Bar['effect']
  messages: string[]
  maxMessages: number
}>()

const emit = defineEmits<{
  patch: [updates: Partial<Pick<Bar, 'messages'>>]
  commit: []
  update: [updates: Partial<Pick<Bar, 'effect' | 'messages'>>]
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
