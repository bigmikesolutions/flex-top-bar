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
          <div
            v-for="(column, columnIndex) in localBar.columns"
            :key="column.id"
            class="top-bar-column-creator-grid"
          >
            <div class="item-creator no">
              <p class="bold lg">{{ columnIndex + 1 }}</p>
              <button
                v-if="localBar.columns.length > 1"
                type="button"
                class="top-bar-btn amber sm"
                :title="__('Remove column', 'top-bar')"
                @click="removeColumn(columnIndex)"
              >
                X
              </button>
            </div>

            <ColumnTypeSelector />

            <TextColumnEditor
              :bar-id="bar.id"
              :column-id="column.id"
              :effect="column.effect"
              :messages="column.messages"
              :max-messages="maxMessages"
              @patch="onColumnMessagesPatch(columnIndex, $event)"
              @commit="saveChanges"
              @update="onTextColumnPersist(columnIndex, $event)"
            />

            <div class="item item-creator">
              <fieldset>
                <legend class="bold">{{ __('Size column', 'top-bar') }}</legend>
                <label>
                  <select
                    :value="column.size_percent"
                    @change="onColumnSizeChange(columnIndex, $event)"
                  >
                    <option :value="25">25%</option>
                    <option :value="33">33%</option>
                    <option :value="50">50%</option>
                    <option :value="75">75%</option>
                    <option :value="100">100%</option>
                  </select>
                </label>
              </fieldset>
              <fieldset>
                <legend class="bold">{{ __('Visible on the mobile', 'top-bar') }}</legend>
                <select
                  :value="column.messages_mobile_visible"
                  @change="onColumnMobileVisibleChange(columnIndex, $event)"
                >
                  <option :value="true">{{ __('On', 'top-bar') }}</option>
                  <option :value="false">{{ __('Off', 'top-bar') }}</option>
                </select>
              </fieldset>
            </div>
          </div>
        </div>
      </div>

      <div class="top-bar-grid">
        <div class="item">
          <button
            type="button"
            class="top-bar-btn mint sm"
            :disabled="localBar.columns.length >= maxColumns"
            @click="addColumn"
          >
            {{ __('Add column', 'top-bar') }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import type { Bar, BarColumn } from '@/types'
import { __ } from '@wordpress/i18n'
import ColumnTypeSelector from './ColumnTypeSelector.vue'
import TextColumnEditor from './TextColumnEditor.vue'

const maxColumns = 4

function newColumnId(): string {
  if (typeof crypto !== 'undefined' && crypto.randomUUID) {
    return `col_${crypto.randomUUID().replace(/-/g, '')}`
  }
  return `col_${String(Date.now())}_${Math.random().toString(36).slice(2, 10)}`
}

function legacyFromColumns(cols: BarColumn[]) {
  const first = cols[0]
  if (!first) {
    return {}
  }
  return {
    effect: first.effect,
    messages: first.messages,
    messages_mobile_visible: first.messages_mobile_visible,
  }
}

function defaultSizePercentForColumnCount(count: number): BarColumn['size_percent'] {
  if (count <= 1) {
    return 100
  }
  if (count === 2) {
    return 50
  }
  if (count === 3) {
    return 33
  }
  return 25
}

function withColumns(bar: Bar): Bar {
  if (bar.columns?.length) {
    return bar
  }
  return {
    ...bar,
    columns: [
      {
        id: newColumnId(),
        type: 'text',
        effect: bar.effect,
        messages: bar.messages,
        size_percent: 100,
        messages_mobile_visible: bar.messages_mobile_visible,
      },
    ],
  }
}

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

const localBar = ref<Bar>(withColumns({ ...props.bar }))
const isExpanded = ref(props.bar.admin_visibile !== false)

// Only sync from props on initial load, not on every update
// This prevents the form from resetting while user is typing
watch(() => props.bar.id, () => {
  localBar.value = withColumns({ ...props.bar })
  isExpanded.value = props.bar.admin_visibile !== false
})

function onColumnMessagesPatch(
  columnIndex: number,
  updates: Partial<Pick<BarColumn, 'messages'>>,
) {
  const cols = [...localBar.value.columns]
  cols[columnIndex] = { ...cols[columnIndex], ...updates }
  localBar.value = { ...localBar.value, columns: cols, ...legacyFromColumns(cols) }
}

function onTextColumnPersist(
  columnIndex: number,
  updates: Partial<Pick<BarColumn, 'effect' | 'messages'>>,
) {
  const cols = [...localBar.value.columns]
  cols[columnIndex] = { ...cols[columnIndex], ...updates }
  localBar.value = { ...localBar.value, columns: cols, ...legacyFromColumns(cols) }
  saveChanges()
}

function onColumnSizeChange(columnIndex: number, e: Event) {
  const value = Number((e.target as HTMLSelectElement).value) as BarColumn['size_percent']
  const cols = [...localBar.value.columns]
  cols[columnIndex] = { ...cols[columnIndex], size_percent: value }
  localBar.value = { ...localBar.value, columns: cols, ...legacyFromColumns(cols) }
  saveChanges()
}

function onColumnMobileVisibleChange(columnIndex: number, e: Event) {
  const raw = (e.target as HTMLSelectElement).value
  const visible = raw === 'true'
  const cols = [...localBar.value.columns]
  cols[columnIndex] = { ...cols[columnIndex], messages_mobile_visible: visible }
  localBar.value = { ...localBar.value, columns: cols, ...legacyFromColumns(cols) }
  saveChanges()
}

function addColumn() {
  if (localBar.value.columns.length >= maxColumns) {
    return
  }
  const cols = [...localBar.value.columns]
  const nextCount = cols.length + 1
  const share = defaultSizePercentForColumnCount(nextCount)
  const resized = cols.map(c => ({ ...c, size_percent: share }))
  resized.push({
    id: newColumnId(),
    type: 'text',
    effect: 'none',
    messages: ['', ''],
    size_percent: share,
    messages_mobile_visible: true,
  })
  localBar.value = { ...localBar.value, columns: resized, ...legacyFromColumns(resized) }
  saveChanges()
}

function removeColumn(columnIndex: number) {
  if (localBar.value.columns.length <= 1) {
    return
  }
  const cols = localBar.value.columns.filter((_, i) => i !== columnIndex)
  if (cols.length === 1) {
    cols[0] = { ...cols[0], size_percent: 100 }
  }
  localBar.value = { ...localBar.value, columns: cols, ...legacyFromColumns(cols) }
  saveChanges()
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
