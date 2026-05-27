=== Flex Top Bar ===
Contributors: bigmikesolutions
Tags: notification bar, announcement bar, site banner, site message, admin settings
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 8.1
Stable tag: 0.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Plugin repository: https://github.com/bigmikesolutions/flex-top-bar

Build and publish customizable notification bars (top or bottom) with a modern admin UI.

== Description ==

Flex Top Bar lets you add a configurable notification/announcement bar to the top or bottom of your site.

**Key features**

- Display a bar at the **top or bottom** of the site.
- Edit the message with a **WYSIWYG editor** (text, links, formatting, media).
- Build the bar layout using **columns**, each containing **text or an icon**.
- Customize **background color** and optional **border/frame color**.
- Changes are saved from the settings screen and you can **publish** when ready.

== Installation ==

1. Upload the plugin folder to your `/wp-content/plugins/` directory, or install the plugin through the WordPress Plugins screen.
2. Activate the plugin through the **Plugins** screen in WordPress.
3. Go to **Settings → Flex Top Bar** to configure the bar.

== Frequently Asked Questions ==

= Where do I configure the bar? =

In WordPress admin, go to **Settings → Flex Top Bar**.

= Is Flex Top Bar free? Is there a Pro version? =

The plugin works as a **free** plugin by default with core features. There is also an **optional Pro** upgrade available via an in-plugin checkout to unlock additional features. The free version remains usable without purchasing anything.

Pro details: https://example.com/upgrade

= Does this plugin add external links or credits to my site? =

No. The front-end output is only what you configure in the message editor.

= Can I use external icon libraries or load media from external URLs? =

No. For security and performance reasons, the plugin only supports **pre-defined icons/media** bundled with the plugin, and does not load third-party icon libraries.

= Where does the bar display? Does it depend on my theme? =

The bar is intended to display on the front-end of your site based on your settings. If your theme significantly customizes or replaces standard WordPress hooks/templates, you may need theme-specific adjustments.

= How do I disable or remove the bar? =

You can remove the message or unpublish from **Settings → Flex Top Bar**, or deactivate the plugin from the **Plugins** screen.

= Does it work with caching plugins/CDNs? =

In general, yes. If your cache is serving an old version of the page, clear/purge your cache after publishing changes.

= What happens on uninstall? Does it delete settings? =

By default, WordPress deactivation does not remove saved settings. If you need a full cleanup, remove the plugin and then delete the plugin’s options from the database.

== Screenshots ==

1. Settings overview screen.
2. Basic settings (position and colors).
3. Message editor (WYSIWYG).
4. Social media/icons configuration.
5. Example bars displayed on the front-end.

== Privacy ==

Flex Top Bar is not designed to collect personal data from your site visitors, and it does not, on its own, transmit visitor personal data to the plugin author.

The Freemius SDK is bundled with the plugin and active (in the free or Pro version) for licensing, payments, updates, support, and abuse prevention. Freemius may display an opt-in consent notice in the WordPress admin (where applicable). If enabled/consented, Freemius may process data related to the license/installation (for example: license identifiers, the site domain/URL, IP address, and technical details about the WordPress environment such as versions and active plugins).

Freemius provides details in their privacy policy: https://freemius.com/privacy/

== License & Disclaimer ==

This plugin is licensed under the GNU General Public License, version 2 or later (GPLv2 or later). If anything in this section could be interpreted as limiting rights granted under the GPL, the GPL takes precedence.

The plugin is provided “AS IS” and “AS AVAILABLE” without warranties of any kind. You are responsible for testing in a staging environment, keeping backups, and ensuring your site’s security and compliance. The plugin author is not responsible for data loss, downtime, or incompatibilities with WordPress versions, themes, other plugins, or third-party services. 

More detailed EULA and disclaimer texts are included in the plugin package under `assets/doc/`.

== Changelog ==

= 0.2.0 =

- count-down timer column added
- icon column with possibility to upload custom icons

= 0.1.2 =

- TZ added for scheduled top-bars

= 0.1.0 =

Initial release.
