const WALL_CLOCK_PATTERN = /^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2})$/

type WallClockParts = {
  year: number
  month: number
  day: number
  hour: number
  minute: number
}

function parseWallClockParts(value: string): WallClockParts | null {
  const match = value.match(WALL_CLOCK_PATTERN)
  if (!match) {
    return null
  }

  return {
    year: Number(match[1]),
    month: Number(match[2]),
    day: Number(match[3]),
    hour: Number(match[4]),
    minute: Number(match[5]),
  }
}

function readFormatterParts(date: Date, timeZone: string): WallClockParts {
  const formatter = new Intl.DateTimeFormat('en-US', {
    timeZone,
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
    hour12: false,
  })
  const parts = formatter.formatToParts(date)
  const get = (type: Intl.DateTimeFormatPartTypes) =>
    Number(parts.find((part) => part.type === type)?.value ?? '0')

  return {
    year: get('year'),
    month: get('month'),
    day: get('day'),
    hour: get('hour') % 24,
    minute: get('minute'),
  }
}

function compareWallClockParts(a: WallClockParts, b: WallClockParts): number {
  if (a.year !== b.year) return a.year - b.year
  if (a.month !== b.month) return a.month - b.month
  if (a.day !== b.day) return a.day - b.day
  if (a.hour !== b.hour) return a.hour - b.hour
  return a.minute - b.minute
}

function getLocalTimezoneOffsetMinutes(date = new Date()): number {
  return -date.getTimezoneOffset()
}

function getTimezoneOffsetMinutes(timeZone: string, date = new Date()): number {
  try {
    const utcDate = new Date(date.toLocaleString('en-US', { timeZone: 'UTC' }))
    const tzDate = new Date(date.toLocaleString('en-US', { timeZone }))
    return Math.round((tzDate.getTime() - utcDate.getTime()) / 60_000)
  } catch {
    return Number.NaN
  }
}

function formatOffsetDisplay(offsetMinutes: number): string {
  if (offsetMinutes === 0) {
    return 'GMT+0'
  }

  const sign = offsetMinutes >= 0 ? '+' : '-'
  const abs = Math.abs(offsetMinutes)
  const hours = Math.floor(abs / 60)
  const minutes = abs % 60

  if (minutes === 0) {
    return `GMT${sign}${hours}`
  }

  return `GMT${sign}${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`
}

function isUtcEquivalent(timeZone: string, date = new Date()): boolean {
  const normalized = normalizeTimezoneId(timeZone)
  if (!normalized) {
    return true
  }

  if (normalized === 'UTC' || normalized === 'Etc/UTC' || normalized === 'Etc/GMT') {
    return true
  }

  return getTimezoneOffsetMinutes(normalized, date) === 0
}

export function normalizeTimezoneId(timeZone: string): string {
  const trimmed = timeZone.trim()
  if (!trimmed) {
    return ''
  }

  if (trimmed === '+00:00' || trimmed === '-00:00' || trimmed === 'GMT') {
    return 'UTC'
  }

  try {
    Intl.DateTimeFormat('en-US', { timeZone: trimmed })
    return trimmed
  } catch {
    return ''
  }
}

function formatOffsetTimezoneId(offsetMinutes: number): string {
  const sign = offsetMinutes >= 0 ? '+' : '-'
  const abs = Math.abs(offsetMinutes)
  const hours = Math.floor(abs / 60)
  const minutes = abs % 60

  return `${sign}${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`
}

const PREFERRED_TIMEZONES = [
  'Europe/Warsaw',
  'Europe/Berlin',
  'Europe/Paris',
  'Europe/London',
  'Europe/Madrid',
  'Europe/Rome',
  'America/New_York',
  'America/Chicago',
  'America/Los_Angeles',
  'Asia/Tokyo',
  'Australia/Sydney',
]

function findTimezoneForOffset(offsetMinutes: number, date = new Date()): string | null {
  if (offsetMinutes === 0) {
    return 'UTC'
  }

  const candidates: string[] = []
  const siteTimezone =
    typeof window !== 'undefined' ? window.flexTopBarConfig?.siteTimezone ?? '' : ''

  if (siteTimezone) {
    candidates.push(siteTimezone)
  }
  candidates.push(...PREFERRED_TIMEZONES)
  candidates.push(...getTimezoneOptionValues())

  const seen = new Set<string>()
  for (const timeZone of candidates) {
    if (!timeZone || seen.has(timeZone)) {
      continue
    }
    seen.add(timeZone)
    if (getTimezoneOffsetMinutes(timeZone, date) === offsetMinutes) {
      return timeZone
    }
  }

  return formatOffsetTimezoneId(offsetMinutes)
}

