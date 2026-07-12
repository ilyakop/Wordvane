=== Wordvane ===
Contributors: topdevs
Tags: ai content, seo, article generator, claude ai, content marketing, wordpress seo
Requires at least: 5.8
Tested up to: 6.5
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

AI-powered SEO article generator. Create keyword-optimized content with Claude AI and publish directly to WordPress.

== Description ==

**Wordvane** is a free WordPress plugin that helps any business create and publish keyword-optimized SEO content using Claude AI — one of the world's most capable AI models, made by Anthropic.

If you have ever wanted a consistent blog strategy but lacked the time or budget to hire a writer, Wordvane is built for you. In minutes, you can generate a 1,500-word, SEO-ready article that sounds like a real human wrote it — not a robot.

**Who is this for?**

* Small business owners who want Google traffic without paying an agency
* eCommerce stores that need product-focused blog content
* Local businesses that want to rank for local search keywords
* Bloggers and content sites looking to publish consistently
* SaaS companies that want to educate and attract prospects

**What it does:**

* Generates full 1,500-word SEO articles using Claude AI (Haiku, Sonnet, or Opus)
* Streams content live as it is written — you see the article appear in real time
* Automatically fills in meta title, meta description, URL slug, and tags
* Integrates with Yoast SEO, Rank Math, and All in One SEO automatically
* Injects FAQ schema markup for featured snippet eligibility
* Publishes directly to your WordPress site as a draft or live post
* Shows a built-in SEO score with actionable checklist
* Includes a complete onboarding wizard with step-by-step setup
* Includes an SEO Insights dashboard that explains Google ranking timelines
* Limits the free version to 5 articles per month, resetting on the 1st

**How it works:**

Wordvane connects to the Anthropic API using your own API key. You pay Anthropic directly — typically $1 to $5 per month for most users. The plugin never stores your key on external servers. It is encrypted and saved only on your own WordPress installation.

The free Anthropic account includes approximately $5 in credits, which covers 100 to 500 test articles on the Haiku model before any payment is required.

**Privacy:**

Wordvane does not collect, transmit, or store any user data on external servers. Your API key is encrypted using your WordPress security keys. Article inputs (keywords, business descriptions) are sent to the Anthropic API only at the time of generation. No data is logged by this plugin.

For full details, visit: https://topdevs.net/wordvane

== Installation ==

1. Upload the `wordvane` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. You will be redirected to the 4-step setup wizard automatically
4. Get your free API key at console.anthropic.com (no credit card needed)
5. Paste your key in Step 4 of the wizard, test the connection, and complete setup
6. Go to Wordvane → Generate Article to write your first article

== Frequently Asked Questions ==

= Do I need a paid subscription? =

No. Wordvane is free to install. You will need a free Anthropic account to get an API key. Anthropic gives you approximately $5 in free credits when you sign up — no credit card required. After that, you pay Anthropic directly for usage, typically $1–5 per month for most users.

= Is my API key safe? =

Yes. Your API key is encrypted using your WordPress site's unique security keys (SECURE_AUTH_KEY and SECURE_AUTH_SALT from wp-config.php) before being stored in your database. It is never transmitted to any server other than api.anthropic.com when you generate an article.

= How much does article generation cost? =

You pay Anthropic directly based on which model you use. Approximate costs per article:
- Claude Haiku 4.5: $0.01–$0.02
- Claude Sonnet 4.6: $0.03–$0.08
- Claude Opus 4.7: $0.10–$0.25

Most users spend $1–5 per month total.

= Does this work with Yoast and Rank Math? =

Yes. Wordvane integrates with Yoast SEO, Rank Math, and All in One SEO. Select your SEO plugin in Settings → API & Model, and meta titles, descriptions, and focus keywords will be filled in automatically each time you publish.

= Can I use my Claude Pro subscription instead of an API key? =

No. Claude Pro and Claude Max are chat subscription plans at claude.ai. The API is a separate, pay-as-you-go service accessed at console.anthropic.com. You need an API key from the API console — your Claude.ai subscription will not work here.

= Why is there a 5-article monthly limit on the free version? =

The free version includes 5 articles per month to give you a full experience before you decide to upgrade. The limit resets automatically on the 1st of every month. Upgrade to Wordvane Pro for unlimited generation, bulk scheduling, and more article types.

== Screenshots ==

1. Onboarding wizard — Step 4: API setup with cost breakdown and model selection
2. Article generator with live streaming output
3. Post-generation SEO score card and meta fields
4. SEO Insights page with timeline and quick wins checklist
5. Business DNA settings page

== Changelog ==

= 1.0.0 =
* Initial release
* 4-step onboarding wizard
* Live SSE streaming article generation
* Integration with Yoast SEO, Rank Math, and All in One SEO
* SEO score card with grade
* Monthly usage counter (5 article free limit)
* SEO Insights dashboard with tips and checklist
* Support for Claude Haiku, Sonnet, and Opus models

== Upgrade Notice ==

= 1.0.0 =
Initial release.
