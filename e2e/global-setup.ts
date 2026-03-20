declare const process: { cwd: () => string; env: Record<string, string | undefined> };
declare const require: (name: string) => any;

const { execSync } = require('node:child_process');

export default async function globalSetup(): Promise<void> {
  const root = process.cwd();
  const composeFile = `${root}/docker-compose.yml`;
  const adminUser = process.env.WP_ADMIN_USER ?? 'admin';
  const adminPass = process.env.WP_ADMIN_PASSWORD ?? 'admin';

  const command =
    `docker compose -f "${composeFile}" run --rm wp-install sh -lc ` +
    `'cd /var/www/html && ` +
    `if wp core is-installed --allow-root 2>/dev/null; then ` +
    `echo "WordPress already installed."; ` +
    `else ` +
    `wp core install --allow-root --url="http://localhost:8080" --title="CI WordPress" --admin_user="${adminUser}" --admin_password="${adminPass}" --admin_email="admin@localhost" --skip-email; ` +
    `fi; ` +
    `wp plugin activate top-bar --allow-root || true'`;

  execSync(command, { stdio: 'inherit' });
}
