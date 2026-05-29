# Social & Custom Fields Shortcodes

**Contributors:** Steel..xD
**Plugin URI:** https://github.com/vadikonline1/custom-fields-shortcodes
**Tags:** shortcodes, custom fields, social buttons, mobile bar, floating buttons, whatsapp, messenger, contact bar
**Requires at least:** 5.0
**Tested up to:** 6.7
**Stable tag:** 1.4.0
**Requires PHP:** 7.0
**License:** GPL v2 or later
**License URI:** https://www.gnu.org/licenses/gpl-2.0.html

---

## Description

**Social & Custom Fields Shortcodes** is an advanced WordPress plugin that combines:

* ✅ Dynamic custom field shortcodes
* ✅ Social floating buttons
* ✅ Mobile Smart Bar integration
* ✅ Frontend social/contact actions
* ✅ AJAX-powered settings
* ✅ Modern responsive mobile UI

This plugin is designed for developers, agencies, bloggers, business websites, and landing pages that need flexible shortcode management and beautiful mobile interaction tools.

---

# ✨ Features

## 🔹 Custom Fields Shortcodes

Easily display dynamic content anywhere on your website using shortcodes.

### Supported Features

* Display custom fields
* Use shortcodes in posts/pages/widgets
* Frontend rendering support
* Dynamic content output
* Lightweight and optimized

### Example

```php
[scfs field="phone"]
```

---

# 📱 Mobile Smart Bar Included

The plugin includes a fully customizable **Mobile Smart Bar** for mobile devices.

Perfect for:

* WhatsApp
* Messenger
* Phone Calls
* Email
* Facebook
* Instagram
* TikTok
* Telegram
* Support links
* Custom actions

---

# 🎨 Mobile Smart Bar Features

## Responsive Mobile Interface

* Works on phones and tablets
* Automatically hidden on desktop
* Sticky floating footer navigation

## Theme Presets

Choose from:

* Auto
* Light
* Dark
* Glass
* Custom

## Unlimited Buttons

* Add unlimited action buttons
* Drag & drop sorting
* Enable/disable individually
* Left / Center / Right positioning

## Advanced Design Controls

Customize:

* Background colors
* Hover colors
* Text colors
* Shadows
* Border radius
* Pulse animation
* Custom CSS

## Icon Support

Use:

* Emojis
* SVG icons
* HTML
* Font Awesome
* Text symbols

---

# ⚡ Example Button URLs

## WhatsApp

```text
https://wa.me/1234567890
```

## Messenger

```text
https://m.me/username
```

## Phone Call

```text
tel:+1234567890
```

## Email

```text
mailto:email@example.com
```

---

# 📦 Installation

## Automatic Installation

1. Go to:

   ```text
   WordPress Admin → Plugins → Add New
   ```
2. Upload or search for the plugin
3. Click:

   ```text
   Install Now
   ```
4. Activate the plugin

---

## Manual Installation

1. Upload plugin folder to:

   ```text
   /wp-content/plugins/
   ```
2. Activate through:

   ```text
   Plugins → Installed Plugins
   ```

---

# ⚠️ Required Dependency

This plugin requires:

## GitHub Plugin Manager

Repository:

```text
https://github.com/vadikonline1/github-plugin-manager
```

If the dependency is missing:

* The plugin will show a warning
* Download link will appear automatically

---

# ⚙️ Configuration

## Main Settings

Navigate to:

```text
Admin Panel → SCFS
```

or

```text
Admin Panel → Mobile Bar
```

---

# 📱 Mobile Smart Bar Setup

## Enable Bar

* Enable Mobile Smart Bar
* Choose theme preset
* Configure buttons
* Save settings

---

## Add Buttons

For each button configure:

| Option   | Description           |
| -------- | --------------------- |
| Enabled  | Enable/disable button |
| Label    | Button text           |
| URL      | Destination link      |
| Position | Left / Center / Right |
| Icon     | Emoji, SVG, HTML      |

---

# 🎨 Custom CSS Example

```css
.scfs-mobile-bar {
    backdrop-filter: blur(10px);
}

.scfs-mobile-item span {
    font-weight: bold;
}
```

---

# 🔥 Shortcode Examples

## Display Custom Field

```php
[scfs field="email"]
```

## Display Phone Number

```php
[scfs field="phone"]
```

## Display Custom Meta

```php
[scfs field="custom_meta"]
```

---

# 🧠 Architecture

The plugin uses:

* OOP structure
* Namespaces
* Autoloading
* Singleton pattern
* AJAX handlers
* Frontend classes

---

# 📂 Plugin Structure

```text
custom-fields-shortcodes/
│
├── includes/
├── assets/
├── languages/
├── templates/
├── custom-fields-shortcodes.php
└── README.md
```

---

# 🔄 Hooks

## Activation

```php
register_activation_hook()
```

## Deactivation

```php
register_deactivation_hook()
```

## Uninstall

```php
register_uninstall_hook()
```

---

# 🌍 Translation Ready

Text domain:

```text
scfs-oop
```

Supports:

* `.pot`
* `.po`
* `.mo`

---

# 📱 Compatibility

Compatible with:

* Elementor
* Gutenberg
* Classic Editor
* WooCommerce
* Most WordPress themes
* Cache plugins

---

# ❓ Frequently Asked Questions

## Does the mobile bar appear on desktop?

No. The Mobile Smart Bar is optimized only for mobile devices.

---

## Can I add unlimited buttons?

Yes.

---

## Can I use SVG icons?

Yes, HTML and SVG are supported.

---

## Does it support Font Awesome?

Yes.

---

## Is it translation ready?

Yes.

---

## Autoloading

PSR-4 style class loading.

---

# 🚀 Changelog

## Version 1.4.0

### Added

* Mobile Smart Bar integration
* Custom themes
* Pulse animation
* Drag & drop sorting
* AJAX improvements
* Better dependency handling

### Improved

* Frontend performance
* Mobile responsiveness
* Plugin architecture

### Fixed

* Minor UI bugs
* Settings validation
* Compatibility fixes

---

# 🆘 Support

## GitHub Issues

```text
https://github.com/vadikonline1/custom-fields-shortcodes/issues
```

## Documentation

```text
https://github.com/vadikonline1/custom-fields-shortcodes/wiki
```

---

# 👨‍💻 Credits

Developed by:
**Steel..xD**

GitHub:

```text
https://github.com/vadikonline1
```

---

# 📜 License

GPL v2 or later.

---

# ❤️ Tips & Tricks

## Custom Pulse Effect

```css
.scfs-mobile-center {
    animation: pulse 2s infinite;
}
```
