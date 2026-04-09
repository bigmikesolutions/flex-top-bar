import { expect, type Page, type Response } from '@playwright/test';

declare const process: { env: Record<string, string | undefined>; cwd: () => string };
declare const require: (name: string) => any;

const { execSync } = require('node:child_process');

const ADMIN_USER = process.env.WP_ADMIN_USER ?? 'admin';
const ADMIN_PASS = process.env.WP_ADMIN_PASSWORD ?? 'admin';
const TOP_BAR_SETTINGS_PATH = '/wp-admin/admin.php?page=flex-top-bar';

/** WordPress admin after login (covers admin.php, index.php, load-scripts.php, etc.). */
const WP_ADMIN_URL_REGEX = /\/wp-admin\//;

export const MAX_BARS = 5;

export function toDatetimeLocalValue(date: Date): string {
  const pad = (n: number) => String(n).padStart(2, '0');
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
}

/** True when response is a successful PUT updating a single bar (matches pretty and plain permalink REST URLs). */
function isTopBarBarPut(response: Response): boolean {
  if (response.request().method() !== 'PUT' || !response.ok()) {
    return false;
  }
  const url = decodeURIComponent(response.url());
  // Pretty: /wp-json/flex-top-bar/v1/bars/{id} — Plain: ?rest_route=/flex-top-bar/v1/bars/{id}
  // Back-compat: accept older `top-bar/v1` namespace too.
  return /(flex-top-bar|top-bar)\/v1\/bars\/[a-z0-9_]+/i.test(url);
}

/** Wait for the Vue admin to persist a bar via REST (PUT …/top-bar/v1/bars/:id). */
export async function waitForTopBarPut(page: Page): Promise<void> {
  await page.waitForResponse(isTopBarBarPut, { timeout: 45000 });
}

export async function waitForTopBarPutWhere(
  page: Page,
  predicate: (requestBody: string) => boolean
): Promise<void> {
  await page.waitForResponse(
    (response) => {
      if (!isTopBarBarPut(response)) return false;
      const body = response.request().postData() ?? '';
      return predicate(body);
    },
    { timeout: 45000 }
  );
}

/**
 * Wait until the Top Bar admin UI finished loading (not the Loading… notice).
 * Do not require "Add new Top Bar" — that control is omitted when already at max_bars.
 * Long timeout: CI can be slow; Vue mounts into #top-bar-app (see includes/class-admin.php).
 */
export async function waitForTopBarAdminReady(page: Page): Promise<void> {
  const mount = page.locator('#top-bar-app');
  await mount.waitFor({ state: 'visible', timeout: 60000 });

  // Wait until Vue is done loading (the Loading… notice can be slow/flaky in CI).
  await mount
    .locator('.notice.notice-info')
    .filter({ hasText: 'Loading' })
    .first()
    .waitFor({ state: 'hidden', timeout: 60000 });

  // Once loading is done, we consider it "ready" when it shows either:
  // - a bar row, or
  // - the empty state row, or
  // - an error notice.
  await mount
    .locator('.top-bar-row.bg, .top-bar-row.center.empty, .notice.notice-error, .notice-error')
    .first()
    .waitFor({ state: 'visible', timeout: 60000 });
}

