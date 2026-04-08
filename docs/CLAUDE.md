# Claude Code — project context

This file mirrors `.cursor/rules/*.mdc` so behavior stays consistent across tools. Prefer commands from `README.md` when in doubt.

---

## Repo layout and stack

- **Docker Compose** runs WordPress (default **http://localhost:8080**). Custom plugins live under **`plugins/`** and mount into the container.
- **Primary plugin:** `plugins/flex-top-bar` — Vue 3 + Vite (admin + frontend), PHP REST API, Pinia, PHPUnit + Vitest + Playwright.
- **Tests:** From repo root: `npm run test` runs the **flex-top-bar** workspace (Vitest); `npm run test:e2e` runs **Playwright** (`playwright.config.ts`, `e2e/`). PHPUnit: `composer test` (see README).
- **Env (E2E):** `WP_BASE_URL`, `WP_ADMIN_USER`, `WP_ADMIN_PASSWORD` (defaults in `README.md`).

**Working style:** Change only what the task requires. Do not refactor unrelated code, add unsolicited docs, or edit markdown unless asked. Match naming, types, and patterns in the files you touch.

---

## WordPress plugin PHP (`plugins/**/*.php`)

- Use `declare(strict_types=1);`, namespaces (e.g. `TopBar\`), and `final` classes where the codebase already does.
- Escape output (`esc_*`), sanitize/validate input, capability checks (`current_user_can`) for admin/REST.
- REST routes: `includes/class-api.php`, namespace **`top-bar/v1`**. Keep payloads aligned with `plugins/flex-top-bar/src/api/client.ts`.
- Options/normalization: `includes/class-options.php`; bar shape must stay consistent with `plugins/flex-top-bar/src/types`.

---

## Top Bar Vue (`plugins/flex-top-bar/src/`)

- **Admin** mounts on `#top-bar-app` (`includes/class-admin.php`). **Frontend** mounts on `#top-bar-frontend-mount`; public data loads via REST (`fetch` / **public-bars**), not the authenticated admin API.
- **State:** Pinia in `src/stores/`; API wrapper in `src/api/client.ts` uses `window.topBarConfig` (nonce, `apiRoot`).
- **Saving:** Admin saves automatically on `@change` / `@blur` — there is **no** WordPress “Save Changes” button for the Vue settings screen.
- **i18n:** `@wordpress/i18n` `__()` consistent with existing components.
- **Mobile visibility on frontend:** Class **`top-bar--messages-mobile-hidden`** on the bar root (`TopBarFrontend.vue`); do not assume a `data-top-bar-mobile-visible` attribute unless you add it in the component.

After Vue/TS changes, rebuild assets: `npm run build:top-bar` (from repo root) or `npm run build -w plugins/flex-top-bar`.

---

## Playwright E2E (`e2e/`)

- **Timeouts / `baseURL`:** `playwright.config.ts` — avoid assuming very short global timeouts for login + Docker + multi-step flows.
- **Readiness:** Prefer `#top-bar`, “Add new Top Bar”, etc., over arbitrary `waitForTimeout` sleeps.
- **Persistence:** Vue admin saves with **PUT** to `top-bar/v1/bars/:id`. Use **`waitForTopBarPut`** in `e2e/helpers/topBarHelpers.ts` after saves; URL matching must work for both **`/wp-json/`** and **`rest_route=`** styles.
- **Do not** target `getByRole('button', { name: 'Save Changes' })` on the Top Bar Vue admin page.
- **Selectors:** Admin uses current Vue markup (e.g. buttons for “Add new text”), not legacy PHP form `name="top_bars[0][...]"` attributes.
- **Frontend assertions:** Align with `TopBarFrontend.vue` (e.g. mobile hidden → class `top-bar--messages-mobile-hidden`).

---

## Optional: local permissions

`.claude/settings.local.json` may list allowed Bash patterns (composer, npm, playwright, docker, etc.). Do not commit secrets; keep machine-specific settings local.
