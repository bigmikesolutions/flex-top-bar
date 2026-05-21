import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import IconColumnEditor from '@/components/IconColumnEditor.vue'
import type { IconBarColumn } from '@/types'

vi.mock('@wordpress/i18n', () => ({
  __: (text: string) => text,
  sprintf: (format: string, ...args: (string | number)[]) => {
    let i = 0
    return format.replace(/%[0-9]*\$?[sd]/g, () => String(args[i++]))
  },
}))

const openIconPicker = vi.fn()

vi.mock('@/composables/useWpMedia', () => ({
  useWpMedia: () => ({
    limits: {
      value: {
        maxWidth: 64,
        maxHeight: 64,
        maxFileBytes: 524288,
        allowedMimeTypes: ['image/png'],
        displaySizePx: 24,
      },
    },
    openIconPicker,
  }),
}))

describe('IconColumnEditor', () => {
  const column: IconBarColumn = {
    id: 'col_1',
    type: 'icon',
    icon_attachment_id: 0,
    icon_url: '',
    text: '',
    icon_position: 'before',
    size_percent: 50,
    content_position: 'center',
    messages_mobile_visible: true,
  }

  beforeEach(() => {
    openIconPicker.mockClear()
  })

  it('renders icon position, media actions, and text field', () => {
    const wrapper = mount(IconColumnEditor, {
      props: { barId: 'bar_1', columnId: 'col_1', column },
    })

    expect(wrapper.text()).toContain('Icon position')
    expect(wrapper.text()).toContain('Before text')
    expect(wrapper.text()).toContain('After text')
    expect(wrapper.text()).toContain('Select icon')
    expect(wrapper.find('input[type="text"]').exists()).toBe(true)
  })

  it('emits patch when text changes', async () => {
    const wrapper = mount(IconColumnEditor, {
      props: { barId: 'bar_1', columnId: 'col_1', column },
    })

    await wrapper.find('input[type="text"]').setValue('Hello')

    expect(wrapper.emitted('patch')?.[0]).toEqual([{ text: 'Hello' }])
  })

  it('emits patch and commit when icon position changes', async () => {
    const wrapper = mount(IconColumnEditor, {
      props: { barId: 'bar_1', columnId: 'col_1', column },
    })

    const afterRadio = wrapper.findAll('input[type="radio"]').find(
      (r) => (r.element as HTMLInputElement).value === 'after',
    )
    expect(afterRadio).toBeDefined()
    await afterRadio!.setValue(true)

    expect(wrapper.emitted('patch')?.at(-1)).toEqual([{ icon_position: 'after' }])
    expect(wrapper.emitted('commit')).toBeTruthy()
  })

  it('opens media picker on select icon click', async () => {
    const wrapper = mount(IconColumnEditor, {
      props: { barId: 'bar_1', columnId: 'col_1', column },
    })

    await wrapper.get('button.top-bar-btn.mint').trigger('click')

    expect(openIconPicker).toHaveBeenCalledTimes(1)
  })
})
