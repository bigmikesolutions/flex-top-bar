import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import TopBarView from '@/components/TopBarView.vue'
import type { Bar } from '@/types'
import * as scheduleDateTime from '@/utils/scheduleDateTime'

// Mock fetch
global.fetch = vi.fn()

vi.mock('@/utils/scheduleDateTime', async (importOriginal) => {
  const actual = await importOriginal<typeof scheduleDateTime>()
  return {
    ...actual,
    getVisitorScheduleTimezone: vi.fn(() => 'UTC'),
    isWithinScheduleWindowForVisitor: vi.fn((from: string, to: string, storedTimezone = '', nowMs?: number) =>
      actual.isWithinScheduleWindowForVisitor(from, to, storedTimezone, nowMs),
    ),
  }
})

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
      scheduled_timezone: '',
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

  it('shows bars inside visitor schedule window', async () => {
    vi.mocked(scheduleDateTime.isWithinScheduleWindowForVisitor).mockReturnValue(true)

    const scheduledBar = {
      ...mockBars[0],
      scheduled_enabled: true,
      scheduled_from_datetime: '2026-03-25T10:00',
      scheduled_to_datetime: '2026-03-25T18:00',
      scheduled_timezone: 'Europe/Warsaw',
    }

    vi.mocked(fetch).mockResolvedValueOnce({
      ok: true,
      json: async () => [scheduledBar],
    } as Response)

    const wrapper = mount(TopBarView)
    await new Promise(resolve => setTimeout(resolve, 50))
    await wrapper.vm.$nextTick()

    expect(scheduleDateTime.isWithinScheduleWindowForVisitor).toHaveBeenCalledWith(
      '2026-03-25T10:00',
      '2026-03-25T18:00',
      'Europe/Warsaw',
    )
    expect(wrapper.find('.top-bar').exists()).toBe(true)
  })

  it('filters out bars outside schedule window', async () => {
    vi.mocked(scheduleDateTime.isWithinScheduleWindowForVisitor).mockReturnValue(false)

    const scheduledBar = {
      ...mockBars[0],
      scheduled_enabled: true,
      scheduled_from_datetime: '2099-01-01T00:00',
      scheduled_to_datetime: '2099-12-31T23:59',
      scheduled_timezone: 'UTC',
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
      scheduled_timezone: 'UTC',
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

  it('shows only the first message for "none" effect', async () => {
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

    expect(wrapper.text()).toContain('Hello')
    expect(wrapper.text()).not.toContain('World')
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

  const iconColumnBar: Bar = {
    ...mockBars[0],
    effect: 'none',
    messages: ['', ''],
    columns: [
      {
        id: 'col_icon',
        type: 'icon',
        icon_attachment_id: 1,
        icon_url: 'http://example.test/icon-64x64.png',
        text: 'Free delivery',
        icon_position: 'before',
        size_percent: 100,
        content_position: 'center',
        messages_mobile_visible: true,
      },
    ],
  }

  it('renders icon column with image and text', async () => {
    vi.mocked(fetch).mockResolvedValueOnce({
      ok: true,
      json: async () => [iconColumnBar],
    } as Response)

    const wrapper = mount(TopBarView)
    await wrapper.vm.$nextTick()
    await new Promise(resolve => setTimeout(resolve, 0))

    const col = wrapper.find('.top-bar-icon-text-column')
    expect(col.exists()).toBe(true)
    expect(col.classes()).toContain('top-bar-icon-text-column--icon-before')
    expect(col.find('.top-bar-icon-text-column__img').attributes('src'))
      .toBe('http://example.test/icon-64x64.png')
    expect(col.text()).toContain('Free delivery')
  })

  it('applies icon-after class when icon_position is after', async () => {
    const barAfter: Bar = {
      ...iconColumnBar,
      columns: [
        {
          ...(iconColumnBar.columns[0] as typeof iconColumnBar.columns[0]),
          icon_position: 'after',
        },
      ],
    }

    vi.mocked(fetch).mockResolvedValueOnce({
      ok: true,
      json: async () => [barAfter],
    } as Response)

    const wrapper = mount(TopBarView)
    await wrapper.vm.$nextTick()
    await new Promise(resolve => setTimeout(resolve, 0))

    expect(wrapper.find('.top-bar-icon-text-column').classes())
      .toContain('top-bar-icon-text-column--icon-after')
  })

  const countdownColumnBar: Bar = {
    ...mockBars[0],
    effect: 'none',
    messages: ['', ''],
    columns: [
      {
        id: 'col_countdown',
        type: 'countdown',
        counter_style: 'boxed',
        count_direction: 'down',
        countdown_to_datetime: '2099-01-01T00:00',
        countup_from_datetime: '',
        countdown_timezone: 'UTC',
        text: 'Ends in',
        text_position: 'before',
        background_color: '#000000',
        counter_color: '#ffffff',
        text_color: '#ffffff',
        size_percent: 100,
        content_position: 'center',
        messages_mobile_visible: true,
      },
    ],
  }

  it('renders countdown column with label and boxed timer', async () => {
    const wrapper = mount(TopBarView, {
      props: {
        barsOverride: [countdownColumnBar],
        preview: true,
      },
    })
    await wrapper.vm.$nextTick()

    const col = wrapper.find('.top-bar-countdown-column')
    expect(col.exists()).toBe(true)
    expect(col.classes()).toContain('top-bar-countdown-column--boxed')
    expect(col.find('.top-bar-countdown-column__text').text()).toBe('Ends in')
    expect(col.findAll('.top-bar-countdown-column__unit').length).toBeGreaterThan(0)
  })

  it('renders plain countdown column in preview mode', async () => {
    const wrapper = mount(TopBarView, {
      props: {
        barsOverride: [
          {
            ...countdownColumnBar,
            columns: [
              {
                ...(countdownColumnBar.columns[0] as typeof countdownColumnBar.columns[0]),
                counter_style: 'plain',
              },
            ],
          },
        ],
        preview: true,
      },
    })
    await wrapper.vm.$nextTick()

    expect(wrapper.find('.top-bar-countdown-column--plain').exists()).toBe(true)
    expect(wrapper.find('.top-bar-countdown-column__plain').exists()).toBe(true)
  })

  it('renders icon column in preview mode via barsOverride', async () => {
    const wrapper = mount(TopBarView, {
      props: {
        barsOverride: [iconColumnBar],
        preview: true,
      },
    })
    await wrapper.vm.$nextTick()

    expect(wrapper.find('.top-bar-icon-text-column').exists()).toBe(true)
    expect(wrapper.find('.top-bar-container--preview').exists()).toBe(true)
    expect(fetch).not.toHaveBeenCalled()
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
