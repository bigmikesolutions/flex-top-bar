import { expect, test } from '@playwright/test';
import {
  getBarIdByIndex,
  loginAndOpenTopBarSettings,
  openPanel,
  resetToSingleColumnBar,
  waitForTopBarPut,
  waitForTopBarPutWhere,
} from './helpers/topBarHelpers';

test.describe('contact column', () => {
  test('should change contact icon appearance (rounded/square/black/white) and render on frontend', async ({
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
    await expect(styleLabels).toHaveCount(4);

    const styles: Array<'rounded' | 'square' | 'black' | 'white'> = ['rounded', 'square', 'black', 'white'];

    async function setStyleByIndex(index: number) {
      const style = styles[index]!;
      await openPanel(page, 0);
      await styleLabels.nth(index).scrollIntoViewIfNeeded();
      await Promise.all([
        waitForTopBarPutWhere(page, (body) => body.includes(`"icon_style":"${style}"`)),
        styleLabels.nth(index).click({ force: true }),
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

      const bgInput = panel.locator(`#contact_bg_${barId}_${columnId}`);
      const iconInput = panel.locator(`#contact_icon_${barId}_${columnId}`);
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

    async function assertFrontendStyle(expected: 'rounded' | 'square' | 'black' | 'white') {
      await page.goto('/');
      const bar = page.locator(`[data-top-bar-id="${barId}"]`);
      const contactColumn = bar.locator('.top-bar-contact-column');
      const icon = contactColumn.locator('.top-bar-icon--contact').first();

      await expect(contactColumn).toHaveClass(new RegExp(`top-bar-contact-column--${expected}`));
      await expect(icon).toHaveCount(1);
      await expect(icon).toHaveAttribute('style', /mask-image/i);
    }

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
  });
});
