import { expect, test } from '@playwright/test';
import {
  ensureAtLeastBars,
  enableSchedule,
  getBarIdByIndex,
  getScheduleTimezoneValue,
  loginAndOpenTopBarSettings,
  openPanel,
  publishBar,
  resetToSingleBar,
  schedulePanelLocator,
  setScheduleTimezone,
  toDatetimeLocalValue,
  waitForTopBarPutWhere,
} from './helpers/topBarHelpers';

test.describe('scheduled', () => {
  test('should set future schedule and make top-bar hidden on frontend', async ({ page }) => {
    await loginAndOpenTopBarSettings(page);
    await resetToSingleBar(page);
    await ensureAtLeastBars(page, 2);
    await openPanel(page, 0);

    const barId = await getBarIdByIndex(page, 0);

    await enableSchedule(page, 0);
    await openPanel(page, 0);

    const schedulePanel = schedulePanelLocator(page, 0);
    await expect(schedulePanel).toBeVisible({ timeout: 15000 });

    const timezoneSelect = page.locator(`#scheduled_timezone_${barId}`);
    await expect(timezoneSelect).toBeVisible({ timeout: 15000 });
    await expect(timezoneSelect.locator('option')).not.toHaveCount(0);

    const dateInputs = schedulePanel.locator('input.top-bar-life-time-datetime[type="datetime-local"]');
    const fromInput = dateInputs.nth(0);
    const toInput = dateInputs.nth(1);
    await expect(fromInput).toBeVisible({ timeout: 15000 });
    await expect(toInput).toBeVisible({ timeout: 15000 });

    await setScheduleTimezone(page, 0, 'Europe/Warsaw');

    await fromInput.fill('2099-03-21T11:00');
    await expect(fromInput).toHaveValue('2099-03-21T11:00');
    await fromInput.blur();
    await waitForTopBarPutWhere(page, (body) => body.includes('"scheduled_from_datetime":"2099-03-21T11:00"'));
    await openPanel(page, 0);
    await expect(toInput).toBeVisible({ timeout: 15000 });

    await toInput.fill('2099-03-21T12:30');
    await expect(toInput).toHaveValue('2099-03-21T12:30');
    await toInput.blur();
    await waitForTopBarPutWhere(page, (body) =>
      body.includes('"scheduled_to_datetime":"2099-03-21T12:30"') &&
      body.includes('"scheduled_timezone":"Europe/Warsaw"')
    );

    await page.reload();
    await openPanel(page, 0);

    const scheduled = page.locator('.top-bar-toggle-life-time').nth(0);
    await expect(scheduled).toBeChecked();
    await expect(fromInput).toHaveValue('2099-03-21T11:00');
    await expect(toInput).toHaveValue('2099-03-21T12:30');
    await expect(timezoneSelect).toHaveValue('Europe/Warsaw');

    await publishBar(page, 0, barId);
    await page.goto('/');
    await expect(page.locator(`[data-top-bar-id="${barId}"]`)).toHaveCount(0);
  });

  test('should set schedule covering now and make top-bar visible on frontend', async ({ page }) => {
    await loginAndOpenTopBarSettings(page);
    await resetToSingleBar(page);
    await ensureAtLeastBars(page, 2);
    await openPanel(page, 0);

    const barId = await getBarIdByIndex(page, 0);

    const now = new Date();
    const from = new Date(now.getTime() - 24 * 60 * 60 * 1000);
    const to = new Date(now.getTime() + 24 * 60 * 60 * 1000);
    const fromValue = toDatetimeLocalValue(from);
    const toValue = toDatetimeLocalValue(to);

    await enableSchedule(page, 0);
    await openPanel(page, 0);

    const schedulePanel = schedulePanelLocator(page, 0);
    await expect(schedulePanel).toBeVisible({ timeout: 15000 });

    const timezoneSelect = page.locator(`#scheduled_timezone_${barId}`);
    await expect(timezoneSelect).toBeVisible({ timeout: 15000 });
    await setScheduleTimezone(page, 0, 'Europe/Warsaw');

    const dateInputs = schedulePanel.locator('input.top-bar-life-time-datetime[type="datetime-local"]');
    const fromInput = dateInputs.nth(0);
    const toInput = dateInputs.nth(1);
    await expect(fromInput).toBeVisible({ timeout: 15000 });
    await expect(toInput).toBeVisible({ timeout: 15000 });
    await fromInput.fill(fromValue);
    await expect(fromInput).toHaveValue(fromValue);
    await fromInput.blur();
    await waitForTopBarPutWhere(page, (body) => body.includes(`"scheduled_from_datetime":"${fromValue}"`));
    await openPanel(page, 0);
    await expect(toInput).toBeVisible({ timeout: 15000 });

    await toInput.fill(toValue);
    await expect(toInput).toHaveValue(toValue);
    await toInput.blur();
    await waitForTopBarPutWhere(page, (body) =>
      body.includes(`"scheduled_to_datetime":"${toValue}"`) &&
      body.includes('"scheduled_timezone":"Europe/Warsaw"')
    );

    await page.reload();
    await openPanel(page, 0);

    const scheduled = page.locator('.top-bar-toggle-life-time').nth(0);
    await expect(scheduled).toBeChecked();
    await expect(fromInput).toHaveValue(fromValue);
    await expect(toInput).toHaveValue(toValue);
    await expect(timezoneSelect).toHaveValue('Europe/Warsaw');

    await publishBar(page, 0, barId);
    await page.goto('/');
    await expect(page.locator(`[data-top-bar-id="${barId}"]`)).toHaveCount(1);
  });

  test.describe('timezone', () => {
    test.use({ timezoneId: 'Europe/Warsaw' });

    test('should default schedule timezone to browser timezone when enabling schedule', async ({ page }) => {
      await loginAndOpenTopBarSettings(page);
      await resetToSingleBar(page);
      await ensureAtLeastBars(page, 2);
      await openPanel(page, 0);

      await enableSchedule(page, 0);
      await openPanel(page, 0);

      const timezone = await getScheduleTimezoneValue(page, 0);
      expect(timezone).toBe('Europe/Warsaw');
    });

    test('should save selected timezone and persist after reload', async ({ page }) => {
      await loginAndOpenTopBarSettings(page);
      await resetToSingleBar(page);
      await ensureAtLeastBars(page, 2);
      await openPanel(page, 0);

      const barId = await getBarIdByIndex(page, 0);

      await enableSchedule(page, 0);
      await openPanel(page, 0);
      await setScheduleTimezone(page, 0, 'America/New_York');

      const timezoneSelect = page.locator(`#scheduled_timezone_${barId}`);
      await expect(timezoneSelect).toHaveValue('America/New_York');

      await page.reload();
      await openPanel(page, 0);
      await expect(timezoneSelect).toHaveValue('America/New_York');
    });

    test('should save UTC timezone and persist after reload', async ({ page }) => {
      await loginAndOpenTopBarSettings(page);
      await resetToSingleBar(page);
      await ensureAtLeastBars(page, 2);
      await openPanel(page, 0);

      const barId = await getBarIdByIndex(page, 0);

      await enableSchedule(page, 0);
      await openPanel(page, 0);
      await setScheduleTimezone(page, 0, 'UTC');

      const timezoneSelect = page.locator(`#scheduled_timezone_${barId}`);
      await expect(timezoneSelect).toHaveValue('UTC');

      await page.reload();
      await openPanel(page, 0);
      await expect(timezoneSelect).toHaveValue('UTC');
    });

    test('should show bar when active window uses Europe/Warsaw timezone', async ({ page }) => {
      await loginAndOpenTopBarSettings(page);
      await resetToSingleBar(page);
      await ensureAtLeastBars(page, 2);
      await openPanel(page, 0);

      const barId = await getBarIdByIndex(page, 0);
      const now = new Date();
      const from = new Date(now.getTime() - 2 * 60 * 60 * 1000);
      const to = new Date(now.getTime() + 2 * 60 * 60 * 1000);
      const fromValue = toDatetimeLocalValue(from);
      const toValue = toDatetimeLocalValue(to);

      await enableSchedule(page, 0);
      await openPanel(page, 0);
      await setScheduleTimezone(page, 0, 'Europe/Warsaw');

      const schedulePanel = schedulePanelLocator(page, 0);
      const dateInputs = schedulePanel.locator('input.top-bar-life-time-datetime[type="datetime-local"]');
      const fromInput = dateInputs.nth(0);
      const toInput = dateInputs.nth(1);

      await fromInput.fill(fromValue);
      await fromInput.blur();
      await waitForTopBarPutWhere(page, (body) => body.includes(`"scheduled_from_datetime":"${fromValue}"`));
      await openPanel(page, 0);

      await toInput.fill(toValue);
      await toInput.blur();
      await waitForTopBarPutWhere(page, (body) => body.includes('"scheduled_timezone":"Europe/Warsaw"'));

      await publishBar(page, 0, barId);
      await page.goto('/');
      await expect(page.locator(`[data-top-bar-id="${barId}"]`)).toHaveCount(1);
    });

    test('should hide bar when schedule window is in the future for Europe/Warsaw timezone', async ({ page }) => {
      await loginAndOpenTopBarSettings(page);
      await resetToSingleBar(page);
      await ensureAtLeastBars(page, 2);
      await openPanel(page, 0);

      const barId = await getBarIdByIndex(page, 0);

      await enableSchedule(page, 0);
      await openPanel(page, 0);
      await setScheduleTimezone(page, 0, 'Europe/Warsaw');

      const schedulePanel = schedulePanelLocator(page, 0);
      const dateInputs = schedulePanel.locator('input.top-bar-life-time-datetime[type="datetime-local"]');
      const fromInput = dateInputs.nth(0);
      const toInput = dateInputs.nth(1);

      await fromInput.fill('2099-03-21T11:00');
      await fromInput.blur();
      await waitForTopBarPutWhere(page, (body) => body.includes('"scheduled_from_datetime":"2099-03-21T11:00"'));
      await openPanel(page, 0);

      await toInput.fill('2099-03-21T12:30');
      await toInput.blur();
      await waitForTopBarPutWhere(page, (body) => body.includes('"scheduled_timezone":"Europe/Warsaw"'));

      await publishBar(page, 0, barId);
      await page.goto('/');
      await expect(page.locator(`[data-top-bar-id="${barId}"]`)).toHaveCount(0);
    });
  });
});
