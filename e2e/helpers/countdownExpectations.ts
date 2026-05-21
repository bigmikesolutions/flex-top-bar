import {
  formatPlainCountdown,
  getCountdownRemainingMs,
  splitCountdownMs,
} from '../../plugins/flex-top-bar/src/utils/countdown';

/** Fixed instant for deterministic frontend countdown assertions. */
export const COUNTDOWN_E2E_FIXED_NOW = new Date('2026-05-21T10:00:00Z');

/** Wall-clock end time interpreted in the column's selected timezone. */
export const COUNTDOWN_E2E_TARGET_END = '2026-06-15T14:00';

export function expectedPlainCountdownForTimezone(
  timezone: string,
  nowMs = COUNTDOWN_E2E_FIXED_NOW.getTime()
): string {
  const remaining = getCountdownRemainingMs(
    'down',
    COUNTDOWN_E2E_TARGET_END,
    '',
    timezone,
    nowMs
  );
  return formatPlainCountdown(splitCountdownMs(remaining));
}

export function expectedCountdownRemainingMs(
  timezone: string,
  nowMs = COUNTDOWN_E2E_FIXED_NOW.getTime()
): number {
  return getCountdownRemainingMs('down', COUNTDOWN_E2E_TARGET_END, '', timezone, nowMs);
}

export function parsePlainCountdownLabel(label: string): number {
  const match = /^(\d+)d (\d{2})h (\d{2})m (\d{2})s$/.exec(label.trim());
  if (!match) {
    return 0;
  }

  const days = Number(match[1]);
  const hours = Number(match[2]);
  const minutes = Number(match[3]);
  const seconds = Number(match[4]);

  return (
    days * 86_400_000 + hours * 3_600_000 + minutes * 60_000 + seconds * 1000
  );
}

/** Allow a few seconds of live ticker drift on the frontend. */
export const COUNTDOWN_E2E_TOLERANCE_MS = 5000;
