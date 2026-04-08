# Top Bar

Displays a simple info bar at the top or bottom of the menu.

## WP admin 

Use the WordPress admin:

1. Log in to the site (e.g. http://localhost:8080/wp-admin).
2. In the left sidebar, open Settings.
3. Click Flex Top Bar (under Settings).
4. Change what you need:
    Position – Top or Bottom
    Message – WYSIWYG (text, links, formatting, media)
    Background colour – colour picker
    Frame (border) colour – colour picker, or tick No border
    All changes are auto-saved but you must click publish button to make them visible.

## Options

- **Position**: `top_bar_position` option — `top` (default) or `bottom`.
- **Message**: `top_bar_message` option — text to display (default: "Welcome!").

## Development

Run unit tests from the project root:

```bash
composer test
# or
./vendor/bin/phpunit
```

## Local development

1) Check top plugins data:

```sh
docker compose exec wordpress php -r 'require "/var/www/html/wp-load.php"; $v=get_option("top_bars"); echo json_encode($v, JSON_PRETTY_PRINT), PHP_EOL;'
```

## Buying on sandbox via Freemius

- [Testing credit cards & PayPal accounts](https://freemius.com/help/documentation/checkout/integration/testing/#testing-credit-cards)

