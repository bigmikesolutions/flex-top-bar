import { expect, test } from '@playwright/test';
import {
  ensureAtLeastBars,
  loginAndOpenTopBarSettings,
  openPanel,
  resetToSingleBar,
  resetToSingleColumnBar,
  getBarIdByIndex,
  waitForTopBarPut,
} from './helpers/topBarHelpers';

test.describe('icons - social media', () => {

  test('should change social icon appearance (rounded → square with custom colors) and render on frontend', async ({
    page,
  }) => {
    await loginAndOpenTopBarSettings(page);
    await resetToSingleColumnBar(page, 'social');
    await openPanel(page, 0);

    const barId = await getBarIdByIndex(page, 0);
    const columnId = 'col_front_social';

    const appearanceFieldset = page
      .locator('.top-bar-row.bg')
      .first()
      .locator('fieldset')
      .filter({ has: page.locator('legend', { hasText: 'Choose the icon appearance' }) })
      .first();
    const styleLabels = appearanceFieldset.locator('label');
    await expect(styleLabels).toHaveCount(5);

    async function setStyleByIndex(index: number) {
      const save = waitForTopBarPut(page);
      await styleLabels.nth(index).click({ force: true });
      await save;
    }

    async function setPillColors(bg: string, fg: string) {
      const bgInput = page.locator(`#social_bg_${barId}_${columnId}`);
      const iconInput = page.locator(`#social_icon_${barId}_${columnId}`);
      await expect(bgInput).toBeVisible();
      await expect(iconInput).toBeVisible();

      await bgInput.fill(bg);
      const bgSave = waitForTopBarPut(page);
      await bgInput.blur();
      await bgSave;

      await iconInput.fill(fg);
      const fgSave = waitForTopBarPut(page);
      await iconInput.blur();
      await fgSave;
    }

    async function assertFrontendStyle(expected: 'rounded' | 'square' | 'black' | 'white' | 'color') {
      await page.goto('/');
      const bar = page.locator(`[data-top-bar-id="${barId}"]`);
      const socialColumn = bar.locator('.top-bar-social-column');
      const icon = socialColumn.locator('.top-bar-icon--social').first();

      await expect(socialColumn).toHaveClass(new RegExp(`top-bar-social-column--${expected}`));
      await expect(icon).toHaveCount(1);

      if (expected === 'color') {
        // Color style uses background-image and does NOT use masking.
        await expect(icon).toHaveAttribute('style', /background-image:\s*url\(/i);
        await expect(icon).not.toHaveAttribute('style', /mask-image/i);
      } else {
        // Other styles use masking (black/white fixed, rounded/square uses configured icon color).
        await expect(icon).toHaveAttribute('style', /mask-image/i);
      }
    }

    // Verify all appearance options:
    // 0 Rounded, 1 Square, 2 Black, 3 White, 4 Color (per iconStyleOptions)

    // Square + custom colors
    await setStyleByIndex(1);
    await setPillColors('#123456', '#abcdef');
    await assertFrontendStyle('square');
    await expect(page.locator(`[data-top-bar-id="${barId}"] .top-bar-social-column`)).toHaveAttribute(
      'style',
      /--top-bar-social-bg:\s*#123456/i
    );
    await expect(page.locator(`[data-top-bar-id="${barId}"] .top-bar-social-column`)).toHaveAttribute(
      'style',
      /--top-bar-social-fg:\s*#abcdef/i
    );

    // Rounded
    await loginAndOpenTopBarSettings(page);
    await openPanel(page, 0);
    await setStyleByIndex(0);
    await assertFrontendStyle('rounded');

    // Black
    await loginAndOpenTopBarSettings(page);
    await openPanel(page, 0);
    await setStyleByIndex(2);
    await assertFrontendStyle('black');

    // White
    await loginAndOpenTopBarSettings(page);
    await openPanel(page, 0);
    await setStyleByIndex(3);
    await assertFrontendStyle('white');

    // Color (original SVG colors)
    await loginAndOpenTopBarSettings(page);
    await openPanel(page, 0);
    await setStyleByIndex(4);
    await assertFrontendStyle('color');
  });

});