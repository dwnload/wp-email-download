# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Email Download** — A WordPress plugin that gates file downloads behind MailChimp email subscription verification. Users enter their email, the plugin checks if they're subscribed to a specified MailChimp list, and if so, grants access to the download.

**Requirements:** PHP 8.3+, WordPress 6.7+, Node.js (for block building).

## Key Commands

### PHP
```
composer install              # Install PHP dependencies
composer phpcs                # Run PHP_CodeSniffer (default workflow)
```

### JavaScript / Block Building
```
npm install                   # Install JS dependencies
npm run build                 # Build block assets (`wp-scripts build --webpack-src-dir=block --webpack-copy-php`)
npm run start                 # Dev mode with HMR (`wp-scripts start --webpack-src-dir=block --blocks-manifest`)
npm run plugin-zip            # Generate plugin ZIP for distribution
npm run lint:js               # Lint JS
npm run lint:css              # Lint CSS/SCSS
npm run format                # Format code
npm run packages-update       # Update @wordpress/scripts packages
```

### CI (GitHub Actions)
- `composer phpcs` runs on PHP 8.3 and 8.4 in CI (`./.github/workflows/main.yml`)
- PHPCS output is reported in PRs via `cs2pr`

## Architecture

### Entry Point
- **`email-download.php`** — Plugin header, version check (PHP 8.3+), autoloader boot
- **`src/EmailDownload.php`** — Bootstrap class. Calls `hookup()` which registers all components via `PluginFactory::create()` chain, then `initialize()`

### PHP Component Organization (`src/`)

| Namespace | Class | Purpose |
|-----------|-------|---------|
| `Admin\Settings` | `Settings` | WordPress admin settings page (MailChimp API key + list selector). Registers via `wp-settings-api` init hook. |
| `Api\Api` | `Api` | Core encryption/decryption, email validation (RFC + DNS), transient rate-limiting, file URL resolution. Used by controllers. |
| `Api\ApiFactory` | `ApiFactory` (trait) | Injects `Api` into any class via constructor — used by `DownloadController`, `SubscriptionController`. |
| `Api\Mailchimp` | `Mailchimp` | Extends `DrewM\MailChimp\MailChimp`. Wraps `get('lists')` calls with WordPress transients (1-day cache). |
| `Api\Scripts` | `Scripts` | Enqueues frontend JS/CSS, localizes script data (root URL, namespace, nonce) for both shortcode and block. |
| `Blocks\EmailDownload` | `EmailDownload` | Registers the Gutenberg block from `build/` directory. |
| `RestApi\DownloadController` | `DownloadController` | GET `/dwnload/v1/download/<encrypted-data>` — decrypts, validates expiration (2 days), streams file. |
| `RestApi\MailchimpLists` | `MailchimpLists` | GET `/dwnload/v1/lists` — returns MailChimp lists for the block editor (admin permission). |
| `RestApi\SubscriptionController` | `SubscriptionController` | POST `/dwnload/v1/user/<email>` — verifies subscription, returns encrypted download URL. Rate-limited (5/hour per transient). |
| `Shortcode\Handler` | `Handler` | `[email_to_download list-id="..." file="..."]` shortcode handler. Renders `views/form.php`. Registers shortcode UI. |

### Block / Frontend JS

| File | Role |
|------|------|
| `block/block.json` | Block registration (attributes: `listId`, `listName`, `attachmentId`, `attachmentUrl`) |
| `block/edit.js` | Gutenberg editor UI — list selector (fetched from `/dwnload/v1/lists`), media picker |
| `block/render.php` | Server-side render of the email form for frontend/block view |
| `block/view.js` | Frontend form submission — fetches `/dwnload/v1/user/<email>`, handles redirect on success |
| `block/index.js` | Block registration (imports `edit.js`, `style.scss`) |
| `assets/js/email-download.js` | Fallback shortcodes frontend handler |

### REST API Routes

All routes under namespace `dwnload/v1`:
- `GET /lists` — MailChimp lists (admin only)
- `POST /user/<email>` — Verify subscription, return download URL
- `GET /download/<encrypted-data>` — Stream the actual file

### Key Patterns

- **Encryption:** Download URLs are encrypted with `Api::encrypt()` containing `email||sub_hash||file_url||file_id||expires`. Decrypted by `DownloadController`. 2-day expiration.
- **Rate Limiting:** Transient-based (per computer ID / user agent + IP hash). Max 5 submissions per hour, transient stored 1 day.
- **Transients:** MailChimp lists cached 1 day (`dwnload/mailchimp_lists_<base64(api_key)>`).
- **Settings:** Stored via `dwnload/wp-settings-api` package. API key stored with option `dwnload_api_key`.
- **Dependencies:** Uses `the frosty` ecosystem — `wp-utilities`, `wp-settings-api`, `wp-api` — all following the same `AbstractHookProvider` + `PluginFactory` pattern.

### Configuration Files

- `composer.json` — PHP deps (MailChimp API 2.5.4, wp-settings-api ^3.11, wp-utilities ^3.8.6), PHPCS config, PHPUnit
- `package.json` — `@wordpress/scripts ^32.4.0`, build/lint scripts
- `.github/dependabot.yml` — Weekly composer/npm/github-actions updates on `develop`
- `phpcs-ruleset.xml` — WordPress-Docs + WordPress.WP.I18n + PSR12 + Slevomat + PHPCompatibility rules
