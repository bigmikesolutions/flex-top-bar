import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import AdminBarView from '@/components/AdminBarView.vue'
import ColumnTypeSelector from '@/components/ColumnTypeSelector.vue'
import CountdownColumnEditor from '@/components/CountdownColumnEditor.vue'
import IconColumnEditor from '@/components/IconColumnEditor.vue'
import TextColumnEditor from '@/components/TextColumnEditor.vue'
import type { Bar, BarColumn, CountdownBarColumn, IconBarColumn, TextBarColumn } from '@/types'

// Mock @wordpress/i18n
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

describe('AdminBarView', () => {
  const mockColumn: TextBarColumn = {
    id: 'col_test',
    type: 'text',
    effect: 'none',
    messages: ['Hello', 'World'],
    size_percent: 100,
    content_position: 'center',
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
    scheduled_timezone: '',
  }

  const defaultProps = {
    bar: mockBar,
    publishedBar: mockBar,
    canDelete: true,
    maxMessages: 4,
    maxColumns: 4,
    scheduleEnabled: true,
  }

  function barWithColumnMessages(messages: string[]): Bar {
    return {
      ...mockBar,
      effect: 'slider',
      messages,
      columns: [{ ...mockColumn, effect: 'slider', messages }],
    }
  }

  beforeEach(() => {
    vi.clearAllMocks()
  })

  describe('AdminBarView', () => {
    it('renders bar name', () => {
      const wrapper = mount(AdminBarView, { props: defaultProps })
      expect(wrapper.text()).toContain('Test Bar')
    })

    it('renders one layout column row per bar column', () => {
      const col2: BarColumn = {
        id: 'col_test_2',
        type: 'text',
        effect: 'blink',
        messages: ['Second', ''],
        size_percent: 50,
        content_position: 'center',
        messages_mobile_visible: true,
      }
      const barTwoCols: Bar = {
        ...mockBar,
        messages: ['Hello', 'World'],
        columns: [mockColumn, col2],
      }
      const wrapper = mount(AdminBarView, {
        props: { ...defaultProps, bar: barTwoCols },
      })
      const creator = wrapper.find('.top-bar-column-creator')
      expect(creator.exists()).toBe(true)
      const directLayoutRows = creator.element.querySelectorAll(':scope > .top-bar-column-creator-grid')
      expect(directLayoutRows.length).toBe(2)
    })

    it('sets add column button tooltip with remaining slots when under max columns', () => {
      const wrapper = mount(AdminBarView, { props: defaultProps })
      const btn = wrapper.find('.title-with-action__btn .top-bar-btn.mint.sm')
      expect(btn.attributes('title')).toBe(
        'Your plan allows you to add yet 3 more column(s) out of 4. If you want to change limits, check other plans on the plugin page or contact us.',
      )
    })

    it('sets add column button tooltip when at max columns', () => {
      const col = (id: string): BarColumn => ({
        ...mockColumn,
        id,
      })
      const barFourCols: Bar = {
        ...mockBar,
        columns: [col('c1'), col('c2'), col('c3'), col('c4')],
      }
      const wrapper = mount(AdminBarView, {
        props: { ...defaultProps, bar: barFourCols, maxColumns: 4 },
      })
      const btn = wrapper.find('.title-with-action__btn .top-bar-btn.mint.sm')
      expect(btn.attributes('disabled')).toBeDefined()
      expect(btn.attributes('title')).toBe(
        'You have reached the maximum of 4 columns for your plan. If you want to change limits, check other plans on the plugin page or contact us.',
      )
    })

    it('renders default name when bar name is empty', () => {
      const wrapper = mount(AdminBarView, {
        props: {
          ...defaultProps,
          bar: { ...mockBar, name: '' },
        },
      })
      expect(wrapper.text()).toContain('Flex Top Bar')
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

  describe('publish', () => {
    it('emits publish when publish button is clicked', async () => {
      // confirm() is used by the component in the browser.
      vi.stubGlobal('confirm', () => true)
      const wrapper = mount(AdminBarView, { props: defaultProps })
      const btn = wrapper.find('button.top-bar-icons.publish')
      expect(btn.exists()).toBe(true)
      await btn.trigger('click')
      expect(wrapper.emitted('publish')).toBeTruthy()
      expect(wrapper.emitted('publish')?.[0]).toEqual(['bar_1'])
    })

    it('adds dirty class when draft differs from published', () => {
      const wrapper = mount(AdminBarView, {
        props: {
          ...defaultProps,
          bar: { ...mockBar, bg_color: '#111111' },
          publishedBar: { ...mockBar, bg_color: '#222222' },
        },
      })
      const btn = wrapper.find('button.top-bar-icons.publish')
      expect(btn.classes()).toContain('top-bar-publish--dirty')
    })

    it('shows pending-publish tooltip when draft differs from published', () => {
      const wrapper = mount(AdminBarView, {
        props: {
          ...defaultProps,
          bar: { ...mockBar, bg_color: '#111111' },
          publishedBar: { ...mockBar, bg_color: '#222222' },
        },
      })
      const btn = wrapper.find('button.top-bar-icons.publish')
      expect(btn.attributes('title')).toBe('Pending changes ready to be published')
      expect(btn.attributes('aria-label')).toBe('Pending changes ready to be published')
    })

    it('shows no-changes tooltip when draft matches published', () => {
      const wrapper = mount(AdminBarView, { props: defaultProps })
      const btn = wrapper.find('button.top-bar-icons.publish')
      expect(btn.attributes('title')).toBe('There are no changes to publish')
      expect(btn.attributes('aria-label')).toBe('There are no changes to publish')
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

    it('shows hide tooltip when bar is visible', () => {
      const wrapper = mount(AdminBarView, { props: defaultProps })
      const toggleButton = wrapper.find('.top-bar-visibility-toggle')
      expect(toggleButton.attributes('title')).toBe('Hide this bar on the site')
      expect(toggleButton.attributes('aria-label')).toBe('Hide this bar on the site')
    })

    it('shows show tooltip when bar is hidden', () => {
      const wrapper = mount(AdminBarView, {
        props: {
          ...defaultProps,
          bar: { ...mockBar, visible: false },
        },
      })
      const toggleButton = wrapper.find('.top-bar-visibility-toggle')
      expect(toggleButton.attributes('title')).toBe('Show this bar on the site')
      expect(toggleButton.attributes('aria-label')).toBe('Show this bar on the site')
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
      expect(deleteButton?.attributes('title')).toBe('Delete this top bar')
      expect(deleteButton?.attributes('aria-label')).toBe('Delete this top bar')
    })

    it('shows disabled delete button when canDelete is false', () => {
      const wrapper = mount(AdminBarView, {
        props: { ...defaultProps, canDelete: false },
      })
      const deleteButton = wrapper.find('.delete[disabled]')
      expect(deleteButton.exists()).toBe(true)
      expect(deleteButton.attributes('title')).toBe(
        'Cannot delete: at least one top bar must remain',
      )
      expect(deleteButton.attributes('aria-label')).toBe(
        'Cannot delete: at least one top bar must remain',
      )
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

  describe('icon column', () => {
    const iconColumn: IconBarColumn = {
      id: 'col_icon',
      type: 'icon',
      icon_attachment_id: 12,
      icon_url: 'http://example.test/icon.png',
      text: 'Sale',
      icon_position: 'after',
      size_percent: 100,
      content_position: 'left',
      messages_mobile_visible: true,
    }

    const iconBar: Bar = {
      ...mockBar,
      effect: 'none',
      messages: ['', ''],
      columns: [iconColumn],
    }

    it('mounts IconColumnEditor for icon column type', () => {
      const wrapper = mount(AdminBarView, {
        props: { ...defaultProps, bar: iconBar },
      })

      const editor = wrapper.findComponent(IconColumnEditor)
      expect(editor.exists()).toBe(true)
      expect(editor.props('column')).toMatchObject({
        type: 'icon',
        text: 'Sale',
        icon_position: 'after',
      })
      expect(wrapper.findComponent(TextColumnEditor).exists()).toBe(false)
    })

    it('persists icon_position after when IconColumnEditor commits', async () => {
      const wrapper = mount(AdminBarView, {
        props: { ...defaultProps, bar: { ...iconBar, columns: [{ ...iconColumn, icon_position: 'before' }] } },
      })

      const editor = wrapper.findComponent(IconColumnEditor)
      await editor.vm.$emit('patch', { icon_position: 'after' })
      await editor.vm.$emit('commit')

      const updates = wrapper.emitted('update')
      expect(updates).toBeTruthy()
      const last = updates?.[updates.length - 1]?.[1] as Partial<Bar>
      const col = (last.columns?.[0] ?? {}) as IconBarColumn
      expect(col.icon_position).toBe('after')
    })

    it('resets to default icon column when type changes to icon', async () => {
      const wrapper = mount(AdminBarView, { props: defaultProps })
      const selector = wrapper.findComponent(ColumnTypeSelector)

      await selector.vm.$emit('update:columnType', 'icon')

      const updates = wrapper.emitted('update')
      expect(updates).toBeTruthy()
      const last = updates?.[updates.length - 1]?.[1] as Partial<Bar>
      const col = (last.columns?.[0] ?? {}) as IconBarColumn
      expect(col.type).toBe('icon')
      expect(col.icon_attachment_id).toBe(0)
      expect(col.icon_url).toBe('')
      expect(col.icon_position).toBe('before')
    })

    it('renders CountdownColumnEditor for countdown columns', () => {
      const countdownColumn: CountdownBarColumn = {
        id: 'col_countdown',
        type: 'countdown',
        counter_style: 'boxed',
        count_direction: 'down',
        countdown_to_datetime: '2026-12-01T10:00',
        countup_from_datetime: '',
        countdown_timezone: 'UTC',
        text: 'Sale',
        text_position: 'before',
        background_color: '#1d2327',
        counter_color: '#ffffff',
        text_color: '#1d2327',
        size_percent: 100,
        content_position: 'center',
        messages_mobile_visible: true,
      }
      const wrapper = mount(AdminBarView, {
        props: {
          ...defaultProps,
          bar: { ...mockBar, columns: [countdownColumn] },
        },
      })

      expect(wrapper.findComponent(CountdownColumnEditor).exists()).toBe(true)
      expect(wrapper.findComponent(TextColumnEditor).exists()).toBe(false)
    })

    it('resets to default countdown column when type changes to countdown', async () => {
      const wrapper = mount(AdminBarView, { props: defaultProps })
      const selector = wrapper.findComponent(ColumnTypeSelector)

      await selector.vm.$emit('update:columnType', 'countdown')

      const updates = wrapper.emitted('update')
      expect(updates).toBeTruthy()
      const last = updates?.[updates.length - 1]?.[1] as Partial<Bar>
      const col = (last.columns?.[0] ?? {}) as CountdownBarColumn
      expect(col.type).toBe('countdown')
      expect(col.counter_style).toBe('boxed')
      expect(col.count_direction).toBe('down')
      expect(col.countdown_to_datetime).toBe('')
      expect(col.countup_from_datetime).toBe('')
      expect(col.text_position).toBe('before')
    })
  })

  describe('form fields', () => {
    it('forces column size to 100% and disables size select when maxColumns is 1', () => {
      const barOneColNonFull: Bar = {
        ...mockBar,
        columns: [{ ...mockColumn, size_percent: 50 }],
      }
      const wrapper = mount(AdminBarView, {
        props: { ...defaultProps, bar: barOneColNonFull, maxColumns: 1 },
      })

      const sizeSelect = wrapper
        .findAll('fieldset')
        .find((f) => f.text().includes('Size column'))
        ?.find('select')

      expect(sizeSelect).toBeTruthy()
      expect(sizeSelect?.attributes('disabled')).toBeDefined()
      expect((sizeSelect?.element as HTMLSelectElement).value).toBe('100')
    })

    it('normalizes emitted columns[*].size_percent to 100 when maxColumns is 1', async () => {
      const barOneColNonFull: Bar = {
        ...mockBar,
        columns: [{ ...mockColumn, size_percent: 33 }],
      }
      const wrapper = mount(AdminBarView, {
        props: { ...defaultProps, bar: barOneColNonFull, maxColumns: 1 },
      })

      const nameInput = wrapper.find('input[type="text"]')
      await nameInput.trigger('blur')

      const updateEvents = wrapper.emitted('update')
      expect(updateEvents).toBeTruthy()
      const lastEvent = updateEvents?.[updateEvents.length - 1]
      expect(lastEvent?.[0]).toBe('bar_1')
      expect(lastEvent?.[1]).toMatchObject({
        columns: [{ size_percent: 100 }],
      })
    })

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
      const wrapper = mount(AdminBarView, {
        props: { ...defaultProps, bar: barWithColumnMessages(['Hello', 'World']) },
      })
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
      const removeButtons = wrapper.findAll(
        '.top-bar-message-list button.top-bar-btn.top-bar-icons.delete.remove.empty'
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

    it('shows scheduling as unavailable when scheduleEnabled is false', () => {
      const wrapper = mount(AdminBarView, {
        props: { ...defaultProps, scheduleEnabled: false },
      })
      expect(wrapper.text()).toContain('Scheduled')
      expect(wrapper.text()).toContain('Schedule when the bar should be visible.')
      const checkbox = wrapper.find('.top-bar-toggle-life-time')
      expect((checkbox.element as HTMLInputElement).disabled).toBe(true)
    })

    it('toggles scheduled_enabled', async () => {
      const wrapper = mount(AdminBarView, { props: defaultProps })
      const checkbox = wrapper.find('.top-bar-toggle-life-time')

      ;(checkbox.element as HTMLInputElement).checked = true
      await checkbox.trigger('change')
      await wrapper.vm.$nextTick()

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
      await wrapper.vm.$nextTick()

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
      await wrapper.vm.$nextTick()

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
      await wrapper.vm.$nextTick()

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
