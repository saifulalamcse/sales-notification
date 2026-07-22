=== Sales Notification ===
Contributors: yourname
Tags: woocommerce, sales, notification, social proof, conversion, popup
Requires at least: 5.8
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.0.0
WC requires at least: 6.0
WC tested up to: 9.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Display real-time WooCommerce purchase notifications on product pages to build social proof and increase conversion rates.

== Description ==

**Sales Notification** displays real-time and recent WooCommerce purchase activity as small, dismissible popup notifications on the frontend of your online store.

These notifications create powerful social proof by showing prospective customers that others are actively buying products, which increases trust and drives conversions.

= Key Features =

* **Real WooCommerce Orders** — Pulls from live orders (HPOS compatible)
* **Demo Mode** — Test appearance with configurable fake notifications
* **6 Popup Positions** — Bottom-left, bottom-right, bottom-center, top-left, top-right, top-center
* **3 Templates** — Horizontal, Vertical, and Minimal layouts
* **6 Animations** — Slide-in, Fade-in, Bounce-in, Zoom-in, Flip-in, and None
* **Full Design Control** — Colors, typography, border radius, shadows
* **Cookie Deduplication** — Never show the same notification twice per session
* **GDPR Compliant** — CMP-aware, no raw IP storage, WP privacy tools integration
* **Analytics** — Track impressions, clicks, and dismissals
* **RTL Support** — Full right-to-left layout support
* **Developer Friendly** — 8 PHP action hooks, 12 filter hooks, 7 JS custom events
* **Import / Export** — Migrate settings between sites easily
* **Multilingual** — Ready for translation via .pot file

= Privacy =

This plugin:
* Does NOT store customer email addresses
* Stores IP addresses as one-way SHA-256 hashes only
* Supports consent management platforms (CookieYes, Complianz, GDPR Cookie Consent)
* Integrates with WordPress Personal Data Export and Erasure tools

== Installation ==

1. Upload the `sales-notification` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the **Plugins** menu in WordPress
3. Ensure WooCommerce is installed and active
4. Navigate to **Sales Notification → Settings** to configure the plugin
5. For real order notifications, set source to **Real WooCommerce Orders**
6. Visit your site frontend to see notifications appear

== Frequently Asked Questions ==

= Does this work with WooCommerce HPOS? =

Yes. The plugin declares full HPOS (High-Performance Order Storage / Custom Order Tables) compatibility.

= Can I show fake notifications? =

Yes. Switch the source to **Demo Notifications** in General Settings, then add and manage demo entries under the **Demo Notifications** menu.

= How do I change the popup position? =

Go to **Sales Notification → Settings → Design** and click any of the 6 position buttons in the visual grid.

= Can I override the popup template from my theme? =

Yes. Copy any file from `sales-notification/templates/template-{n}.php` to `yourtheme/sales-notification/template-{n}.php`.

= Is this GDPR compliant? =

Yes. When GDPR mode is enabled, no cookies are set until consent is detected from a supported CMP. IP addresses are never stored in plain text.

= How do I use the developer hooks? =

See the full hook documentation in the plugin's `includes/class-sn-notification-data.php` and `includes/class-sn-woocommerce.php` files.

== Screenshots ==

1. Frontend notification popup — Horizontal template
2. Admin settings panel — Design tab with live preview
3. Analytics dashboard
4. Demo notification manager
5. Mobile responsive notification

== Changelog ==

= 1.0.0 =
* Initial release
* Real WooCommerce order notifications (HPOS compatible)
* Demo notification support with custom manager
* 6 popup positions, 3 templates, 6 animations
* Full design customization (colors, typography, border radius, shadows)
* Cookie-based deduplication
* GDPR mode with CMP integration
* Impressions, click, and dismissal analytics
* Import / export settings
* RTL support
* Full developer hooks API (PHP + JS)
* WordPress Personal Data Export/Erasure integration

== Upgrade Notice ==

= 1.0.0 =
Initial release.
