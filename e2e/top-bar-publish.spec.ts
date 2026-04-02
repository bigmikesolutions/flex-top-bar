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

test.describe('top bar - publish changes', () => {

  test('single bar: draft changes are not visible on frontend until published', async ({ page }) => {
    await loginAndOpenTopBarSettings(page);
    await resetToSingleBar(page);
    await ensureAtLeastBars(page, 1);
    await openPanel(page, 0);

    const barId = await getBarIdByIndex(page, 0);

    const original = 'Single bar for tests.';
    const draftMsg = `Draft only ${Date.now()}`;

    // Change message in admin (draft autosave).
    const textarea = page.locator('.top-bar-row.bg').first().locator('textarea').first();
    await textarea.fill(draftMsg);
    const save = waitForTopBarPut(page);
    await textarea.blur();
    await save;

    // Frontend should still show published content.
    await page.goto('/');
    const inner = page.locator(`[data-top-bar-id="${barId}"] .top-bar__inner`);
    await expect(inner).toContainText(original);
    await expect(inner).not.toContainText(draftMsg);
  });

  test('single bar: published changes become visible on frontend after publish click', async ({ page }) => {
    await loginAndOpenTopBarSettings(page);
    await resetToSingleBar(page);
    await ensureAtLeastBars(page, 1);
    await openPanel(page, 0);

    const barId = await getBarIdByIndex(page, 0);

    const original = 'Single bar for tests.';
    const publishedMsg = `Published ${Date.now()}`;

    const textarea = page.locator('.top-bar-row.bg').first().locator('textarea').first();
    await textarea.fill(publishedMsg);
    const save = waitForTopBarPut(page);
    await textarea.blur();
    await save;

    // Publish this bar.
    page.once('dialog', (d) => d.accept());
    const publishBtn = page.locator('.top-bar-row.bg').first().locator('button.top-bar-icons.publish');
    const publishSave = page.waitForResponse((r) => {
      if (r.request().method() !== 'POST' || !r.ok()) return false;
      const url = decodeURIComponent(r.url());
      return new RegExp(`/top-bar/v1/bars/${barId}/publish`, 'i').test(url);
    });
    await publishBtn.click();
    await publishSave;

    // Frontend should now show the new message.
    await page.goto('/');
    const inner = page.locator(`[data-top-bar-id="${barId}"] .top-bar__inner`);
    await expect(inner).toContainText(publishedMsg);
    await expect(inner).not.toContainText(original);
  });

  test('two bars: publish one bar only affects that bar on frontend', async ({ page }) => {
    await loginAndOpenTopBarSettings(page);
    await resetToSingleBar(page);
    await ensureAtLeastBars(page, 2);

    // Expand both panels.
    await openPanel(page, 0);
    await openPanel(page, 1);

    const barId0 = await getBarIdByIndex(page, 0);
    const barId1 = await getBarIdByIndex(page, 1);

    const textarea0 = page.locator('.top-bar-row.bg').nth(0).locator('textarea').first();
    const textarea1 = page.locator('.top-bar-row.bg').nth(1).locator('textarea').first();

    // First, make sure BOTH bars exist in published state (so frontend has both DOM nodes).
    const initial0 = `Bar0 initial ${Date.now()}`;
    const initial1 = `Bar1 initial ${Date.now()}`;

    await textarea0.fill(initial0);
    const initSave0 = waitForTopBarPut(page);
    await textarea0.blur();
    await initSave0;

    page.once('dialog', (d) => d.accept());
    const publishBtn0_init = page.locator('.top-bar-row.bg').nth(0).locator('button.top-bar-icons.publish');
    const publishSave0_init = page.waitForResponse((r) => {
      if (r.request().method() !== 'POST' || !r.ok()) return false;
      const url = decodeURIComponent(r.url());
      return new RegExp(`/top-bar/v1/bars/${barId0}/publish`, 'i').test(url);
    });
    await publishBtn0_init.click();
    await publishSave0_init;

    await textarea1.fill(initial1);
    const initSave1 = waitForTopBarPut(page);
    await textarea1.blur();
    await initSave1;

    page.once('dialog', (d) => d.accept());
    const publishBtn1_init = page.locator('.top-bar-row.bg').nth(1).locator('button.top-bar-icons.publish');
    const publishSave1_init = page.waitForResponse((r) => {
      if (r.request().method() !== 'POST' || !r.ok()) return false;
      const url = decodeURIComponent(r.url());
      return new RegExp(`/top-bar/v1/bars/${barId1}/publish`, 'i').test(url);
    });
    await publishBtn1_init.click();
    await publishSave1_init;

    // Now change both bars in draft, but publish only bar 0.
    const msg0 = `Bar0 published ${Date.now()}`;
    const msg1 = `Bar1 draft ${Date.now()}`;

    await textarea0.fill(msg0);
    const save0 = waitForTopBarPut(page);
    await textarea0.blur();
    await save0;

    await textarea1.fill(msg1);
    const save1 = waitForTopBarPut(page);
    await textarea1.blur();
    await save1;

    page.once('dialog', (d) => d.accept());
    const publishBtn0 = page.locator('.top-bar-row.bg').nth(0).locator('button.top-bar-icons.publish');
    const publishSave0 = page.waitForResponse((r) => {
      if (r.request().method() !== 'POST' || !r.ok()) return false;
      const url = decodeURIComponent(r.url());
      return new RegExp(`/top-bar/v1/bars/${barId0}/publish`, 'i').test(url);
    });
    await publishBtn0.click();
    await publishSave0;

    await page.goto('/');
    const inner0 = page.locator(`[data-top-bar-id="${barId0}"] .top-bar__inner`);
    const inner1 = page.locator(`[data-top-bar-id="${barId1}"] .top-bar__inner`);

    // Bar 0 shows its published update.
    await expect(inner0).toContainText(msg0);
    // Bar 1 should keep its previously published content until its publish is clicked.
    await expect(inner1).toContainText(initial1);
    await expect(inner1).not.toContainText(msg1);
  });

});

