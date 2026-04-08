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

  test('should change social icon appearance (rounded/square/black/white/color) and render on frontend', async ({
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

    async function publishThisBar() {
      // Publish draft -> frontend for this bar.
      page.once('dialog', (d) => d.accept());
      const publishBtn = page.locator('.top-bar-row.bg').first().locator('button.top-bar-icons.publish');
      const publishSave = page.waitForResponse((r) => {
        if (r.request().method() !== 'POST' || !r.ok()) return false;
        const url = decodeURIComponent(r.url());
        return new RegExp(`/top-bar/v1/bars/${barId}/publish`, 'i').test(url);
      });
      await publishBtn.click();
      await publishSave;
    }

    async function setPillColors(bg: string, fg: string) {
      // Avoid coupling to exact `id` formatting; locate by the visible fieldset legends.
      const row = page.locator('.top-bar-row.bg').first();
      const bgInput = row
        .locator('fieldset')
        .filter({ has: row.locator('legend', { hasText: 'Background color' }) })
        .locator('input[type="color"]')
        .first();
      const iconInput = row
        .locator('fieldset')
        .filter({ has: row.locator('legend', { hasText: 'Color icon' }) })
        .locator('input[type="color"]')
        .first();
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
    await publishThisBar();
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
    await publishThisBar();
    await assertFrontendStyle('rounded');

    // Black
    await loginAndOpenTopBarSettings(page);
    await openPanel(page, 0);
    await setStyleByIndex(2);
    await publishThisBar();
    await assertFrontendStyle('black');

    // White
    await loginAndOpenTopBarSettings(page);
    await openPanel(page, 0);
    await setStyleByIndex(3);
    await publishThisBar();
    await assertFrontendStyle('white');

    // Color (original SVG colors)
    await loginAndOpenTopBarSettings(page);
    await openPanel(page, 0);
    await setStyleByIndex(4);
    await publishThisBar();
    await assertFrontendStyle('color');
  });

});

test.describe('icons - contact column', () => {
  test('should change contact icon appearance (rounded/square/black/white/color) and render on frontend', async ({
    page,
  }) => {
    await loginAndOpenTopBarSettings(page);
    await resetToSingleColumnBar(page, 'contact');
    await openPanel(page, 0);

    const barId = await getBarIdByIndex(page, 0);
    const columnId = 'col_front_contact';

    const appearanceFieldset = page
      .locator('.top-bar-row.bg')
      .first()
      .locator('fieldset')
      .filter({ has: page.locator('legend', { hasText: 'Choose the icon appearance' }) })
      .first();
    const styleLabels = appearanceFieldset.locator('label');
    // Contact editor hides "color" option (only 4 appearances available).
    await expect(styleLabels).toHaveCount(4);

    async function setStyleByIndex(index: number) {
      const save = waitForTopBarPut(page);
      await styleLabels.nth(index).click({ force: true });
      await save;
    }

    async function publishThisBar() {
      page.once('dialog', (d) => d.accept());
      const publishBtn = page.locator('.top-bar-row.bg').first().locator('button.top-bar-icons.publish');
      const publishSave = page.waitForResponse((r) => {
        if (r.request().method() !== 'POST' || !r.ok()) return false;
        const url = decodeURIComponent(r.url());
        return new RegExp(`/top-bar/v1/bars/${barId}/publish`, 'i').test(url);
      });
      await publishBtn.click();
      await publishSave;
    }

    async function setPillColors(bg: string, fg: string) {
      // Avoid coupling to exact `id` formatting; locate by the visible fieldset legends.
      const row = page.locator('.top-bar-row.bg').first();
      const bgInput = row
        .locator('fieldset')
        .filter({ has: row.locator('legend', { hasText: 'Background color' }) })
        .locator('input[type="color"]')
        .first();
      const iconInput = row
        .locator('fieldset')
        .filter({ has: row.locator('legend', { hasText: 'Color icon' }) })
        .locator('input[type="color"]')
        .first();
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

    async function assertFrontendStyle(expected: 'rounded' | 'square' | 'black' | 'white') {
      await page.goto('/');
      const bar = page.locator(`[data-top-bar-id="${barId}"]`);
      const contactColumn = bar.locator('.top-bar-contact-column');
      const icon = contactColumn.locator('.top-bar-icon--contact').first();

      await expect(contactColumn).toHaveClass(new RegExp(`top-bar-contact-column--${expected}`));
      await expect(icon).toHaveCount(1);

      await expect(icon).toHaveAttribute('style', /mask-image/i);
    }

    // Verify all appearance options:
    // 0 Rounded, 1 Square, 2 Black, 3 White

    // Square + custom colors
    await setStyleByIndex(1);
    await setPillColors('#123456', '#abcdef');
    await publishThisBar();
    await assertFrontendStyle('square');
    await expect(page.locator(`[data-top-bar-id="${barId}"] .top-bar-contact-column`)).toHaveAttribute(
      'style',
      /--top-bar-contact-bg:\s*#123456/i
    );
    await expect(page.locator(`[data-top-bar-id="${barId}"] .top-bar-contact-column`)).toHaveAttribute(
      'style',
      /--top-bar-contact-fg:\s*#abcdef/i
    );

    // Rounded
    await loginAndOpenTopBarSettings(page);
    await openPanel(page, 0);
    await setStyleByIndex(0);
    await publishThisBar();
    await assertFrontendStyle('rounded');

    // Black
    await loginAndOpenTopBarSettings(page);
    await openPanel(page, 0);
    await setStyleByIndex(2);
    await publishThisBar();
    await assertFrontendStyle('black');

    // White
    await loginAndOpenTopBarSettings(page);
    await openPanel(page, 0);
    await setStyleByIndex(3);
    await publishThisBar();
    await assertFrontendStyle('white');
  });
});