<template>
  <div class="item-creator grid-3 top-bar-icon-column-editor">
    <fieldset class="line">
      <legend class="bold">{{ __('Icon position', 'flex-top-bar') }}</legend>
      <label class="radio">
        <input
          type="radio"
          :name="`icon_position_${barId}_${columnId}`"
          value="before"
          :checked="column.icon_position === 'before'"
          @change="setIconPosition('before')"
        />
        <span>{{ __('Before text', 'flex-top-bar') }}</span>
      </label>
      <label class="radio">
        <input
          type="radio"
          :name="`icon_position_${barId}_${columnId}`"
          value="after"
          :checked="column.icon_position === 'after'"
          @change="setIconPosition('after')"
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
                __('Larger images are scaled down to %1$d×%2$d px. Max %3$d KB. PNG, JPG, GIF, WebP, or SVG.', 'flex-top-bar'),
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
import type { IconBarColumn, IconPosition } from '@/types'
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

function setIconPosition(position: IconPosition) {
  patch({ icon_position: position })
  emit('commit')
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
/* Match admin column row proportions (type | editor grid-3 | settings | remove). */
.top-bar-icon-column-editor__row {
  display: grid;
  grid-template-columns: minmax(150px, 32%) minmax(0, 1fr);
  gap: 0;
  width: 100%;
  align-items: stretch;
}

.top-bar-icon-column-editor__icon,
.top-bar-icon-column-editor__text {
  min-width: 0;
  padding: 16px;
  box-sizing: border-box;
}

.top-bar-icon-column-editor__icon {
  border-right: 1px solid rgba(0, 0, 0, 0.08);
}

@media (max-width: 1024px) {
  .top-bar-icon-column-editor__row {
    grid-template-columns: minmax(0, 1fr);
  }

  .top-bar-icon-column-editor__icon {
    border-right: 0;
    border-bottom: 1px solid rgba(0, 0, 0, 0.08);
  }
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
