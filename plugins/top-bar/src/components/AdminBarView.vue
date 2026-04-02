<!-- TOOD 
- wyrzucic z Contact data ikony COlOR
- DODAC plugin do stylizacji Select 
- Walidacja: Opcja Text edytor -> Gdy Effect None, ukryć tworzenie kolumn (Add new text) -> Opcja statycznego pojedynczego tekstu.
  Pokazac button dopiero w momencie wybrania Slide, fadeIn itp

- datetime-local dziala tylko po kliknieciu w ikone. Powinno byc w cale pole.
- Wyciagnac nazwe pluginu z Settings do glownych menu
- Zmienic nazwe pluginu
- Dodac przycisk publish (dodalem do belki button + ikona)
- Dodac do opcji ikon (border i border color) -> dodalem takie pola

// Do przemyslenia
- Dodac tooltipy do buttonow
- Gdy ustawie dwa TopBary w pozycji TOP, jeden nachodzi na drugi. dobrze by bylo aby jeden byl pod drugim itp. To samo w pozycji bottom
- Moze dodac opcje: zamykania recznego lub po czasie dla danego TopBar ? Wtedy jeden mozna zrobic statycznie, drugi pod spodem znikal by po np 30sek 

BUGI
- Gdy usuwam bar z Scheduled wywala blad : An error occurred
- Po dodaniu slider, wpisywanie tekstu w textarea muli

-->


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
          :class="['top-bar-icons mask black', 'top-bar-visibility-toggle', localBar.visible ? 'status-on' : 'status-off']"
          :aria-label="__('Toggle bar visibility on page', 'top-bar')"
          @click="toggleVisibility"
        >
        </button>
        <button
          v-if="canDelete"
          type="button"
          class="top-bar-icons mask black delete"
          :title="__('Remove', 'top-bar')"
          @click="handleDelete"
        >
        </button>
        <button
          v-else
          type="button"
          class="top-bar-icons mask black delete"
          disabled
          :title="__('At least one bar is required', 'top-bar')"
        >
        </button>
        <button
          type="button"
          class="top-bar-icons mask black publish"
          disabled
          :title="__('Publish Flex Bar', 'top-bar')"
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

      <BasicSettingsSection v-model="localBar" @save="saveChanges" />

      <ScheduleSection v-model="localBar" :schedule-enabled="scheduleEnabled" @save="saveChanges" />

      <!-- Messages section title + add column. Inline flex avoids #top-bar .top-bar-grid { grid } collapsing the 2nd column to 0 width. -->
      <div
        class="top-bar-grid title title-with-action"
        style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px 16px; width: 100%; box-sizing: border-box;"
      >
        <div class="item" style="flex: 1 1 220px; min-width: 0;">
          <p class="bold lg">{{ __('Create a design', 'top-bar') }}</p>
          <p class="xs">
            {{
              __('Create your own top bar. You can add a maximum of %d columns, choosing different types of content.', 'top-bar')
                .replace('%d', String(maxColumns))
            }}
          </p>
        </div>
        <div
          class="item title-with-action__btn right"
          style="flex: 0 0 auto; min-width: min(100%, 11rem); border-left: none !important; padding-left: 0 !important;"
        >
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
                :title="__('Drag to reorder columns', 'top-bar')"
                draggable="true"
                @dragstart="onDragStart(columnIndex, $event)"
                @dragend="onDragEnd"
              >
                {{ columnIndex + 1 }}
              </button>
            
            </div>

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

            <div class="item item-creator">
              <fieldset>
                <legend class="bold">{{ __('Size column', 'top-bar') }}</legend>
                <label>
                  <select
                    :value="column.size_percent"
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
                <legend class="bold">{{ __('Content position', 'top-bar') }}</legend>
                <select :value="column.content_position" @change="onColumnContentPositionChange(columnIndex, $event)">
                  <option value="left">{{ __('Left', 'top-bar') }}</option>
                  <option value="center">{{ __('Center', 'top-bar') }}</option>
                  <option value="right">{{ __('Right', 'top-bar') }}</option>
                </select>
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
            <div class="item item-creator">
              <button
                v-if="localBar.columns.length > 1"
                type="button"
                class="top-bar-btn top-bar-icons delete mask black remove empty"
                :title="__('Remove column', 'top-bar')"
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
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import type {
  Bar,
  BarColumn,
  ColumnType,
  ContactBarColumn,
  ContentPosition,
  SocialBarColumn,
} from '@/types'
import { __ } from '@wordpress/i18n'
import BasicSettingsSection from './BasicSettingsSection.vue'
import ColumnTypeSelector from './ColumnTypeSelector.vue'
import ContactColumnEditor from './ContactColumnEditor.vue'
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
      links: [{ platform: '', url: '' }],
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
}>()

const localBar = ref<Bar>(withColumns(cloneBar(props.bar)))
const isExpanded = ref(props.bar.admin_visibile !== false)

// Re-sync when the bar id or server column set changes (fetch, PUT response).
watch(
  () => [props.bar.id, JSON.stringify(props.bar.columns ?? null)] as const,
  () => {
    localBar.value = withColumns(cloneBar(props.bar))
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
  syncBarRootForPersist()
  emit('update', props.bar.id, localBar.value)
}

function handleDelete() {
  if (confirm(__('Are you sure you want to delete this bar?', 'top-bar'))) {
    emit('delete', props.bar.id)
  }
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
</style>