export async function loginAndOpenTopBarSettings(page: Page): Promise<void> {
  const gotoSettings = async () => {
    // `load` can be flaky/slow in WP admin because of long-running assets; Vue UI is ready once
    // the relevant DOM renders, which we already assert in `waitForTopBarAdminReady`.
    //
    // WP occasionally triggers navigation races (redirects / frame detach) that manifest as
    // `net::ERR_ABORTED` for `page.goto`. Retry a couple times to make E2E robust.
    const attempts = 3;
    for (let i = 0; i < attempts; i += 1) {
      try {
        await page.goto(TOP_BAR_SETTINGS_PATH, { waitUntil: 'domcontentloaded', timeout: 45000 });
        return;
      } catch (err) {
        const msg = String((err as any)?.message ?? err);
        const isRetryable =
          msg.includes('net::ERR_ABORTED') ||
          msg.toLowerCase().includes('frame was detached') ||
          msg.toLowerCase().includes('navigation') ||
          msg.toLowerCase().includes('target page, context or browser has been closed');
        if (!isRetryable || i === attempts - 1) {
          throw err;
        }
        await page.waitForTimeout(750);
      }
    }
  };

  // Go straight to the admin settings page; WP redirects to login if needed.
  await gotoSettings();

  const loginInput = page.locator('input[name="log"]');
  const topBarRoot = page.locator('#top-bar-app');
  const hasLogin = (await loginInput.count()) > 0;
  const hasTopBar = (await topBarRoot.count()) > 0;

  if (!hasLogin && !hasTopBar) {
    // One retry in case WordPress is still finishing startup.
    await page.waitForTimeout(2000);
    await gotoSettings();
  }

  if (await loginInput.count()) {
    await loginInput.first().fill(ADMIN_USER);
    await page.locator('input[name="pwd"]').first().fill(ADMIN_PASS);
    const submit = page.locator('input[name="wp-submit"]').first();
    try {
      await Promise.all([
        // `waitForURL` defaults to waiting for the full "load" event; WP admin often keeps the page
        // "loading" longer than necessary. `domcontentloaded` is enough for our subsequent UI checks.
        page.waitForURL(WP_ADMIN_URL_REGEX, { timeout: 45000, waitUntil: 'domcontentloaded' }),
        submit.click(),
      ]);
    } catch (err) {
      // WP login can trigger navigation races (redirects / frame detach) that surface as ERR_ABORTED.
      // If that happens, proceed and rely on the subsequent settings open + readiness checks.
      const msg = String((err as any)?.message ?? err);
      const isRetryable =
        msg.includes('net::ERR_ABORTED') ||
        msg.toLowerCase().includes('frame was detached') ||
        msg.toLowerCase().includes('navigation');
      if (!isRetryable) {
        throw err;
      }
    }
  }

  await gotoSettings();

  try {
    await waitForTopBarAdminReady(page);
  } catch {
    // Avoid throwing a secondary error if the browser/page was already closed by a timeout.
    const url = (() => {
      try {
        return page.url();
      } catch {
        return '(unavailable)';
      }
    })();

    const title = await (async () => {
      try {
        if (page.isClosed()) return '(page closed)';
        return await page.title();
      } catch {
        return '(unavailable)';
      }
    })();

    throw new Error(`Failed to open Top Bar settings. URL: ${url}, title: ${title}`);
  }
}

export async function ensureAtLeastBars(page: Page, expectedBars: number): Promise<void> {
  for (let i = 0; i < 5; i += 1) {
    const count = await page.locator('.top-bar-row.bg').count();
    if (count >= expectedBars) {
      return;
    }
    await clickAddNewTopBar(page);
  }
}

export async function openPanel(page: Page, index: number): Promise<void> {
  const panel = page.locator('.top-bar-options').nth(index);
  const isActive = async () =>
    panel
      .evaluate((el) => el.classList.contains('active'))
      .catch(() => false);

  if (!(await isActive())) {
    // Panel visibility is controlled by the `active` class (Vue state), not by mounting/unmounting.
    // Force the click to avoid flakiness from sticky preview overlays / transitions.
    await page.locator('.top-bar-toggle-options').nth(index).click({ force: true });
  }

  // Wait for Vue to mark it expanded.
  await page.waitForFunction(
    (el) => (el as HTMLElement).classList.contains('active'),
    await panel.elementHandle(),
    { timeout: 15000 }
  );
}

export async function clickAddNewTopBar(page: Page): Promise<void> {
  // Vue app uses a button, not a link
  const addButton = page.getByRole('button', { name: 'Add new Top Bar' }).first();

  if ((await addButton.count()) === 0) {
    throw new Error('No "Add new Top Bar" control found on page.');
  }

  await addButton.waitFor({ state: 'visible', timeout: 10000 });
  // The control can be temporarily disabled while Vue is fetching/saving.
  // Wait for enabled to avoid flakiness (especially after DB seeding helpers).
  await addButton.waitFor({ state: 'attached', timeout: 10000 });
  await page.waitForFunction(
    (el) => !(el as HTMLButtonElement).disabled,
    await addButton.elementHandle(),
    { timeout: 15000 }
  );
  await addButton.click({ timeout: 10000 });
  // Vue updates reactively, no page reload
  await page.waitForTimeout(500); // Wait for Vue to update DOM
}

export async function addBars(page: Page, count: number): Promise<void> {
  for (let i = 0; i < count; i += 1) {
    await clickAddNewTopBar(page);
  }
}

