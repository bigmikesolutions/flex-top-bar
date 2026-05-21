<template>
  <div class="top-bar-row bg">
    <!-- Navigation -->
    <div class="top-bar-nav">
      <div class="item name">
        <p class="lg bold">{{ bar.name || __('Flex Top Bar', 'flex-top-bar') }}</p>
      </div>

      <div class="item nav">
        <button
          type="button"
          :class="['top-bar-icons mask black', 'top-bar-visibility-toggle', localBar.visible ? 'status-on' : 'status-off']"
          :title="visibilityToggleTooltip"
          :aria-label="visibilityToggleTooltip"
          @click="toggleVisibility"
        >
        </button>
        <button
          v-if="canDelete"
          type="button"
          class="top-bar-icons mask black delete"
          :title="__('Delete this top bar', 'flex-top-bar')"
          :aria-label="__('Delete this top bar', 'flex-top-bar')"
          @click="handleDelete"
        >
        </button>
        <button
          v-else
          type="button"
          class="top-bar-icons mask black delete"
          disabled
          :title="__('Cannot delete: at least one top bar must remain', 'flex-top-bar')"
          :aria-label="__('Cannot delete: at least one top bar must remain', 'flex-top-bar')"
        >
        </button>
        <button
          type="button"
          :class="['top-bar-icons mask black publish', { 'top-bar-publish--dirty': hasUnpublishedChanges }]"
          :title="publishButtonTooltip"
          :aria-label="publishButtonTooltip"
          @click="handlePublish"
        >
        </button>
        <button
          type="button"
          class="top-bar-icons mask black arrow-down top-bar-toggle-options"
          :aria-expanded="isExpanded"
          @click="toggleExpanded"
        >
        </button>
      </div>
    </div>

    <!-- Preview (sticky within this bar row) -->
    <div class="top-bar-grid top-bar-admin-preview-row">
      <div class="item">
        <p class="bold lg">Preview</p>
        <div class="top-bar-admin-preview">
          <TopBarView :bars-override="[localBar]" preview />
        </div>
      </div>
    </div>

    <div :class="['top-bar-options', { active: isExpanded }]">
      <!-- Name -->
      <div class="top-bar-grid">
        <div class="item">
          <fieldset class="clear">
            <legend class="bold lg">{{ __('Name', 'flex-top-bar') }}</legend>
            <input
              :id="`name_${bar.id}`"
              v-model="localBar.name"
              type="text"
              :placeholder="__('Name of Top Bar', 'flex-top-bar')"
              @blur="saveChanges"
            />
          </fieldset>
        </div>
      </div>

      <BasicSettingsSection v-model="localBar" @save="saveChanges" />

      <ScheduleSection
        v-model="localBar"
        :schedule-enabled="scheduleEnabled"
        :section-tooltip="scheduleSectionTooltip"
        @save="saveChanges"
      />

      <!-- Messages section title + add column. Inline flex avoids #top-bar .top-bar-grid { grid } collapsing the 2nd column to 0 width. -->
      <div
        class="top-bar-grid title title-with-action">
        <div class="item" style="flex: 1 1 220px; min-width: 0;">
          <p class="bold lg">{{ __('Create a design', 'flex-top-bar') }}</p>
          <p class="xs">
            {{
              __('Create your own top bar. You can add a maximum of %d columns, choosing different types of content.', 'flex-top-bar')
                .replace('%d', String(maxColumns))
            }}
          </p>
        </div>
        <div
          class="item title-with-action__btn right">
          <button
            type="button"
            class="top-bar-btn mint sm"
            :disabled="localBar.columns.length >= maxColumns"
            :title="addColumnTooltip"
            @click="addColumn"
          >
            {{ __('Add column', 'flex-top-bar') }}
          </button>
        </div>
      </div>

      <!-- Column creator -->
      <div class="top-bar-grid">
        <div class="top-bar-column-creator">
          <div
            v-for="(column, columnIndex) in localBar.columns"
            :key="`${column.id}-${columnIndex}`"
            class="top-bar-column-creator-grid"
            :class="{
              'top-bar-column-creator-grid--dragging': dragState.fromIndex === columnIndex,
              'top-bar-column-creator-grid--drag-over': dragState.overIndex === columnIndex,
            }"
            @dragover.prevent="onDragOver(columnIndex)"
            @dragleave="onDragLeave(columnIndex)"
            @drop.prevent="onDrop(columnIndex)"
          >
            <div class="item-creator no">
              <button
                v-if="localBar.columns.length > 1"
                type="button"
                class="top-bar-btn drag-drop"
                :title="__('Drag to reorder columns', 'flex-top-bar')"
                draggable="true"
                @dragstart="onDragStart(columnIndex, $event)"
                @dragend="onDragEnd"
              >
                {{ columnIndex + 1 }}
              </button>            
            </div>
            <div class="top-bar-column-creator-grid responsive">
                <ColumnTypeSelector
                  :group-name="`${bar.id}_${column.id}`"
                  :column-type="column.type"
                  @update:column-type="onColumnTypeChange(columnIndex, $event)"
                />

                <TextColumnEditor
                  v-if="column.type === 'text'"
                  :bar-id="bar.id"
                  :column-id="column.id"
                  :effect="column.effect"
                  :messages="column.messages"
                  :max-messages="maxMessages"
                  @patch="onColumnMessagesPatch(columnIndex, $event)"
                  @commit="saveChanges"
                  @update="onTextColumnPersist(columnIndex, $event)"
                />

                <SocialColumnEditor
                  v-else-if="column.type === 'social'"
                  :bar-id="bar.id"
                  :column-id="column.id"
                  :column="column"
                  :max-links="maxMessages"
                  @patch="onSocialColumnPatch(columnIndex, $event)"
                  @commit="saveChanges"
                />

                <ContactColumnEditor
                  v-else-if="column.type === 'contact'"
                  :bar-id="bar.id"
                  :column-id="column.id"
                  :column="column"
                  :max-entries="maxMessages"
                  @patch="onContactColumnPatch(columnIndex, $event)"
                  @commit="saveChanges"
                />

                <IconColumnEditor
                  v-else-if="column.type === 'icon'"
                  :bar-id="bar.id"
                  :column-id="column.id"
                  :column="column"
                  @patch="onIconColumnPatch(columnIndex, $event)"
                  @commit="saveChanges"
                />

                <CountdownColumnEditor
                  v-else-if="column.type === 'countdown'"
                  :bar-id="bar.id"
                  :column-id="column.id"
                  :column="column"
                  @patch="onCountdownColumnPatch(columnIndex, $event)"
                  @commit="saveChanges"
                />

                <div class="item item-creator">
                  <fieldset>
                    <legend class="bold">{{ __('Size column', 'flex-top-bar') }}</legend>
                    <label>
                      <select
                        :value="maxColumns <= 1 ? 100 : column.size_percent"
                        :disabled="maxColumns <= 1"
                        :title="columnSizeTooltip"
                        @change="onColumnSizeChange(columnIndex, $event)"
                      >
                        <option :value="10">10%</option>
                        <option :value="25">25%</option>
                        <option :value="33">33%</option>
                        <option :value="50">50%</option>
                        <option :value="75">75%</option>
                        <option :value="100">100%</option>
                      </select>
                    </label>
                  </fieldset>
                  <fieldset>
                    <legend class="bold">{{ __('Content position', 'flex-top-bar') }}</legend>
                    <select :value="column.content_position" @change="onColumnContentPositionChange(columnIndex, $event)">
                      <option value="left">{{ __('Left', 'flex-top-bar') }}</option>
                      <option value="center">{{ __('Center', 'flex-top-bar') }}</option>
                      <option value="right">{{ __('Right', 'flex-top-bar') }}</option>
                    </select>
                  </fieldset>
                  <fieldset>
                    <legend class="bold">{{ __('Visible on the mobile', 'flex-top-bar') }}</legend>
                    <select
                      :value="column.messages_mobile_visible"
                      @change="onColumnMobileVisibleChange(columnIndex, $event)"
                    >
                      <option :value="true">{{ __('On', 'flex-top-bar') }}</option>
                      <option :value="false">{{ __('Off', 'flex-top-bar') }}</option>
                    </select>
                  </fieldset>
                </div>
                <div class="item item-creator remove">
                  <button
                    v-if="localBar.columns.length > 1"
                    type="button"
                    class="top-bar-btn top-bar-icons delete mask black remove empty"
                    :title="__('Remove column', 'flex-top-bar')"
                    @click="removeColumn(columnIndex)"
                  >
                    Remove
                  </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import type {
  Bar,
  BarColumn,
  ColumnType,
  ContactBarColumn,
  ContentPosition,
  CountdownBarColumn,
  IconBarColumn,
  SocialBarColumn,
} from '@/types'
import { getDefaultScheduleTimezone } from '@/utils/scheduleDateTime'
import { __, sprintf } from '@wordpress/i18n'
import BasicSettingsSection from './BasicSettingsSection.vue'
import ColumnTypeSelector from './ColumnTypeSelector.vue'
import ContactColumnEditor from './ContactColumnEditor.vue'
import CountdownColumnEditor from './CountdownColumnEditor.vue'
import IconColumnEditor from './IconColumnEditor.vue'
import ScheduleSection from './ScheduleSection.vue'
import SocialColumnEditor from './SocialColumnEditor.vue'
import TextColumnEditor from './TextColumnEditor.vue'
import TopBarView from './TopBarView.vue'

