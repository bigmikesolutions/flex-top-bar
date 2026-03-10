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

### Using local plugins/themes

To develop plugins or themes on your machine, uncomment the `wp-content` volume in `docker-compose.yml` under the wordpress service, then put your plugins/themes in a `wp-content` folder in this repo. The container will use that directory instead of the default one.
