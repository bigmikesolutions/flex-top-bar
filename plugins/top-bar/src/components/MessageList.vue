<template>
  <div class="top-bar-grid">
    <div class="item">
      <label>
        <p class="bold lg">{{ __('Messages', 'top-bar') }}</p>
        <p class="xs">{{ __('Content to display in the bar', 'top-bar') }}</p>
      </label>
    </div>
    <div class="item">
      <div class="top-bar-messages">
        <div v-for="(message, index) in localMessages" :key="index" class="top-bar-message-row">
          <textarea
            v-model="localMessages[index]"
            :placeholder="__('Enter message...', 'top-bar')"
            rows="2"
            class="large-text"
            @blur="handleUpdate"
          ></textarea>
          <button
            v-if="canRemove(index)"
            type="button"
            class="top-bar-icons delete"
            :title="__('Remove message', 'top-bar')"
            @click="removeMessage(index)"
          >
            <span class="dashicons dashicons-no"></span>
          </button>
        </div>
        <button
          v-if="canAddMore"
          type="button"
          class="top-bar-add-message button button-secondary"
          @click="addMessage"
        >
          {{ __('Add Message', 'top-bar') }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { __ } from '@wordpress/i18n'

const props = defineProps<{
  modelValue: string[]
  barId: string
  maxMessages: number
}>()

const emit = defineEmits<{
  'update:modelValue': [value: string[]]
  update: []
}>()

const localMessages = ref<string[]>([...props.modelValue])

watch(() => props.modelValue, (newMessages) => {
  localMessages.value = [...newMessages]
}, { deep: true })

const canAddMore = computed(() => localMessages.value.length < props.maxMessages)

function canRemove(index: number): boolean {
  // Can't remove first message, need at least one
  return index > 0 && localMessages.value.length > 1
}

function addMessage() {
  if (canAddMore.value) {
    localMessages.value.push('')
    handleUpdate()
  }
}

function removeMessage(index: number) {
  if (canRemove(index)) {
    localMessages.value.splice(index, 1)
    handleUpdate()
  }
}

function handleUpdate() {
  // Filter out empty messages except the first one
  const filtered = localMessages.value.filter((msg, idx) => idx === 0 || msg.trim() !== '')
  emit('update:modelValue', filtered.length > 0 ? filtered : [''])
  emit('update')
}
</script>

<style scoped>
.top-bar-messages {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.top-bar-message-row {
  display: flex;
  gap: 10px;
  align-items: flex-start;
}

.top-bar-message-row textarea {
  flex: 1;
}

.top-bar-add-message {
  align-self: flex-start;
}
</style>
