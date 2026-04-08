import { expect, test, type Page } from '@playwright/test';
import {
  ensureAtLeastBars,
  loginAndOpenTopBarSettings,
  openPanel,
  resetToSingleBar,
  toDatetimeLocalValue,
  getBarIdByIndex,
  setBarPosition,
  setBarHideOnScroll,
  waitForTopBarPut,
} from './helpers/topBarHelpers';

declare const process: { env: Record<string, string | undefined> };

test.describe('single-bar', () => {

  async function publishBar(page: Page, barIndex: number, barId: string): Promise<void> {
    page.once('dialog', (d) => d.accept());
    const publishBtn = page.locator('.top-bar-row.bg').nth(barIndex).locator('button.top-bar-icons.publish');
    // If there are no unpublished changes, publish can be a no-op.
    if (await publishBtn.evaluate((el) => el.classList.contains('top-bar-publish--dirty')).catch(() => false)) {
      const publishRequest = page.waitForResponse(
        (r) => {
          if (r.request().method() !== 'POST' || !r.ok()) return false;
          const url = decodeURIComponent(r.url());
          // Match both endpoints: single-bar publish and bulk publish.
          // Supports both pretty (/wp-json/…) and plain (?rest_route=/…) WP REST styles.
          return (
            new RegExp(`flex-top-bar/v1/bars/${barId}/publish`, 'i').test(url) ||
            /flex-top-bar\/v1\/publish/i.test(url)
          );
        },
        { timeout: 45000 }
      );

      // Some WP setups can complete publish without us reliably observing the response (navigation races,
      // request routed via service worker/proxy, etc.). The UI reliably reflects completion by clearing
      // the dirty marker, so accept either signal.
      await publishBtn.click();
      await Promise.race([
        publishRequest,
        expect(publishBtn).not.toHaveClass(/top-bar-publish--dirty/, { timeout: 45000 }),
      ]);
    }
  }

  test.describe('basic settings - position', () => {
    test('should save bar as top and render it at top', async ({ page }) => {
      await loginAndOpenTopBarSettings(page);
      await resetToSingleBar(page);

      const id0 = await getBarIdByIndex(page, 0);
      await setBarPosition(page, 0, 'top');
      await publishBar(page, 0, id0);

      await page.goto('/');

      const topBar = page.locator(`[data-top-bar-id="${id0}"]`);
      await expect(topBar).toHaveCount(1);
      await expect(topBar).toHaveAttribute('data-top-bar-position', 'top');
      await expect(topBar).toHaveClass(/top-bar--top/);
    });

    test('should save bar as bottom and render it at bottom', async ({ page }) => {
      await loginAndOpenTopBarSettings(page);
      await resetToSingleBar(page);

      const id0 = await getBarIdByIndex(page, 0);
      await setBarPosition(page, 0, 'bottom');
      await publishBar(page, 0, id0);

      await page.goto('/');

      const bottomBar = page.locator(`[data-top-bar-id="${id0}"]`);
      await expect(bottomBar).toHaveCount(1);
      await expect(bottomBar).toHaveAttribute('data-top-bar-position', 'bottom');
      await expect(bottomBar).toHaveClass(/top-bar--bottom/);
    });
  });

  test.describe('basic settings - hide on scroll', () => {
    test('should save top position with hide-on-scroll and hide after window scroll', async ({ page }) => {
      await loginAndOpenTopBarSettings(page);
      await resetToSingleBar(page);
      await openPanel(page, 0);

      const id0 = await getBarIdByIndex(page, 0);
      await setBarPosition(page, 0, 'top');
      await setBarHideOnScroll(page, 0, true);
      await publishBar(page, 0, id0);

      await page.goto('/');

      const topBar = page.locator(`[data-top-bar-id="${id0}"]`);
      await expect(topBar).toHaveCount(1);
      await expect(topBar).toHaveAttribute('data-top-bar-position', 'top');
      await expect(topBar).toHaveClass(/top-bar--top/);
      await expect(topBar).toHaveAttribute('data-top-bar-hide-on-scroll', '1');

      // Initial state at top of page: visible.
      await expect(topBar).toBeVisible();
      await expect(topBar).not.toHaveCSS('display', 'none');

      // Scroll past threshold (30px) and verify the bar is hidden by script.
      await page.evaluate(() => window.scrollTo(0, 200));
      await page.waitForTimeout(500); // Wait for scroll handler to execute
      await expect(topBar).toBeHidden();
    });

    test('should save top position with hide-on-scroll disabled and stay visible after window scroll', async ({ page }) => {
      await loginAndOpenTopBarSettings(page);
      await resetToSingleBar(page);
      await openPanel(page, 0);

      const id0 = await getBarIdByIndex(page, 0);
      await setBarPosition(page, 0, 'top');
      await setBarHideOnScroll(page, 0, false);
      await publishBar(page, 0, id0);

      await page.goto('/');

      const topBar = page.locator(`[data-top-bar-id="${id0}"]`);
      await expect(topBar).toHaveCount(1);
      await expect(topBar).toHaveAttribute('data-top-bar-position', 'top');
      await expect(topBar).toHaveClass(/top-bar--top/);
      // Disabled mode omits the attribute entirely in frontend markup.
      await expect(topBar).not.toHaveAttribute('data-top-bar-hide-on-scroll', '1');

      await expect(topBar).toBeVisible();
      await expect(topBar).not.toHaveCSS('display', 'none');

      await page.evaluate(() => window.scrollTo(0, 200));
      await expect(topBar).toBeVisible();
      await expect(topBar).not.toHaveCSS('display', 'none');
    });

    test('should save bottom position with hide-on-scroll and hide after window scroll', async ({ page }) => {
      await loginAndOpenTopBarSettings(page);
      await resetToSingleBar(page);
      await openPanel(page, 0);

      const id0 = await getBarIdByIndex(page, 0);
      await setBarPosition(page, 0, 'bottom');
      await setBarHideOnScroll(page, 0, true);
      await publishBar(page, 0, id0);

      await page.goto('/');

      const bottomBar = page.locator(`[data-top-bar-id="${id0}"]`);
      await expect(bottomBar).toHaveCount(1);
      await expect(bottomBar).toHaveAttribute('data-top-bar-position', 'bottom');
      await expect(bottomBar).toHaveClass(/top-bar--bottom/);
      await expect(bottomBar).toHaveAttribute('data-top-bar-hide-on-scroll', '1');

      await expect(bottomBar).toBeVisible();
      await expect(bottomBar).not.toHaveCSS('display', 'none');

      await page.evaluate(() => window.scrollTo(0, 200));
      await page.waitForTimeout(500); // Wait for scroll handler to execute
      await expect(bottomBar).toBeHidden();
    });

    test('should save bottom position with hide-on-scroll disabled and stay visible after window scroll', async ({ page }) => {
      await loginAndOpenTopBarSettings(page);
      await resetToSingleBar(page);
      await openPanel(page, 0);

      const id0 = await getBarIdByIndex(page, 0);
      await setBarPosition(page, 0, 'bottom');
      await setBarHideOnScroll(page, 0, false);
      await publishBar(page, 0, id0);

      await page.goto('/');

      const bottomBar = page.locator(`[data-top-bar-id="${id0}"]`);
      await expect(bottomBar).toHaveCount(1);
      await expect(bottomBar).toHaveAttribute('data-top-bar-position', 'bottom');
      await expect(bottomBar).toHaveClass(/top-bar--bottom/);
      await expect(bottomBar).not.toHaveAttribute('data-top-bar-hide-on-scroll', '1');

      await expect(bottomBar).toBeVisible();
      await expect(bottomBar).not.toHaveCSS('display', 'none');

      await page.evaluate(() => window.scrollTo(0, 200));
      await expect(bottomBar).toBeVisible();
      await expect(bottomBar).not.toHaveCSS('display', 'none');
    });
  });

  test.describe('scheduled', () => {
    test('should set future schedule and make top-bar hidden on frontend', async ({ page }) => {
      await loginAndOpenTopBarSettings(page);
      await resetToSingleBar(page);
      await ensureAtLeastBars(page, 2);
      await openPanel(page, 0);

      const barId = await getBarIdByIndex(page, 0);
      const barRow = page.locator('.top-bar-row.bg').nth(0);

      // Scheduling is a plan feature flag (FF_SCHEDULE). Skip if disabled in this environment.
      const scheduleToggle = barRow.locator('.top-bar-toggle-life-time');
      const toggleDisabled = await scheduleToggle.isDisabled().catch(() => false);
      test.skip(toggleDisabled, 'Scheduling feature flag is disabled (FF_SCHEDULE=false).');

      // Enable scheduling (Vue auto-saves on change).
      if (!(await scheduleToggle.isChecked().catch(() => false))) {
        await Promise.all([
          waitForTopBarPut(page),
          scheduleToggle.check({ force: true }),
        ]);
      }

      await expect(scheduleToggle).toBeChecked({ timeout: 15000 });

      const schedulePanel = barRow.locator('.top-bar-lifetime-panel');
      await expect(schedulePanel).toBeVisible({ timeout: 15000 });

      // Find schedule inputs by labels (IDs can vary if bar IDs differ).
      const dateInputs = schedulePanel.locator('input.top-bar-life-time-datetime[type="datetime-local"]');
      const fromInput = dateInputs.nth(0);
      const toInput = dateInputs.nth(1);
      await expect(fromInput).toBeVisible({ timeout: 15000 });
      await expect(toInput).toBeVisible({ timeout: 15000 });
      await fromInput.fill('2099-03-21T11:00');
      const fromSave = waitForTopBarPut(page);
      await fromInput.blur();
      await fromSave;

      await toInput.fill('2099-03-21T12:30');
      const toSave = waitForTopBarPut(page);
      await toInput.blur();
      await toSave;

      await page.reload();
      await openPanel(page, 0);

      // Verify schedule is set
      const scheduled = page.locator('.top-bar-toggle-life-time').nth(0);
      await expect(scheduled).toBeChecked();
      await expect(fromInput).toHaveValue('2099-03-21T11:00');
      await expect(toInput).toHaveValue('2099-03-21T12:30');

      await page.goto('/');
      await expect(page.locator(`[data-top-bar-id="${barId}"]`)).toHaveCount(0);
    });

    test('should set schedule covering now and make top-bar visible on frontend', async ({ page }) => {
      await loginAndOpenTopBarSettings(page);
      await resetToSingleBar(page);
      await ensureAtLeastBars(page, 2);
      await openPanel(page, 0);

      const barId = await getBarIdByIndex(page, 0);
      const barRow = page.locator('.top-bar-row.bg').nth(0);

      const scheduleToggle = barRow.locator('.top-bar-toggle-life-time');
      const toggleDisabled = await scheduleToggle.isDisabled().catch(() => false);
      test.skip(toggleDisabled, 'Scheduling feature flag is disabled (FF_SCHEDULE=false).');

      // Wide range around now to avoid timezone edge cases.
      const now = new Date();
      const from = new Date(now.getTime() - 24 * 60 * 60 * 1000);
      const to = new Date(now.getTime() + 24 * 60 * 60 * 1000);
      const fromValue = toDatetimeLocalValue(from);
      const toValue = toDatetimeLocalValue(to);

      if (!(await scheduleToggle.isChecked().catch(() => false))) {
        await Promise.all([
          waitForTopBarPut(page),
          scheduleToggle.check({ force: true }),
        ]);
      }

      await expect(scheduleToggle).toBeChecked({ timeout: 15000 });

      const schedulePanel = barRow.locator('.top-bar-lifetime-panel');
      await expect(schedulePanel).toBeVisible({ timeout: 15000 });

      const dateInputs = schedulePanel.locator('input.top-bar-life-time-datetime[type="datetime-local"]');
      const fromInput = dateInputs.nth(0);
      const toInput = dateInputs.nth(1);
      await expect(fromInput).toBeVisible({ timeout: 15000 });
      await expect(toInput).toBeVisible({ timeout: 15000 });
      await fromInput.fill(fromValue);
      const fromSave2 = waitForTopBarPut(page);
      await fromInput.blur();
      await fromSave2;

      await toInput.fill(toValue);
      const toSave2 = waitForTopBarPut(page);
      await toInput.blur();
      await toSave2;

      await page.reload();
      await openPanel(page, 0);

      // Verify schedule is set
      const scheduled = page.locator('.top-bar-toggle-life-time').nth(0);
      await expect(scheduled).toBeChecked();
      await expect(fromInput).toHaveValue(fromValue);
      await expect(toInput).toHaveValue(toValue);

      await page.goto('/');
      await expect(page.locator(`[data-top-bar-id="${barId}"]`)).toHaveCount(1);
    });
  });

  test.describe('multi-message - admin panel', () => {
    test('should add a new message field', async ({ page }) => {
      await loginAndOpenTopBarSettings(page);
      await resetToSingleBar(page);
      await ensureAtLeastBars(page, 1);
      await openPanel(page, 0);

      // "Add new text" is only available when effect is not "none".
      const effectSelect = page.locator('select').filter({
        has: page.locator('option[value="slider"]'),
      }).first();
      const effectDisabled = await effectSelect.isDisabled().catch(() => false);
      test.skip(effectDisabled, 'Multi-message is disabled by plan (FF_MAX_MESSAGES <= 1).');

      await Promise.all([waitForTopBarPut(page), effectSelect.selectOption('slider')]);

      const messageList = page.locator('.top-bar-message-list').first();
      const addTextButton = page.getByRole('button', { name: 'Add new text' }).first();
      const canAdd = await addTextButton.isVisible().catch(() => false);
      test.skip(!canAdd, 'Multi-message is disabled by plan (no "Add new text" control).');
      const beforeCount = await messageList.locator('.top-bar-column-creator-grid').count();

      await Promise.all([waitForTopBarPut(page), addTextButton.click()]);

      const afterCount = await messageList.locator('.top-bar-column-creator-grid').count();
      expect(afterCount).toBe(beforeCount + 1);
    });

    test('should add a new message field and then remove it', async ({ page }) => {
      await loginAndOpenTopBarSettings(page);
      await resetToSingleBar(page);
      await ensureAtLeastBars(page, 1);
      await openPanel(page, 0);

      // "Add new text" is only available when effect is not "none".
      const effectSelect = page.locator('select').filter({
        has: page.locator('option[value="slider"]'),
      }).first();
      const effectSave = waitForTopBarPut(page);
      await effectSelect.selectOption('slider');
      await effectSave;

      const messageList = page.locator('.top-bar-message-list').first();
      const addTextButton = page.getByRole('button', { name: 'Add new text' }).first();

      const addSave2 = waitForTopBarPut(page);
      await addTextButton.click();
      await addSave2;
      await openPanel(page, 0);

      const afterAddCount = await messageList.locator('.top-bar-column-creator-grid').count();
      const removeButtons = messageList.locator('button.top-bar-btn.top-bar-icons.delete.mask.black.remove.empty');
      const removeSave = waitForTopBarPut(page);
      await removeButtons.last().click();
      await removeSave;
      await openPanel(page, 0);

      const afterRemoveCount = await messageList.locator('.top-bar-column-creator-grid').count();
      expect(afterRemoveCount).toBe(afterAddCount - 1);
    });

    test('should not allow removing first message field', async ({ page }) => {
      await loginAndOpenTopBarSettings(page);
      await resetToSingleBar(page);
      await ensureAtLeastBars(page, 1);
      await openPanel(page, 0);

      const messageList = page.locator('.top-bar-message-list').first();

      // Reduce to a single message field.
      while ((await messageList.locator('.top-bar-column-creator-grid').count()) > 1) {
        const removeLoop = messageList.locator('button.top-bar-btn.top-bar-icons.delete.mask.black.remove.empty');
        const removeLoopSave = waitForTopBarPut(page);
        await removeLoop.last().click();
        await removeLoopSave;
        await openPanel(page, 0);
      }

      await expect(messageList.locator('.top-bar-column-creator-grid')).toHaveCount(1);
      await expect(messageList.locator('button.top-bar-btn.top-bar-icons.delete.mask.black.remove.empty')).toHaveCount(0);
    });
  });

  test.describe('multi-message - effects', () => {
    async function configureEffectAndMessages(
      page: Page,
      effect: 'none' | 'slider' | 'fadein' | 'blink',
      firstMessage: string,
      secondMessage: string
    ): Promise<string> {
      await loginAndOpenTopBarSettings(page);
      await resetToSingleBar(page);
      await ensureAtLeastBars(page, 1);
      await openPanel(page, 0);

      const id0 = await getBarIdByIndex(page, 0);
      const barRow = page.locator('.top-bar-row.bg').first();
      const effectSelect = barRow
        .locator('fieldset')
        .filter({ has: page.locator('legend', { hasText: 'Effect' }) })
        .locator('select')
        .first();
      const messageList = barRow.locator('.top-bar-message-list').first();
      const addTextButton = page.getByRole('button', { name: 'Add new text' }).first();

      // Ensure UI allows multi-message editing (Add button is hidden for effect "none").
      const preSave = waitForTopBarPut(page);
      await effectSelect.selectOption('slider');
      await preSave;

      while ((await messageList.locator('.top-bar-column-creator-grid').count()) < 2) {
        const addLoopSave = waitForTopBarPut(page);
        await addTextButton.click();
        await addLoopSave;
        await openPanel(page, 0);
      }

      const textareas = barRow.locator('.top-bar-message-list textarea');
      await textareas.nth(0).fill(firstMessage);
      const msg0Save = waitForTopBarPut(page);
      await textareas.nth(0).blur();
      await msg0Save;

      await textareas.nth(1).fill(secondMessage);
      const msg1Save = waitForTopBarPut(page);
      await textareas.nth(1).blur();
      await msg1Save;

      // Apply requested effect after messages are configured.
      const effectSave = waitForTopBarPut(page);
      await effectSelect.selectOption(effect);
      await effectSave;

      // Publish this bar so frontend reflects the draft changes.
      page.once('dialog', (d) => d.accept());
      const publishBtn = barRow.locator('button.top-bar-icons.publish');
      if (await publishBtn.evaluate((el) => el.classList.contains('top-bar-publish--dirty')).catch(() => false)) {
        const publishSave = page.waitForResponse(
          (r) => {
            if (r.request().method() !== 'POST' || !r.ok()) return false;
            const url = decodeURIComponent(r.url());
            return (
              new RegExp(`/top-bar/v1/bars/${id0}/publish`, 'i').test(url) ||
              /\/top-bar\/v1\/publish/i.test(url)
            );
          },
          { timeout: 45000 }
        );
        await Promise.all([publishSave, publishBtn.click()]);
        await expect(publishBtn).not.toHaveClass(/top-bar-publish--dirty/);
      }

      return id0;
    }

    test('should rotate message text on frontend when effect is slider', async ({ page }) => {
      const messageOne = 'Slider first message';
      const messageTwo = 'Slider second message';
      const id0 = await configureEffectAndMessages(page, 'slider', messageOne, messageTwo);

      await page.goto('/');
      const topBarInner = page.locator(`[data-top-bar-id="${id0}"] .top-bar__inner`);

      await expect(topBarInner).toContainText(messageOne);
      await expect
        .poll(async () => (await topBarInner.textContent())?.trim() ?? '', { timeout: 7000 })
        .toBe(messageTwo);
    });

    test('should rotate message text on frontend when effect is fade-in', async ({ page }) => {
      const messageOne = 'Fade first message';
      const messageTwo = 'Fade second message';
      const id0 = await configureEffectAndMessages(page, 'fadein', messageOne, messageTwo);

      await page.goto('/');
      const topBarInner = page.locator(`[data-top-bar-id="${id0}"] .top-bar__inner`);

      await expect(topBarInner).toContainText(messageOne);
      await expect
        .poll(async () => (await topBarInner.textContent())?.trim() ?? '', { timeout: 7000 })
        .toBe(messageTwo);
    });

    test('should rotate message text on frontend when effect is blink', async ({ page }) => {
      const messageOne = 'Blink first message';
      const messageTwo = 'Blink second message';
      const id0 = await configureEffectAndMessages(page, 'blink', messageOne, messageTwo);

      await page.goto('/');
      const topBarInner = page.locator(`[data-top-bar-id="${id0}"] .top-bar__inner`);

      await expect(topBarInner).toContainText(messageOne);
      await expect
        .poll(async () => (await topBarInner.textContent())?.trim() ?? '', { timeout: 7000 })
        .toBe(messageTwo);
    });

    test('should show only the first message on frontend when effect is none', async ({ page }) => {
      const messageOne = 'No effect first';
      const messageTwo = 'No effect second';
      const id0 = await configureEffectAndMessages(page, 'none', messageOne, messageTwo);

      await page.goto('/');
      const topBarInner = page.locator(`[data-top-bar-id="${id0}"] .top-bar__inner`);

      await expect(topBarInner).toContainText(messageOne);
      await expect(topBarInner).not.toContainText(messageTwo);
    });
  });

  test.describe('multi-message - mobile support', () => {
    async function configureMessagesMobileVisibility(page: Page, mobileVisible: boolean): Promise<string> {
      await loginAndOpenTopBarSettings(page);
      await resetToSingleBar(page);
      await ensureAtLeastBars(page, 1);
      await openPanel(page, 0);

      const id0 = await getBarIdByIndex(page, 0);
      const barRow = page.locator('.top-bar-row.bg').first();
      const mobileVisibility = barRow
        .locator('fieldset')
        .filter({ has: page.locator('legend', { hasText: 'Visible on the mobile' }) })
        .locator('select')
        .first();

      const mobileSave = waitForTopBarPut(page);
      await mobileVisibility.selectOption({ label: mobileVisible ? 'On' : 'Off' });
      await mobileSave;

      // Frontend reads published bars only.
      page.once('dialog', (d) => d.accept());
      const publishBtn = barRow.locator('button.top-bar-icons.publish');
      if (await publishBtn.evaluate((el) => el.classList.contains('top-bar-publish--dirty')).catch(() => false)) {
        const publishSave = page.waitForResponse(
          (r) => {
            if (r.request().method() !== 'POST' || !r.ok()) return false;
            const url = decodeURIComponent(r.url());
            return (
              new RegExp(`/top-bar/v1/bars/${id0}/publish`, 'i').test(url) ||
              /\/top-bar\/v1\/publish/i.test(url)
            );
          },
          { timeout: 45000 }
        );
        await Promise.all([publishSave, publishBtn.click()]);
        await expect(publishBtn).not.toHaveClass(/top-bar-publish--dirty/);
      }

      return id0;
    }

    test('should show top bar on mobile when mobile visibility is enabled', async ({ page }) => {
      const id0 = await configureMessagesMobileVisibility(page, true);

      await page.setViewportSize({ width: 375, height: 812 });
      await page.goto('/');

      const topBar = page.locator(`[data-top-bar-id="${id0}"]`);
      await expect(topBar).toHaveCount(1);
      await expect(topBar).toBeVisible();
    });

    test('should hide top bar on mobile when mobile visibility is disabled', async ({ page }) => {
      const id0 = await configureMessagesMobileVisibility(page, false);

      await page.setViewportSize({ width: 375, height: 812 });
      await page.goto('/');

      const topBar = page.locator(`[data-top-bar-id="${id0}"]`);
      await expect(topBar).toHaveCount(1);
      const column = topBar.locator('.top-bar__column').first();
      await expect(column).toHaveClass(/top-bar__column--mobile-hidden/);
      await expect(column).toBeHidden();
    });
  });

});