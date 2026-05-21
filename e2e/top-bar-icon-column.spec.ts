import { expect, test } from '@playwright/test';
import {
  getBarIdByIndex,
  loginAndOpenTopBarSettings,
  openPanel,
  resetToSingleColumnBar,
  waitForTopBarPut,
  waitForTopBarPutWhere,
} from './helpers/topBarHelpers';

test.describe('icon + text column', () => {
  test('should save icon before/after text position and render on frontend', async ({ page }) => {
    await loginAndOpenTopBarSettings(page);
    await resetToSingleColumnBar(page, 'icon');
    await openPanel(page, 0);

    const barId = await getBarIdByIndex(page, 0);
    const columnText = 'E2E icon column';

    const row = page.locator('.top-bar-row.bg').first();
    const panel = () => row.locator('.top-bar-options.active');

    const positionFieldset = () =>
      panel()
        .locator('fieldset')
        .filter({ has: page.locator('legend', { hasText: 'Icon position' }) })
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

    async function setIconPosition(position: 'before' | 'after') {
      await openPanel(page, 0);
      await expect(panel()).toBeVisible({ timeout: 15000 });

      const labelText = position === 'before' ? 'Before text' : 'After text';
      const label = positionFieldset().locator('label').filter({ hasText: labelText });
      const radio = label.locator('input[type="radio"]');

      await label.scrollIntoViewIfNeeded();
      await Promise.all([
        waitForTopBarPutWhere(page, (body) => body.includes(`"icon_position":"${position}"`)),
        radio.evaluate((el: HTMLInputElement) => el.click()),
      ]);
    }

    async function setColumnText(text: string) {
      await openPanel(page, 0);
      await expect(panel()).toBeVisible({ timeout: 15000 });

      const textInput = panel()
        .locator('fieldset')
        .filter({ has: page.locator('legend', { hasText: 'Text' }) })
        .locator('input[type="text"]')
        .first();

      await textInput.fill(text);
      const save = waitForTopBarPut(page);
      await textInput.blur();
      await save;
    }

    async function assertAdminPosition(position: 'before' | 'after') {
      await openPanel(page, 0);
      const labelText = position === 'before' ? 'Before text' : 'After text';
      const radio = positionFieldset()
        .locator('label')
        .filter({ hasText: labelText })
        .locator('input[type="radio"]');
      await expect(radio).toBeChecked();
    }

    async function assertFrontendIconColumn(expected: {
      position: 'before' | 'after';
      text: string;
    }) {
      await page.goto('/');
      const bar = page.locator(`[data-top-bar-id="${barId}"]`);
      const iconColumn = bar.locator('.top-bar-icon-text-column');

      await expect(iconColumn).toHaveClass(
        new RegExp(`top-bar-icon-text-column--icon-${expected.position}`)
      );
      await expect(iconColumn.locator('.top-bar-icon-text-column__img')).toHaveCount(1);
      await expect(iconColumn.locator('.top-bar-icon-text-column__text')).toHaveText(expected.text);

      const flexDirection = await iconColumn.evaluate(
        (el) => window.getComputedStyle(el).flexDirection
      );
      if (expected.position === 'before') {
        expect(flexDirection).toBe('row');
      } else {
        expect(flexDirection).toBe('row-reverse');
      }
    }

    await expect(positionFieldset()).toBeVisible();
    await expect(positionFieldset().locator('label')).toHaveCount(2);
    await assertAdminPosition('before');
    await expect(panel().locator('.top-bar-icon-column-editor__preview-img')).toHaveCount(1);

    await publishThisBar();
    await assertFrontendIconColumn({ position: 'before', text: columnText });

    await loginAndOpenTopBarSettings(page);
    await setIconPosition('after');
    await assertAdminPosition('after');
    await publishThisBar();
    await assertFrontendIconColumn({ position: 'after', text: columnText });

    await loginAndOpenTopBarSettings(page);
    await setIconPosition('before');
    const updatedText = 'Icon before label';
    await setColumnText(updatedText);
    await assertAdminPosition('before');
    await publishThisBar();
    await assertFrontendIconColumn({ position: 'before', text: updatedText });
  });
});
