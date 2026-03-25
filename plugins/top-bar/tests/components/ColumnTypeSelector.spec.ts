import { describe, it, expect, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import ColumnTypeSelector from '@/components/ColumnTypeSelector.vue'

vi.mock('@wordpress/i18n', () => ({
  __: (text: string) => text,
}))

describe('ColumnTypeSelector', () => {
  it('renders type legend and text editor option', () => {
    const wrapper = mount(ColumnTypeSelector)

    expect(wrapper.find('legend').text()).toBe('Type')
    expect(wrapper.text()).toContain('Text Editor')
    expect(wrapper.find('input[type="radio"]').exists()).toBe(true)
  })

  it('uses item-creator wrapper for layout', () => {
    const wrapper = mount(ColumnTypeSelector)

    expect(wrapper.find('.item-creator').exists()).toBe(true)
  })
})
