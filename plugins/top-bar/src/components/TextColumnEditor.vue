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
          v-for="(_message, index) in effect === 'none' ? messages.slice(0, 1) : messages"
          :key="index"
          class="top-bar-column-creator-grid"
          draggable="true"
          :data-dragging="draggingIndex === index ? 'true' : 'false'"
          @dragstart="onDragStart(index, $event)"
          @dragend="onDragEnd"
          @dragenter="onDragEnter(index, $event)"
          @dragover="onDragOver(index, $event)"
          @drop="onDrop(index, $event)"
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
              v-if="effect !== 'none' && messages.length > 1"
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
        v-if="effect !== 'none' && messages.length < maxMessages"
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
import { ref } from 'vue'
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

const draggingIndex = ref<number | null>(null)

function onEffectChange(e: Event) {
  const value = (e.target as HTMLSelectElement).value as Bar['effect']
  emit('update', { effect: value })
}

function canDropAt(targetIndex: number, fromIndex: number | null) {
  if (fromIndex === null) return false
  if (fromIndex === targetIndex) return false
  if (fromIndex < 0 || fromIndex >= props.messages.length) return false
  if (targetIndex < 0 || targetIndex >= props.messages.length) return false
  return true
}

function onDragEnter(targetIndex: number, e: DragEvent) {
  const from = draggingIndex.value
  if (!canDropAt(targetIndex, from)) return
  e.preventDefault()
  if (e.dataTransfer) e.dataTransfer.dropEffect = 'move'
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

function onDragStart(index: number, e: DragEvent) {
  if (props.messages.length <= 1) return
  draggingIndex.value = index
  e.dataTransfer?.setData('text/plain', String(index))
  if (e.dataTransfer) e.dataTransfer.effectAllowed = 'move'
}

function onDragEnd() {
  draggingIndex.value = null
}

function onDragOver(targetIndex: number, e: DragEvent) {
  const from = draggingIndex.value
  if (!canDropAt(targetIndex, from)) return
  e.preventDefault()
  if (e.dataTransfer) e.dataTransfer.dropEffect = 'move'
}

function onDrop(targetIndex: number, e: DragEvent) {
  e.preventDefault()

  const from =
    draggingIndex.value ??
    (() => {
      const raw = e.dataTransfer?.getData('text/plain') ?? ''
      const parsed = Number.parseInt(raw, 10)
      return Number.isFinite(parsed) ? parsed : null
    })()

  if (!canDropAt(targetIndex, from)) return

  const fromIndex = from as number
  const next = [...props.messages]
  const [moved] = next.splice(fromIndex, 1)
  next.splice(targetIndex, 0, moved ?? '')

  emit('update', { messages: next })
  emit('commit')
  draggingIndex.value = null
}
</script>
