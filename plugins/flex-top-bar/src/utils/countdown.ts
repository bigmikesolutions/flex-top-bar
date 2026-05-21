import type { CountdownDirection } from '@/types'
import { resolvePublicScheduleTimezone, wallClockToTimestamp } from '@/utils/scheduleDateTime'

export type CountdownParts = {
  days: number
  hours: number
  minutes: number
  seconds: number
}

export function pad2(value: number): string {
  return String(value).padStart(2, '0')
}

export function splitCountdownMs(ms: number): CountdownParts {
  const totalSeconds = Math.max(0, Math.floor(ms / 1000))
  const days = Math.floor(totalSeconds / 86_400)
  const hours = Math.floor((totalSeconds % 86_400) / 3_600)
  const minutes = Math.floor((totalSeconds % 3_600) / 60)
  const seconds = totalSeconds % 60
  return { days, hours, minutes, seconds }
}

export function getCountdownTargetDatetime(
  direction: CountdownDirection,
  countdownToDatetime: string,
  countupFromDatetime: string,
): string {
  return direction === 'down' ? countdownToDatetime : countupFromDatetime
}

export function getCountdownRemainingMs(
  direction: CountdownDirection,
  countdownToDatetime: string,
  countupFromDatetime: string,
  timezone: string,
  nowMs = Date.now(),
): number {
  const target = getCountdownTargetDatetime(direction, countdownToDatetime, countupFromDatetime)
  if (!target.trim()) {
    return 0
  }

  const resolvedTimezone = resolvePublicScheduleTimezone(timezone)
  const targetMs = wallClockToTimestamp(target, resolvedTimezone)
  if (targetMs === 0) {
    return 0
  }

  if (direction === 'down') {
    return Math.max(0, targetMs - nowMs)
  }

  return Math.max(0, nowMs - targetMs)
}

export function formatPlainCountdown(parts: CountdownParts): string {
  return `${parts.days}d ${pad2(parts.hours)}h ${pad2(parts.minutes)}m ${pad2(parts.seconds)}s`
}

export function digitChars(value: number, minDigits = 2): string[] {
  const raw = String(Math.max(0, value))
  const padded = raw.padStart(minDigits, '0')
  return padded.split('')
}
