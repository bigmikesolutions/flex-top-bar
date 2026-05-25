<template>
  <div class="item-creator grid-3 hide">
    <fieldset class="line">
      <legend class="bold">{{ __('Choose the icon appearance', 'flex-top-bar') }}</legend>
      <label v-for="opt in iconStyleOptions" :key="opt.value" :class="['radio', { 'bg-grey radio': opt.value === 'white' }]">
        <input
          type="radio"
          :name="`contact_icon_style_${barId}_${columnId}`"
          :checked="column.icon_style === opt.value"
          @change="patchIconStyle(opt.value)"
        />
      <span></span>
       <div class="item icons">
        <div class="scroll">
          <span
            v-for="platform in CONTACT_KINDS"
            :key="platform"
            class="top-bar-icons contact"
            :class="{
              circle: opt.value === 'rounded',
              square: opt.value === 'square',
              'mask black': opt.value === 'black',
              'mask white': opt.value === 'white',
              'no-mask': opt.value === 'color',
              [platform]: true
            }"
            :title="kindLabel(platform)"
          ></span>
        </div>
      </div>
      </label>
    </fieldset>

    <div v-if="column.icon_style === 'rounded' || column.icon_style === 'square'" class="top-bar-grid options">
      <div class="item">
        <fieldset class="line">
          <legend class="bold">{{ __('Background color', 'flex-top-bar') }}</legend>
          <input
            :id="`contact_bg_${barId}_${columnId}`"
            type="color"
            :value="column.background_color"
            @input="onBgInput"
            @blur="emit('commit')"
          />
        </fieldset>
      </div>
      <div class="item">
        <fieldset class="line">
          <legend class="bold">{{ __('Color icon', 'flex-top-bar') }}</legend>
          <input
            :id="`contact_icon_${barId}_${columnId}`"
            type="color"
            :value="column.icon_color"
            @input="onIconColorInput"
            @blur="emit('commit')"
          />
        </fieldset>
      </div>
      <div class="item">
        <fieldset class="line vertical">
          <legend class="bold">{{ __('Border size & Color', 'flex-top-bar') }}</legend>
          <select
            class="border"
            :id="`contact_icon_border_width_${barId}_${columnId}`"
            :value="column.icon_border_width"
            @change="onBorderWidthChange"
          >
            <option :value="0">0px</option>
            <option :value="1">1px</option>
            <option :value="2">2px</option>
            <option :value="3">3px</option>
            <option :value="4">4px</option>
            <option :value="5">5px</option>
            <option :value="6">6px</option>
            <option :value="7">7px</option>
            <option :value="8">8px</option>
            <option :value="9">9px</option>
            <option :value="10">10</option>
          </select>
          <input
            :id="`contact_icon_border_color_${barId}_${columnId}`"
            type="color"
            :value="column.icon_border_color"
            @input="onBorderColorInput"
            @blur="emit('commit')"
          />
        </fieldset>
      </div>
    </div>

    <fieldset class="line">
      <legend class="bold">{{ __('Add your contact', 'flex-top-bar') }}</legend>
      <div class="top-bar-message-list">
        <div
          v-for="(_entry, index) in column.contacts"
          :key="index"
          class="top-bar-column-creator-grid"
        >
          <div class="item-creator no">
            <p class="bold md">{{ index + 1 }}</p>           
          </div>
          <div class="item-creator vertical">
            <!-- <label class="screen-reader-text" :for="`contact_kind_${barId}_${columnId}_${index}`">
              {{ __('Contact type', 'flex-top-bar') }}
            </label> -->
            <select
              :id="`contact_kind_${barId}_${columnId}_${index}`"
              :value="column.contacts[index]?.kind ?? ''"
              @change="onKindChange(index, $event)"
            >
              <option value="">{{ __('Select type', 'flex-top-bar') }}</option>
              <option v-for="k in CONTACT_KINDS" :key="k" :value="k">
                {{ kindLabel(k) }}
              </option>
            </select>
            <!-- <label class="screen-reader-text" :for="`contact_value_${barId}_${columnId}_${index}`">
              {{ __('Contact value', 'flex-top-bar') }}
            </label> -->
            <input
              :id="`contact_value_${barId}_${columnId}_${index}`"
              type="text"
              :value="column.contacts[index]?.value ?? ''"
              :placeholder="__('Your contact details', 'flex-top-bar')"
              @input="onValueInput(index, $event)"
              @blur="emit('commit')"
            />
          </div>
          <div class="item-creator center">
             <button
                v-if="column.contacts.length > 1"
                type="button"
                class="top-bar-btn top-bar-icons delete mask black remove empty"
                @click="removeEntry(index)"
              >
              Remove
            </button>
          </div>
        </div>
      </div>
    </fieldset>

    <div class="top-bar-row rt">
      <button
        v-if="column.contacts.length < maxEntries"
        type="button"
        class="top-bar-btn amber sm right"
        @click="addEntry"
      >
        {{ __('Add new contact', 'flex-top-bar') }}
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { __ } from '@wordpress/i18n'
import type { ContactBarColumn, ContactKind, IconStyle } from '@/types'
import { CONTACT_KINDS } from '@/types'
import { getIconStyleOptions } from '@/constants/iconStyleOptions'

