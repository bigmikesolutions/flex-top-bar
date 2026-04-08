import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import TextColumnEditor from '@/components/TextColumnEditor.vue'
import type { Bar } from '@/types'

vi.mock('@wordpress/i18n', () => ({
  __: (text: string) => text,
  sprintf: (format: string, ...args: unknown[]) => {
    let s = format
    args.forEach((arg, i) => {
      s = s.replace(new RegExp(`%${i + 1}\\$d`, 'g'), String(arg))
    })
    return s
  },
}))

describe('TextColumnEditor', () => {
  const defaultProps = {
    barId: 'bar_1',
    columnId: 'col_1',
    effect: 'none' as Bar['effect'],
    messages: ['Hello', 'World'],
    maxMessages: 4,
  }

  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('renders effect select with bar and column id', () => {
    const wrapper = mount(TextColumnEditor, { props: defaultProps })

    const select = wrapper.find('#effect_bar_1_col_1')
    expect(select.exists()).toBe(true)
    expect((select.element as HTMLSelectElement).value).toBe('none')
  })

  it('renders one textarea per message', () => {
    const wrapper = mount(TextColumnEditor, { props: defaultProps })

    const textareas = wrapper.findAll('textarea')
    // When effect is "none", UI is a static single text field.
    expect(textareas).toHaveLength(1)
    expect(textareas[0].element.value).toBe('Hello')
  })

  it('renders all textareas when effect is not none', () => {
    const wrapper = mount(TextColumnEditor, {
      props: { ...defaultProps, effect: 'slider' },
    })

    const textareas = wrapper.findAll('textarea')
    expect(textareas).toHaveLength(2)
    expect(textareas[0].element.value).toBe('Hello')
    expect(textareas[1].element.value).toBe('World')
  })

  it('emits update with effect when effect changes', async () => {
    const wrapper = mount(TextColumnEditor, { props: defaultProps })

    await wrapper.find('#effect_bar_1_col_1').setValue('slider')

    expect(wrapper.emitted('update')).toBeTruthy()
    expect(wrapper.emitted('update')?.[0]).toEqual([{ effect: 'slider' }])
  })

  it('disables effect and forces none when only one message is allowed', () => {
    const wrapper = mount(TextColumnEditor, {
      props: { ...defaultProps, effect: 'slider', maxMessages: 1 },
    })

    const select = wrapper.find('#effect_bar_1_col_1')
    expect(select.attributes('disabled')).toBeDefined()
    expect((select.element as HTMLSelectElement).value).toBe('none')

    // Normalizes persisted value so parents don't keep a non-none effect.
    expect(wrapper.emitted('update')?.[0]).toEqual([{ effect: 'none' }])
  })

  it('does not emit effect changes when effect select is disabled', async () => {
    const wrapper = mount(TextColumnEditor, {
      props: { ...defaultProps, effect: 'none', maxMessages: 1 },
    })
    const select = wrapper.find('#effect_bar_1_col_1')
    await select.setValue('slider')
    expect(wrapper.emitted('update')).toBeFalsy()
  })

  it('emits patch on message input without commit', async () => {
    const wrapper = mount(TextColumnEditor, { props: defaultProps })

    const first = wrapper.findAll('textarea')[0]
    await first.setValue('Updated')

    expect(wrapper.emitted('patch')?.[0]).toEqual([
      { messages: ['Updated', 'World'] },
    ])
    expect(wrapper.emitted('commit')).toBeFalsy()
  })

  it('emits commit on textarea blur', async () => {
    const wrapper = mount(TextColumnEditor, { props: defaultProps })

    await wrapper.findAll('textarea')[0].trigger('blur')

    expect(wrapper.emitted('commit')).toBeTruthy()
  })

  it('sets add button tooltip with remaining and max text fields', () => {
    const wrapper = mount(TextColumnEditor, {
      props: {
        ...defaultProps,
        effect: 'slider',
        messages: ['Hello', 'World'],
        maxMessages: 4,
      },
    })
    const btn = wrapper.find('.top-bar-btn.amber.sm.right')
    expect(btn.attributes('title')).toBe(
      'Your plan allows you to add yet 2 more text field(s) out of 4. If you want to change limits, check other plans on the plugin page or contact us.',
    )
  })

  it('emits update with extended messages when add is clicked', async () => {
    const wrapper = mount(TextColumnEditor, {
      props: { ...defaultProps, effect: 'slider' },
    })

    await wrapper.find('.top-bar-btn.amber.sm.right').trigger('click')

    expect(wrapper.emitted('update')?.[0]).toEqual([
      { messages: ['Hello', 'World', ''] },
    ])
  })

  it('does not render add when max messages reached', () => {
    const wrapper = mount(TextColumnEditor, {
      props: {
        ...defaultProps,
        effect: 'slider',
        messages: ['1', '2', '3', '4'],
        maxMessages: 4,
      },
    })

    expect(wrapper.find('.top-bar-btn.amber.sm.right').exists()).toBe(false)
  })

  it('emits update to remove message when X is clicked on index > 0', async () => {
    const wrapper = mount(TextColumnEditor, {
      props: { ...defaultProps, effect: 'slider' },
    })

    const removeButtons = wrapper.findAll(
      '.top-bar-message-list button.top-bar-btn.top-bar-icons.delete.remove.empty'
    )
    expect(removeButtons).toHaveLength(2)
    await removeButtons[1].trigger('click')

    expect(wrapper.emitted('update')?.[0]).toEqual([{ messages: ['Hello'] }])
  })

  it('does not show remove buttons when only one message', () => {
    const wrapper = mount(TextColumnEditor, {
      props: {
        ...defaultProps,
        effect: 'slider',
        messages: ['Only'],
      },
    })

    const removeButtons = wrapper.findAll(
      '.top-bar-message-list button.top-bar-btn.top-bar-icons.delete.remove.empty'
    )
    expect(removeButtons).toHaveLength(0)
  })

  it('does not render add/remove controls when effect is none', () => {
    const wrapper = mount(TextColumnEditor, { props: defaultProps })

    expect(wrapper.find('.top-bar-btn.amber.sm.right').exists()).toBe(false)
    const removeButtons = wrapper.findAll(
      '.top-bar-message-list button.top-bar-btn.top-bar-icons.delete.remove.empty'
    )
    expect(removeButtons).toHaveLength(0)
  })
})
