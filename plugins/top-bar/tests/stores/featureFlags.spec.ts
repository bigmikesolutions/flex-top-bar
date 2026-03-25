import { describe, it, expect, beforeEach, vi } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useFeatureFlagsStore } from '@/stores/featureFlags'
import { api } from '@/api/client'
import type { FeatureFlags } from '@/types'

vi.mock('@/api/client', () => ({
  api: {
    getFeatureFlags: vi.fn(),
  },
}))

describe('useFeatureFlagsStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  describe('initial state', () => {
    it('has default feature flags', () => {
      const store = useFeatureFlagsStore()
      expect(store.flags).toEqual({
        max_bars: 1,
        max_messages: 1,
        schedule_enabled: false,
      })
    })

    it('is not loading initially', () => {
      const store = useFeatureFlagsStore()
      expect(store.loading).toBe(false)
    })

    it('has no error initially', () => {
      const store = useFeatureFlagsStore()
      expect(store.error).toBeNull()
    })
  })

  describe('fetchFlags', () => {
    it('fetches feature flags successfully', async () => {
      const mockFlags: FeatureFlags = {
        max_bars: 5,
        max_messages: 10,
        schedule_enabled: true,
      }

      vi.mocked(api.getFeatureFlags).mockResolvedValueOnce(mockFlags)

      const store = useFeatureFlagsStore()
      await store.fetchFlags()

      expect(store.flags).toEqual(mockFlags)
      expect(store.loading).toBe(false)
      expect(store.error).toBeNull()
    })

    it('sets loading state during fetch', async () => {
      vi.mocked(api.getFeatureFlags).mockImplementationOnce(
        () =>
          new Promise(resolve =>
            setTimeout(
              () =>
                resolve({
                  max_bars: 1,
                  max_messages: 1,
                  schedule_enabled: false,
                }),
              100
            )
          )
      )

      const store = useFeatureFlagsStore()
      const promise = store.fetchFlags()

      expect(store.loading).toBe(true)
      await promise
      expect(store.loading).toBe(false)
    })

    it('handles fetch error', async () => {
      const consoleError = vi.spyOn(console, 'error').mockImplementation(() => {})
      vi.mocked(api.getFeatureFlags).mockRejectedValueOnce(new Error('Network error'))

      const store = useFeatureFlagsStore()
      await store.fetchFlags()

      expect(store.error).toBe('Network error')
      expect(store.loading).toBe(false)
      expect(consoleError).toHaveBeenCalled()

      consoleError.mockRestore()
    })

    it('clears previous error on successful fetch', async () => {
      const mockFlags: FeatureFlags = {
        max_bars: 3,
        max_messages: 5,
        schedule_enabled: true,
      }

      const store = useFeatureFlagsStore()
      store.error = 'Previous error'

      vi.mocked(api.getFeatureFlags).mockResolvedValueOnce(mockFlags)
      await store.fetchFlags()

      expect(store.error).toBeNull()
    })

    it('retains old flags on error', async () => {
      const consoleError = vi.spyOn(console, 'error').mockImplementation(() => {})
      const initialFlags = { ...useFeatureFlagsStore().flags }

      vi.mocked(api.getFeatureFlags).mockRejectedValueOnce(new Error('Failed'))

      const store = useFeatureFlagsStore()
      await store.fetchFlags()

      expect(store.flags).toEqual(initialFlags)

      consoleError.mockRestore()
    })
  })
})
