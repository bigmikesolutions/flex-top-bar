import { describe, it, expect, beforeEach, vi, beforeAll } from 'vitest'
import type { Bar, FeatureFlags } from '@/types'

// Mock fetch
global.fetch = vi.fn()

// Mock window.flexTopBarConfig before importing
beforeAll(() => {
  global.window.flexTopBarConfig = {
    apiRoot: '/wp-json/flex-top-bar/v1',
    nonce: 'test-nonce-123',
  }
})

// Import after setting up window
const { api } = await import('@/api/client')

beforeEach(() => {
  vi.clearAllMocks()
})

describe('ApiClient', () => {
  describe('getBars', () => {
    it('fetches bars from API', async () => {
      const mockBars: Bar[] = [
        {
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
        scheduled_timezone: '',
          scheduled_timezone: '',
        },
      ]

      vi.mocked(fetch).mockResolvedValueOnce({
        ok: true,
        json: async () => mockBars,
      } as Response)

      const result = await api.getBars()

      expect(fetch).toHaveBeenCalledWith(
        '/wp-json/flex-top-bar/v1/bars',
        expect.objectContaining({
          headers: expect.objectContaining({
            'Content-Type': 'application/json',
          }),
        })
      )
      expect(result).toEqual(mockBars)
    })

    it('throws error when API returns error', async () => {
      vi.mocked(fetch).mockResolvedValueOnce({
        ok: false,
        status: 500,
        json: async () => ({ error: 'Server error' }),
      } as Response)

      await expect(api.getBars()).rejects.toThrow('Server error')
    })

    it('throws generic error when error response has no body', async () => {
      vi.mocked(fetch).mockResolvedValueOnce({
        ok: false,
        status: 500,
        json: async () => {
          throw new Error('Invalid JSON')
        },
      } as Response)

      await expect(api.getBars()).rejects.toThrow('HTTP Error 500')
    })
  })

  describe('getPublishedBars', () => {
    it('fetches published bars from API', async () => {
      const mockBars: Bar[] = []
      vi.mocked(fetch).mockResolvedValueOnce({
        ok: true,
        json: async () => mockBars,
      } as Response)

      const result = await api.getPublishedBars()

      expect(fetch).toHaveBeenCalledWith(
        '/wp-json/flex-top-bar/v1/published-bars',
        expect.objectContaining({
          headers: expect.objectContaining({
            'Content-Type': 'application/json',
          }),
        })
      )
      expect(result).toEqual(mockBars)
    })
  })

  describe('publish', () => {
    it('posts publish request', async () => {
      const published: Bar[] = []
      vi.mocked(fetch).mockResolvedValueOnce({
        ok: true,
        json: async () => published,
      } as Response)

      const result = await api.publish()

      expect(fetch).toHaveBeenCalledWith(
        '/wp-json/flex-top-bar/v1/publish',
        expect.objectContaining({
          method: 'POST',
          headers: expect.objectContaining({
            'Content-Type': 'application/json',
          }),
        })
      )
      expect(result).toEqual(published)
    })
  })

  describe('publishBar', () => {
    it('posts publishBar request', async () => {
      const publishedBar = { id: 'bar_1' } as Bar
      vi.mocked(fetch).mockResolvedValueOnce({
        ok: true,
        json: async () => publishedBar,
      } as Response)

      const result = await api.publishBar('bar_1')

      expect(fetch).toHaveBeenCalledWith(
        '/wp-json/flex-top-bar/v1/bars/bar_1/publish',
        expect.objectContaining({
          method: 'POST',
          headers: expect.objectContaining({
            'Content-Type': 'application/json',
          }),
        })
      )
      expect(result).toEqual(publishedBar)
    })
  })

  describe('createBar', () => {
    it('creates a new bar', async () => {
      const newBar: Partial<Bar> = {
        name: 'New Bar',
        position: 'top',
        messages: ['Welcome'],
      }

      const createdBar: Bar = {
        id: 'bar_new',
        name: 'New Bar',
        position: 'top',
        effect: 'none',
        messages: ['Welcome'],
        messages_mobile_visible: true,
        columns: [
          {
            id: 'col_new',
            type: 'text',
            effect: 'none',
            messages: ['Welcome'],
            size_percent: 100,
            content_position: 'center',
            messages_mobile_visible: true,
          },
        ],
        bg_color: '#1d2327',
        frame_color: '',
        frame_width: 0,
        hide_on_scroll: false,
        visible: true,
        admin_visibile: true,
        scheduled_enabled: false,
        scheduled_from_datetime: '',
        scheduled_to_datetime: '',
        scheduled_timezone: '',
      }

      vi.mocked(fetch).mockResolvedValueOnce({
        ok: true,
        json: async () => createdBar,
      } as Response)

      const result = await api.createBar(newBar)

      expect(fetch).toHaveBeenCalledWith(
        '/wp-json/flex-top-bar/v1/bars',
        expect.objectContaining({
          method: 'POST',
          body: JSON.stringify(newBar),
          headers: expect.objectContaining({
            'Content-Type': 'application/json',
          }),
        })
      )
      expect(result).toEqual(createdBar)
    })
  })

  describe('updateBar', () => {
    it('updates an existing bar', async () => {
      const updates: Partial<Bar> = {
        name: 'Updated Name',
        visible: false,
      }

      const updatedBar: Bar = {
        id: 'bar_1',
        name: 'Updated Name',
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
        visible: false,
        admin_visibile: true,
        scheduled_enabled: false,
        scheduled_from_datetime: '',
        scheduled_to_datetime: '',
        scheduled_timezone: '',
      }

      vi.mocked(fetch).mockResolvedValueOnce({
        ok: true,
        json: async () => updatedBar,
      } as Response)

      const result = await api.updateBar('bar_1', updates)

      expect(fetch).toHaveBeenCalledWith(
        '/wp-json/flex-top-bar/v1/bars/bar_1',
        expect.objectContaining({
          method: 'PUT',
          body: JSON.stringify(updates),
          headers: expect.objectContaining({
            'Content-Type': 'application/json',
          }),
        })
      )
      expect(result).toEqual(updatedBar)
    })
  })

  describe('deleteBar', () => {
    it('deletes a bar', async () => {
      vi.mocked(fetch).mockResolvedValueOnce({
        ok: true,
        status: 204,
      } as Response)

      const result = await api.deleteBar('bar_1')

      expect(fetch).toHaveBeenCalledWith(
        '/wp-json/flex-top-bar/v1/bars/bar_1',
        expect.objectContaining({
          method: 'DELETE',
          headers: expect.objectContaining({
            'Content-Type': 'application/json',
          }),
        })
      )
      expect(result).toBeNull()
    })
  })

  describe('getFeatureFlags', () => {
    it('fetches feature flags from API', async () => {
      const mockFlags: FeatureFlags = {
        max_bars: 3,
        max_messages: 5,
        max_columns: 4,
        schedule_enabled: true,
      }

      vi.mocked(fetch).mockResolvedValueOnce({
        ok: true,
        json: async () => mockFlags,
      } as Response)

      const result = await api.getFeatureFlags()

      expect(fetch).toHaveBeenCalledWith(
        '/wp-json/flex-top-bar/v1/feature-flags',
        expect.objectContaining({
          headers: expect.objectContaining({
            'Content-Type': 'application/json',
          }),
        })
      )
      expect(result).toEqual(mockFlags)
    })
  })

  describe('configuration', () => {
    it('uses default values when flexTopBarConfig is not set', async () => {
      delete (global.window as any).flexTopBarConfig

      vi.mocked(fetch).mockResolvedValueOnce({
        ok: true,
        json: async () => [],
      } as Response)

      // Create new instance to test constructor
      const { api: newApi } = await import('@/api/client')
      await newApi.getBars()

      expect(fetch).toHaveBeenCalledWith('/wp-json/flex-top-bar/v1/bars', {
        headers: {
          'Content-Type': 'application/json',
          'X-WP-Nonce': '',
        },
      })
    })
  })
})
