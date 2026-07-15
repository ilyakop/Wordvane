=== Wordvane ===
Contributors: topdevs
Tags: ai content, seo, article generator, content marketing, wordpress seo
Requires at least: 7.0
Tested up to: 7.0
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

AI-powered SEO article generator built on the WordPress 7.0 native AI Client. No API keys — connect any provider under Settings → Connectors.

== Description ==

**Wordvane** is a free WordPress plugin that generates 1,500-word, SEO-optimized articles and publishes them directly to your site — using WordPress 7.0's built-in AI Client, not a bespoke API-key system.

That means Wordvane works with **any** AI provider you connect under Settings → Connectors (Anthropic, Google, OpenAI, and others), and you never have to paste an API key into a plugin settings page. Provider management stays in WordPress core where it belongs.

**Who is this for?**

* Small business owners who want Google traffic without paying an agency
* eCommerce stores that need product-focused blog content
* Local businesses targeting local search keywords
* Bloggers and content sites that need to publish consistently
* SaaS companies that want to educate and attract prospects

**What it does:**

* Generates full 1,500-word SEO articles using the WordPress AI Client (provider-agnostic)
* Automatically fills in meta title, meta description, URL slug, and tags
* Publishes directly to WordPress as a draft or live post in native Gutenberg blocks
* Integrates with Yoast SEO, Rank Math, and All in One SEO — meta fields filled automatically
* Injects FAQ JSON-LD schema in `<head>` for featured snippet eligibility (no SEO plugin required)
* Shows a built-in SEO score checklist with live grade
* Includes an onboarding wizard (Business DNA setup — 3 steps, no API key required)
* Includes an SEO Insights dashboard explaining Google ranking timelines and best practices
* Limits the free version to 5 articles per month, resetting on the 1st of every month

**How it works:**

Wordvane uses the `wp_ai_client_prompt()` API introduced in WordPress 7.0. You connect an AI provider (Anthropic, Google, or OpenAI) once under **Settings → Connectors** — Wordvane uses whichever provider is active. No keys are stored in Wordvane's settings or database. Article inputs (keywords, business descriptions) are sent to the connected provider at generation time only.

**Requirements:**

* WordPress 7.0 or higher
* An AI provider plugin active under Settings → Connectors (Anthropic, Google, or OpenAI)

**Privacy:**

Wordvane does not collect, log, or transmit any user data on its own servers. Article inputs (keywords, business descriptions) are sent to the AI provider you select at the time of generation — governed by that provider's privacy policy. Wordvane uses Freemius for license management and telemetry. See the Privacy section below for details.

== Installation ==

1. Upload the `wordvane` folder to `/wp-content/plugins/`
2. Activate the plugin through the Plugins menu in WordPress
3. You will be redirected to the 3-step setup wizard automatically
4. Complete the wizard (Business DNA: what you sell, who you sell to, your products)
5. Go to **Settings → Connectors** and install + connect an AI provider plugin
6. Return to **Wordvane → Generate Article** and write your first article

== Frequently Asked Questions ==

= Do I need to pay anything to use Wordvane? =

Wordvane is free and includes 5 articles per month. You do need a connected AI provider — Anthropic, Google, and OpenAI all offer free tiers or pay-as-you-go pricing. The plugin itself charges nothing.

= I do not see a place to enter an API key. How does the AI connection work? =

Wordvane uses the WordPress 7.0 AI Client, which centralises provider management in core. Go to **Settings → Connectors** and install the provider plugin for your chosen AI service (e.g. the Anthropic provider plugin). Once connected there, Wordvane picks it up automatically — no keys in Wordvane itself.

= Which AI providers are supported? =

Any provider that registers with the WordPress AI Client. As of WordPress 7.0 this includes Anthropic (Claude), Google (Gemini), and OpenAI (GPT). Additional providers may be added by the core team or the provider plugins themselves.

= Does this work with WordPress versions before 7.0? =

No. Wordvane requires WordPress 7.0+ because it depends on `wp_ai_client_prompt()`, which was introduced in that version. The generator UI is locked with a checklist when requirements are not met.

= Does this work with Yoast SEO, Rank Math, or All in One SEO? =

Yes. Select your active SEO plugin in **Wordvane → Settings → Publishing** and meta titles, descriptions, and focus keywords will be filled in automatically every time you publish.

= How does the FAQ schema work? =

If you have not selected a third-party SEO plugin, Wordvane outputs FAQ JSON-LD schema in your site's `<head>` for every article that has FAQ data. This makes it eligible for Google's featured snippet boxes. If you use Yoast or Rank Math, manage schema through those plugins instead.

= Why is there a 5-article monthly limit on the free version? =

The free version includes 5 articles per month so you can experience the full workflow before deciding to upgrade. The counter resets automatically on the 1st of every month. Wordvane Pro removes the limit entirely.

= What does Wordvane Pro add? =

See the **Wordvane Pro** section below.

== Wordvane Pro ==

Wordvane Pro is a separate paid add-on distributed via Freemius.

**Pro features:**

* **Unlimited article generation** — no monthly cap
* **Bulk Queue** — paste a keyword list, queue dozens of articles, process automatically via WP-Cron in the background
***Content Refresh Mode** — feed an existing post URL, get an AI-proposed update with a diff view before publishing
* **Internal Linking Automation** — indexes your existing content and suggests/inserts contextual links into new articles
* **Multiple Business DNA profiles** — one per client site, ideal for agencies managing multiple WordPress installations
* **Additional article types** — Comparison, Listicle, Category Page, WooCommerce Product Description
* **White-label reporting** — branded PDF/CSV client reports
* **Team roles & permissions** — control who can generate, publish, or edit settings

Wordvane Pro requires Wordvane free ≥ 1.0.0 and WordPress ≥ 7.0.

For pricing and purchase: https://topdevs.net/wordvane-pro

== Screenshots ==

1. Article generator — keyword input, article type selection, and custom instructions
2. Article output with live SEO score card and meta fields
3. SEO Insights dashboard with timeline, article type guide, and quick wins checklist
4. Business DNA settings page
5. Requirements lock screen shown when WordPress < 7.0 or no AI provider is connected

== Changelog ==

= 1.0.0 =
* Initial release

== Privacy ==

**What data leaves your site:**

* Article generation inputs (keywords, business descriptions) are sent to whichever AI provider plugin you have connected under Settings → Connectors. This is governed by that provider's privacy policy, not Wordvane's.
* Wordvane uses **Freemius** for license management. When you activate the plugin, Freemius may collect anonymous usage data (WordPress version, PHP version, plugin version, site URL hash) to help improve the product. You will be asked to opt in or opt out on first activation. You can opt out at any time from **Wordvane → Account** in the admin menu. Full details: https://freemius.com/privacy/

**What Wordvane stores locally (never transmitted by Wordvane):**

* Your Business DNA settings (business name, products, brand voice) — stored in `wv_settings` in your database
* Monthly article usage counters — stored as WordPress options
* Per-post meta: `_wv_meta_title`, `_wv_meta_description`, `_wv_target_keyword`, `_wv_faq_schema`
* Per-user checklist progress — stored in user meta

Wordvane does not have its own backend server. It makes no outbound HTTP requests other than through the WordPress AI Client to your chosen AI provider.
