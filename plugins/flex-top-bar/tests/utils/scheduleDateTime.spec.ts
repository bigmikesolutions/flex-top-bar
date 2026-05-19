import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import {
  buildTimezoneOptions,
  formatTimezoneLabel,
  fromDatetimeLocalValue,
  getBrowserTimezone,
  getDefaultScheduleTimezone,
  getVisitorScheduleTimezone,
  isWithinScheduleWindow,
  isWithinScheduleWindowForVisitor,
  normalizeTimezoneId,
  resolvePublicScheduleTimezone,
  resolveScheduleTimezone,
  toDatetimeLocalValue,
  wallClockToTimestamp,
} from '@/utils/scheduleDateTime'

function mockWarsawBrowser(offsetMinutes = -120): void {
  vi.spyOn(Intl.DateTimeFormat.prototype, 'resolvedOptions').mockReturnValue({
    locale: 'en-US',
    calendar: 'gregory',
    numberingSystem: 'latn',
    timeZone: 'Europe/Warsaw',
  })
  vi.spyOn(Date.prototype, 'getTimezoneOffset').mockReturnValue(-offsetMinutes)
}

function mockUtcBrowser(): void {
  vi.spyOn(Intl.DateTimeFormat.prototype, 'resolvedOptions').mockReturnValue({
    locale: 'en-US',
    calendar: 'gregory',
    numberingSystem: 'latn',
    timeZone: 'UTC',
  })
  vi.spyOn(Date.prototype, 'getTimezoneOffset').mockReturnValue(0)
}

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

  describe('normalizeTimezoneId', () => {
    it('normalizes UTC aliases', () => {
      expect(normalizeTimezoneId('UTC')).toBe('UTC')
      expect(normalizeTimezoneId('+00:00')).toBe('UTC')
      expect(normalizeTimezoneId('-00:00')).toBe('UTC')
      expect(normalizeTimezoneId('GMT')).toBe('UTC')
    })

    it('accepts valid IANA timezone ids', () => {
      expect(normalizeTimezoneId('Europe/Warsaw')).toBe('Europe/Warsaw')
    })

    it('rejects invalid values', () => {
      expect(normalizeTimezoneId('')).toBe('')
      expect(normalizeTimezoneId('   ')).toBe('')
      expect(normalizeTimezoneId('Not/AZone')).toBe('')
      expect(normalizeTimezoneId(undefined as unknown as string)).toBe('')
    })
  })

  describe('datetime-local helpers', () => {
    it('converts to and from datetime-local strings', () => {
      expect(toDatetimeLocalValue('2026-03-25T10:00')).toBe('2026-03-25T10:00')
      expect(toDatetimeLocalValue('2026-03-25T10:00:45')).toBe('2026-03-25T10:00')
      expect(fromDatetimeLocalValue('2026-03-25T10:00')).toBe('2026-03-25T10:00')
    })

    it('returns empty string for empty input', () => {
      expect(toDatetimeLocalValue('')).toBe('')
      expect(fromDatetimeLocalValue('')).toBe('')
    })
  })

  describe('wallClockToTimestamp', () => {
    it('maps UTC wall-clock time directly', () => {
      const ts = wallClockToTimestamp('2026-03-25T10:00', 'UTC')
      expect(new Date(ts).toISOString()).toBe('2026-03-25T10:00:00.000Z')
    })

    it('maps Europe/Warsaw wall-clock time to the correct UTC instant', () => {
      const fromMs = wallClockToTimestamp('2026-05-19T10:00', 'Europe/Warsaw')
      const toMs = wallClockToTimestamp('2026-05-19T18:00', 'Europe/Warsaw')

      expect(new Date(fromMs).toISOString()).toBe('2026-05-19T08:00:00.000Z')
      expect(new Date(toMs).toISOString()).toBe('2026-05-19T16:00:00.000Z')
    })

    it('returns 0 for invalid wall-clock or timezone', () => {
      expect(wallClockToTimestamp('not-a-date', 'UTC')).toBe(0)
      expect(wallClockToTimestamp('2026-03-25T10:00', 'Invalid/Zone')).toBe(0)
    })

    it('falls back to Date.parse when timezone is empty', () => {
      expect(wallClockToTimestamp('2026-03-25T10:00', '')).toBe(Date.parse('2026-03-25T10:00'))
    })
  })

  describe('isWithinScheduleWindow', () => {
    const from = '2026-03-25T10:00'
    const to = '2026-03-25T18:00'

    it('returns true when now is inside the window', () => {
      expect(isWithinScheduleWindow(from, to, 'UTC', Date.parse('2026-03-25T11:00:00Z'))).toBe(true)
    })

    it('returns true on inclusive boundaries', () => {
      expect(isWithinScheduleWindow(from, to, 'UTC', Date.parse('2026-03-25T10:00:00Z'))).toBe(true)
      expect(isWithinScheduleWindow(from, to, 'UTC', Date.parse('2026-03-25T18:00:00Z'))).toBe(true)
    })

    it('returns false before start and after end', () => {
      expect(isWithinScheduleWindow(from, to, 'UTC', Date.parse('2026-03-25T09:59:00Z'))).toBe(false)
      expect(isWithinScheduleWindow(from, to, 'UTC', Date.parse('2026-03-25T18:01:00Z'))).toBe(false)
    })

    it('returns false when from is after to', () => {
      expect(isWithinScheduleWindow('2026-03-25T18:00', '2026-03-25T10:00', 'UTC')).toBe(false)
    })

    it('returns false for invalid datetimes', () => {
      expect(isWithinScheduleWindow('invalid', to, 'UTC')).toBe(false)
      expect(isWithinScheduleWindow(from, 'invalid', 'UTC')).toBe(false)
    })
  })

  describe('resolvePublicScheduleTimezone', () => {
    it('returns stored timezone when valid', () => {
      expect(resolvePublicScheduleTimezone('Europe/Warsaw')).toBe('Europe/Warsaw')
    })

    it('falls back to browser timezone when stored value is empty', () => {
      mockWarsawBrowser(60)
      expect(resolvePublicScheduleTimezone('')).toBe('Europe/Warsaw')
    })
  })

  describe('isWithinScheduleWindowForVisitor', () => {
    const warsawFrom = '2026-05-19T10:00'
    const warsawTo = '2026-05-19T18:00'

    beforeEach(() => {
      vi.setSystemTime(new Date('2026-05-19T10:30:00Z'))
    })

    it('uses stored timezone when set (Europe/Warsaw)', () => {
      const inside = Date.parse('2026-05-19T10:30:00Z') // 12:30 CEST
      const before = Date.parse('2026-05-19T07:00:00Z') // 09:00 CEST

      expect(
        isWithinScheduleWindowForVisitor(warsawFrom, warsawTo, 'Europe/Warsaw', inside),
      ).toBe(true)
      expect(
        isWithinScheduleWindowForVisitor(warsawFrom, warsawTo, 'Europe/Warsaw', before),
      ).toBe(false)
    })

    it('evaluates Warsaw schedule differently from UTC for the same wall-clock strings', () => {
      const insideWarsawOnly = Date.parse('2026-05-19T09:00:00Z') // 11:00 CEST

      expect(
        isWithinScheduleWindowForVisitor(warsawFrom, warsawTo, 'Europe/Warsaw', insideWarsawOnly),
      ).toBe(true)
      expect(
        isWithinScheduleWindowForVisitor(warsawFrom, warsawTo, 'UTC', insideWarsawOnly),
      ).toBe(false)
    })

    it('falls back to browser timezone when stored timezone is empty', () => {
      mockWarsawBrowser(120)

      expect(getVisitorScheduleTimezone()).toBe('Europe/Warsaw')
      expect(
        isWithinScheduleWindowForVisitor(warsawFrom, warsawTo, '', Date.parse('2026-05-19T10:30:00Z')),
      ).toBe(true)
      expect(
        isWithinScheduleWindowForVisitor(warsawFrom, warsawTo, '', Date.parse('2026-05-19T07:00:00Z')),
      ).toBe(false)
    })

    it('uses browser timezone when stored timezone is invalid', () => {
      mockUtcBrowser()

      expect(
        isWithinScheduleWindowForVisitor('2026-05-19T10:00', '2026-05-19T18:00', 'Invalid/Zone', Date.parse('2026-05-19T11:00:00Z')),
      ).toBe(true)
      expect(
        isWithinScheduleWindowForVisitor('2026-05-19T10:00', '2026-05-19T18:00', 'Invalid/Zone', Date.parse('2026-05-19T09:00:00Z')),
      ).toBe(false)
    })

    it('returns true on inclusive start and end boundaries', () => {
      expect(
        isWithinScheduleWindowForVisitor(
          warsawFrom,
          warsawTo,
          'Europe/Warsaw',
          wallClockToTimestamp(warsawFrom, 'Europe/Warsaw'),
        ),
      ).toBe(true)
      expect(
        isWithinScheduleWindowForVisitor(
          warsawFrom,
          warsawTo,
          'Europe/Warsaw',
          wallClockToTimestamp(warsawTo, 'Europe/Warsaw'),
        ),
      ).toBe(true)
    })

    it('returns false for invalid or reversed ranges', () => {
      expect(isWithinScheduleWindowForVisitor('', warsawTo, 'Europe/Warsaw')).toBe(false)
      expect(isWithinScheduleWindowForVisitor(warsawFrom, '', 'Europe/Warsaw')).toBe(false)
      expect(
        isWithinScheduleWindowForVisitor('2026-05-19T18:00', '2026-05-19T10:00', 'Europe/Warsaw'),
      ).toBe(false)
    })

    it('defaults now to the current time when nowMs is omitted', () => {
      vi.setSystemTime(new Date('2026-05-19T10:30:00Z'))

      expect(isWithinScheduleWindowForVisitor(warsawFrom, warsawTo, 'Europe/Warsaw')).toBe(true)

      vi.setSystemTime(new Date('2026-05-19T06:00:00Z'))

      expect(isWithinScheduleWindowForVisitor(warsawFrom, warsawTo, 'Europe/Warsaw')).toBe(false)
    })
  })

  describe('getBrowserTimezone', () => {
    it('returns resolved timezone when offset matches', () => {
      mockWarsawBrowser(60)
      expect(getBrowserTimezone()).toBe('Europe/Warsaw')
    })

    it('infers timezone from local offset when Intl reports UTC', () => {
      vi.setSystemTime(new Date('2026-05-19T10:00:00Z'))
      mockUtcBrowser()
      vi.spyOn(Date.prototype, 'getTimezoneOffset').mockReturnValue(-120)

      expect(getBrowserTimezone()).toBe('Europe/Warsaw')
    })
  })

  describe('getDefaultScheduleTimezone', () => {
    it('returns browser timezone when stored value is empty', () => {
      vi.setSystemTime(new Date('2026-05-19T10:00:00Z'))
      mockWarsawBrowser(120)

      expect(getDefaultScheduleTimezone('')).toBe('Europe/Warsaw')
    })

    it('keeps explicit non-UTC stored timezone', () => {
      expect(getDefaultScheduleTimezone('America/New_York')).toBe('America/New_York')
    })

    it('replaces UTC-equivalent stored values with browser timezone', () => {
      vi.setSystemTime(new Date('2026-05-19T10:00:00Z'))
      mockWarsawBrowser(120)

      expect(getDefaultScheduleTimezone('UTC')).toBe('Europe/Warsaw')
      expect(getDefaultScheduleTimezone('+00:00')).toBe('Europe/Warsaw')
    })
  })

  describe('resolveScheduleTimezone', () => {
    it('delegates to getDefaultScheduleTimezone', () => {
      expect(resolveScheduleTimezone('Europe/Berlin')).toBe('Europe/Berlin')
    })
  })

  describe('formatTimezoneLabel', () => {
    it('includes timezone id and offset label', () => {
      expect(formatTimezoneLabel('UTC')).toBe('UTC (GMT+0)')
      expect(formatTimezoneLabel('Europe/Warsaw')).toContain('Europe/Warsaw')
      expect(formatTimezoneLabel('Europe/Warsaw')).toContain('GMT+1')
    })

    it('returns empty string for invalid timezone', () => {
      expect(formatTimezoneLabel('')).toBe('')
      expect(formatTimezoneLabel('Invalid/Zone')).toBe('')
    })
  })

  describe('buildTimezoneOptions', () => {
    it('pins browser and selected timezones at the top', () => {
      const options = buildTimezoneOptions('Europe/Warsaw')
      const warsaw = options.find((option) => option.value === 'Europe/Warsaw')

      expect(warsaw).toBeTruthy()
      expect(warsaw?.label).toContain('Europe/Warsaw')
      expect(options[0]?.value).toBe(getBrowserTimezone())
    })
  })
})
