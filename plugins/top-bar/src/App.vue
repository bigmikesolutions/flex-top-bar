<template>
  <div id="top-bar" class="wrap">
    <h1 class="top-bar-admin-title">
      {{ __('Flex Top Bar', 'top-bar') }}
      <span v-if="pluginVersion" class="top-bar-admin-version">v{{ pluginVersion }}</span>
    </h1>

    <!-- Loading state -->
    <div v-if="isLoading" class="notice notice-info">
      <p>{{ __('Loading...', 'top-bar') }}</p>
    </div>

    <!-- Error state -->
    <div v-else-if="barsStore.error" class="notice notice-error is-dismissible">
      <p>{{ barsStore.error }}</p>
      <button type="button" class="notice-dismiss" @click="barsStore.clearError">
        <span class="screen-reader-text">{{ __('Dismiss this notice.', 'top-bar') }}</span>
      </button>
    </div>

    <!-- Main content -->
    <template v-else>
      <!-- Max bars warning -->
      <div v-if="showMaxBarsWarning" class="notice notice-warning">
        <p>
          {{ __('You can add at most %d top bars.', 'top-bar').replace('%d', String(featureFlags.max_bars)) }}
        </p>
      </div>

      <!-- Empty state -->
      <div v-if="bars.length === 0" class="top-bar-row center empty">
        <p class="xlg bold">{{ __('Welcome to Top Bar plugin', 'top-bar') }}</p>
        <p class="xs">{{ __('Click the button to add your first Top Bar', 'top-bar') }}</p>
        <button
          v-if="canAddBar"
          type="button"
          class="top-bar-btn mint md"
          :title="addBarTooltip"
          @click="handleAddBar"
        >
          {{ __('Add new Top Bar', 'top-bar') }}
        </button>
      </div>

      <!-- Bar list -->
      <template v-else>
        <div class="top-bar-row rt">
          <button
            v-if="canAddBar"
            type="button"
            class="top-bar-btn mint sm"
            :disabled="isAdding"
            :title="addBarTooltip"
            @click="handleAddBar"
          >
            {{ isAdding ? __('Adding...', 'top-bar') : __('Add new Top Bar', 'top-bar') }}
          </button>
        </div>

        <BarItem
          v-for="bar in bars"
          :key="bar.id"
          :bar="bar"
          :published-bar="publishedBarsById.get(bar.id)"
          :can-delete="bars.length > 1"
          :max-messages="featureFlags.max_messages"
          :max-columns="featureFlags.max_columns"
          :schedule-enabled="featureFlags.schedule_enabled"
          @update="handleUpdateBar"
          @delete="handleDeleteBar"
          @publish="handlePublish"
        />

        <div class="top-bar-row rt">
          <p class="description">
            {{ __('Changes are saved automatically.', 'top-bar') }}
          </p>
        </div>
      </template>
    </template>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useBarsStore } from '@/stores/bars'
import { useFeatureFlagsStore } from '@/stores/featureFlags'
import { __, sprintf } from '@wordpress/i18n'
import BarItem from '@/components/AdminBarView.vue'
import type { Bar } from '@/types'

const barsStore = useBarsStore()
const flagsStore = useFeatureFlagsStore()

const isAdding = ref(false)
const showMaxBarsWarning = ref(false)
const pluginVersion = (window.topBarConfig?.version || '').trim()

const bars = computed(() => barsStore.bars)
const publishedBarsById = computed(() => {
  const map = new Map<string, Bar>()
  for (const b of barsStore.publishedBars) {
    map.set(b.id, b)
  }
  return map
})
const isLoading = computed(() => barsStore.loading || flagsStore.loading)
const featureFlags = computed(() => flagsStore.flags)
const canAddBar = computed(() => bars.value.length < featureFlags.value.max_bars)

const addBarTooltip = computed(() => {
  const maxBars = featureFlags.value.max_bars
  const remaining = Math.max(0, maxBars - bars.value.length)
  const lead = sprintf(
    __(
      'Your plan allows you to add yet %1$d more top bar(s) out of %2$d.',
      'top-bar',
    ),
    remaining,
    maxBars,
  )
  const tail = __(
    'If you want to change limits, check other plans on the plugin page or contact us.',
    'top-bar',
  )
  return `${lead} ${tail}`
})

onMounted(async () => {
  await Promise.all([
    barsStore.fetchBars(),
    flagsStore.fetchFlags(),
  ])
})

async function handleAddBar() {
  if (!canAddBar.value) {
    showMaxBarsWarning.value = true
    setTimeout(() => {
      showMaxBarsWarning.value = false
    }, 5000)
    return
  }

  isAdding.value = true
  try {
    await barsStore.createBar()
  } catch (error) {
    console.error('Failed to add bar:', error)
  } finally {
    isAdding.value = false
  }
}

async function handleUpdateBar(id: string, updates: Partial<Bar>) {
  try {
    await barsStore.updateBar(id, updates)
  } catch (error) {
    console.error('Failed to update bar:', error)
  }
}

async function handleDeleteBar(id: string) {
  try {
    await barsStore.deleteBar(id)
  } catch (error) {
    console.error('Failed to delete bar:', error)
  }
}

async function handlePublish(id: string) {
  try {
    await barsStore.publishBar(id)
  } catch (error) {
    console.error('Failed to publish:', error)
  }
}
</script>

<style scoped>
.top-bar-admin-title {
  display: flex;
  align-items: baseline;
  gap: 10px;
}

.top-bar-admin-version {
  font-size: 12px;
  font-weight: 600;
  opacity: 0.7;
}
</style>
