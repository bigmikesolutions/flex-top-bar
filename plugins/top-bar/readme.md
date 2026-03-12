# Top Bar

Displays a simple info bar at the top or bottom of the menu.

## WP admin 

Use the WordPress admin:

1. Log in to the site (e.g. http://localhost:8080/wp-admin).
2. In the left sidebar, open Settings.
3. Click Top Bar (under Settings).
4. Change what you need:
    Position – Top or Bottom
    Message – WYSIWYG (text, links, formatting, media)
    Background colour – colour picker
    Frame (border) colour – colour picker, or tick No border
5. Click Save Changes.

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
