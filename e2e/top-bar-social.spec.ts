import { expect, test } from '@playwright/test';
import {
  getBarIdByIndex,
  loginAndOpenTopBarSettings,
  openPanel,
  resetToSingleColumnBar,
  waitForTopBarPut,
  waitForTopBarPutWhere,
} from './helpers/topBarHelpers';

test.describe('social media column', () => {
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

    const styles: Array<'rounded' | 'square' | 'black' | 'white' | 'color'> = [
      'rounded',
      'square',
      'black',
      'white',
      'color',
    ];

    async function setStyleByIndex(index: number) {
      const style = styles[index]!;
      await openPanel(page, 0);
      await styleLabels.nth(index).scrollIntoViewIfNeeded();
      const radio = appearanceFieldset.locator('input[type="radio"]').nth(index);
      await Promise.all([
        waitForTopBarPutWhere(page, (body) => body.includes(`"icon_style":"${style}"`)),
        radio.evaluate((el: HTMLInputElement) => el.click()),
      ]);
    }

    async function publishThisBar() {
      page.once('dialog', (d) => d.accept());
      const publishBtn = page.locator('.top-bar-row.bg').first().locator('button.top-bar-icons.publish');
      const publishSave = page.waitForResponse((r) => {
        if (r.request().method() !== 'POST' || !r.ok()) return false;
        const url = decodeURIComponent(r.url());
        return new RegExp(`/(flex-top-bar|top-bar)/v1/bars/${barId}/publish`, 'i').test(url);
      });
      await publishBtn.click();
      await publishSave;
    }

    async function setPillColors(bg: string, fg: string) {
      await openPanel(page, 0);
      const row = page.locator('.top-bar-row.bg').first();
      const panel = row.locator('.top-bar-options.active');
      await expect(panel).toBeVisible({ timeout: 15000 });

      const bgInput = panel.locator(`#social_bg_${barId}_${columnId}`);
      const iconInput = panel.locator(`#social_icon_${barId}_${columnId}`);
      await expect(bgInput).toHaveCount(1, { timeout: 15000 });
      await expect(iconInput).toHaveCount(1, { timeout: 15000 });
      await expect(bgInput).toBeVisible({ timeout: 15000 });
      await expect(iconInput).toBeVisible({ timeout: 15000 });

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
        await expect(icon).toHaveAttribute('style', /background-image:\s*url\(/i);
        await expect(icon).not.toHaveAttribute('style', /mask-image/i);
      } else {
        await expect(icon).toHaveAttribute('style', /mask-image/i);
      }
    }

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

    await loginAndOpenTopBarSettings(page);
    await openPanel(page, 0);
    await setStyleByIndex(0);
    await publishThisBar();
    await assertFrontendStyle('rounded');

    await loginAndOpenTopBarSettings(page);
    await openPanel(page, 0);
    await setStyleByIndex(2);
    await publishThisBar();
    await assertFrontendStyle('black');

    await loginAndOpenTopBarSettings(page);
    await openPanel(page, 0);
    await setStyleByIndex(3);
    await publishThisBar();
    await assertFrontendStyle('white');

    await loginAndOpenTopBarSettings(page);
    await openPanel(page, 0);
    await setStyleByIndex(4);
    await publishThisBar();
    await assertFrontendStyle('color');
  });
});
