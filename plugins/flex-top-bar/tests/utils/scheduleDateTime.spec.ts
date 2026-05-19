import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import {
  buildTimezoneOptions,
  formatTimezoneLabel,
  fromDatetimeLocalValue,
  getBrowserTimezone,
  getDefaultScheduleTimezone,
  getSiteTimezone,
  isWithinScheduleWindow,
  toDatetimeLocalValue,
  wallClockToTimestamp,
} from '@/utils/scheduleDateTime'

describe('scheduleDateTime', () => {
  beforeEach(() => {
    vi.useFakeTimers()
    vi.setSystemTime(new Date('2026-03-25T09:30:00Z'))
  })

  afterEach(() => {
    vi.useRealTimers()
    vi.restoreAllMocks()
    delete window.flexTopBarConfig
  })

  it('reads browser timezone as fallback for site timezone config', () => {
    expect(getBrowserTimezone()).toBeTruthy()
  })

  it('builds timezone options including selected value', () => {
    const options = buildTimezoneOptions('Europe/Warsaw')
    const warsaw = options.find((option) => option.value === 'Europe/Warsaw')
    expect(warsaw).toBeTruthy()
    expect(warsaw?.label).toContain('Europe/Warsaw')
    expect(options[0]?.value).toBe(getBrowserTimezone())
  })

  it('reads site timezone from admin config', () => {
    window.flexTopBarConfig = {
      apiRoot: '/wp-json/flex-top-bar/v1',
      nonce: 'test',
      i18n: {},
      siteTimezone: 'Europe/Warsaw',
    }

    expect(getSiteTimezone()).toBe('Europe/Warsaw')
  })

  it('converts datetime-local values to wall-clock strings', () => {
    expect(toDatetimeLocalValue('2026-03-25T10:00')).toBe('2026-03-25T10:00')
    expect(fromDatetimeLocalValue('2026-03-25T10:00')).toBe('2026-03-25T10:00')
  })

  it('formats timezone label with offset', () => {
    expect(formatTimezoneLabel('UTC')).toBe('UTC (GMT+0)')
    expect(formatTimezoneLabel('Europe/Warsaw')).toContain('Europe/Warsaw')
    expect(formatTimezoneLabel('Europe/Warsaw')).toContain('GMT+1')
  })

  it('replaces UTC-equivalent stored values with browser timezone', () => {
    vi.setSystemTime(new Date('2026-05-19T10:00:00Z'))

    window.flexTopBarConfig = {
      apiRoot: '/wp-json/flex-top-bar/v1',
      nonce: 'test',
      i18n: {},
      siteTimezone: 'Europe/Warsaw',
    }

    vi.spyOn(Intl.DateTimeFormat.prototype, 'resolvedOptions').mockReturnValue({
      locale: 'en-US',
      calendar: 'gregory',
      numberingSystem: 'latn',
      timeZone: 'Europe/Warsaw',
    })
    vi.spyOn(Date.prototype, 'getTimezoneOffset').mockReturnValue(-120)

    expect(getDefaultScheduleTimezone('UTC')).toBe('Europe/Warsaw')
    expect(getDefaultScheduleTimezone('+00:00')).toBe('Europe/Warsaw')
  })

  it('infers site timezone when Intl reports UTC but local offset differs', () => {
    vi.setSystemTime(new Date('2026-05-19T10:00:00Z'))

    window.flexTopBarConfig = {
      apiRoot: '/wp-json/flex-top-bar/v1',
      nonce: 'test',
      i18n: {},
      siteTimezone: 'Europe/Warsaw',
    }

    vi.spyOn(Intl.DateTimeFormat.prototype, 'resolvedOptions').mockReturnValue({
      locale: 'en-US',
      calendar: 'gregory',
      numberingSystem: 'latn',
      timeZone: 'UTC',
    })
    vi.spyOn(Date.prototype, 'getTimezoneOffset').mockReturnValue(-120)

    expect(getBrowserTimezone()).toBe('Europe/Warsaw')
    expect(getDefaultScheduleTimezone('UTC')).toBe('Europe/Warsaw')
  })

  it('evaluates schedule window in the configured timezone', () => {
    const from = '2026-03-25T10:00'
    const to = '2026-03-25T18:00'

    expect(isWithinScheduleWindow(from, to, 'UTC', Date.parse('2026-03-25T09:00:00Z'))).toBe(false)
    expect(isWithinScheduleWindow(from, to, 'UTC', Date.parse('2026-03-25T11:00:00Z'))).toBe(true)
    expect(isWithinScheduleWindow(from, to, 'UTC', Date.parse('2026-03-25T19:00:00Z'))).toBe(false)
  })

  it('maps wall-clock time to UTC timestamp for a timezone', () => {
    const ts = wallClockToTimestamp('2026-03-25T10:00', 'UTC')
    expect(new Date(ts).toISOString()).toBe('2026-03-25T10:00:00.000Z')
  })
})
