import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { Bar } from '@/types'
import { api } from '@/api/client'

export const useBarsStore = defineStore('bars', () => {
  // State
  const bars = ref<Bar[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  // Getters
  const activeBars = computed(() => bars.value.filter(bar => bar.visible))
  const barCount = computed(() => bars.value.length)

  // Actions
  async function fetchBars() {
    loading.value = true
    error.value = null
    try {
      bars.value = await api.getBars()
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to fetch bars'
      console.error('Failed to fetch bars:', e)
    } finally {
      loading.value = false
    }
  }

  async function createBar(bar: Partial<Bar> = {}) {
    loading.value = true
    error.value = null
    try {
      const newBar = await api.createBar(bar)
      bars.value.push(newBar)
      return newBar
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to create bar'
      console.error('Failed to create bar:', e)
      throw e
    } finally {
      loading.value = false
    }
  }

  async function updateBar(id: string, updates: Partial<Bar>) {
    loading.value = true
    error.value = null
    try {
      const updated = await api.updateBar(id, updates)
      const index = bars.value.findIndex(b => b.id === id)
      if (index !== -1) {
        bars.value[index] = updated
      }
      return updated
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to update bar'
      console.error('Failed to update bar:', e)
      throw e
    } finally {
      loading.value = false
    }
  }

  async function deleteBar(id: string) {
    loading.value = true
    error.value = null
    try {
      await api.deleteBar(id)
      bars.value = bars.value.filter(b => b.id !== id)
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to delete bar'
      console.error('Failed to delete bar:', e)
      throw e
    } finally {
      loading.value = false
    }
  }

  function clearError() {
    error.value = null
  }

  return {
    // State
    bars,
    loading,
    error,
    // Getters
    activeBars,
    barCount,
    // Actions
    fetchBars,
    createBar,
    updateBar,
    deleteBar,
    clearError,
  }
})
