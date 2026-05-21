import { describe, it, expect, vi, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import CountdownDisplay from '@/components/CountdownDisplay.vue'
import type { CountdownBarColumn } from '@/types'

describe('CountdownDisplay', () => {
  afterEach(() => {
    vi.useRealTimers()
  })

  const baseColumn: CountdownBarColumn = {
    id: 'col_cd',
    type: 'countdown',
    counter_style: 'boxed',
    count_direction: 'down',
    countdown_to_datetime: '2026-12-31T23:59',
    countup_from_datetime: '',
    countdown_timezone: 'UTC',
    text: 'Sale ends in',
    text_position: 'before',
    background_color: '#111111',
    counter_color: '#ffffff',
    text_color: '#eeeeee',
    size_percent: 25,
    content_position: 'center',
    messages_mobile_visible: true,
  }

  const previewParts = { days: 1, hours: 2, minutes: 3, seconds: 4 }

  it('renders plain style as single formatted string', () => {
    const wrapper = mount(CountdownDisplay, {
      props: {
        column: { ...baseColumn, counter_style: 'plain' },
        previewParts,
      },
    })

    expect(wrapper.find('.top-bar-countdown-column--plain').exists()).toBe(true)
    expect(wrapper.find('.top-bar-countdown-column__plain').text()).toBe('1d 02h 03m 04s')
    expect(wrapper.find('.top-bar-countdown-column__unit').exists()).toBe(false)
  })

  it('renders boxed style with digit units', () => {
    const wrapper = mount(CountdownDisplay, {
      props: { column: baseColumn, previewParts },
    })

    expect(wrapper.find('.top-bar-countdown-column--boxed').exists()).toBe(true)
    const units = wrapper.findAll('.top-bar-countdown-column__unit')
    expect(units).toHaveLength(4)
    expect(units.map((u) => u.find('.top-bar-countdown-column__unit-label').text())).toEqual([
      'd',
      'h',
      'm',
      's',
    ])
    expect(wrapper.findAll('.top-bar-countdown-column__digit').map((d) => d.text()).join('')).toContain('01')
  })

  it('shows optional label text and text-after layout class', () => {
    const wrapper = mount(CountdownDisplay, {
      props: {
        column: { ...baseColumn, text_position: 'after', text: 'Ends soon' },
        previewParts,
      },
    })

    expect(wrapper.find('.top-bar-countdown-column__text').text()).toBe('Ends soon')
    expect(wrapper.find('.top-bar-countdown-column--text-after').exists()).toBe(true)
  })

  it('sets CSS variables for colors and boxed background', () => {
    const wrapper = mount(CountdownDisplay, {
      props: { column: baseColumn, previewParts },
    })

    const style = wrapper.find('.top-bar-countdown-column').attributes('style') ?? ''
    expect(style).toContain('--top-bar-countdown-text')
    expect(style).toContain('--top-bar-countdown-counter')
    expect(style).toContain('--top-bar-countdown-bg')
  })

  it('omits background CSS variable for plain style', () => {
    const wrapper = mount(CountdownDisplay, {
      props: {
        column: { ...baseColumn, counter_style: 'plain' },
        previewParts,
      },
    })

    const style = wrapper.find('.top-bar-countdown-column').attributes('style') ?? ''
    expect(style).not.toContain('--top-bar-countdown-bg')
  })

  it('ticks remaining time when previewParts is not provided', async () => {
    vi.useFakeTimers()
    vi.setSystemTime(new Date('2026-05-21T16:00:00Z'))

    const wrapper = mount(CountdownDisplay, {
      props: {
        column: {
          ...baseColumn,
          counter_style: 'plain',
          countdown_to_datetime: '2026-05-21T18:00',
        },
      },
    })

    expect(wrapper.find('.top-bar-countdown-column__plain').text()).toBe('0d 02h 00m 00s')

    vi.advanceTimersByTime(1000)
    await wrapper.vm.$nextTick()

    expect(wrapper.find('.top-bar-countdown-column__plain').text()).toBe('0d 01h 59m 59s')
  })
})
