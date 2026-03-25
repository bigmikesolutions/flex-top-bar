<template>
  <div id="top-bar" class="wrap">
    <h1>{{ __('Top Bar', 'top-bar') }}</h1>

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
            @click="handleAddBar"
          >
            {{ isAdding ? __('Adding...', 'top-bar') : __('Add new Top Bar', 'top-bar') }}
          </button>
        </div>

        <BarItem
          v-for="bar in bars"
          :key="bar.id"
          :bar="bar"
          :can-delete="bars.length > 1"
          :max-messages="featureFlags.max_messages"
          :schedule-enabled="featureFlags.schedule_enabled"
          @update="handleUpdateBar"
          @delete="handleDeleteBar"
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
import { __ } from '@wordpress/i18n'
import BarItem from '@/components/BarItem.vue'
import type { Bar } from '@/types'

const barsStore = useBarsStore()
const flagsStore = useFeatureFlagsStore()

const isAdding = ref(false)
const showMaxBarsWarning = ref(false)

const bars = computed(() => barsStore.bars)
const isLoading = computed(() => barsStore.loading || flagsStore.loading)
const featureFlags = computed(() => flagsStore.flags)
const canAddBar = computed(() => bars.value.length < featureFlags.value.max_bars)

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
</script>
