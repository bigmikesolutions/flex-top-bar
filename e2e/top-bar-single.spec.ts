import { expect, test, type Page } from '@playwright/test';
import {
  ensureAtLeastBars,
  loginAndOpenTopBarSettings,
  openPanel,
  resetToSingleBar,
  toDatetimeLocalValue,
} from './helpers/topBarHelpers';

declare const process: { env: Record<string, string | undefined> };

test.describe('single-bar', () => {

  test.describe('basic settings - position', () => {
    test('should save bar as top and render it at top', async ({ page }) => {
      await loginAndOpenTopBarSettings(page);
      await resetToSingleBar(page);
      await ensureAtLeastBars(page, 1);
      await openPanel(page, 0);

      const id0 = await page.locator('input[name="top_bars[0][id]"]').inputValue();
      const position0 = page.locator('select[name="top_bars[0][position]"]');

      await position0.evaluate((el: HTMLSelectElement) => {
        el.value = 'top';
        el.dispatchEvent(new Event('change', { bubbles: true }));
      });
      await page.getByRole('button', { name: 'Save Changes' }).click();
      await expect(page.locator('#setting-error-settings_updated, .notice-success')).toBeVisible();

      await page.goto('/');

      const topBar = page.locator(`[data-top-bar-id="${id0}"]`);
      await expect(topBar).toHaveCount(1);
      await expect(topBar).toHaveAttribute('data-top-bar-position', 'top');
      await expect(topBar).toHaveClass(/top-bar--top/);
    });

    test('should save bar as bottom and render it at bottom', async ({ page }) => {
      await loginAndOpenTopBarSettings(page);
      await resetToSingleBar(page);
      await ensureAtLeastBars(page, 1);
      await openPanel(page, 0);

      const id0 = await page.locator('input[name="top_bars[0][id]"]').inputValue();
      const position0 = page.locator('select[name="top_bars[0][position]"]');

      await position0.evaluate((el: HTMLSelectElement) => {
        el.value = 'bottom';
        el.dispatchEvent(new Event('change', { bubbles: true }));
      });
      await page.getByRole('button', { name: 'Save Changes' }).click();
      await expect(page.locator('#setting-error-settings_updated, .notice-success')).toBeVisible();

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
      await ensureAtLeastBars(page, 1);
      await openPanel(page, 0);

      const id0 = await page.locator('input[name="top_bars[0][id]"]').inputValue();
      const position0 = page.locator('select[name="top_bars[0][position]"]');
      const hideOnScroll0 = page.locator('select[name="top_bars[0][hide_on_scroll]"]');

      await position0.evaluate((el: HTMLSelectElement) => {
        el.value = 'top';
        el.dispatchEvent(new Event('change', { bubbles: true }));
      });
      await hideOnScroll0.evaluate((el: HTMLSelectElement) => {
        el.value = '1';
        el.dispatchEvent(new Event('change', { bubbles: true }));
      });

      await page.getByRole('button', { name: 'Save Changes' }).click();
      await expect(page.locator('#setting-error-settings_updated, .notice-success')).toBeVisible();

      await page.goto('/');

      const topBar = page.locator(`[data-top-bar-id="${id0}"]`);
      await expect(topBar).toHaveCount(1);
      await expect(topBar).toHaveAttribute('data-top-bar-position', 'top');
      await expect(topBar).toHaveClass(/top-bar--top/);
      await expect(topBar).toHaveAttribute('data-top-bar-scroll-hide', '1');

      // Initial state at top of page: visible.
      await expect(topBar).toBeVisible();
      await expect(topBar).not.toHaveCSS('display', 'none');

      // Scroll past threshold (30px) and verify the bar is hidden by script.
      await page.evaluate(() => window.scrollTo(0, 200));
      await expect(topBar).toHaveCSS('display', 'none');
    });

    test('should save top position with hide-on-scroll disabled and stay visible after window scroll', async ({ page }) => {
      await loginAndOpenTopBarSettings(page);
      await resetToSingleBar(page);
      await ensureAtLeastBars(page, 1);
      await openPanel(page, 0);

      const id0 = await page.locator('input[name="top_bars[0][id]"]').inputValue();
      const position0 = page.locator('select[name="top_bars[0][position]"]');
      const hideOnScroll0 = page.locator('select[name="top_bars[0][hide_on_scroll]"]');

      await position0.evaluate((el: HTMLSelectElement) => {
        el.value = 'top';
        el.dispatchEvent(new Event('change', { bubbles: true }));
      });
      await hideOnScroll0.evaluate((el: HTMLSelectElement) => {
        el.value = '0';
        el.dispatchEvent(new Event('change', { bubbles: true }));
      });

      await page.getByRole('button', { name: 'Save Changes' }).click();
      await expect(page.locator('#setting-error-settings_updated, .notice-success')).toBeVisible();

      await page.goto('/');

      const topBar = page.locator(`[data-top-bar-id="${id0}"]`);
      await expect(topBar).toHaveCount(1);
      await expect(topBar).toHaveAttribute('data-top-bar-position', 'top');
      await expect(topBar).toHaveClass(/top-bar--top/);
      // Disabled mode omits the attribute entirely in frontend markup.
      await expect(topBar).not.toHaveAttribute('data-top-bar-scroll-hide', '1');

      await expect(topBar).toBeVisible();
      await expect(topBar).not.toHaveCSS('display', 'none');

      await page.evaluate(() => window.scrollTo(0, 200));
      await expect(topBar).toBeVisible();
      await expect(topBar).not.toHaveCSS('display', 'none');
    });

    test('should save bottom position with hide-on-scroll and hide after window scroll', async ({ page }) => {
      await loginAndOpenTopBarSettings(page);
      await resetToSingleBar(page);
      await ensureAtLeastBars(page, 1);
      await openPanel(page, 0);

      const id0 = await page.locator('input[name="top_bars[0][id]"]').inputValue();
      const position0 = page.locator('select[name="top_bars[0][position]"]');
      const hideOnScroll0 = page.locator('select[name="top_bars[0][hide_on_scroll]"]');

      await position0.evaluate((el: HTMLSelectElement) => {
        el.value = 'bottom';
        el.dispatchEvent(new Event('change', { bubbles: true }));
      });
      await hideOnScroll0.evaluate((el: HTMLSelectElement) => {
        el.value = '1';
        el.dispatchEvent(new Event('change', { bubbles: true }));
      });

      await page.getByRole('button', { name: 'Save Changes' }).click();
      await expect(page.locator('#setting-error-settings_updated, .notice-success')).toBeVisible();

      await page.goto('/');

      const bottomBar = page.locator(`[data-top-bar-id="${id0}"]`);
      await expect(bottomBar).toHaveCount(1);
      await expect(bottomBar).toHaveAttribute('data-top-bar-position', 'bottom');
      await expect(bottomBar).toHaveClass(/top-bar--bottom/);
      await expect(bottomBar).toHaveAttribute('data-top-bar-scroll-hide', '1');

      await expect(bottomBar).toBeVisible();
      await expect(bottomBar).not.toHaveCSS('display', 'none');

      await page.evaluate(() => window.scrollTo(0, 200));
      await expect(bottomBar).toHaveCSS('display', 'none');
    });

    test('should save bottom position with hide-on-scroll disabled and stay visible after window scroll', async ({ page }) => {
      await loginAndOpenTopBarSettings(page);
      await resetToSingleBar(page);
      await ensureAtLeastBars(page, 1);
      await openPanel(page, 0);

      const id0 = await page.locator('input[name="top_bars[0][id]"]').inputValue();
      const position0 = page.locator('select[name="top_bars[0][position]"]');
      const hideOnScroll0 = page.locator('select[name="top_bars[0][hide_on_scroll]"]');

      await position0.evaluate((el: HTMLSelectElement) => {
        el.value = 'bottom';
        el.dispatchEvent(new Event('change', { bubbles: true }));
      });
      await hideOnScroll0.evaluate((el: HTMLSelectElement) => {
        el.value = '0';
        el.dispatchEvent(new Event('change', { bubbles: true }));
      });

      await page.getByRole('button', { name: 'Save Changes' }).click();
      await expect(page.locator('#setting-error-settings_updated, .notice-success')).toBeVisible();

      await page.goto('/');

      const bottomBar = page.locator(`[data-top-bar-id="${id0}"]`);
      await expect(bottomBar).toHaveCount(1);
      await expect(bottomBar).toHaveAttribute('data-top-bar-position', 'bottom');
      await expect(bottomBar).toHaveClass(/top-bar--bottom/);
      await expect(bottomBar).not.toHaveAttribute('data-top-bar-scroll-hide', '1');

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

      const scheduled = page.locator('input[name="top_bars[0][scheduled_enabled]"][type="checkbox"]');
      const barIdInput = page.locator('input[name="top_bars[0][id]"]');
      const fromInput = page.locator('input[name="top_bars[0][scheduled_from_datetime]"]');
      const toInput = page.locator('input[name="top_bars[0][scheduled_to_datetime]"]');
      const barId = (await barIdInput.inputValue()).trim();

      // Hidden checkbox: toggle via label first, then force JS state as fallback.
      await page.locator('label.top-bar-life-time-checkbox').first().click();
      await scheduled.evaluate((el: HTMLInputElement) => {
        if (!el.checked) {
          el.checked = true;
          el.dispatchEvent(new Event('change', { bubbles: true }));
        }
      });

      await expect(scheduled).toBeChecked();
      // Use a far-future window so this bar should be hidden on frontend now.
      await fromInput.fill('2099-03-21T11:00');
      await toInput.fill('2099-03-21T12:30');

      await page.getByRole('button', { name: 'Save Changes' }).click();
      await expect(page.locator('#setting-error-settings_updated, .notice-success')).toBeVisible();

      await page.reload();
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

      const scheduled = page.locator('input[name="top_bars[0][scheduled_enabled]"][type="checkbox"]');
      const barIdInput = page.locator('input[name="top_bars[0][id]"]');
      const fromInput = page.locator('input[name="top_bars[0][scheduled_from_datetime]"]');
      const toInput = page.locator('input[name="top_bars[0][scheduled_to_datetime]"]');
      const barId = (await barIdInput.inputValue()).trim();

      await page.locator('label.top-bar-life-time-checkbox').first().click();
      await scheduled.evaluate((el: HTMLInputElement) => {
        if (!el.checked) {
          el.checked = true;
          el.dispatchEvent(new Event('change', { bubbles: true }));
        }
      });
      await expect(scheduled).toBeChecked();

      // Wide range around now to avoid timezone edge cases.
      const now = new Date();
      const from = new Date(now.getTime() - 24 * 60 * 60 * 1000);
      const to = new Date(now.getTime() + 24 * 60 * 60 * 1000);
      const fromValue = toDatetimeLocalValue(from);
      const toValue = toDatetimeLocalValue(to);

      await fromInput.fill(fromValue);
      await toInput.fill(toValue);

      await page.getByRole('button', { name: 'Save Changes' }).click();
      await expect(page.locator('#setting-error-settings_updated, .notice-success')).toBeVisible();

      await page.reload();
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
      const addTextButton = page.getByRole('link', { name: 'Add new text' }).first();
      const beforeCount = await messageList.locator('.top-bar-column-creator-grid').count();

      await addTextButton.click();
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
      const addTextButton = page.getByRole('link', { name: 'Add new text' }).first();

      await addTextButton.click();
      await page.waitForLoadState('domcontentloaded');
      await openPanel(page, 0);

      const afterAddCount = await messageList.locator('.top-bar-column-creator-grid').count();
      const removeButtons = messageList.locator('.item-creator.no a.top-bar-btn', { hasText: 'X' });
      const removeHref = await removeButtons.last().getAttribute('href');
      expect(removeHref).toBeTruthy();
      await page.goto(String(removeHref));
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
        const removeButtons = messageList.locator('.item-creator.no a.top-bar-btn', { hasText: 'X' });
        const removeHref = await removeButtons.last().getAttribute('href');
        expect(removeHref).toBeTruthy();
        await page.goto(String(removeHref));
        await openPanel(page, 0);
      }

      await expect(messageList.locator('.top-bar-column-creator-grid')).toHaveCount(1);
      await expect(messageList.locator('.item-creator.no a.top-bar-btn', { hasText: 'X' })).toHaveCount(0);
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

      const id0 = (await page.locator('input[name="top_bars[0][id]"]').inputValue()).trim();
      const effect0 = page.locator('select[name="top_bars[0][effect]"]');
      const messageList = page.locator('.top-bar-message-list').first();
      const addTextButton = page.getByRole('link', { name: 'Add new text' }).first();

      // Ensure there are two message fields.
      while ((await messageList.locator('.top-bar-column-creator-grid').count()) < 2) {
        await addTextButton.click();
        await page.waitForLoadState('domcontentloaded');
        await openPanel(page, 0);
      }

      await effect0.evaluate(
        (el: HTMLSelectElement, value: string) => {
          el.value = value;
          el.dispatchEvent(new Event('change', { bubbles: true }));
        },
        effect
      );

      await page.evaluate(
        ({ first, second }) => {
          const setMessage = (index: number, value: string): void => {
            const editorId = `top_bar_message_0_${index}`;
            const maybeTinyMce = (window as Window & { tinymce?: any }).tinymce;
            const editor = maybeTinyMce?.get?.(editorId);
            if (editor) {
              editor.setContent(value);
            }

            const textarea = document.querySelector(
              `textarea[name="top_bars[0][messages][${index}]"]`
            ) as HTMLTextAreaElement | null;
            if (textarea) {
              textarea.value = value;
              textarea.dispatchEvent(new Event('input', { bubbles: true }));
              textarea.dispatchEvent(new Event('change', { bubbles: true }));
            }
          };

          setMessage(0, first);
          setMessage(1, second);

          // Synchronize TinyMCE editors to hidden textareas before submit.
          const maybeTinyMce = (window as Window & { tinymce?: any }).tinymce;
          if (maybeTinyMce?.triggerSave) {
            maybeTinyMce.triggerSave();
          }
        },
        { first: firstMessage, second: secondMessage }
      );

      await page.getByRole('button', { name: 'Save Changes' }).click();
      await expect(page.locator('#setting-error-settings_updated, .notice-success')).toBeVisible();

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

      const id0 = (await page.locator('input[name="top_bars[0][id]"]').inputValue()).trim();
      const mobileVisibility = page.locator('select[name="top_bars[0][messages_mobile_visible]"]');

      await mobileVisibility.evaluate(
        (el: HTMLSelectElement, value: string) => {
          el.value = value;
          el.dispatchEvent(new Event('change', { bubbles: true }));
        },
        mobileVisible ? '1' : '0'
      );

      await page.getByRole('button', { name: 'Save Changes' }).click();
      await expect(page.locator('#setting-error-settings_updated, .notice-success')).toBeVisible();

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
      await expect(topBar).toHaveAttribute('data-top-bar-mobile-visible', '0');
      await expect(topBar).toBeHidden();
    });
  });

});