function clearTopBarSeedOptions(composeFile: string): void {
  const command = `docker compose -f "${composeFile}" exec -T wordpress php -r 'require_once "/var/www/html/wp-load.php"; delete_option("flex_top_bar_bars"); delete_option("flex_top_bar_bars_draft");'`;
  execSync(command, { stdio: 'pipe' });
}

export async function resetToSingleBar(page: Page): Promise<void> {
  const root = process.cwd();
  const composeFile = `${root}/docker-compose.yml`;
  clearTopBarSeedOptions(composeFile);
  const command = `docker compose -f "${composeFile}" exec -T wordpress php -r 'require_once "/var/www/html/wp-load.php"; $bars = [[ "id" => "bar_single", "name" => "Single bar", "visible" => true, "admin_visibile" => true, "scheduled_enabled" => false, "scheduled_from_datetime" => "", "scheduled_to_datetime" => "", "position" => "top", "effect" => "none", "messages" => ["Single bar for tests.", ""], "messages_mobile_visible" => true, "bg_color" => "#389339", "frame_color" => "", "frame_width" => 0, "hide_on_scroll" => false ]]; update_option("flex_top_bar_bars", $bars); /* Admin edits drafts; keep draft in sync with published for seeds. */ update_option("flex_top_bar_bars_draft", $bars);'`;

  execSync(command, { stdio: 'pipe' });
  // After mutating DB state, always re-open settings through the login-aware helper.
  // Otherwise we can end up on wp-login.php and `waitForTopBarAdminReady` will hang.
  await loginAndOpenTopBarSettings(page);
  // Ensure the UI actually reflects the seeded DB state before continuing.
  // If the admin app was already open, it can briefly show stale state until stores refetch.
  await expect(page.locator('.top-bar-row.bg')).toHaveCount(1, { timeout: 30000 });
}

/**
 * One bar with two layout columns (50% / 50%) for multi-column E2E tests.
 * Requires Docker Compose `wordpress` service (same as resetToSingleBar).
 */
export async function resetToTwoColumnBar(page: Page): Promise<void> {
  const root = process.cwd();
  const composeFile = `${root}/docker-compose.yml`;
  clearTopBarSeedOptions(composeFile);
  const command = `docker compose -f "${composeFile}" exec -T wordpress php -r 'require_once "/var/www/html/wp-load.php"; $bars = [[ "id" => "bar_mcol", "name" => "Multi column", "visible" => true, "admin_visibile" => true, "scheduled_enabled" => false, "scheduled_from_datetime" => "", "scheduled_to_datetime" => "", "position" => "top", "effect" => "none", "messages" => ["Col A", ""], "messages_mobile_visible" => true, "columns" => [ [ "id" => "col_e2e_a", "type" => "text", "effect" => "none", "messages" => ["Col A", ""], "size_percent" => 50, "messages_mobile_visible" => true ], [ "id" => "col_e2e_b", "type" => "text", "effect" => "none", "messages" => ["Col B", ""], "size_percent" => 50, "messages_mobile_visible" => true ] ], "bg_color" => "#389339", "frame_color" => "", "frame_width" => 0, "hide_on_scroll" => false ]]; update_option("flex_top_bar_bars", $bars); /* Admin edits drafts; keep draft in sync with published for seeds. */ update_option("flex_top_bar_bars_draft", $bars);'`;

  execSync(command, { stdio: 'pipe' });
  await loginAndOpenTopBarSettings(page);
}

/**
 * One visible bar with a single column of the requested type for frontend E2E tests.
 */
