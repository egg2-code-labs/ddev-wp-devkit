[![add-on registry](https://img.shields.io/badge/DDEV-Add--on_Registry-blue)](https://addons.ddev.com)
[![tests](https://github.com/egg2-code-labs/ddev-wp-devkit/actions/workflows/tests.yml/badge.svg?branch=main)](https://github.com/egg2-code-labs/ddev-wp-devkit/actions/workflows/tests.yml?query=branch%3Amain)
[![last commit](https://img.shields.io/github/last-commit/egg2-code-labs/ddev-wp-devkit)](https://github.com/egg2-code-labs/ddev-wp-devkit/commits)
[![release](https://img.shields.io/github/v/release/egg2-code-labs/ddev-wp-devkit)](https://github.com/egg2-code-labs/ddev-wp-devkit/releases/latest)

# ddev-wp-devkit

DDEV add-on for local WordPress development with real databases from production, staging or a teammate.
Import a database and the site runs locally, without caches, security plugins, cron or outgoing mail getting in the way. Optionally share the database between developers via a remote folder (e.g. Google Drive) with a pointer in git.

## What it does

| When | What |
|---|---|
| `ddev start` | Creates `wp-config.php` if none exists (or only the DDEV-generated one), activates the git hooks. |
| `ddev import-db` | Rewrites URLs to the DDEV URL (incl. multisite tables, JSON-escaped and URL-encoded variants, `http` → `https`). |
| Every request | Disables cache, security/2FA, backup, analytics and SMTP plugins **at runtime**; the database stays unchanged. |
| Every request | `DISABLE_WP_CRON`, Action Scheduler async runner off, all mail to Mailpit (even with SMTP plugins), `WP_DEBUG_LOG` on, `WP_CACHE` off. |
| PHP | `.user.ini` files are ignored (e.g. Wordfence WAF with a production server path). |

The add-on **never writes plugin state to the database**. Plugins stay active in the database and are only filtered out locally. Saving the plugins screen does not remove them from the database either.

## Installation

```bash
ddev add-on get egg2-code-labs/ddev-wp-devkit
ddev restart
ddev wpdev status
```

After installation, make sure to commit the `.ddev` directory to version control.

## Requirements

- DDEV v1.24.10 or newer
- A DDEV project of type `wordpress`
- WordPress core in the docroot or in one subdirectory below it (like DDEV itself supports; the generated `wp-config.php` detects the location of `wp-settings.php`)
- `python3` on the host (used by `ddev dbsync`)
- `rclone` (only for `ddev dbsync` with a remote folder)
- `git` (database sharing relies on git hooks)

## Commands

```bash
ddev wpdev status            # overview: URL, multisite, plugins disabled locally, ...
ddev wpdev prepare           # rewrite URLs (runs automatically after ddev import-db)
ddev wpdev admin             # create local admin "devadmin" / "devadmin" or reset its password
ddev wpdev admin name pass   # with your own name/password
ddev wpdev setup             # create wp-config.php (runs automatically on ddev start)
ddev wpdev hash [--verbose]  # content hash of the database (used by ddev dbsync)
```

## Sharing the database (`ddev dbsync`)

The database lives in a shared remote folder (Google Drive via rclone, any other rclone remote, or a locally synced folder). Git only tracks `db/snapshot.json` with file name, SHA-256 and content hash of the dump.

| When | What |
|---|---|
| `git commit` (pre-commit) | Content hash of the database ≠ pointer → `ddev export-db`, upload, the new pointer is added to the commit |
| `git checkout` / `merge` / `rebase` | Pointer ≠ loaded snapshot → download, SHA check, `ddev import-db`. Unsaved local changes are never overwritten. |
| Merge conflict in `db/snapshot.json` | Both sides changed the database → `ddev dbsync resolve ours\|theirs` |

The content hash ignores data that changes without anyone editing content: transients, cron, sessions, edit locks, auto-drafts, WPML caches, logs, statistics and security tables. Browsing or logging in does not create a snapshot.

```bash
ddev dbsync status | pull [--force] | push | resolve ours|theirs
ddev dbsync init <dump>          # first import from production: rewrite URLs + create devadmin
ddev dbsync golive <url> <id>    # production export: reassign devadmin's content to <id>, delete devadmin, rewrite URLs back
```

Setup: set `WPDEV_DB_REMOTE=<rclone-remote>:` in `.ddev/.env`; every developer creates an rclone remote with that name (`rclone config create <name> drive scope=drive root_folder_id=<folder-id>`). Alternatively use a local folder in `.ddev/.env.local`. The git hooks are activated on `ddev start` via `core.hooksPath`; if it is already set to something else, it is left unchanged.

Skip the hook once: `WPDEV_DB_SKIP=1 git commit ...`

Always commit with a plain `git commit` (or `git commit -a`). `git commit --only <file>` would bypass the pointer staged by the pre-commit hook and silently desynchronize `db/snapshot.json` from the uploaded dump.

## Configuration

In `.ddev/.env` (committed), then `ddev restart`:

| Variable | Default | Meaning |
|---|---|---|
| `WPDEV_MULTISITE` | empty | `subdirectory` or `subdomain` for multisite networks. `prepare` reports when it is missing. |
| `WPDEV_TABLE_PREFIX` | `wp_` | Table prefix |
| `WPDEV_DISABLE_PLUGINS` | see `wp-devkit/wp-config-devkit.php` | Replaces the default list (plugin folder names, separated by spaces/commas) |
| `WPDEV_DISABLE_PLUGINS_EXTRA` | empty | Disable additionally |
| `WPDEV_KEEP_PLUGINS` | empty | Allow again from the default list |
| `WPDEV_DISABLE_CRON` | `true` | WP-Cron and Action Scheduler async runner off |
| `WPDEV_BLOCK_EXTERNAL_HTTP` | `false` | Block outgoing HTTP requests |
| `WPDEV_HTTP_ALLOW` | `*.wordpress.org` | Allowed hosts when blocked |
| `WPDEV_SR_SKIP_TABLES` | statistics/log tables | Tables skipped during URL rewrite (wildcards) |
| `WPDEV_ADMIN_USER` / `_PASSWORD` / `_EMAIL` | `devadmin` / `devadmin` / `<user>@example.test` | Defaults for `ddev wpdev admin` |
| `WPDEV_ADMIN_AUTO` | `false` | Create the admin after every import if missing |
| `WPDEV_DB_REMOTE` | empty | Target for `ddev dbsync`: rclone remote (`name:path`) or absolute local path |
| `WPDEV_DB_HASH_SKIP_TABLES` / `_OPTIONS` / `_META` | see `commands/web/wpdev` | Regex: what does not affect the content hash |

Project-specific PHP constants (e.g. dev license keys, WPML settings) go into `.ddev/wp-config-project.php`, which is loaded first.

## Custom wp-config.php

If the project has its own committed `wp-config.php`, the add-on leaves it alone. Add this line before `wp-config-ddev.php` is loaded:

```php
if ( getenv( 'IS_DDEV_PROJECT' ) === 'true' ) { require '/var/www/html/.ddev/wp-devkit/wp-config-devkit.php'; }
```

## Limitations

- **Base64-encoded content** (e.g. WPBakery `vc_raw_html`) is not covered by the URL rewrite.
- WP-CLI skips serialized objects of plugin classes (warning "Skipping an uninitialized class"), usually only admin notices.
- Multisite: user names need at least 4 characters, `admin` is not allowed.
- Subdomain multisite also needs `additional_hostnames` in the DDEV config.
- Databases cannot be merged. `ddev dbsync` only makes parallel changes visible (merge conflict); resolving means picking one side and redoing the other side's changes by hand.
- The content hash ignores known noise. If a plugin writes to the database on every request, every commit creates a new snapshot. Compare `ddev wpdev hash --verbose` before/after a page request and exclude the option via `WPDEV_DB_HASH_SKIP_OPTIONS` / `_META` / `_TABLES`.
- The content hash aggregates every table in SQL and runs on every `git commit` (pre-commit hook). On small and medium databases this takes seconds; on very large databases it can take considerably longer. `ddev wpdev hash --verbose` shows which tables dominate the run time.
- rclone's shared Google Drive client ID is being retired in 2026. Create your own OAuth client: https://rclone.org/drive/#making-your-own-client-id

## Credits

- Initial implementation of the plugin code by [Florian Behrend](https://github.com/florianbehrend).

**Contributed and maintained by [@egg2-code-labs](https://github.com/egg2-code-labs)**
