# Wordvane

[![WP version](https://img.shields.io/badge/WordPress-%3E%3D7.0-blue)](https://wordpress.org/download/)
[![PHP](https://img.shields.io/badge/PHP-%3E%3D7.4-purple)](https://www.php.net/)
[![License](https://img.shields.io/badge/License-GPLv2%2B-green)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Freemius](https://img.shields.io/badge/Monetization-Freemius-orange)](https://freemius.com)

AI-powered SEO article generator for WordPress, built on the native **WordPress 7.0 AI Client** — not a bespoke API-key system.

---

## Why the WordPress AI Client instead of a direct API key?

Most AI-writing plugins ask you to paste an Anthropic or OpenAI key into a settings page, store it in `wp_options`, and make HTTP requests directly. This creates three problems:

1. **Lock-in** — if you switch AI providers, you configure every plugin separately.
2. **Security surface** — each plugin is responsible for encrypting and protecting your key.
3. **No standardisation** — every plugin ships its own HTTP client, rate-limit handling, and error recovery.

WordPress 7.0 solved all three with `wp_ai_client_prompt()`. You connect a provider **once** under **Settings → Connectors** and every compatible plugin (including Wordvane) picks it up. Provider plugins handle credentials. Wordvane handles content.

---

## Features (free tier)

| Feature | Detail |
|---|---|
| Article generation | 1,500-word SEO articles via WP AI Client |
| Providers | Any WP 7.0 AI provider (Anthropic, Google, OpenAI) |
| Article types | How-To Guide, Product Spotlight, FAQ Post |
| Publishing | Native Gutenberg blocks — fully editable |
| SEO integration | Yoast SEO, Rank Math, All in One SEO — meta auto-filled |
| Schema | FAQ JSON-LD in `<head>` (no SEO plugin required) |
| Business DNA | 1 profile (business type, products, brand voice) |
| Monthly limit | 5 articles / month |
| Onboarding | 3-step wizard |
| Insights | SEO Insights dashboard with timeline + checklist |

---

## Installation

### From WordPress.org

1. Plugins → Add New → search "Wordvane" → Install → Activate
2. Complete the setup wizard (redirects automatically)
3. Go to **Settings → Connectors**, install your AI provider plugin, and connect it
4. Return to **Wordvane → Generate Article**

### From source / Composer

```bash
# Clone into your plugins directory
git clone https://github.com/topdevs/wordvane.git wp-content/plugins/wordvane

# If you're bundling the Freemius SDK via Composer:
cd wp-content/plugins/wordvane
composer install
```

The Freemius SDK is not committed to this repo. Download it from your [Freemius dashboard](https://dashboard.freemius.com) and place it at `wordvane/freemius/`. Without it, the plugin works fully in free mode — Freemius-dependent features (license activation, Freemius upgrade URL) fall back to static values.

---

## Architecture

```
wordvane/
├── wordvane.php          # Main file — constants, lifecycle hooks, Freemius init
├── includes/
│   ├── class-wv-features.php
│   ├── class-wv-limits.php
│   ├── class-wv-generator.php
│   ├── class-wv-publisher.php
│   ├── class-wv-seo.php
│   ├── class-wv-admin.php
│   └── wv-tooltips.php
├── admin/
│   ├── css/wv-admin.css
│   ├── js/wv-admin.js
│   └── views/
│       ├── page-generator.php
│       ├── page-settings.php
│       ├── page-insights.php
│       └── page-wizard.php
├── vendor/freemius/      # Freemius SDK
└── readme.txt            # WP.org readme
```

---

## Wordvane Pro

Wordvane Pro is a separate paid add-on. Minimum version requirement: Wordvane free ≥ 1.0.0.

**Pro feature set:**
- Unlimited generation + Bulk Queue (keyword list → background WP-Cron jobs)
-Content Refresh Mode (diff view before publishing updates)
- Internal Linking Automation
- Multiple Business DNA profiles (agency / multi-site)
- Additional content types: Comparison, Listicle, Category Page, WooCommerce Product Description
- White-label PDF/CSV reporting
- Team roles & permissions

**Purchase:** https://topdevs.net/wordvane-pro

---

## Contributing

1. Fork the repo and create a branch from `main`
2. Follow [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/) (WPCS)
3. Add or update PHPDoc for any public method you change
4. Test against WordPress 7.0+ with at least one AI provider plugin active
5. Open a PR with a clear description of what changed and why

**Issue reporting:** https://github.com/topdevs/wordvane/issues

Please include: WordPress version, PHP version, active AI provider, and steps to reproduce.

---

## License

GPLv2 or later — see [LICENSE](https://www.gnu.org/licenses/gpl-2.0.html).