export async function resetToSingleColumnBar(
  page: Page,
  type: 'text' | 'social' | 'contact'
): Promise<void> {
  const root = process.cwd();
  const composeFile = `${root}/docker-compose.yml`;
  clearTopBarSeedOptions(composeFile);

  // Keep bar-level legacy fields consistent with the first column (for backward compat).
  const command = `docker compose -f "${composeFile}" exec -T wordpress php -r 'require_once "/var/www/html/wp-load.php"; $bars = [[ "id" => "bar_single_col", "name" => "Single column", "visible" => true, "admin_visibile" => true, "scheduled_enabled" => false, "scheduled_from_datetime" => "", "scheduled_to_datetime" => "", "position" => "top", "effect" => "none", "messages" => ["", ""], "messages_mobile_visible" => true, "columns" => [ ${
    type === 'text'
      ? `[ "id" => "col_front_text", "type" => "text", "effect" => "none", "messages" => ["Front text", ""], "size_percent" => 100, "messages_mobile_visible" => true ]`
      : type === 'social'
        ? `[ "id" => "col_front_social", "type" => "social", "icon_style" => "rounded", "background_color" => "#ffffff", "icon_color" => "#ff0000", "icon_border_width" => 0, "icon_border_color" => "#1d2327", "links" => [ [ "platform" => "youtube", "url" => "https://www.youtube.com/" ] ], "size_percent" => 100, "messages_mobile_visible" => true ]`
        : `[ "id" => "col_front_contact", "type" => "contact", "icon_style" => "rounded", "background_color" => "#ffffff", "icon_color" => "#1d2327", "icon_border_width" => 0, "icon_border_color" => "#1d2327", "contacts" => [ [ "kind" => "email", "value" => "hello@example.com" ] ], "size_percent" => 100, "messages_mobile_visible" => true ]`
  } ], "bg_color" => "#389339", "frame_color" => "", "frame_width" => 0, "hide_on_scroll" => false ]]; update_option("top_bars", $bars); /* Admin edits drafts; keep draft in sync with published for seeds. */ update_option("top_bars_draft", $bars);'`;

  execSync(command, { stdio: 'pipe' });
  await loginAndOpenTopBarSettings(page);
}

export async function getBarIds(page: Page): Promise<string[]> {
  const count = await page.locator('.top-bar-row.bg').count();
  const ids: string[] = [];
  for (let i = 0; i < count; i += 1) {
    const barRow = page.locator('.top-bar-row.bg').nth(i);
    // Extract ID from the name input's id attribute (e.g., "name_bar_abc123")
    const nameInput = barRow.locator('input[type="text"]').first();
    const inputId = await nameInput.getAttribute('id');
    if (inputId && inputId.startsWith('name_')) {
      const barId = inputId.replace('name_', '');
      ids.push(barId);
    }
  }
  return ids;
}

export async function getBarIdByIndex(page: Page, index: number): Promise<string> {
  const barRow = page.locator('.top-bar-row.bg').nth(index);
  const nameInput = barRow.locator('input[type="text"]').first();
  const inputId = await nameInput.getAttribute('id');
  if (inputId && inputId.startsWith('name_')) {
    return inputId.replace('name_', '');
  }
  throw new Error(`Could not find bar ID for index ${index}`);
}

export async function setBarPosition(page: Page, barIndex: number, position: 'top' | 'bottom'): Promise<void> {
  await openPanel(page, barIndex); // Ensure panel is open
  await page.waitForTimeout(300); // Wait for panel animation
  const barId = await getBarIdByIndex(page, barIndex);
  const positionSelect = page.locator(`#position_${barId}`);
  await positionSelect.waitFor({ state: 'visible', timeout: 5000 });
  await positionSelect.selectOption(position);
  await page.waitForTimeout(500); // Wait for API save
}

export async function setBarHideOnScroll(page: Page, barIndex: number, hideOnScroll: boolean): Promise<void> {
  const barId = await getBarIdByIndex(page, barIndex);
  const hideOnScrollSelect = page.locator(`#hide_on_scroll_${barId}`);
  await hideOnScrollSelect.selectOption(hideOnScroll ? 'true' : 'false');
  await page.waitForTimeout(500); // Wait for API save
}

export async function setSchedule(
  page: Page,
  barIndex: number,
  enabled: boolean,
  from?: string,
  to?: string
): Promise<void> {
  await openPanel(page, barIndex);

  const checkbox = page.locator('.top-bar-toggle-life-time').nth(barIndex);
  const isChecked = await checkbox.isChecked();

  if (enabled && !isChecked) {
    await checkbox.check();
    await page.waitForTimeout(500);
  } else if (!enabled && isChecked) {
    await checkbox.uncheck();
    await page.waitForTimeout(500);
  }

  if (enabled && from && to) {
    const barId = await getBarIdByIndex(page, barIndex);
    const fromInput = page.locator(`#scheduled_from_${barId}`);
    const toInput = page.locator(`#scheduled_to_${barId}`);

    await fromInput.fill(from);
    await toInput.fill(to);
    await page.waitForTimeout(500); // Wait for API save
  }
}

