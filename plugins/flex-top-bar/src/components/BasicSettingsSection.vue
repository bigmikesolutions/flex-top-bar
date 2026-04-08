<template>
  <div>
    <div class="top-bar-grid title">
      <div class="item">
        <p class="bold lg">{{ __('Basic settings', 'flex-top-bar') }}</p>
      </div>
    </div>

    <div class="top-bar-grid bg bg-blue options">
      <div class="item">
        <fieldset class="clear">
          <legend class="bold">{{ __('Position', 'flex-top-bar') }}</legend>
          <select
            :id="`position_${model.id}`"
            v-model="model.position"
            @change="emit('save')"
          >
            <option value="top">{{ __('Top', 'flex-top-bar') }}</option>
            <option value="bottom">{{ __('Bottom', 'flex-top-bar') }}</option>
          </select>
        </fieldset>
      </div>
      <!-- <div class="item">
        <fieldset class="clear">
          <legend class="bold">Fonts</legend>
          <select disabled>
            <option>Roboto</option>
          </select>
        </fieldset>
      </div> -->
      <div class="item">
        <fieldset class="clear">
          <legend class="bold">{{ __('Background', 'flex-top-bar') }}</legend>
          <input
            :id="`bg_color_${model.id}`"
            v-model="model.bg_color"
            type="color"
            @change="emit('save')"
          />
        </fieldset>
      </div>
      <div class="item">
        <fieldset class="clear">
          <legend class="bold">{{ __('Border frame', 'flex-top-bar') }}</legend>
          <div class="top-bar-border-frame-controls">
            <input
              :id="`frame_color_${model.id}`"
              v-model="model.frame_color"
              type="color"
              @change="emit('save')"
            />
            <select
              class="border"  
              v-model.number="model.frame_width"
              @change="onFrameWidthChange"
            >
              <option v-for="px in 11" :key="px - 1" :value="px - 1">{{ px - 1 }}px</option>
            </select>
          </div>
        </fieldset>
      </div>
      <div class="item">
        <fieldset class="clear">
          <legend class="bold">{{ __('On scroll', 'flex-top-bar') }}</legend>
          <select
            :id="`hide_on_scroll_${model.id}`"
            v-model="model.hide_on_scroll"
            @change="emit('save')"
          >
            <option :value="false">{{ __('Keep showing', 'flex-top-bar') }}</option>
            <option :value="true">{{ __('Hide on scroll', 'flex-top-bar') }}</option>
          </select>
          <p class="xs">{{ __('Whether the bar stays visible or hides when the user scrolls the page.', 'flex-top-bar') }}</p>
        </fieldset>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { Bar } from '@/types'
import { __ } from '@wordpress/i18n'

const model = defineModel<Bar>({ required: true })

const emit = defineEmits<{
  save: []
}>()

function onFrameWidthChange(e: Event) {
  const nextWidth = Number((e.target as HTMLSelectElement).value)
  // `input[type="color"]` may show a default color even when the model is empty.
  // Ensure the border becomes visible as soon as width > 0.
  if (nextWidth > 0 && !model.value.frame_color) {
    model.value.frame_color = '#000000'
  }
  emit('save')
}
</script>
