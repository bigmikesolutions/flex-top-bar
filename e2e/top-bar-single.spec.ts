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

  test.describe('basic settings - position', () => {
    test('should save bar as top and render it at top', async ({ page }) => {
      await loginAndOpenTopBarSettings(page);
      await resetToSingleBar(page);

      const id0 = await getBarIdByIndex(page, 0);
      await setBarPosition(page, 0, 'top');

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

      // Enable scheduling (Vue auto-saves on change)
      const scheduleSave = waitForTopBarPut(page);
      await page.locator('label.top-bar-life-time-checkbox').first().click();
      await scheduleSave;

      const fromInput = page.locator(`#scheduled_from_${barId}`);
      const toInput = page.locator(`#scheduled_to_${barId}`);
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

      // Wide range around now to avoid timezone edge cases.
      const now = new Date();
      const from = new Date(now.getTime() - 24 * 60 * 60 * 1000);
      const to = new Date(now.getTime() + 24 * 60 * 60 * 1000);
      const fromValue = toDatetimeLocalValue(from);
      const toValue = toDatetimeLocalValue(to);

      const scheduleSave2 = waitForTopBarPut(page);
      await page.locator('label.top-bar-life-time-checkbox').first().click();
      await scheduleSave2;

      const fromInput = page.locator(`#scheduled_from_${barId}`);
      const toInput = page.locator(`#scheduled_to_${barId}`);
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

      const messageList = page.locator('.top-bar-message-list').first();
      const addTextButton = page.getByRole('button', { name: 'Add new text' }).first();
      const beforeCount = await messageList.locator('.top-bar-column-creator-grid').count();

      const addSave = waitForTopBarPut(page);
      await addTextButton.click();
      await addSave;
      await page.waitForLoadState('domcontentloaded');

      const afterCount = await messageList.locator('.top-bar-column-creator-grid').count();
      expect(afterCount).toBe(beforeCount + 1);
    });

    test('should add a new message field and then remove it', async ({ page }) => {
      await loginAndOpenTopBarSettings(page);
      await resetToSingleBar(page);
      await ensureAtLeastBars(page, 1);
      await openPanel(page, 0);

      const messageList = page.locator('.top-bar-message-list').first();
      const addTextButton = page.getByRole('button', { name: 'Add new text' }).first();

      const addSave2 = waitForTopBarPut(page);
      await addTextButton.click();
      await addSave2;
      await openPanel(page, 0);

      const afterAddCount = await messageList.locator('.top-bar-column-creator-grid').count();
      const removeButtons = messageList.locator('.item-creator.no').getByRole('button', { name: 'X' });
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
        const removeLoop = messageList.locator('.item-creator.no').getByRole('button', { name: 'X' });
        const removeLoopSave = waitForTopBarPut(page);
        await removeLoop.last().click();
        await removeLoopSave;
        await openPanel(page, 0);
      }

      await expect(messageList.locator('.top-bar-column-creator-grid')).toHaveCount(1);
      await expect(messageList.locator('.item-creator.no').getByRole('button', { name: 'X' })).toHaveCount(0);
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

      while ((await messageList.locator('.top-bar-column-creator-grid').count()) < 2) {
        const addLoopSave = waitForTopBarPut(page);
        await addTextButton.click();
        await addLoopSave;
        await openPanel(page, 0);
      }

      const effectSave = waitForTopBarPut(page);
      await effectSelect.selectOption(effect);
      await effectSave;

      const textareas = barRow.locator('.top-bar-message-list textarea');
      await textareas.nth(0).fill(firstMessage);
      const msg0Save = waitForTopBarPut(page);
      await textareas.nth(0).blur();
      await msg0Save;

      await textareas.nth(1).fill(secondMessage);
      const msg1Save = waitForTopBarPut(page);
      await textareas.nth(1).blur();
      await msg1Save;

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

    test('should show concatenated messages on frontend when effect is none', async ({ page }) => {
      const messageOne = 'No effect first';
      const messageTwo = 'No effect second';
      const id0 = await configureEffectAndMessages(page, 'none', messageOne, messageTwo);

      await page.goto('/');
      const topBarInner = page.locator(`[data-top-bar-id="${id0}"] .top-bar__inner`);

      await expect(topBarInner).toContainText(`${messageOne} ${messageTwo}`);
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