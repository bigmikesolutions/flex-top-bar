<template>
  <div class="top-bar-row bg">
    <!-- Navigation -->
    <div class="top-bar-nav">
      <div class="item name">
        <p class="lg bold">{{ bar.name || __('Top Bar', 'top-bar') }}</p>
      </div>

      <div class="item nav">
        <button
          type="button"
          :class="['top-bar-icons', 'top-bar-visibility-toggle', localBar.visible ? 'status-on' : 'status-off']"
          :aria-label="__('Toggle bar visibility on page', 'top-bar')"
          @click="toggleVisibility"
        >
        </button>
        <button
          v-if="canDelete"
          type="button"
          class="top-bar-icons delete"
          :title="__('Remove', 'top-bar')"
          @click="handleDelete"
        >
        </button>
        <button
          v-else
          type="button"
          class="top-bar-icons delete"
          disabled
          :title="__('At least one bar is required', 'top-bar')"
        >
        </button>
        <button
          type="button"
          class="top-bar-icons arrow-down top-bar-toggle-options"
          :aria-expanded="isExpanded"
          @click="toggleExpanded"
        >
        </button>
      </div>
    </div>

    <div :class="['top-bar-options', { active: isExpanded }]">
      <!-- Name -->
      <div class="top-bar-grid">
        <div class="item">
          <fieldset class="clear">
            <legend class="bold lg">{{ __('Name', 'top-bar') }}</legend>
            <input
              :id="`name_${bar.id}`"
              v-model="localBar.name"
              type="text"
              :placeholder="__('Name of Top Bar', 'top-bar')"
              @blur="saveChanges"
            />
          </fieldset>
        </div>
      </div>

      <!-- Basic settings title -->
      <div class="top-bar-grid title">
        <div class="item">
          <p class="bold lg">{{ __('Basic settings', 'top-bar') }}</p>
        </div>
      </div>

      <!-- Basic settings grid (4 columns - no Effect here) -->
      <div class="top-bar-grid bg bg-blue">
        <div class="item">
          <fieldset class="clear">
            <legend class="bold">{{ __('Position', 'top-bar') }}</legend>
            <select
              :id="`position_${bar.id}`"
              v-model="localBar.position"
              @change="saveChanges"
            >
              <option value="top">{{ __('Top', 'top-bar') }}</option>
              <option value="bottom">{{ __('Bottom', 'top-bar') }}</option>
            </select>
          </fieldset>
        </div>
        <div class="item">
          <fieldset class="clear">
            <legend class="bold">Fonts</legend>
            <select disabled>
              <option>Roboto</option>
            </select>
          </fieldset>
        </div>
        <div class="item">
          <fieldset class="clear">
            <legend class="bold">{{ __('Background', 'top-bar') }}</legend>
            <input
              :id="`bg_color_${bar.id}`"
              v-model="localBar.bg_color"
              type="color"
              @change="saveChanges"
            />
          </fieldset>
        </div>
        <div class="item">
          <fieldset class="clear">
            <legend class="bold">{{ __('Border frame', 'top-bar') }}</legend>
            <div class="top-bar-border-frame-controls">
              <input
                :id="`frame_color_${bar.id}`"
                v-model="localBar.frame_color"
                type="color"
                @change="saveChanges"
              />
              <select
                v-model.number="localBar.frame_width"
                @change="saveChanges"
              >
                <option v-for="px in 11" :key="px - 1" :value="px - 1">{{ px - 1 }}px</option>
              </select>
            </div>
          </fieldset>
        </div>
        <div class="item">
          <fieldset class="clear">
            <legend class="bold">{{ __('On scroll', 'top-bar') }}</legend>
            <select
              :id="`hide_on_scroll_${bar.id}`"
              v-model="localBar.hide_on_scroll"
              @change="saveChanges"
            >
              <option :value="false">{{ __('Keep showing', 'top-bar') }}</option>
              <option :value="true">{{ __('Hide on scroll', 'top-bar') }}</option>
            </select>
            <p class="xs">{{ __('Whether the bar stays visible or hides when the user scrolls the page.', 'top-bar') }}</p>
          </fieldset>
        </div>
      </div>

      <!-- Scheduling (if enabled) -->
      <template v-if="scheduleEnabled">
        <div class="top-bar-grid title">
          <div class="item">
            <label class="check top-bar-life-time-checkbox">
              <input
                v-model="localBar.scheduled_enabled"
                type="checkbox"
                class="top-bar-toggle-life-time"
                @change="saveChanges"
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
          v-if="localBar.scheduled_enabled"
          class="top-bar-grid bg bg-amber top-bar-lifetime-panel"
        >
          <div class="item">
            <fieldset class="clear">
              <legend class="bold">{{ __('From', 'top-bar') }}</legend>
              <label>
                <input
                  :id="`scheduled_from_${bar.id}`"
                  v-model="localBar.scheduled_from_datetime"
                  type="datetime-local"
                  class="top-bar-life-time-datetime"
                  @blur="saveChanges"
                />
              </label>
            </fieldset>
          </div>
          <div class="item">
            <fieldset class="clear">
              <legend class="bold">{{ __('To', 'top-bar') }}</legend>
              <label>
                <input
                  :id="`scheduled_to_${bar.id}`"
                  v-model="localBar.scheduled_to_datetime"
                  type="datetime-local"
                  class="top-bar-life-time-datetime"
                  @blur="saveChanges"
                />
              </label>
            </fieldset>
          </div>
        </div>
      </template>

      <!-- Messages section title -->
      <div class="top-bar-grid title">
        <div class="item">
          <p class="bold lg">{{ __('Create a design', 'top-bar') }}</p>
          <p class="xs">{{ __('Create your own top bar. You can add a maximum of 4 columns, choosing different types of content.', 'top-bar') }}</p>
        </div>
      </div>

      <!-- Column creator -->
      <div class="top-bar-grid">
        <div id="top-bar-column-creator">
          <div class="top-bar-column-creator-grid">
            <!-- Column number -->
            <div class="item-creator no">
              <p class="bold lg">1</p>
            </div>

            <!-- Type selector (UI mock for now) -->
            <div class="item-creator">
              <fieldset>
                <legend class="bold">{{ __('Type', 'top-bar') }}</legend>
                <label><input type="radio" checked disabled /> {{ __('Text Editor', 'top-bar') }}</label>
              </fieldset>
            </div>

            <!-- Messages section (large column) -->
            <div class="item-creator lg">
              <fieldset class="line">
                <legend class="bold">{{ __('Effect', 'top-bar') }}</legend>
                <label>
                  <select
                    v-model="localBar.effect"
                    @change="saveChanges"
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
                    v-for="(message, index) in localBar.messages"
                    :key="index"
                    class="top-bar-column-creator-grid"
                  >
                    <div class="item-creator no">
                      <p class="bold md">{{ index + 1 }}</p>
                      <button
                        v-if="localBar.messages.length > 1"
                        type="button"
                        class="top-bar-btn amber sm"
                        @click="removeMessage(index)"
                      >
                        X
                      </button>
                    </div>
                    <div class="item-creator">
                      <textarea
                        v-model="localBar.messages[index]"
                        :placeholder="index === 0 ? __('Welcome!', 'top-bar') : ''"
                        rows="2"
                        @blur="saveChanges"
                      ></textarea>
                    </div>
                  </div>
                </div>
              </fieldset>

              <div class="top-bar-row rt">
                <button
                  v-if="localBar.messages.length < maxMessages"
                  type="button"
                  class="top-bar-btn amber sm right"
                  @click="addMessage"
                >
                  {{ __('Add new text', 'top-bar') }}
                </button>
              </div>
            </div>

            <!-- Size + Mobile visibility -->
            <div class="item item-creator">
              <fieldset>
                <legend class="bold">{{ __('Size column', 'top-bar') }}</legend>
                <label>
                  <select disabled>
                    <option>100%</option>
                  </select>
                </label>
              </fieldset>
              <fieldset>
                <legend class="bold">{{ __('Visible on the mobile', 'top-bar') }}</legend>
                <select
                  v-model="localBar.messages_mobile_visible"
                  @change="saveChanges"
                >
                  <option :value="true">{{ __('On', 'top-bar') }}</option>
                  <option :value="false">{{ __('Off', 'top-bar') }}</option>
                </select>
              </fieldset>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import type { Bar } from '@/types'
