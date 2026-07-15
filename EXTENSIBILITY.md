# Wordvane Extensibility Reference

This document is the interface contract between **Wordvane (free)** and **Wordvane Pro** (or any third-party add-on). Every hook listed here is stable across minor versions. Breaking changes will bump the minor version and be documented in the changelog.

**Since:** 1.0.0  
**Required free plugin:** ≥ 1.0.0 (`WV_VERSION`)  
**Class to detect:** `WV_Features`

---

## Feature Gate

### `WV_Features::is_pro() : bool`
Returns `true` when a valid Freemius Pro license is active, or when `WV_PRO_DEV` is defined and truthy (local dev bypass). All Pro behaviour in the free plugin is gated through this method — never check Freemius directly in add-on code.

### `WV_Features::has_feature( string $slug ) : bool`
Fine-grained gate for a single Pro feature. Filter `wordvane_has_feature` to restrict features within a Pro license tier. Known slugs: `bulk_queue`, `content_calendar`, `multi_dna_profile`, `internal_linking`, `content_refresh`, `white_label`, `team_roles`.

### `WV_Features::get_upgrade_url() : string`
Returns the upgrade URL shown in all CTAs. Freemius replaces the fallback via the `wordvane_upgrade_url` filter.

---

## Filters

### `wordvane_is_pro`
**File:** `wordvane.php`  
**Type:** filter → `bool`

Freemius wires into this filter after SDK initialization. Add-ons that want to confirm a valid license are active should call `WV_Features::is_pro()` rather than filtering this directly.

```php
// Already wired in wordvane.php — shown for documentation only.
add_filter( 'wordvane_is_pro', function() {
    $fs = wordvane_fs();
    return $fs && $fs->can_use_premium_code();
} );
```

---

### `wordvane_upgrade_url`
**File:** `wordvane.php`  
**Type:** filter → `string`

Override the upgrade URL shown in CTAs throughout the admin. Freemius replaces this with `wordvane_fs()->get_upgrade_url()`. You can also use it to point to a custom checkout page.

```php
add_filter( 'wordvane_upgrade_url', fn() => wordvane_fs()->get_upgrade_url() );
```

---

### `wordvane_monthly_limit`
**File:** `includes/class-wv-limits.php` → `WV_Limits::get_limit()`  
**Type:** filter → `int`

Overrides the monthly article generation cap. Free default is `5`. Pro sets this to `PHP_INT_MAX` (unlimited). The generator, usage counter, and progress bar all read this value.

```php
// Wordvane Pro — unlimited generation.
add_filter( 'wordvane_monthly_limit', fn() => PHP_INT_MAX );

// Hypothetical "Starter" plan — 25/month.
add_filter( 'wordvane_monthly_limit', fn() => 25 );
```

---

### `wordvane_generation_args`
**File:** `includes/class-wv-generator.php` → `WV_Generator::ajax_generate()`  
**Type:** filter → `array`  
**Parameters:** `( array $args, int $user_id )`

Filters the generation arguments before the AI prompt is built. Pro uses this to:
- Inject bulk-queue job context (`_queue_id`, `_batch_id`)
- Swap in a specific Business DNA profile
- Set `_system_prompt` / `_user_message` to bypass the free prompt templates (needed for Pro article types)
- Override `max_tokens` for longer articles

```php
// Register a custom article type with a bespoke prompt.
add_filter( 'wordvane_generation_args', function( $args, $user_id ) {
    if ( 'comparison' !== $args['article_type'] ) {
        return $args;
    }
    $args['_system_prompt'] = my_comparison_system_prompt( $args );
    $args['_user_message']  = my_comparison_user_message( $args );
    $args['max_tokens']     = 6000;
    return $args;
}, 10, 2 );
```

**`$args` keys:**

| Key | Type | Description |
|---|---|---|
| `keyword` | string | Primary keyword |
| `secondary_keywords` | string | Comma-separated secondary keywords |
| `article_type` | string | Content type slug |
| `featured_product` | int | Product array index, -1 for none |
| `custom_instructions` | string | User-supplied extra instructions |
| `settings` | array | Business DNA option array |
| `max_tokens` | int | AI client max tokens (default 4096) |
| `_system_prompt` | string | *Optional override* — bypasses built-in system prompt |
| `_user_message` | string | *Optional override* — bypasses built-in user message |

---

### `wordvane_article_types`
**File:** `admin/views/page-generator.php`  
**Type:** filter → `array`

Filters the article types rendered as cards in the generator UI. Each key is the slug sent as `article_type` to the AJAX handler. Pro registers Comparison, Listicle, Category Page, and WooCommerce Product Description here.

```php
add_filter( 'wordvane_article_types', function( $types ) {
    $types['comparison'] = [
        'icon' => '⚖️',
        'name' => __( 'Comparison', 'wordvane-pro' ),
        'desc' => __( 'X vs Y articles that convert buyers at the final decision stage.', 'wordvane-pro' ),
    ];
    $types['listicle'] = [
        'icon' => '📋',
        'name' => __( 'Listicle', 'wordvane-pro' ),
        'desc' => __( 'Ranked list post. High shareability, strong for featured snippets.', 'wordvane-pro' ),
    ];
    return $types;
} );
```

---

### `wordvane_publisher_post_types`
**File:** `includes/class-wv-publisher.php` → `WV_Publisher::ajax_publish_post()`  
**Type:** filter → `string[]`

Whitelists post types the publisher will accept. Free default: `['post']`. Pro adds `page`, `product` (WooCommerce), and any registered CPT.

```php
add_filter( 'wordvane_publisher_post_types', function( $types ) {
    $types[] = 'page';
    $types[] = 'product'; // WooCommerce
    return $types;
} );
```

---

