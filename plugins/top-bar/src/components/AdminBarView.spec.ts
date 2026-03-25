import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import AdminBarView from './AdminBarView.vue'
import ColumnTypeSelector from './ColumnTypeSelector.vue'
import TextColumnEditor from './TextColumnEditor.vue'
import type { Bar, BarColumn } from '@/types'

// Mock @wordpress/i18n
vi.mock('@wordpress/i18n', () => ({
  __: (text: string) => text,
}))

describe('AdminBarView', () => {
  const mockColumn: BarColumn = {
    id: 'col_test',
    type: 'text',
    effect: 'none',
    messages: ['Hello', 'World'],
    size_percent: 100,
    messages_mobile_visible: true,
  }

  const mockBar: Bar = {
    id: 'bar_1',
    name: 'Test Bar',
    position: 'top',
    effect: 'none',
    messages: ['Hello', 'World'],
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

  const defaultProps = {
    bar: mockBar,
    canDelete: true,
    maxMessages: 4,
    scheduleEnabled: true,
  }

  function barWithColumnMessages(messages: string[]): Bar {
    return {
      ...mockBar,
      messages,
      columns: [{ ...mockColumn, messages }],
    }
  }

  beforeEach(() => {
    vi.clearAllMocks()
  })

  describe('rendering', () => {
    it('renders bar name', () => {
      const wrapper = mount(AdminBarView, { props: defaultProps })
      expect(wrapper.text()).toContain('Test Bar')
    })

    it('renders default name when bar name is empty', () => {
      const wrapper = mount(AdminBarView, {
        props: {
          ...defaultProps,
          bar: { ...mockBar, name: '' },
        },
      })
      expect(wrapper.text()).toContain('Top Bar')
    })

    it('renders expanded by default when admin_visibile is true', () => {
      const wrapper = mount(AdminBarView, { props: defaultProps })
      expect(wrapper.find('.top-bar-options').classes()).toContain('active')
    })

    it('renders collapsed when admin_visibile is false', () => {
      const wrapper = mount(AdminBarView, {
        props: {
          ...defaultProps,
          bar: { ...mockBar, admin_visibile: false },
        },
      })
      expect(wrapper.find('.top-bar-options').classes()).not.toContain('active')
    })
  })

  describe('visibility toggle', () => {
    it('shows status-on class when bar is visible', () => {
      const wrapper = mount(AdminBarView, { props: defaultProps })
      const toggleButton = wrapper.find('.top-bar-visibility-toggle')
      expect(toggleButton.classes()).toContain('status-on')
    })

    it('shows status-off class when bar is not visible', () => {
      const wrapper = mount(AdminBarView, {
        props: {
          ...defaultProps,
          bar: { ...mockBar, visible: false },
        },
      })
      const toggleButton = wrapper.find('.top-bar-visibility-toggle')
      expect(toggleButton.classes()).toContain('status-off')
    })

    it('emits update event when visibility is toggled', async () => {
      const wrapper = mount(AdminBarView, { props: defaultProps })
      const toggleButton = wrapper.find('.top-bar-visibility-toggle')

      await toggleButton.trigger('click')

      expect(wrapper.emitted('update')).toBeTruthy()
      const updateEvent = wrapper.emitted('update')?.[0]
      expect(updateEvent?.[0]).toBe('bar_1')
      expect(updateEvent?.[1]).toMatchObject({ visible: false })
    })
  })

  describe('delete button', () => {
    it('shows enabled delete button when canDelete is true', () => {
      const wrapper = mount(AdminBarView, { props: defaultProps })
      const deleteButton = wrapper.findAll('.delete').find(btn => !btn.attributes('disabled'))
      expect(deleteButton?.exists()).toBe(true)
    })

    it('shows disabled delete button when canDelete is false', () => {
      const wrapper = mount(AdminBarView, {
        props: { ...defaultProps, canDelete: false },
      })
      const deleteButton = wrapper.find('.delete[disabled]')
      expect(deleteButton.exists()).toBe(true)
      expect(deleteButton.attributes('title')).toBe('At least one bar is required')
    })

    it('emits delete event when delete is confirmed', async () => {
      global.confirm = vi.fn(() => true)

      const wrapper = mount(AdminBarView, { props: defaultProps })
      const deleteButton = wrapper.findAll('.delete').find(btn => !btn.attributes('disabled'))

      await deleteButton?.trigger('click')

      expect(global.confirm).toHaveBeenCalledWith('Are you sure you want to delete this bar?')
      expect(wrapper.emitted('delete')).toBeTruthy()
      expect(wrapper.emitted('delete')?.[0]).toEqual(['bar_1'])
    })

    it('does not emit delete event when delete is cancelled', async () => {
      global.confirm = vi.fn(() => false)

      const wrapper = mount(AdminBarView, { props: defaultProps })
      const deleteButton = wrapper.findAll('.delete').find(btn => !btn.attributes('disabled'))

      await deleteButton?.trigger('click')

      expect(wrapper.emitted('delete')).toBeFalsy()
    })
  })

  describe('expand/collapse', () => {
    it('toggles expansion when arrow button is clicked', async () => {
      const wrapper = mount(AdminBarView, { props: defaultProps })
      const arrowButton = wrapper.find('.top-bar-toggle-options')

      expect(wrapper.find('.top-bar-options').classes()).toContain('active')

      await arrowButton.trigger('click')

      expect(wrapper.emitted('update')).toBeTruthy()
      const updateEvent = wrapper.emitted('update')?.[0]
      expect(updateEvent?.[1]).toMatchObject({ admin_visibile: false })
    })

    it('sets aria-expanded correctly', async () => {
      const wrapper = mount(AdminBarView, { props: defaultProps })
      const arrowButton = wrapper.find('.top-bar-toggle-options')

      expect(arrowButton.attributes('aria-expanded')).toBe('true')

      await arrowButton.trigger('click')
      await wrapper.vm.$nextTick()

      expect(arrowButton.attributes('aria-expanded')).toBe('false')
    })
  })

  describe('column design (type + text editor)', () => {
    it('mounts ColumnTypeSelector and TextColumnEditor with bar data', () => {
      const wrapper = mount(AdminBarView, { props: defaultProps })

      expect(wrapper.findComponent(ColumnTypeSelector).exists()).toBe(true)
      const editor = wrapper.findComponent(TextColumnEditor)
      expect(editor.exists()).toBe(true)
      expect(editor.props('barId')).toBe('bar_1')
      expect(editor.props('columnId')).toBe('col_test')
      expect(editor.props('effect')).toBe('none')
      expect(editor.props('messages')).toEqual(['Hello', 'World'])
      expect(editor.props('maxMessages')).toBe(4)
    })
  })

  describe('form fields', () => {
    it('updates name field', async () => {
      const wrapper = mount(AdminBarView, { props: defaultProps })
      const nameInput = wrapper.find('input[type="text"]')

      await nameInput.setValue('New Name')
      await nameInput.trigger('blur')

      expect(wrapper.emitted('update')).toBeTruthy()
      const updateEvent = wrapper.emitted('update')?.[0]
      expect(updateEvent?.[1]).toMatchObject({ name: 'New Name' })
    })

    it('updates position select', async () => {
      const wrapper = mount(AdminBarView, { props: defaultProps })
      const positionSelect = wrapper.find('#position_bar_1')

      await positionSelect.setValue('bottom')

      expect(wrapper.emitted('update')).toBeTruthy()
      const updateEvent = wrapper.emitted('update')?.[0]
      expect(updateEvent?.[1]).toMatchObject({ position: 'bottom' })
    })

    it('updates background color', async () => {
      const wrapper = mount(AdminBarView, { props: defaultProps })
      const bgColorInput = wrapper.find('#bg_color_bar_1')

      await bgColorInput.setValue('#abcdef')

      expect(wrapper.emitted('update')).toBeTruthy()
      const updateEvent = wrapper.emitted('update')?.[0]
      expect(updateEvent?.[1]).toMatchObject({ bg_color: '#abcdef' })
    })

    it('updates frame color', async () => {
      const wrapper = mount(AdminBarView, { props: defaultProps })
      const frameColorInput = wrapper.find('#frame_color_bar_1')

      await frameColorInput.setValue('#00ff00')

      expect(wrapper.emitted('update')).toBeTruthy()
      const updateEvent = wrapper.emitted('update')?.[0]
      expect(updateEvent?.[1]).toMatchObject({ frame_color: '#00ff00' })
    })

    it('updates hide on scroll', async () => {
      const wrapper = mount(AdminBarView, { props: defaultProps })
      const hideOnScrollSelect = wrapper.find('#hide_on_scroll_bar_1')

      await hideOnScrollSelect.setValue('true')

      expect(wrapper.emitted('update')).toBeTruthy()
      const updateEvent = wrapper.emitted('update')?.[0]
      expect(updateEvent?.[1]).toMatchObject({ hide_on_scroll: true })
    })

    it('updates effect select', async () => {
      const wrapper = mount(AdminBarView, { props: defaultProps })
      const effectSelect = wrapper.findAll('select').find(s =>
        Array.from((s.element as HTMLSelectElement).options).some(o => o.value === 'slider')
      )

      await effectSelect?.setValue('slider')

      expect(wrapper.emitted('update')).toBeTruthy()
      const updateEvent = wrapper.emitted('update')?.[0]
      expect(updateEvent?.[1]).toMatchObject({ effect: 'slider' })
    })

    it('updates mobile visibility', async () => {
      const wrapper = mount(AdminBarView, { props: defaultProps })
      const mobileVisibleSelects = wrapper.findAll('select')
      const mobileVisibleSelect = mobileVisibleSelects.find(s =>
        Array.from((s.element as HTMLSelectElement).options).some(o => o.textContent === 'On')
      )

      await mobileVisibleSelect?.setValue('false')

      expect(wrapper.emitted('update')).toBeTruthy()
      const updateEvent = wrapper.emitted('update')?.[0]
      expect(updateEvent?.[1]).toMatchObject({ messages_mobile_visible: false })
    })
  })

  describe('messages', () => {
    it('renders all messages', () => {
      const wrapper = mount(AdminBarView, { props: defaultProps })
      const textareas = wrapper.findAll('textarea')

      expect(textareas).toHaveLength(2)
      expect(textareas[0].element.value).toBe('Hello')
      expect(textareas[1].element.value).toBe('World')
    })

    it('updates message content', async () => {
      const wrapper = mount(AdminBarView, {
        props: {
          ...defaultProps,
          bar: barWithColumnMessages(['Hello', 'World']),
        },
      })
      const firstTextarea = wrapper.findAll('textarea')[0]

      await firstTextarea.setValue('Updated message')
      await firstTextarea.trigger('blur')

      expect(wrapper.emitted('update')).toBeTruthy()
      const updateEvent = wrapper.emitted('update')?.[0]
      expect(updateEvent?.[1]).toMatchObject({
        messages: ['Updated message', 'World'],
      })
    })

    it('adds new message when button is clicked', async () => {
      const wrapper = mount(AdminBarView, {
        props: {
          ...defaultProps,
          bar: barWithColumnMessages(['Hello', 'World']),
        },
      })
      const addButton = wrapper.find('.top-bar-btn.amber.sm.right')

      await addButton.trigger('click')

      expect(wrapper.emitted('update')).toBeTruthy()
      const updateEvents = wrapper.emitted('update')
      const lastEvent = updateEvents?.[updateEvents.length - 1]
      expect(lastEvent?.[1]).toMatchObject({
        messages: ['Hello', 'World', ''],
      })
    })

    it('does not show add button when max messages reached', () => {
      const wrapper = mount(AdminBarView, {
        props: {
          ...defaultProps,
          bar: barWithColumnMessages(['1', '2', '3', '4']),
          maxMessages: 4,
        },
      })

      const addButton = wrapper.find('.top-bar-btn.amber.sm.right')
      expect(addButton.exists()).toBe(false)
    })

    it('removes message when X button is clicked', async () => {
      const wrapper = mount(AdminBarView, {
        props: {
          ...defaultProps,
          bar: barWithColumnMessages(['Hello', 'World']),
        },
      })
      // Find X buttons - when there are multiple messages, all show X buttons (v-if="localBar.messages.length > 1")
      const removeButtons = wrapper.findAll('.top-bar-message-list .top-bar-btn.amber.sm').filter(
        btn => btn.text() === 'X'
      )

      // Should have 2 X buttons (both messages show button when length > 1)
      expect(removeButtons).toHaveLength(2)
      // Click the second message's X button (index 1) - only index > 0 can be removed
      await removeButtons[1].trigger('click')

      expect(wrapper.emitted('update')).toBeTruthy()
      const updateEvents = wrapper.emitted('update')
      const lastEvent = updateEvents?.[updateEvents.length - 1]
      expect(lastEvent?.[1]).toMatchObject({
        messages: ['Hello'],
      })
    })

    it('does not show remove button on first message when only one exists', () => {
      const wrapper = mount(AdminBarView, {
        props: {
          ...defaultProps,
          bar: barWithColumnMessages(['Only one']),
        },
      })

      const removeButtons = wrapper.findAll('.top-bar-message-list .top-bar-btn.amber.sm').filter(
        btn => btn.text() === 'X'
      )
      expect(removeButtons).toHaveLength(0)
    })
  })

  describe('scheduling', () => {
    it('shows scheduling section when scheduleEnabled is true', () => {
      const wrapper = mount(AdminBarView, { props: defaultProps })
      expect(wrapper.text()).toContain('Scheduled')
    })

    it('hides scheduling section when scheduleEnabled is false', () => {
      const wrapper = mount(AdminBarView, {
        props: { ...defaultProps, scheduleEnabled: false },
      })
      expect(wrapper.text()).not.toContain('Scheduled')
    })

    it('toggles scheduled_enabled', async () => {
      const wrapper = mount(AdminBarView, { props: defaultProps })
      const checkbox = wrapper.find('.top-bar-toggle-life-time')

      await checkbox.setChecked(true)

      expect(wrapper.emitted('update')).toBeTruthy()
      const updateEvent = wrapper.emitted('update')?.[0]
      expect(updateEvent?.[1]).toMatchObject({ scheduled_enabled: true })
    })

    it('shows datetime inputs when scheduled_enabled is true', async () => {
      const wrapper = mount(AdminBarView, {
        props: {
          ...defaultProps,
          bar: { ...mockBar, scheduled_enabled: true },
        },
      })

      expect(wrapper.find('#scheduled_from_bar_1').exists()).toBe(true)
      expect(wrapper.find('#scheduled_to_bar_1').exists()).toBe(true)
    })

    it('hides datetime inputs when scheduled_enabled is false', () => {
      const wrapper = mount(AdminBarView, { props: defaultProps })

      expect(wrapper.find('#scheduled_from_bar_1').exists()).toBe(false)
      expect(wrapper.find('#scheduled_to_bar_1').exists()).toBe(false)
    })

    it('updates scheduled_from_datetime', async () => {
      const wrapper = mount(AdminBarView, {
        props: {
          ...defaultProps,
          bar: { ...mockBar, scheduled_enabled: true },
        },
      })

      const fromInput = wrapper.find('#scheduled_from_bar_1')
      await fromInput.setValue('2026-03-25T10:00')
      await fromInput.trigger('blur')

      expect(wrapper.emitted('update')).toBeTruthy()
      const updateEvent = wrapper.emitted('update')?.[0]
      expect(updateEvent?.[1]).toMatchObject({
        scheduled_from_datetime: '2026-03-25T10:00',
      })
    })

    it('updates scheduled_to_datetime', async () => {
      const wrapper = mount(AdminBarView, {
        props: {
          ...defaultProps,
          bar: { ...mockBar, scheduled_enabled: true },
        },
      })

      const toInput = wrapper.find('#scheduled_to_bar_1')
      await toInput.setValue('2026-03-25T18:00')
      await toInput.trigger('blur')

      expect(wrapper.emitted('update')).toBeTruthy()
      const updateEvent = wrapper.emitted('update')?.[0]
      expect(updateEvent?.[1]).toMatchObject({
        scheduled_to_datetime: '2026-03-25T18:00',
      })
    })
  })

  describe('reactivity', () => {
    it('updates localBar when bar id changes', async () => {
      const wrapper = mount(AdminBarView, { props: defaultProps })

      const newBar = { ...mockBar, id: 'bar_2', name: 'New Bar' }
      await wrapper.setProps({ bar: newBar })

      expect(wrapper.text()).toContain('New Bar')
    })
  })
})
