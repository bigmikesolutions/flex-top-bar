<template>
  <div class="item-creator top-bar-icon-column-editor">
    <fieldset class="line">
      <legend class="bold">{{ __('Icon position', 'flex-top-bar') }}</legend>
      <label class="radio">
        <input
          type="radio"
          :name="`icon_position_${barId}_${columnId}`"
          value="before"
          :checked="column.icon_position === 'before'"
          @change="patch({ icon_position: 'before' })"
        />
        <span>{{ __('Before text', 'flex-top-bar') }}</span>
      </label>
      <label class="radio">
        <input
          type="radio"
          :name="`icon_position_${barId}_${columnId}`"
          value="after"
          :checked="column.icon_position === 'after'"
          @change="patch({ icon_position: 'after' })"
        />
        <span>{{ __('After text', 'flex-top-bar') }}</span>
      </label>
    </fieldset>

    <div class="top-bar-icon-column-editor__row">
      <div class="top-bar-icon-column-editor__icon">
        <fieldset class="line">
          <legend class="bold">{{ __('Icon', 'flex-top-bar') }}</legend>
          <p class="xs top-bar-icon-column-editor__hint">
            {{
              sprintf(
                __('Max %1$d×%2$d px, %3$d KB. PNG, JPG, GIF, WebP, or SVG.', 'flex-top-bar'),
                limits.maxWidth,
                limits.maxHeight,
                Math.round(limits.maxFileBytes / 1024),
              )
            }}
          </p>
          <div v-if="column.icon_url" class="top-bar-icon-column-editor__preview">
            <img
              :src="column.icon_url"
              alt=""
              class="top-bar-icon-column-editor__preview-img"
              :width="limits.displaySizePx"
              :height="limits.displaySizePx"
            />
          </div>
          <div class="top-bar-icon-column-editor__actions">
            <button type="button" class="top-bar-btn mint sm" @click="selectIcon">
              {{ column.icon_url ? __('Change icon', 'flex-top-bar') : __('Select icon', 'flex-top-bar') }}
            </button>
            <button
              v-if="column.icon_url"
              type="button"
              class="top-bar-btn sm"
              @click="clearIcon"
            >
              {{ __('Remove', 'flex-top-bar') }}
            </button>
          </div>
        </fieldset>
      </div>
      <div class="top-bar-icon-column-editor__text">
        <fieldset class="line">
          <legend class="bold">{{ __('Text', 'flex-top-bar') }}</legend>
          <input
            type="text"
            :value="column.text"
            :placeholder="__('Short message', 'flex-top-bar')"
            @input="onTextInput"
            @blur="emit('commit')"
          />
        </fieldset>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { __, sprintf } from '@wordpress/i18n'
import type { IconBarColumn } from '@/types'
import { useWpMedia } from '@/composables/useWpMedia'

const props = defineProps<{
  barId: string
  columnId: string
  column: IconBarColumn
}>()

const emit = defineEmits<{
  patch: [partial: Partial<IconBarColumn>]
  commit: []
}>()

const { limits, openIconPicker } = useWpMedia()

function patch(partial: Partial<IconBarColumn>) {
  emit('patch', partial)
}

function selectIcon() {
  openIconPicker(({ id, url }) => {
    patch({
      icon_attachment_id: id,
      icon_url: url,
    })
    emit('commit')
  })
}

function clearIcon() {
  patch({
    icon_attachment_id: 0,
    icon_url: '',
  })
  emit('commit')
}

function onTextInput(e: Event) {
  const value = (e.target as HTMLInputElement).value
  patch({ text: value })
}
</script>

<style scoped>
.top-bar-icon-column-editor__row {
  display: flex;
  flex-wrap: wrap;
  gap: 16px;
  align-items: flex-start;
}

.top-bar-icon-column-editor__icon,
.top-bar-icon-column-editor__text {
  flex: 1 1 200px;
  min-width: 0;
}

.top-bar-icon-column-editor__hint {
  margin: 0 0 8px;
}

.top-bar-icon-column-editor__preview {
  margin-bottom: 8px;
}

.top-bar-icon-column-editor__preview-img {
  display: block;
  object-fit: contain;
}

.top-bar-icon-column-editor__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}
</style>