export function getBrowserTimezone(): string {
  try {
    const resolved = Intl.DateTimeFormat().resolvedOptions().timeZone
    const localOffsetMinutes = getLocalTimezoneOffsetMinutes()

    if (resolved && resolved !== 'UTC') {
      const resolvedOffset = getTimezoneOffsetMinutes(resolved)
      if (resolvedOffset === localOffsetMinutes) {
        return resolved
      }
    }

    const inferred = findTimezoneForOffset(localOffsetMinutes)
    if (inferred) {
      return inferred
    }

    return resolved || 'UTC'
  } catch {
    return 'UTC'
  }
}

export function getDefaultScheduleTimezone(storedTimezone = ''): string {
  const browserTimezone = getBrowserTimezone()
  const normalizedStored = normalizeTimezoneId(storedTimezone)

  if (!normalizedStored) {
    return browserTimezone
  }

  if (isUtcEquivalent(normalizedStored) && !isUtcEquivalent(browserTimezone)) {
    return browserTimezone
  }

  return normalizedStored
}

export function getSiteTimezone(): string {
  if (typeof window !== 'undefined' && window.flexTopBarConfig?.siteTimezone) {
    return window.flexTopBarConfig.siteTimezone
  }

  return getBrowserTimezone()
}

const FALLBACK_TIMEZONES = [
  'UTC',
  'Europe/London',
  'Europe/Warsaw',
  'Europe/Berlin',
  'Europe/Paris',
  'America/New_York',
  'America/Chicago',
  'America/Denver',
  'America/Los_Angeles',
  'Asia/Tokyo',
  'Asia/Shanghai',
  'Australia/Sydney',
]

export function getTimezoneOptionValues(): string[] {
  if (typeof Intl.supportedValuesOf === 'function') {
    return Intl.supportedValuesOf('timeZone').slice().sort((a, b) => a.localeCompare(b))
  }

  return FALLBACK_TIMEZONES.slice()
}

export function buildTimezoneOptions(selectedValue = ''): Array<{ value: string; label: string }> {
  const normalizedSelected = normalizeTimezoneId(selectedValue)
  const values = getTimezoneOptionValues()
  const browserTimezone = getBrowserTimezone()
  const pinned = new Set<string>()
  const ordered: string[] = []

  for (const timezone of [browserTimezone, normalizedSelected]) {
    if (timezone && !pinned.has(timezone)) {
      ordered.push(timezone)
      pinned.add(timezone)
    }
  }

  if (normalizedSelected && !values.includes(normalizedSelected) && !pinned.has(normalizedSelected)) {
    ordered.push(normalizedSelected)
    pinned.add(normalizedSelected)
  }

  for (const value of values) {
    if (!pinned.has(value)) {
      ordered.push(value)
    }
  }

  return ordered.map((value) => ({
    value,
    label: formatTimezoneLabel(value),
  }))
}

export function resolveScheduleTimezone(timezone: string): string {
  return getDefaultScheduleTimezone(timezone)
}

export function formatTimezoneLabel(timeZone: string, date = new Date()): string {
  const normalized = normalizeTimezoneId(timeZone)
  if (!normalized) {
    return ''
  }

  const offsetMinutes = getTimezoneOffsetMinutes(normalized, date)
  if (Number.isNaN(offsetMinutes)) {
    return normalized
  }

  return `${normalized} (${formatOffsetDisplay(offsetMinutes)})`
}

export function toDatetimeLocalValue(isoString: string): string {
  if (!isoString) {
    return ''
  }

  return isoString.slice(0, 16)
}

export function fromDatetimeLocalValue(datetimeLocal: string): string {
  if (!datetimeLocal) {
    return ''
  }

  return datetimeLocal.slice(0, 16)
}

export function wallClockToTimestamp(value: string, timeZone: string): number {
  const target = parseWallClockParts(value)
  if (!target || !timeZone) {
    const parsed = Date.parse(value)
    return Number.isNaN(parsed) ? 0 : parsed
  }

  let candidate = Date.UTC(target.year, target.month - 1, target.day, target.hour, target.minute)

  for (let i = 0; i < 48; i += 1) {
    const cmp = compareWallClockParts(readFormatterParts(new Date(candidate), timeZone), target)
    if (cmp === 0) {
      return candidate
    }
    candidate -= cmp * 60_000
  }

  return candidate
}

export function isWithinScheduleWindow(
  from: string,
  to: string,
  timeZone: string,
  nowMs: number = Date.now(),
): boolean {
  const fromMs = wallClockToTimestamp(from, timeZone)
  const toMs = wallClockToTimestamp(to, timeZone)

  if (!fromMs || !toMs) {
    return false
  }

  return nowMs >= fromMs && nowMs <= toMs
}