function newColumnId(): string {
  if (typeof crypto !== 'undefined' && crypto.randomUUID) {
    return `col_${crypto.randomUUID().replace(/-/g, '')}`
  }
  return `col_${String(Date.now())}_${Math.random().toString(36).slice(2, 10)}`
}

/** Keeps bar-level effect/messages in sync with `columns` for API payloads (PHP does not derive these from columns). */
function syncBarRootForPersist() {
  const cols = localBar.value.columns
  const first = cols[0]
  if (!first) {
    localBar.value.effect = 'none'
    localBar.value.messages = ['', '']
    localBar.value.messages_mobile_visible = true
    return
  }
  if (first.type === 'text') {
    localBar.value.effect = first.effect
    localBar.value.messages = [...first.messages]
    localBar.value.messages_mobile_visible = first.messages_mobile_visible
    return
  }
  localBar.value.effect = 'none'
  localBar.value.messages = ['', '']
  localBar.value.messages_mobile_visible = first.messages_mobile_visible
}

function defaultColumnForType(
  type: ColumnType,
  id: string,
  sizePercent: BarColumn['size_percent'],
  messagesMobileVisible: boolean,
): BarColumn {
  if (type === 'text') {
    return {
      id,
      type: 'text',
      effect: 'none',
      messages: ['', ''],
      size_percent: sizePercent,
      content_position: 'center',
      messages_mobile_visible: messagesMobileVisible,
    }
  }
  if (type === 'social') {
    return {
      id,
      type: 'social',
      icon_style: 'rounded',
      background_color: '#ffffff',
      icon_color: '#1d2327',
      icon_border_width: 0,
      icon_border_color: '#1d2327',
      links: [{ platform: '', url: '' }],
      size_percent: sizePercent,
      content_position: 'center',
      messages_mobile_visible: messagesMobileVisible,
    }
  }
  if (type === 'icon') {
    return {
      id,
      type: 'icon',
      icon_attachment_id: 0,
      icon_url: '',
      text: '',
      icon_position: 'before',
      size_percent: sizePercent,
      content_position: 'center',
      messages_mobile_visible: messagesMobileVisible,
    }
  }
  if (type === 'countdown') {
    return {
      id,
      type: 'countdown',
      counter_style: 'boxed',
      count_direction: 'down',
      countdown_to_datetime: '',
      countup_from_datetime: '',
      countdown_timezone: getDefaultScheduleTimezone(),
      text: '',
      text_position: 'before',
      background_color: '#1d2327',
      counter_color: '#ffffff',
      text_color: '#1d2327',
      size_percent: sizePercent,
      content_position: 'center',
      messages_mobile_visible: messagesMobileVisible,
    }
  }
  return {
    id,
    type: 'contact',
    icon_style: 'rounded',
    background_color: '#ffffff',
    icon_color: '#1d2327',
    icon_border_width: 0,
    icon_border_color: '#1d2327',
    contacts: [{ kind: '', value: '' }],
    size_percent: sizePercent,
    content_position: 'center',
    messages_mobile_visible: messagesMobileVisible,
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

/** Detach from Pinia / props so local edits never mutate the store by reference. */
function cloneBar(bar: Bar): Bar {
  // Deep clone via JSON — must NOT use toRaw() here: it only unwraps one level, so nested
  // `columns` could stay as proxies or be missing and collapse to a single synthetic column.
  return JSON.parse(JSON.stringify(bar)) as Bar
}

/**
 * Ensure `columns` exists. Always returns a clone — never return `bar` by reference, or localBar
 * shares the store object and multi-column UI can collapse to one column.
 */
function withColumns(bar: Bar): Bar {
  if (bar.columns?.length) {
    return cloneBar(bar)
  }
  const b = cloneBar(bar)
  return {
    ...b,
    columns: [
      {
        id: newColumnId(),
        type: 'text',
        effect: b.effect,
        messages: [...(b.messages ?? [])],
        size_percent: 100,
        content_position: 'center',
        messages_mobile_visible: b.messages_mobile_visible,
      },
    ],
  }
}

const props = defineProps<{
  bar: Bar
  publishedBar?: Bar
  canDelete: boolean
  maxMessages: number
  maxColumns: number
  scheduleEnabled: boolean
}>()

const maxColumns = computed(() => props.maxColumns)

const dragState = ref<{ fromIndex: number | null; overIndex: number | null }>({
  fromIndex: null,
  overIndex: null,
})

function onDragStart(fromIndex: number, e: DragEvent) {
  dragState.value = { fromIndex, overIndex: fromIndex }
  if (e.dataTransfer) {
    e.dataTransfer.effectAllowed = 'move'
    e.dataTransfer.setData('text/plain', String(fromIndex))
  }
}

function onDragOver(overIndex: number) {
  if (dragState.value.fromIndex === null) return
  dragState.value.overIndex = overIndex
}

function onDragLeave(overIndex: number) {
  if (dragState.value.overIndex === overIndex) {
    dragState.value.overIndex = null
  }
}

function onDrop(toIndex: number) {
  const fromIndex = dragState.value.fromIndex
  dragState.value = { fromIndex: null, overIndex: null }
  if (fromIndex === null || fromIndex === toIndex) return

  const cols = [...localBar.value.columns]
  const [moved] = cols.splice(fromIndex, 1)
  if (!moved) return
  cols.splice(toIndex, 0, moved)
  localBar.value = { ...localBar.value, columns: cols }
  saveChanges()
}

function onDragEnd() {
  dragState.value = { fromIndex: null, overIndex: null }
}

const emit = defineEmits<{
  update: [id: string, updates: Partial<Bar>]
  delete: [id: string]
  publish: [id: string]
}>()

const localBar = ref<Bar>(withColumns(cloneBar(props.bar)))
const isExpanded = ref(props.bar.admin_visibile !== false)

function normalizeColumnSizesForPlan() {
  if (maxColumns.value > 1) return
  if (!localBar.value.columns?.length) return
  const cols = localBar.value.columns.map((c) => ({ ...c, size_percent: 100 }))
  localBar.value = { ...localBar.value, columns: cols }
}

const visibilityToggleTooltip = computed(() =>
  localBar.value.visible
    ? __('Hide this bar on the site', 'flex-top-bar')
    : __('Show this bar on the site', 'flex-top-bar'),
)

function stripAdminOnlyFields(b: Bar): unknown {
  const { admin_visibile, ...rest } = b as any
  return rest
}

const hasUnpublishedChanges = computed(() => {
  if (!props.publishedBar) {
    // Draft bar not present in published set yet (e.g., newly created).
    return true
  }
  try {
    return (
      JSON.stringify(stripAdminOnlyFields(localBar.value)) !==
      JSON.stringify(stripAdminOnlyFields(props.publishedBar))
    )
  } catch {
    return false
  }
})

const publishButtonTooltip = computed(() =>
  hasUnpublishedChanges.value
    ? __('Pending changes ready to be published', 'flex-top-bar')
    : __('There are no changes to publish', 'flex-top-bar'),
)

const addColumnTooltip = computed(() => {
  const max = maxColumns.value
  const count = localBar.value.columns.length
  const tail = __(
    'If you want to change limits, check other plans on the plugin page or contact us.',
    'flex-top-bar',
  )
  if (count >= max) {
    const lead = sprintf(
      __('You have reached the maximum of %1$d columns for your plan.', 'flex-top-bar'),
      max,
    )
    return `${lead} ${tail}`
  }
  const remaining = Math.max(0, max - count)
  const lead = sprintf(
    __(
      'Your plan allows you to add yet %1$d more column(s) out of %2$d.',
      'flex-top-bar',
    ),
    remaining,
    max,
  )
  return `${lead} ${tail}`
})

const columnSizeTooltip = computed(() => {
  const max = maxColumns.value
  if (max > 1) return ''
  const tail = __(
    'If you want to change limits, check other plans on the plugin page or contact us.',
    'flex-top-bar',
  )
  const lead = __(
    'Your plan allows only one column, so the column size is fixed.',
    'flex-top-bar',
  )
  return `${lead} ${tail}`
})

const scheduleSectionTooltip = computed(() => {
  const tail = __(
    'Set start and end dates to control when the bar is visible.',
    'flex-top-bar',
  )
  if (!props.scheduleEnabled) {
    const lead = __("Your plan doesn't include scheduling.", 'flex-top-bar')
    return `${lead} ${tail}`
  }
  const lead = __(
    'Your plan includes scheduling for this top bar. ',
    'flex-top-bar',
  )
  return `${lead} ${tail}`
})

// Re-sync when the bar id or server column set changes (fetch, PUT response).
watch(
  () => [props.bar.id, JSON.stringify(props.bar.columns ?? null)] as const,
  () => {
    localBar.value = withColumns(cloneBar(props.bar))
    normalizeColumnSizesForPlan()
    isExpanded.value = props.bar.admin_visibile !== false
  },
  { immediate: true },
)

function onColumnMessagesPatch(
  columnIndex: number,
  updates: Partial<Pick<Extract<BarColumn, { type: 'text' }>, 'messages'>>,
) {
  const cols = [...localBar.value.columns]
  const cur = cols[columnIndex]
  if (cur.type !== 'text') {
    return
  }
  cols[columnIndex] = { ...cur, ...updates }
  localBar.value = { ...localBar.value, columns: cols }
}

function onTextColumnPersist(
  columnIndex: number,
  updates: Partial<Pick<Extract<BarColumn, { type: 'text' }>, 'effect' | 'messages'>>,
) {
  const cols = [...localBar.value.columns]
  const cur = cols[columnIndex]
  if (cur.type !== 'text') {
    return
  }
  cols[columnIndex] = { ...cur, ...updates }
  localBar.value = { ...localBar.value, columns: cols }
  saveChanges()
}

function onColumnTypeChange(columnIndex: number, newType: ColumnType) {
  const cur = localBar.value.columns[columnIndex]
  if (cur.type === newType) {
    return
  }
  const cols = [...localBar.value.columns]
  cols[columnIndex] = defaultColumnForType(
    newType,
    cur.id,
    cur.size_percent,
    cur.messages_mobile_visible,
  )
  localBar.value = { ...localBar.value, columns: cols }
  saveChanges()
}

function onSocialColumnPatch(columnIndex: number, partial: Partial<SocialBarColumn>) {
  const cols = [...localBar.value.columns]
  const cur = cols[columnIndex]
  if (cur.type !== 'social') {
    return
  }
  cols[columnIndex] = { ...cur, ...partial }
  localBar.value = { ...localBar.value, columns: cols }
}

function onContactColumnPatch(columnIndex: number, partial: Partial<ContactBarColumn>) {
  const cols = [...localBar.value.columns]
  const cur = cols[columnIndex]
  if (cur.type !== 'contact') {
    return
  }
  cols[columnIndex] = { ...cur, ...partial }
  localBar.value = { ...localBar.value, columns: cols }
}

function onIconColumnPatch(columnIndex: number, partial: Partial<IconBarColumn>) {
  const cols = [...localBar.value.columns]
  const cur = cols[columnIndex]
  if (cur.type !== 'icon') {
    return
  }
  cols[columnIndex] = { ...cur, ...partial }
  localBar.value = { ...localBar.value, columns: cols }
}

function onCountdownColumnPatch(columnIndex: number, partial: Partial<CountdownBarColumn>) {
  const cols = [...localBar.value.columns]
  const cur = cols[columnIndex]
  if (cur.type !== 'countdown') {
    return
  }
  cols[columnIndex] = { ...cur, ...partial }
  localBar.value = { ...localBar.value, columns: cols }
}

function onColumnSizeChange(columnIndex: number, e: Event) {
  const value = Number((e.target as HTMLSelectElement).value) as BarColumn['size_percent']
  const cols = [...localBar.value.columns]
  cols[columnIndex] = { ...cols[columnIndex], size_percent: value }
  localBar.value = { ...localBar.value, columns: cols }
  saveChanges()
}

function onColumnContentPositionChange(columnIndex: number, e: Event) {
  const raw = (e.target as HTMLSelectElement).value as ContentPosition
  const cols = [...localBar.value.columns]
  cols[columnIndex] = { ...cols[columnIndex], content_position: raw }
  localBar.value = { ...localBar.value, columns: cols }
  saveChanges()
}

function onColumnMobileVisibleChange(columnIndex: number, e: Event) {
  const raw = (e.target as HTMLSelectElement).value
  const visible = raw === 'true'
  const cols = [...localBar.value.columns]
  cols[columnIndex] = { ...cols[columnIndex], messages_mobile_visible: visible }
  localBar.value = { ...localBar.value, columns: cols }
  saveChanges()
}

function addColumn() {
  if (localBar.value.columns.length >= props.maxColumns) {
    return
  }
  const cols = [...localBar.value.columns]
  const nextCount = cols.length + 1
  const share = defaultSizePercentForColumnCount(nextCount)
  // Keep existing column sizes untouched; only choose a default for the new column.
  const updated = [...cols]
  updated.push({
    id: newColumnId(),
    type: 'text',
    effect: 'none',
    messages: ['', ''],
    size_percent: share,
    content_position: 'center',
    messages_mobile_visible: true,
  })
  localBar.value = { ...localBar.value, columns: updated }
  saveChanges()
}

function removeColumn(columnIndex: number) {
  if (localBar.value.columns.length <= 1) {
    return
  }
  const cols = localBar.value.columns.filter((_, i) => i !== columnIndex)
  localBar.value = { ...localBar.value, columns: cols }
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
  normalizeColumnSizesForPlan()
  syncBarRootForPersist()
  emit('update', props.bar.id, localBar.value)
}

function handleDelete() {
  if (confirm(__('Are you sure you want to delete this bar?', 'flex-top-bar'))) {
    emit('delete', props.bar.id)
  }
}

async function handlePublish() {
  if (!confirm(__('Publish changes to frontend?', 'flex-top-bar'))) {
    return
  }
  emit('publish', props.bar.id)
}
</script>

<style scoped>
.top-bar-admin-preview {
  /* Keep preview full-width and avoid layout shifts. */
  max-width: 100%;
}

/* Only adjust the Top Bar columns layout inside the preview. */
.top-bar-admin-preview :deep(.top-bar__columns) {
  flex-wrap: nowrap;
  width: 100%;
  overflow-x: auto;
  overflow-y: hidden;
  scrollbar-gutter: stable;
  -webkit-overflow-scrolling: touch;
}



.top-bar-admin-preview :deep(.top-bar) {
  width: 100%;
}

/* Prevent the preview bar from overlaying other admin content (e.g. 2nd preview). */
.top-bar-admin-preview :deep(.top-bar-container),
.top-bar-admin-preview :deep(.top-bar) {
  z-index: auto !important;
}

/* Keep preview visible while editing this single bar row. */
.top-bar-admin-preview-row {
  position: sticky;
  top: 0;
  z-index: 5;
  background: #fff;
}

.top-bar-icons.publish.top-bar-publish--dirty {
  /* Publish icon uses mask; background controls icon color. */
  background: rgba(33, 80, 237, 0.85) !important;
  box-shadow: 0 0 0 4px rgba(33, 80, 237, 0.16);
  border-radius: 6px;
}
</style>
