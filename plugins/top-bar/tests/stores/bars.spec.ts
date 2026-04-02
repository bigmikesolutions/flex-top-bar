import { describe, it, expect, beforeEach, vi } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useBarsStore } from '@/stores/bars'
import { api } from '@/api/client'
import type { Bar } from '@/types'

vi.mock('@/api/client', () => ({
  api: {
    getBars: vi.fn(),
    getPublishedBars: vi.fn(),
    createBar: vi.fn(),
    updateBar: vi.fn(),
    deleteBar: vi.fn(),
    publish: vi.fn(),
    publishBar: vi.fn(),
  },
}))

describe('useBarsStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  const mockBar: Bar = {
    id: 'bar_1',
    name: 'Test Bar',
    position: 'top',
    effect: 'none',
    messages: ['Hello'],
    messages_mobile_visible: true,
    columns: [
      {
        id: 'col_1',
        type: 'text',
        effect: 'none',
        messages: ['Hello'],
        size_percent: 100,
        content_position: 'center',
        messages_mobile_visible: true,
      },
    ],
    bg_color: '#123456',
    frame_color: '',
    frame_width: 0,
    hide_on_scroll: false,
    visible: true,
    admin_visibile: true,
    scheduled_enabled: false,
    scheduled_from_datetime: '',
    scheduled_to_datetime: '',
  }

  describe('initial state', () => {
    it('has empty bars array', () => {
      const store = useBarsStore()
      expect(store.bars).toEqual([])
    })

    it('is not loading initially', () => {
      const store = useBarsStore()
      expect(store.loading).toBe(false)
    })

    it('has no error initially', () => {
      const store = useBarsStore()
      expect(store.error).toBeNull()
    })
  })

  describe('getters', () => {
    it('activeBars returns only visible bars', () => {
      const store = useBarsStore()
      store.bars = [
        { ...mockBar, id: 'bar_1', visible: true },
        { ...mockBar, id: 'bar_2', visible: false },
        { ...mockBar, id: 'bar_3', visible: true },
      ]

      expect(store.activeBars).toHaveLength(2)
      expect(store.activeBars.map(b => b.id)).toEqual(['bar_1', 'bar_3'])
    })

    it('barCount returns total number of bars', () => {
      const store = useBarsStore()
      store.bars = [mockBar, mockBar, mockBar]

      expect(store.barCount).toBe(3)
    })
  })

  describe('fetchBars', () => {
    it('fetches bars successfully', async () => {
      const bars = [mockBar]
      vi.mocked(api.getBars).mockResolvedValueOnce(bars)
      vi.mocked(api.getPublishedBars).mockResolvedValueOnce(bars)

      const store = useBarsStore()
      await store.fetchBars()

      expect(store.bars).toEqual(bars)
      expect(store.publishedBars).toEqual(bars)
      expect(store.loading).toBe(false)
      expect(store.error).toBeNull()
    })

    it('sets loading state during fetch', async () => {
      vi.mocked(api.getBars).mockImplementationOnce(
        () => new Promise(resolve => setTimeout(() => resolve([]), 100))
      )
      vi.mocked(api.getPublishedBars).mockResolvedValueOnce([])

      const store = useBarsStore()
      const promise = store.fetchBars()

      expect(store.loading).toBe(true)
      await promise
      expect(store.loading).toBe(false)
    })

    it('handles fetch error', async () => {
      const consoleError = vi.spyOn(console, 'error').mockImplementation(() => {})
      vi.mocked(api.getBars).mockRejectedValueOnce(new Error('Network error'))

      const store = useBarsStore()
      await store.fetchBars()

      expect(store.bars).toEqual([])
      expect(store.error).toBe('Network error')
      expect(store.loading).toBe(false)
      expect(consoleError).toHaveBeenCalled()

      consoleError.mockRestore()
    })
  })

  describe('publish', () => {
    it('calls api.publish', async () => {
      vi.mocked(api.publish).mockResolvedValueOnce([mockBar])
      const store = useBarsStore()
      await store.publish()
      expect(api.publish).toHaveBeenCalledTimes(1)
    })

    it('sets error on publish failure', async () => {
      const consoleError = vi.spyOn(console, 'error').mockImplementation(() => {})
      vi.mocked(api.publish).mockRejectedValueOnce(new Error('Publish failed'))
      const store = useBarsStore()
      await expect(store.publish()).rejects.toThrow('Publish failed')
      expect(store.error).toBe('Publish failed')
      consoleError.mockRestore()
    })
  })

  describe('publishBar', () => {
    it('calls api.publishBar with id', async () => {
      vi.mocked(api.publishBar).mockResolvedValueOnce(mockBar)
      const store = useBarsStore()
      await store.publishBar('bar_1')
      expect(api.publishBar).toHaveBeenCalledWith('bar_1')
      expect(store.publishedBars).toEqual([mockBar])
    })

    it('sets error on publishBar failure', async () => {
      const consoleError = vi.spyOn(console, 'error').mockImplementation(() => {})
      vi.mocked(api.publishBar).mockRejectedValueOnce(new Error('Publish failed'))
      const store = useBarsStore()
      await expect(store.publishBar('bar_1')).rejects.toThrow('Publish failed')
      expect(store.error).toBe('Publish failed')
      consoleError.mockRestore()
    })
  })

  describe('createBar', () => {
    it('creates a bar successfully', async () => {
      const newBar = { ...mockBar, id: 'bar_new' }
      vi.mocked(api.createBar).mockResolvedValueOnce(newBar)

      const store = useBarsStore()
      const result = await store.createBar({ name: 'New Bar' })

      expect(result).toEqual(newBar)
      expect(store.bars).toHaveLength(1)
      expect(store.bars[0]).toEqual(newBar)
      expect(store.loading).toBe(false)
      expect(store.error).toBeNull()
    })

    it('handles create error and throws', async () => {
      const consoleError = vi.spyOn(console, 'error').mockImplementation(() => {})
      vi.mocked(api.createBar).mockRejectedValueOnce(new Error('Create failed'))

      const store = useBarsStore()

      await expect(store.createBar({})).rejects.toThrow('Create failed')
      expect(store.error).toBe('Create failed')
      expect(store.loading).toBe(false)

      consoleError.mockRestore()
    })
  })

  describe('updateBar', () => {
    it('updates a bar successfully', async () => {
      const updatedBar = { ...mockBar, name: 'Updated' }
      vi.mocked(api.updateBar).mockResolvedValueOnce(updatedBar)

      const store = useBarsStore()
      store.bars = [mockBar]

      const result = await store.updateBar('bar_1', { name: 'Updated' })

      expect(result).toEqual(updatedBar)
      expect(store.bars[0].name).toBe('Updated')
      expect(store.error).toBeNull()
    })

    it('does not update bars array if bar not found', async () => {
      const updatedBar = { ...mockBar, id: 'bar_2' }
      vi.mocked(api.updateBar).mockResolvedValueOnce(updatedBar)

      const store = useBarsStore()
      store.bars = [mockBar]

      await store.updateBar('bar_2', { name: 'Updated' })

      expect(store.bars).toHaveLength(1)
      expect(store.bars[0].id).toBe('bar_1')
    })

    it('handles update error and throws', async () => {
      const consoleError = vi.spyOn(console, 'error').mockImplementation(() => {})
      vi.mocked(api.updateBar).mockRejectedValueOnce(new Error('Update failed'))

      const store = useBarsStore()
      store.bars = [mockBar]

      await expect(store.updateBar('bar_1', {})).rejects.toThrow('Update failed')
      expect(store.error).toBe('Update failed')

      consoleError.mockRestore()
    })

    it('does not set loading state during update', async () => {
      vi.mocked(api.updateBar).mockResolvedValueOnce(mockBar)

      const store = useBarsStore()
      store.bars = [mockBar]

      await store.updateBar('bar_1', {})

      // Loading should remain false for updates (responsive UI)
      expect(store.loading).toBe(false)
    })
  })

  describe('deleteBar', () => {
    it('deletes a bar successfully', async () => {
      vi.mocked(api.deleteBar).mockResolvedValueOnce(undefined)

      const store = useBarsStore()
      store.bars = [mockBar, { ...mockBar, id: 'bar_2' }]

      await store.deleteBar('bar_1')

      expect(store.bars).toHaveLength(1)
      expect(store.bars[0].id).toBe('bar_2')
      expect(store.loading).toBe(false)
      expect(store.error).toBeNull()
    })

    it('handles delete error and throws', async () => {
      const consoleError = vi.spyOn(console, 'error').mockImplementation(() => {})
      vi.mocked(api.deleteBar).mockRejectedValueOnce(new Error('Delete failed'))

      const store = useBarsStore()
      store.bars = [mockBar]

      await expect(store.deleteBar('bar_1')).rejects.toThrow('Delete failed')
      expect(store.error).toBe('Delete failed')
      expect(store.bars).toHaveLength(1) // Bar not removed on error
      expect(store.loading).toBe(false)

      consoleError.mockRestore()
    })
  })

  describe('clearError', () => {
    it('clears the error message', () => {
      const store = useBarsStore()
      store.error = 'Some error'

      store.clearError()

      expect(store.error).toBeNull()
    })
  })
})
