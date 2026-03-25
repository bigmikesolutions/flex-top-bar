# wordpress
WP plugins

## Local development with Docker

### Prerequisites
- [Docker](https://docs.docker.com/get-docker/) and Docker Compose

### Quick start

1. **Optional:** Copy env example and edit if you want different DB credentials:
   ```bash
   cp .env.example .env
   ```

2. **Start the stack:**
   ```bash
   docker compose up -d
   ```

3. **Restart word press:**
   ```bash
   docker compose restart wordpress
   ```

4. **Open WordPress** (install runs automatically; no browser setup wizard):
   - **WordPress:** http://localhost:8080  
   - **WordPress admin:** http://localhost:8080/wp-admin  
   - **phpMyAdmin:** http://localhost:8081 (DB user/password from `.env` or defaults)

   Default admin login (override via `.env`): **admin** / **admin**   

### Useful commands

- Stop: `docker compose down`
- Stop and remove volumes (fresh DB): `docker compose down -v`
- View logs: `docker compose logs -f`
- Shell into WordPress container: `docker compose exec wordpress bash`

### Custom plugins

Custom plugins live in **`plugins/`** at the repo root and are mounted into the container as `wp-content/plugins`. Each plugin is in its own subdirectory (e.g. `plugins/top-bar/`). Activate them in **WP Admin → Plugins**.

**Unit tests** (PHPUnit) run from the project root:

```bash
composer install
composer test
# or only Top Bar:  composer test:top-bar
```

**Unit tests** (JS Unit) run from the project root:

```bash
npm install
npm run test:e2e
```

**E2E tests** (Playwright):

```bash
npm install
npx playwright install --with-deps
npm run test:e2e
```

Optional env vars for login/base URL:

- `WP_BASE_URL` (default `http://localhost:8080`)
- `WP_ADMIN_USER` (default `admin`)
- `WP_ADMIN_PASSWORD` (default `admin`)

### Seed two demo bars (top + bottom)

Creates `top_bars` in DB with exactly two entries (one `top`, one `bottom`):

```bash
composer seed:top-bars
```
