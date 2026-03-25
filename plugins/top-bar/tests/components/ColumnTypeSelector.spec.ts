import { describe, it, expect, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import ColumnTypeSelector from '@/components/ColumnTypeSelector.vue'

vi.mock('@wordpress/i18n', () => ({
  __: (text: string) => text,
}))

describe('ColumnTypeSelector', () => {
  const defaultProps = {
    groupName: 'bar_1_col_1',
    columnType: 'text' as const,
  }

  it('renders type legend and text editor option', () => {
    const wrapper = mount(ColumnTypeSelector, { props: defaultProps })

    expect(wrapper.find('legend').text()).toBe('Type')
    expect(wrapper.text()).toContain('Text Editor')
    expect(wrapper.text()).toContain('Social media')
    expect(wrapper.text()).toContain('Contact data')
    expect(wrapper.find('input[type="radio"]').exists()).toBe(true)
  })

  it('uses item-creator wrapper for layout', () => {
    const wrapper = mount(ColumnTypeSelector, { props: defaultProps })

    expect(wrapper.find('.item-creator').exists()).toBe(true)
  })

  it('emits update:columnType when a different type is selected', async () => {
    const wrapper = mount(ColumnTypeSelector, { props: defaultProps })
    const radios = wrapper.findAll('input[type="radio"]')

    await radios[1].setValue('social')

    expect(wrapper.emitted('update:columnType')).toBeTruthy()
    expect(wrapper.emitted('update:columnType')?.[0]).toEqual(['social'])
  })
})
