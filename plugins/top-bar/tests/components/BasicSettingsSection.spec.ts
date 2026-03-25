import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import BasicSettingsSection from '@/components/BasicSettingsSection.vue'
import type { Bar, BarColumn } from '@/types'

vi.mock('@wordpress/i18n', () => ({
  __: (text: string) => text,
}))

describe('BasicSettingsSection', () => {
  const mockColumn: BarColumn = {
    id: 'col_test',
    type: 'text',
    effect: 'none',
    messages: ['Hello'],
    size_percent: 100,
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

  it('renders Basic settings title', () => {
    const wrapper = mount(BasicSettingsSection, {
      props: { modelValue: mockBar },
    })
    expect(wrapper.text()).toContain('Basic settings')
  })

  it('renders position select with bar id', () => {
    const wrapper = mount(BasicSettingsSection, {
      props: { modelValue: mockBar },
    })
    const select = wrapper.find('#position_bar_1')
    expect(select.exists()).toBe(true)
    expect((select.element as HTMLSelectElement).value).toBe('top')
  })

  it('emits save when position changes', async () => {
    const wrapper = mount(BasicSettingsSection, {
      props: { modelValue: mockBar },
    })
    await wrapper.find('#position_bar_1').setValue('bottom')
    expect(wrapper.emitted('save')).toBeTruthy()
  })

  it('emits save when background color changes', async () => {
    const wrapper = mount(BasicSettingsSection, {
      props: { modelValue: mockBar },
    })
    await wrapper.find('#bg_color_bar_1').setValue('#abcdef')
    expect(wrapper.emitted('save')).toBeTruthy()
  })
})
