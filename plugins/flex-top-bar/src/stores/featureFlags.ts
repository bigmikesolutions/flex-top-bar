import { defineStore } from 'pinia'
import { ref } from 'vue'
import type { FeatureFlags } from '@/types'
import { api } from '@/api/client'

export const useFeatureFlagsStore = defineStore('featureFlags', () => {
  // State
  const flags = ref<FeatureFlags>({
    plan_name: 'n/a',
    max_bars: 1,
    max_messages: 1,
    max_columns: 4,
    schedule_enabled: false,
  })
  const loading = ref(false)
  const error = ref<string | null>(null)

  // Actions
  async function fetchFlags() {
    loading.value = true
    error.value = null
    try {
      flags.value = await api.getFeatureFlags()
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to fetch feature flags'
      console.error('Failed to fetch feature flags:', e)
    } finally {
      loading.value = false
    }
  }

  return {
    // State
    flags,
    loading,
    error,
    // Actions
    fetchFlags,
  }
})
