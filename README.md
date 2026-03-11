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

3. **Open WordPress** (install runs automatically; no browser setup wizard):
   - **WordPress:** http://localhost:8080  
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