const props = defineProps<{
  barId: string
  columnId: string
  column: ContactBarColumn
  maxEntries: number
}>()

const emit = defineEmits<{
  patch: [updates: Partial<ContactBarColumn>]
  commit: []
}>()

const iconStyleOptions = getIconStyleOptions(__).filter((o) => o.value !== 'color')

function kindLabel(k: ContactKind): string {
  const labels: Record<ContactKind, string> = {
    email: __('Email', 'flex-top-bar'),
    phone: __('Phone', 'flex-top-bar'),
    mobile: __('Mobile', 'flex-top-bar'),
    location: __('Location', 'flex-top-bar'),
    chat: __('Chat', 'flex-top-bar'),
    website: __('Website', 'flex-top-bar'),
    support: __('Support', 'flex-top-bar'),
  }
  return labels[k]
}

function patchIconStyle(style: IconStyle) {
  emit('patch', { icon_style: style })
  emit('commit')
}

function onBgInput(e: Event) {
  emit('patch', { background_color: (e.target as HTMLInputElement).value })
}

function onIconColorInput(e: Event) {
  emit('patch', { icon_color: (e.target as HTMLInputElement).value })
}

function onBorderWidthChange(e: Event) {
  const next = Number.parseInt((e.target as HTMLSelectElement).value, 10)
  emit('patch', { icon_border_width: Number.isFinite(next) ? next : 0 })
  emit('commit')
}

function onBorderColorInput(e: Event) {
  emit('patch', { icon_border_color: (e.target as HTMLInputElement).value })
}

function onKindChange(index: number, e: Event) {
  const kind = (e.target as HTMLSelectElement).value as ContactKind | ''
  const next = [...props.column.contacts]
  next[index] = { ...next[index], kind }
  emit('patch', { contacts: next })
  emit('commit')
}

function onValueInput(index: number, e: Event) {
  const value = (e.target as HTMLInputElement).value
  const next = [...props.column.contacts]
  next[index] = { ...next[index], value }
  emit('patch', { contacts: next })
}

function addEntry() {
  if (props.column.contacts.length < props.maxEntries) {
    emit('patch', { contacts: [...props.column.contacts, { kind: '', value: '' }] })
    emit('commit')
  }
}

function removeEntry(index: number) {
  if (props.column.contacts.length <= 1) {
    return
  }
  const next = props.column.contacts.filter((_, i) => i !== index)
  emit('patch', { contacts: next.length ? next : [{ kind: '', value: '' }] })
  emit('commit')
}
</script>

<style scoped>
.top-bar-radio-line {
  display: block;
  margin-bottom: 6px;
}

/* .screen-reader-text {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
} */
</style>
