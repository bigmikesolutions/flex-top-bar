import { describe, it, expect, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import TimezoneSelect from '@/components/TimezoneSelect.vue'

describe('TimezoneSelect', () => {
  it('keeps explicit UTC selection', async () => {
    vi.spyOn(Intl.DateTimeFormat.prototype, 'resolvedOptions').mockReturnValue({
      locale: 'en-US',
      calendar: 'gregory',
      numberingSystem: 'latn',
      timeZone: 'Europe/Warsaw',
    })

    const wrapper = mount(TimezoneSelect, {
      props: {
        modelValue: 'Europe/Warsaw',
        selectId: 'tz_test',
      },
    })

    const select = wrapper.find('select')
    await select.setValue('UTC')

    expect((select.element as HTMLSelectElement).value).toBe('UTC')
    expect(wrapper.emitted('change')).toBeTruthy()
  })
})
