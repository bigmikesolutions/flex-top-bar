<template>
  <div class="item-creator lg">
    <fieldset class="line">
      <legend class="bold">{{ __('Choose the icon appearance', 'top-bar') }}</legend>
      <label v-for="opt in iconStyleOptions" :key="opt.value" class="top-bar-radio-line">
        <input
          type="radio"
          :name="`contact_icon_style_${barId}_${columnId}`"
          :checked="column.icon_style === opt.value"
          @change="patchIconStyle(opt.value)"
        />
        {{ opt.label }}
      </label>
    </fieldset>

    <div class="top-bar-grid">
      <div class="item">
        <fieldset class="line">
          <legend class="bold">{{ __('Background color', 'top-bar') }}</legend>
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
          <legend class="bold">{{ __('Color icon', 'top-bar') }}</legend>
          <input
            :id="`contact_icon_${barId}_${columnId}`"
            type="color"
            :value="column.icon_color"
            @input="onIconColorInput"
            @blur="emit('commit')"
          />
        </fieldset>
      </div>
    </div>

    <fieldset class="line">
      <legend class="bold">{{ __('Add your contact', 'top-bar') }}</legend>
      <div class="top-bar-message-list">
        <div
          v-for="(_entry, index) in column.contacts"
          :key="index"
          class="top-bar-column-creator-grid"
        >
          <div class="item-creator no">
            <p class="bold md">{{ index + 1 }}</p>
            <button
              v-if="column.contacts.length > 1"
              type="button"
              class="top-bar-btn amber sm"
              @click="removeEntry(index)"
            >
              X
            </button>
          </div>
          <div class="item-creator vertical">
            <label class="screen-reader-text" :for="`contact_kind_${barId}_${columnId}_${index}`">
              {{ __('Contact type', 'top-bar') }}
            </label>
            <select
              :id="`contact_kind_${barId}_${columnId}_${index}`"
              :value="column.contacts[index]?.kind ?? ''"
              @change="onKindChange(index, $event)"
            >
              <option value="">{{ __('Select type', 'top-bar') }}</option>
              <option v-for="k in CONTACT_KINDS" :key="k" :value="k">
                {{ kindLabel(k) }}
              </option>
            </select>
            <label class="screen-reader-text" :for="`contact_value_${barId}_${columnId}_${index}`">
              {{ __('Contact value', 'top-bar') }}
            </label>
            <input
              :id="`contact_value_${barId}_${columnId}_${index}`"
              type="text"
              :value="column.contacts[index]?.value ?? ''"
              :placeholder="__('Your contact details', 'top-bar')"
              @input="onValueInput(index, $event)"
              @blur="emit('commit')"
            />
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
        {{ __('Add new contact', 'top-bar') }}
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

const iconStyleOptions = getIconStyleOptions(__)

function kindLabel(k: ContactKind): string {
  const labels: Record<ContactKind, string> = {
    email: __('Email', 'top-bar'),
    phone: __('Phone', 'top-bar'),
    mobile: __('Mobile', 'top-bar'),
    address: __('Address', 'top-bar'),
    location: __('Map location', 'top-bar'),
    website: __('Website', 'top-bar'),
    fax: __('Fax', 'top-bar'),
    support: __('Customer support', 'top-bar'),
    calendar: __('Appointment / Booking', 'top-bar'),
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

.screen-reader-text {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}
</style>
