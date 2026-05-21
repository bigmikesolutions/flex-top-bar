import { describe, it, expect, vi, afterEach } from 'vitest'
import {
  digitChars,
  formatPlainCountdown,
  getCountdownRemainingMs,
  getCountdownTargetDatetime,
  pad2,
  splitCountdownMs,
} from '@/utils/countdown'
import { wallClockToTimestamp } from '@/utils/scheduleDateTime'

describe('countdown utils', () => {
  afterEach(() => {
    vi.useRealTimers()
  })

  it('picks target datetime by direction', () => {
    expect(
      getCountdownTargetDatetime('down', '2026-05-21T18:00', '2026-05-01T10:00'),
    ).toBe('2026-05-21T18:00')
    expect(
      getCountdownTargetDatetime('up', '2026-05-21T18:00', '2026-05-01T10:00'),
    ).toBe('2026-05-01T10:00')
  })

  it('counts down to target in UTC', () => {
    vi.useFakeTimers()
    vi.setSystemTime(new Date('2026-05-21T16:00:00Z'))

    const remaining = getCountdownRemainingMs(
      'down',
      '2026-05-21T18:00',
      '',
      'UTC',
      Date.now(),
    )

    expect(remaining).toBe(2 * 60 * 60 * 1000)
  })

  it('interprets the same wall-clock end differently for UTC and Europe/Warsaw', () => {
    const nowMs = Date.parse('2026-05-21T10:00:00Z')
    const target = '2026-06-15T14:00'

    const utcTargetMs = wallClockToTimestamp(target, 'UTC')
    const warsawTargetMs = wallClockToTimestamp(target, 'Europe/Warsaw')

    expect(warsawTargetMs).not.toBe(utcTargetMs)
    expect(
      formatPlainCountdown(
        splitCountdownMs(getCountdownRemainingMs('down', target, '', 'UTC', nowMs)),
      ),
    ).not.toBe(
      formatPlainCountdown(
        splitCountdownMs(getCountdownRemainingMs('down', target, '', 'Europe/Warsaw', nowMs)),
      ),
    )
  })

  it('counts up elapsed time since start in UTC', () => {
    vi.useFakeTimers()
    vi.setSystemTime(new Date('2026-05-21T12:00:00Z'))

    const remaining = getCountdownRemainingMs(
      'up',
      '',
      '2026-05-21T10:00',
      'UTC',
      Date.now(),
    )

    expect(remaining).toBe(2 * 60 * 60 * 1000)
  })

  it('formats plain countdown label', () => {
    expect(formatPlainCountdown({ days: 3, hours: 4, minutes: 5, seconds: 6 })).toBe(
      '3d 04h 05m 06s',
    )
  })

  it('splits milliseconds into parts', () => {
    expect(splitCountdownMs(90_061_000)).toEqual({
      days: 1,
      hours: 1,
      minutes: 1,
      seconds: 1,
    })
  })

  it('clamps negative milliseconds to zero parts', () => {
    expect(splitCountdownMs(-5000)).toEqual({
      days: 0,
      hours: 0,
      minutes: 0,
      seconds: 0,
    })
  })

  it('returns zero remaining when target datetime is empty', () => {
    expect(getCountdownRemainingMs('down', '', '', 'UTC', Date.UTC(2026, 4, 21, 12))).toBe(0)
    expect(getCountdownRemainingMs('up', '', '', 'UTC', Date.UTC(2026, 4, 21, 12))).toBe(0)
  })

  it('returns zero when count down target is in the past', () => {
    vi.useFakeTimers()
    vi.setSystemTime(new Date('2026-05-21T20:00:00Z'))

    expect(
      getCountdownRemainingMs('down', '2026-05-21T18:00', '', 'UTC', Date.now()),
    ).toBe(0)
  })

  it('returns zero when target datetime is invalid', () => {
    expect(
      getCountdownRemainingMs('down', 'invalid', '', 'UTC', Date.UTC(2026, 4, 21, 12)),
    ).toBe(0)
  })

  it('pads single-digit values for plain format', () => {
    expect(pad2(3)).toBe('03')
    expect(pad2(12)).toBe('12')
  })

  it('splits values into digit characters with minimum width', () => {
    expect(digitChars(7, 2)).toEqual(['0', '7'])
    expect(digitChars(123, 2)).toEqual(['1', '2', '3'])
    expect(digitChars(-5, 2)).toEqual(['0', '0'])
  })
})
