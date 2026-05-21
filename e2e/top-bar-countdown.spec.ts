import { expect, test } from '@playwright/test';
import {
  COUNTDOWN_E2E_FIXED_NOW,
  COUNTDOWN_E2E_TARGET_END,
  expectedPlainCountdownForTimezone,
} from './helpers/countdownExpectations';
import {
  assertFrontendPlainCountdown,
  getBarIdByIndex,
  getCountdownTimezoneValue,
  loginAndOpenTopBarSettings,
  openPanel,
  publishBar,
  resetToSingleColumnBar,
  setCountdownCounterStyle,
  setCountdownEndDatetime,
  setCountdownTimezone,
  toDatetimeLocalValue,
  waitForTopBarPut,
  waitForTopBarPutWhere,
} from './helpers/topBarHelpers';

test.describe('countdown timer - counter style', () => {
  test('should switch plain/boxed counter style and render on frontend', async ({ page }) => {
    await loginAndOpenTopBarSettings(page);
    await resetToSingleColumnBar(page, 'countdown');
    await openPanel(page, 0);

    const barId = await getBarIdByIndex(page, 0);
    const columnId = 'col_front_countdown';

    const row = page.locator('.top-bar-row.bg').first();
    const panel = () => row.locator('.top-bar-options.active');

    const styleFieldset = () =>
      panel()
        .locator('fieldset')
        .filter({ has: page.locator('legend', { hasText: 'Counter style' }) })
        .first();

    async function publishThisBar() {
      page.once('dialog', (d) => d.accept());
      const publishBtn = row.locator('button.top-bar-icons.publish');
      const publishSave = page.waitForResponse((r) => {
        if (r.request().method() !== 'POST' || !r.ok()) return false;
        const url = decodeURIComponent(r.url());
        return new RegExp(`/(flex-top-bar|top-bar)/v1/bars/${barId}/publish`, 'i').test(url);
      });
      await publishBtn.click();
      await publishSave;
    }

    async function setCounterStyle(style: 'plain' | 'boxed') {
      await openPanel(page, 0);
      await expect(panel()).toBeVisible({ timeout: 15000 });
      const label = styleFieldset().locator('label').filter({
        hasText: style === 'plain' ? 'Plain text' : 'Digits with background',
      });
      const radio = label.locator('input[type="radio"]');
      await label.scrollIntoViewIfNeeded();
      await Promise.all([
        waitForTopBarPutWhere(page, (body) => body.includes(`"counter_style":"${style}"`)),
        radio.evaluate((el: HTMLInputElement) => el.click()),
      ]);
    }

    async function assertFrontendStyle(style: 'plain' | 'boxed') {
      await page.goto('/');
      const countdown = page.locator(`[data-top-bar-id="${barId}"] .top-bar-countdown-column`);
      await expect(countdown).toHaveClass(new RegExp(`top-bar-countdown-column--${style}`));
      if (style === 'plain') {
        await expect(countdown.locator('.top-bar-countdown-column__plain')).toHaveCount(1);
        await expect(countdown.locator('.top-bar-countdown-column__unit')).toHaveCount(0);
        await expect(countdown.locator('.top-bar-countdown-column__plain')).toHaveText(/\d+d \d{2}h \d{2}m \d{2}s/);
      } else {
        await expect(countdown.locator('.top-bar-countdown-column__unit')).toHaveCount(4);
        await expect(countdown.locator('.top-bar-countdown-column__digit').first()).toBeVisible();
      }
    }

    await expect(styleFieldset().locator('label')).toHaveCount(2);

    // Seeded: boxed.
    await publishThisBar();
    await assertFrontendStyle('boxed');

    await loginAndOpenTopBarSettings(page);
    await setCounterStyle('plain');
    await publishThisBar();
    await assertFrontendStyle('plain');

    await loginAndOpenTopBarSettings(page);
    await setCounterStyle('boxed');
    await publishThisBar();
    await assertFrontendStyle('boxed');
  });
});

