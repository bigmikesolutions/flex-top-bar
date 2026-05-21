import { describe, it, expect, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import CountdownColumnEditor from '@/components/CountdownColumnEditor.vue'
import type { CountdownBarColumn } from '@/types'

vi.mock('@wordpress/i18n', () => ({
  __: (text: string) => text,
}))

vi.mock('@/components/TimezoneSelect.vue', () => ({
  default: {
    name: 'TimezoneSelect',
    template: '<select />',
    props: ['selectId'],
  },
}))

describe('CountdownColumnEditor', () => {
  const column: CountdownBarColumn = {
    id: 'col_1',
    type: 'countdown',
    counter_style: 'boxed',
    count_direction: 'down',
    countdown_to_datetime: '2026-06-01T12:00',
    countup_from_datetime: '',
    countdown_timezone: 'UTC',
    text: 'Sale ends in',
    text_position: 'before',
    background_color: '#111111',
    counter_color: '#ffffff',
    text_color: '#222222',
    size_percent: 50,
    content_position: 'center',
    messages_mobile_visible: true,
  }

  it('renders style, direction, calendars, text, and colors', () => {
    const wrapper = mount(CountdownColumnEditor, {
      props: { barId: 'bar_1', columnId: 'col_1', column },
    })

    expect(wrapper.text()).toContain('Counter style')
    expect(wrapper.text()).toContain('Plain text')
    expect(wrapper.text()).toContain('Digits with background')
    expect(wrapper.text()).toContain('Count down until promotion ends')
    expect(wrapper.text()).toContain('Count up from promotion start')
    expect(wrapper.findAll('input[type="datetime-local"]').length).toBe(2)
    expect(wrapper.find('input[type="text"]').exists()).toBe(true)
    expect(wrapper.findAll('input[type="color"]').length).toBe(3)
  })

  it('hides background color when plain style is selected', async () => {
    const wrapper = mount(CountdownColumnEditor, {
      props: {
        barId: 'bar_1',
        columnId: 'col_1',
        column: { ...column, counter_style: 'plain' },
      },
    })

    expect(wrapper.findAll('input[type="color"]').length).toBe(2)
  })

  it('emits patch when text changes', async () => {
    const wrapper = mount(CountdownColumnEditor, {
      props: { barId: 'bar_1', columnId: 'col_1', column },
    })

    await wrapper.find('input[type="text"]').setValue('Ends soon')

    expect(wrapper.emitted('patch')?.some((args) => (args[0] as { text?: string }).text === 'Ends soon')).toBe(
      true,
    )
  })
})