### `wordvane_business_dna_profiles`
**File:** `admin/views/page-settings.php`  
**Type:** filter → `int`

Controls how many Business DNA profiles the user may store. Free tier: `1`. Pro agencies can allow multiple profiles — one per client site — by returning a higher integer. The settings UI displays a badge indicating the current limit.

```php
add_filter( 'wordvane_business_dna_profiles', fn() => 10 ); // Pro agency tier
```

---

### `wordvane_seo_schema_types`
**File:** `includes/class-wv-seo.php` → `WV_SEO::output_faq_schema()`  
**Type:** filter → `string[]`  
**Parameters:** `( string[] $types, int $post_id )`

Controls which schema types are output in `<head>`. Free default: `['FAQPage']`. Remove `'FAQPage'` to suppress it; add other `@type` values for Pro to handle and output separately.

```php
add_filter( 'wordvane_seo_schema_types', function( $types, $post_id ) {
    // Let Pro handle FAQPage with richer markup; suppress the free output.
    return array_diff( $types, [ 'FAQPage' ] );
}, 10, 2 );
```

---

### `wordvane_settings_tabs`
**File:** `admin/views/page-settings.php`  
**Type:** filter → `array`

Filters the tabs array on the Settings page. Keys are tab slugs; values are translated display labels. Pro adds License, Agency, and Team tabs. Tab content is rendered via `wordvane_settings_tab_content` action.

```php
add_filter( 'wordvane_settings_tabs', function( $tabs ) {
    $tabs['license'] = __( 'License', 'wordvane-pro' );
    $tabs['agency']  = __( 'Agency', 'wordvane-pro' );
    return $tabs;
} );
```

---

### `wordvane_has_feature`
**File:** `includes/class-wv-features.php` → `WV_Features::has_feature()`  
**Type:** filter → `bool`  
**Parameters:** `( bool $available, string $slug )`

Lets add-ons gate individual features independently of the main `is_pro()` flag. Useful for partial-feature plans or trial licences.

```php
add_filter( 'wordvane_has_feature', function( $available, $slug ) {
    // Allow internal linking on all plans, restrict bulk_queue to Pro+.
    if ( 'internal_linking' === $slug ) {
        return true;
    }
    return $available;
}, 10, 2 );
```

---

## Actions

### `wordvane_before_generate`
**File:** `includes/class-wv-generator.php`  
**Parameters:** `( array $generation_args )`

Fires immediately before the AI client call. Use for:
- Pre-generation logging / queue status updates
- Rate-limit enforcement at the Pro level
- Injecting last-minute prompt context

```php
add_action( 'wordvane_before_generate', function( $args ) {
    WVP_BulkQueue::mark_job_running( $args['_queue_id'] ?? null );
} );
```

---

### `wordvane_after_generate`
**File:** `includes/class-wv-generator.php`  
**Parameters:** `( string $result, array $generation_args )`

Fires after a successful generation (before sending JSON to the browser). Use for:
- Post-generation logging / reporting
- White-label text substitution
- Internal linking injection (scan `$result`, insert links)

```php
add_action( 'wordvane_after_generate', function( $result, $args ) {
    WVP_InternalLinks::inject( $result, $args['keyword'] );
    WVP_Logger::record( get_current_user_id(), $args );
}, 10, 2 );
```

---

### `wordvane_admin_menu_items`
**File:** `includes/class-wv-admin.php` → `WV_Admin::register_menus()`  
**Parameters:** `( string $parent_slug )`

Fires after the free plugin's submenu pages are registered. Pro uses this to add Bulk Queue and Content Calendar without modifying the free plugin.

```php
add_action( 'wordvane_admin_menu_items', function( $parent_slug ) {
    add_submenu_page(
        $parent_slug,
        __( 'Bulk Queue', 'wordvane-pro' ),
        __( 'Bulk Queue', 'wordvane-pro' ),
        'edit_posts',
        'wv-bulk-queue',
        [ WVP_BulkQueue::class, 'render_page' ]
    );
} );
```

---

### `wordvane_settings_tab_content`
**File:** `admin/views/page-settings.php`  
**Parameters:** `( string $active_tab )`

Fires when a settings tab that is not `dna` or `publishing` is active. Pro renders its License, Agency, and Team tab content here.

```php
add_action( 'wordvane_settings_tab_content', function( $tab ) {
    if ( 'license' === $tab ) {
        include WVP_PLUGIN_DIR . 'admin/views/tab-license.php';
    }
    if ( 'agency' === $tab ) {
        include WVP_PLUGIN_DIR . 'admin/views/tab-agency.php';
    }
} );
```

---

### `wordvane_dashboard_widgets`
**File:** `admin/views/page-insights.php`  
**Parameters:** none

Fires at the end of the Insights dashboard, after all free content. Pro registers content-refresh suggestion cards and agency reporting widgets here.

```php
add_action( 'wordvane_dashboard_widgets', function() {
    include WVP_PLUGIN_DIR . 'admin/views/widget-refresh.php';
} );
```

---

## Minimum Version Check (for Pro plugin)

Always gate Pro activation on the free plugin being present and at minimum version:

```php
add_action( 'plugins_loaded', function() {
    if ( ! class_exists( 'WV_Features' ) || version_compare( WV_VERSION, '1.0.0', '<' ) ) {
        add_action( 'admin_notices', function() {
            echo '<div class="notice notice-error"><p>';
            echo '<strong>Wordvane Pro</strong> requires Wordvane free ≥ 1.0.0. ';
            echo '<a href="' . esc_url( admin_url( 'plugin-install.php?s=wordvane' ) ) . '">Install / update it</a>.';
            echo '</p></div>';
        } );
        return;
    }
    // Boot Pro.
    require_once __DIR__ . '/includes/class-wvp-loader.php';
    new WVP_Loader();
} );
```