test.describe('countdown timer - settings and frontend', () => {
  test('should save direction, datetime, text position, colors and render on frontend', async ({
    page,
  }) => {
    await loginAndOpenTopBarSettings(page);
    await resetToSingleColumnBar(page, 'countdown');
    await openPanel(page, 0);

    const barId = await getBarIdByIndex(page, 0);
    const columnId = 'col_front_countdown';

    const row = page.locator('.top-bar-row.bg').first();
    const panel = () => row.locator('.top-bar-options.active');

    const directionFieldset = () =>
      panel()
        .locator('fieldset')
        .filter({ has: page.locator('legend', { hasText: 'Count direction' }) })
        .first();

    const textPositionFieldset = () =>
      panel()
        .locator('fieldset')
        .filter({ has: page.locator('legend', { hasText: 'Text position' }) })
        .first();

    async function publishThisBar() {
      page.once('dialog', (d) => d.accept());
      const publishBtn = row.locator('button.top-bar-icons.publish');
      const publishSave = page.waitForResponse((r) => {
        if (r.request().method() !== 'POST' || !r.ok()) return false;
        const url = decodeURIComponent(r.url());
        return new RegExp(`/(flex-top-bar|top-bar)/v1/bars/${barId}/publish`, 'i').test(url);
      });
      await publishBtn.click();
      await publishSave;
    }

    async function setCountDirection(direction: 'up' | 'down') {
      await openPanel(page, 0);
      const labelText =
        direction === 'down'
          ? 'Count down until promotion ends'
          : 'Count up from promotion start';
      const label = directionFieldset().locator('label').filter({ hasText: labelText });
      const radio = label.locator('input[type="radio"]');
      await Promise.all([
        waitForTopBarPutWhere(page, (body) => body.includes(`"count_direction":"${direction}"`)),
        radio.evaluate((el: HTMLInputElement) => el.click()),
      ]);
    }

    async function setTargetDatetime(
      value: string,
      field: 'countdown_to_datetime' | 'countup_from_datetime' = 'countdown_to_datetime'
    ) {
      await openPanel(page, 0);
      const inputId =
        field === 'countdown_to_datetime'
          ? `countdown_to_${barId}_${columnId}`
          : `countup_from_${barId}_${columnId}`;
      const datetimeInput = panel().locator(`#${inputId}`);
      await expect(datetimeInput).toBeVisible({ timeout: 15000 });
      // Vue persists on blur (@commit), not on @input — same as other admin fields.
      await datetimeInput.fill(value);
      const save = waitForTopBarPut(page);
      await datetimeInput.blur();
      await save;
      await expect(datetimeInput).toHaveValue(value);
    }

    async function setLabelText(text: string) {
      await openPanel(page, 0);
      const textInput = panel().locator(`#countdown_text_${barId}_${columnId}`);
      await textInput.fill(text);
      const save = waitForTopBarPut(page);
      await textInput.blur();
      await save;
    }

    async function setTextPosition(position: 'before' | 'after') {
      await openPanel(page, 0);
      const labelText = position === 'before' ? 'Before counter' : 'After counter';
      const label = textPositionFieldset().locator('label').filter({ hasText: labelText });
      const radio = label.locator('input[type="radio"]');
      await Promise.all([
        waitForTopBarPutWhere(page, (body) => body.includes(`"text_position":"${position}"`)),
        radio.evaluate((el: HTMLInputElement) => el.click()),
      ]);
    }

    async function setBoxedColors(bg: string, counter: string, textColor: string) {
      await openPanel(page, 0);
      await expect(panel()).toBeVisible({ timeout: 15000 });

      const bgInput = panel().locator(`#countdown_bg_${barId}_${columnId}`);
      const counterInput = panel().locator(`#countdown_counter_${barId}_${columnId}`);
      const textColorInput = panel().locator(`#countdown_text_color_${barId}_${columnId}`);

      await bgInput.fill(bg);
      let save = waitForTopBarPut(page);
      await bgInput.blur();
      await save;

      await counterInput.fill(counter);
      save = waitForTopBarPut(page);
      await counterInput.blur();
      await save;

      await textColorInput.fill(textColor);
      save = waitForTopBarPut(page);
      await textColorInput.blur();
      await save;
    }

    async function assertFrontendCountdown(expected: {
      text: string;
      textPosition: 'before' | 'after';
      hasBoxedBg: boolean;
    }) {
      await page.goto('/');
      const countdown = page.locator(`[data-top-bar-id="${barId}"] .top-bar-countdown-column`);
      await expect(countdown).toHaveClass(
        new RegExp(`top-bar-countdown-column--text-${expected.textPosition}`)
      );
      await expect(countdown.locator('.top-bar-countdown-column__text')).toHaveText(expected.text);
      await expect(countdown.locator('.top-bar-countdown-column__unit')).toHaveCount(4);

      const style = (await countdown.getAttribute('style')) ?? '';
      expect(style).toContain('--top-bar-countdown-counter');
      expect(style).toContain('--top-bar-countdown-text');
      if (expected.hasBoxedBg) {
        expect(style).toContain('--top-bar-countdown-bg');
      }

      const flexDirection = await countdown.evaluate((el) => window.getComputedStyle(el).flexDirection);
      if (expected.textPosition === 'before') {
        expect(flexDirection).toBe('row');
      } else {
        expect(flexDirection).toBe('row-reverse');
      }
    }

    const countDownEnd = toDatetimeLocalValue(new Date(Date.now() + 5 * 24 * 60 * 60 * 1000));
    const countUpStart = toDatetimeLocalValue(new Date(Date.now() - 2 * 60 * 60 * 1000));

    // Seeded: count down, label before text.
    await expect(directionFieldset().locator('input[value="down"]')).toBeChecked();
    await expect(panel().locator('legend', { hasText: 'Count down until (end)' })).toHaveCount(1);

    await setLabelText('Sale ends in');
    await setBoxedColors('#112233', '#aabbcc', '#ddeeff');
    await setTargetDatetime(countDownEnd, 'countdown_to_datetime');
    await publishThisBar();
    await assertFrontendCountdown({
      text: 'Sale ends in',
      textPosition: 'before',
      hasBoxedBg: true,
    });

    await loginAndOpenTopBarSettings(page);
    await setTextPosition('after');
    await publishThisBar();
    await assertFrontendCountdown({
      text: 'Sale ends in',
      textPosition: 'after',
      hasBoxedBg: true,
    });

    await loginAndOpenTopBarSettings(page);
    await setCountDirection('up');
    await expect(panel().locator('legend', { hasText: 'Count up from (start)' })).toHaveCount(1);
    await setTargetDatetime(countUpStart, 'countup_from_datetime');
    await setLabelText('Promo running for');
    await publishThisBar();

    await page.goto('/');
    const countdown = page.locator(`[data-top-bar-id="${barId}"] .top-bar-countdown-column`);
    await expect(countdown.locator('.top-bar-countdown-column__text')).toHaveText('Promo running for');
    await expect(countdown.locator('.top-bar-countdown-column__plain, .top-bar-countdown-column__unit').first()).toBeVisible();
  });

  test.describe('timezone', () => {
    test.use({ timezoneId: 'Europe/Warsaw' });

    const columnId = 'col_front_countdown';
    const warsawLabel = expectedPlainCountdownForTimezone('Europe/Warsaw');
    const utcLabel = expectedPlainCountdownForTimezone('UTC');

    test('should save Europe/Warsaw countdown timezone and persist after reload', async ({ page }) => {
      expect(warsawLabel).not.toBe(utcLabel);

      await loginAndOpenTopBarSettings(page);
      await resetToSingleColumnBar(page, 'countdown');
      await openPanel(page, 0);

      const barId = await getBarIdByIndex(page, 0);
      const timezoneSelect = page.locator(`#countdown_timezone_${barId}_${columnId}`);

      await setCountdownCounterStyle(page, 0, columnId, 'plain');
      await setCountdownEndDatetime(page, 0, columnId, COUNTDOWN_E2E_TARGET_END);
      await expect(timezoneSelect).toBeVisible({ timeout: 15000 });
      await setCountdownTimezone(page, 0, columnId, 'Europe/Warsaw');
      await expect(timezoneSelect).toHaveValue('Europe/Warsaw');

      await publishBar(page, 0, barId);
      await assertFrontendPlainCountdown(page, barId, 'Europe/Warsaw', COUNTDOWN_E2E_FIXED_NOW);

      await loginAndOpenTopBarSettings(page);
      await openPanel(page, 0);
      await expect(timezoneSelect).toHaveValue('Europe/Warsaw');
      await expect(await getCountdownTimezoneValue(page, 0, columnId)).toBe('Europe/Warsaw');

      await publishBar(page, 0, barId);
      await assertFrontendPlainCountdown(page, barId, 'Europe/Warsaw', COUNTDOWN_E2E_FIXED_NOW);
    });

    test('should save UTC countdown timezone and persist after reload', async ({ page }) => {
      expect(warsawLabel).not.toBe(utcLabel);

      await loginAndOpenTopBarSettings(page);
      await resetToSingleColumnBar(page, 'countdown');
      await openPanel(page, 0);

      const barId = await getBarIdByIndex(page, 0);
      const timezoneSelect = page.locator(`#countdown_timezone_${barId}_${columnId}`);

      await setCountdownCounterStyle(page, 0, columnId, 'plain');
      await setCountdownEndDatetime(page, 0, columnId, COUNTDOWN_E2E_TARGET_END);
      await expect(timezoneSelect).toBeVisible({ timeout: 15000 });
      // Seed uses UTC; switch away first so selecting UTC triggers a save.
      await setCountdownTimezone(page, 0, columnId, 'Europe/Warsaw');
      await setCountdownTimezone(page, 0, columnId, 'UTC');
      await expect(timezoneSelect).toHaveValue('UTC');

      await publishBar(page, 0, barId);
      await assertFrontendPlainCountdown(page, barId, 'UTC', COUNTDOWN_E2E_FIXED_NOW);

      await loginAndOpenTopBarSettings(page);
      await openPanel(page, 0);
      await expect(timezoneSelect).toHaveValue('UTC');
      await expect(await getCountdownTimezoneValue(page, 0, columnId)).toBe('UTC');

      await publishBar(page, 0, barId);
      await assertFrontendPlainCountdown(page, barId, 'UTC', COUNTDOWN_E2E_FIXED_NOW);
    });
  });
});
