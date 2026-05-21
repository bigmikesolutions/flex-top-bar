import { describe, it, expect, vi, afterEach } from 'vitest'
import {
  formatPlainCountdown,
  getCountdownRemainingMs,
  getCountdownTargetDatetime,
  splitCountdownMs,
} from '@/utils/countdown'

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
})
