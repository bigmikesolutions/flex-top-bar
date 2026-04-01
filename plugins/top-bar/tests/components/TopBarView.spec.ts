import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import TopBarView from '@/components/TopBarView.vue'
import type { Bar } from '@/types'

// Mock fetch
global.fetch = vi.fn()

describe('TopBarView', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  const mockBars: Bar[] = [
    {
      id: 'bar_top',
      name: 'Test',
      position: 'top',
      effect: 'none',
      messages: ['Welcome to our site'],
      messages_mobile_visible: true,
      columns: [
        {
          id: 'col_top',
          type: 'text',
          effect: 'none',
          messages: ['Welcome to our site'],
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
    },
  ]

  it('renders nothing when no bars are returned from API', async () => {
    vi.mocked(fetch).mockResolvedValueOnce({
      ok: true,
      json: async () => [],
    } as Response)

    const wrapper = mount(TopBarView)
    await wrapper.vm.$nextTick()
    await new Promise(resolve => setTimeout(resolve, 0))

    expect(wrapper.find('.top-bar-container').exists()).toBe(false)
  })

  it('renders bar when API returns visible bar', async () => {
    vi.mocked(fetch).mockResolvedValueOnce({
      ok: true,
      json: async () => mockBars,
    } as Response)

    const wrapper = mount(TopBarView)
    await wrapper.vm.$nextTick()
    await new Promise(resolve => setTimeout(resolve, 0))

    expect(wrapper.find('.top-bar-container').exists()).toBe(true)
    expect(wrapper.find('.top-bar').exists()).toBe(true)
    expect(wrapper.text()).toContain('Welcome to our site')
  })

  it('applies correct CSS classes based on bar position', async () => {
    vi.mocked(fetch).mockResolvedValueOnce({
      ok: true,
      json: async () => mockBars,
    } as Response)

    const wrapper = mount(TopBarView)
    await wrapper.vm.$nextTick()
    await new Promise(resolve => setTimeout(resolve, 0))

    expect(wrapper.find('.top-bar--top').exists()).toBe(true)
  })

  it('applies background color style', async () => {
    vi.mocked(fetch).mockResolvedValueOnce({
      ok: true,
      json: async () => mockBars,
    } as Response)

    const wrapper = mount(TopBarView)
    await wrapper.vm.$nextTick()
    await new Promise(resolve => setTimeout(resolve, 0))

    const bar = wrapper.find('.top-bar')
    expect(bar.attributes('style')).toContain('background-color: #123456')
  })

  it('applies frame border when frame_width > 0', async () => {
    const barWithFrame = {
      ...mockBars[0],
      frame_color: '#ff0000',
      frame_width: 2,
    }

    vi.mocked(fetch).mockResolvedValueOnce({
      ok: true,
      json: async () => [barWithFrame],
    } as Response)

    const wrapper = mount(TopBarView)
    await wrapper.vm.$nextTick()
    await new Promise(resolve => setTimeout(resolve, 0))

    const bar = wrapper.find('.top-bar')
    expect(bar.attributes('style')).toContain('border: 2px solid #ff0000')
  })

  it('hides column on mobile when messages_mobile_visible is false', async () => {
    const mobileHiddenBar: Bar = {
      ...mockBars[0],
      messages_mobile_visible: false,
      columns: [
        {
          ...mockBars[0].columns[0],
          messages_mobile_visible: false,
        },
      ],
    }

    vi.mocked(fetch).mockResolvedValueOnce({
      ok: true,
      json: async () => [mobileHiddenBar],
    } as Response)

    const wrapper = mount(TopBarView)
    await wrapper.vm.$nextTick()
    await new Promise(resolve => setTimeout(resolve, 0))

    expect(wrapper.find('.top-bar__column--mobile-hidden').exists()).toBe(true)
  })

  it('filters out invisible bars', async () => {
    const invisibleBar = {
      ...mockBars[0],
      visible: false,
    }

    vi.mocked(fetch).mockResolvedValueOnce({
      ok: true,
      json: async () => [invisibleBar],
    } as Response)

    const wrapper = mount(TopBarView)
    await wrapper.vm.$nextTick()
    await new Promise(resolve => setTimeout(resolve, 0))

    expect(wrapper.find('.top-bar').exists()).toBe(false)
  })

  it('filters out bars outside schedule window', async () => {
    const scheduledBar = {
      ...mockBars[0],
      scheduled_enabled: true,
      scheduled_from_datetime: '2099-01-01T00:00',
      scheduled_to_datetime: '2099-12-31T23:59',
    }

    vi.mocked(fetch).mockResolvedValueOnce({
      ok: true,
      json: async () => [scheduledBar],
    } as Response)

    const wrapper = mount(TopBarView)
    await wrapper.vm.$nextTick()
    await new Promise(resolve => setTimeout(resolve, 0))

    // Bar should not be visible (future date)
    expect(wrapper.find('.top-bar').exists()).toBe(false)
  })

  it('shows bars when scheduling is disabled', async () => {
    const scheduledBar = {
      ...mockBars[0],
      scheduled_enabled: false, // Scheduling disabled - should always show
      scheduled_from_datetime: '2099-01-01T00:00',
      scheduled_to_datetime: '2099-12-31T23:59',
    }

    vi.mocked(fetch).mockResolvedValueOnce({
      ok: true,
      json: async () => [scheduledBar],
    } as Response)

    const wrapper = mount(TopBarView)

    // Wait for fetch to complete and component to update
    await new Promise(resolve => setTimeout(resolve, 50))
    await wrapper.vm.$nextTick()

    // Bar should be visible (scheduling disabled)
    expect(wrapper.find('.top-bar').exists()).toBe(true)
  })

  it('concatenates messages for "none" effect', async () => {
    const multiMessageBar: Bar = {
      ...mockBars[0],
      effect: 'none',
      messages: ['Hello', 'World', 'Test'],
      columns: [
        {
          id: 'col_top',
          type: 'text',
          effect: 'none',
          messages: ['Hello', 'World', 'Test'],
          size_percent: 100,
          content_position: 'center',
          messages_mobile_visible: true,
        },
      ],
    }

    vi.mocked(fetch).mockResolvedValueOnce({
      ok: true,
      json: async () => [multiMessageBar],
    } as Response)

    const wrapper = mount(TopBarView)
    await wrapper.vm.$nextTick()
    await new Promise(resolve => setTimeout(resolve, 0))

    expect(wrapper.text()).toContain('Hello World Test')
  })

  it('handles API error gracefully', async () => {
    const consoleError = vi.spyOn(console, 'error').mockImplementation(() => {})

    vi.mocked(fetch).mockResolvedValueOnce({
      ok: false,
      status: 500,
    } as Response)

    const wrapper = mount(TopBarView)
    await wrapper.vm.$nextTick()
    await new Promise(resolve => setTimeout(resolve, 0))

    expect(wrapper.find('.top-bar').exists()).toBe(false)
    expect(consoleError).toHaveBeenCalledWith(
      'Failed to load top bars:',
      expect.any(Error)
    )

    consoleError.mockRestore()
  })

  it('sets correct data attributes on bar element', async () => {
    vi.mocked(fetch).mockResolvedValueOnce({
      ok: true,
      json: async () => mockBars,
    } as Response)

    const wrapper = mount(TopBarView)
    await wrapper.vm.$nextTick()
    await new Promise(resolve => setTimeout(resolve, 0))

    const bar = wrapper.find('.top-bar')
    expect(bar.attributes('data-top-bar-id')).toBe('bar_top')
    expect(bar.attributes('data-top-bar-position')).toBe('top')
    expect(bar.attributes('data-top-bar-effect')).toBe('none')
    expect(bar.attributes('data-top-bar-hide-on-scroll')).toBe('0')
  })
})
