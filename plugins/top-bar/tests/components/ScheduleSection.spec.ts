import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import ScheduleSection from '@/components/ScheduleSection.vue'
import type { Bar, BarColumn } from '@/types'

vi.mock('@wordpress/i18n', () => ({
  __: (text: string) => text,
}))

describe('ScheduleSection', () => {
  const mockColumn: BarColumn = {
    id: 'col_test',
    type: 'text',
    effect: 'none',
    messages: ['Hello'],
    size_percent: 100,
    content_position: 'center',
    messages_mobile_visible: true,
  }

  const mockBar: Bar = {
    id: 'bar_1',
    name: 'Test Bar',
    position: 'top',
    effect: 'none',
    messages: ['Hello'],
    messages_mobile_visible: true,
    columns: [mockColumn],
    bg_color: '#123456',
    frame_color: '#ff0000',
    frame_width: 2,
    hide_on_scroll: false,
    visible: true,
    admin_visibile: true,
    scheduled_enabled: false,
    scheduled_from_datetime: '',
    scheduled_to_datetime: '',
  }

  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('renders disabled scheduling row when scheduleEnabled is false', () => {
    const wrapper = mount(ScheduleSection, {
      props: {
        modelValue: mockBar,
        scheduleEnabled: false,
        sectionTooltip: "Your plan doesn't include scheduling.",
      },
    })
    expect(wrapper.text()).toContain('Scheduled')
    expect(wrapper.text()).toContain('Schedule when the bar should be visible.')
    const checkbox = wrapper.find('.top-bar-toggle-life-time')
    expect((checkbox.element as HTMLInputElement).disabled).toBe(true)
  })

  it('renders Scheduled label when scheduleEnabled is true', () => {
    const wrapper = mount(ScheduleSection, {
      props: { modelValue: mockBar, scheduleEnabled: true },
    })
    expect(wrapper.text()).toContain('Scheduled')
  })

  it('emits save when scheduled checkbox is toggled', async () => {
    const wrapper = mount(ScheduleSection, {
      props: { modelValue: mockBar, scheduleEnabled: true },
    })
    await wrapper.find('.top-bar-toggle-life-time').setChecked(true)
    expect(wrapper.emitted('save')).toBeTruthy()
  })

  it('shows datetime inputs when scheduled_enabled is true', () => {
    const wrapper = mount(ScheduleSection, {
      props: {
        modelValue: { ...mockBar, scheduled_enabled: true },
        scheduleEnabled: true,
      },
    })
    expect(wrapper.find('#scheduled_from_bar_1').exists()).toBe(true)
    expect(wrapper.find('#scheduled_to_bar_1').exists()).toBe(true)
  })

  it('emits save on datetime blur', async () => {
    const wrapper = mount(ScheduleSection, {
      props: {
        modelValue: { ...mockBar, scheduled_enabled: true },
        scheduleEnabled: true,
      },
    })
    const fromInput = wrapper.find('#scheduled_from_bar_1')
    await fromInput.setValue('2026-03-25T10:00')
    await fromInput.trigger('blur')
    expect(wrapper.emitted('save')).toBeTruthy()
  })
})