import { __ } from '@wordpress/i18n'

const props = defineProps<{
  bar: Bar
  canDelete: boolean
  maxMessages: number
  scheduleEnabled: boolean
}>()

const emit = defineEmits<{
  update: [id: string, updates: Partial<Bar>]
  delete: [id: string]
}>()

const localBar = ref<Bar>({ ...props.bar })
const isExpanded = ref(props.bar.admin_visibile !== false)

watch(() => props.bar, (newBar) => {
  localBar.value = { ...newBar }
}, { deep: true })

function addMessage() {
  if (localBar.value.messages.length < props.maxMessages) {
    localBar.value.messages.push('')
    saveChanges()
  }
}

function removeMessage(index: number) {
  if (localBar.value.messages.length > 1 && index > 0) {
    localBar.value.messages.splice(index, 1)
    saveChanges()
  }
}

function toggleVisibility() {
  localBar.value.visible = !localBar.value.visible
  saveChanges()
}

function toggleExpanded() {
  isExpanded.value = !isExpanded.value
  emit('update', props.bar.id, { admin_visibile: isExpanded.value })
}

function saveChanges() {
  emit('update', props.bar.id, localBar.value)
}

function handleDelete() {
  if (confirm(__('Are you sure you want to delete this bar?', 'top-bar'))) {
    emit('delete', props.bar.id)
  }
}
</script>
