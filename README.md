# Gjallr

Gjallr is a Laravel-based WordPress rescue tool.

The current slice can inspect a WordPress source, import the useful runtime data, and serve the rescued site without executing WordPress.

- Laravel foundation with Pint, Pest, Pest architecture tests, PHPStan, and Rector
- hard separation between ingestion, transformation, and domain code
- `gjallr:analyze-source` for profiling WordPress dumps and site trees
- `gjallr:import` for moving posts, pages, comments, terms, menus, media records, and legacy query redirects into clean runtime tables
- read-only runtime routes for content, taxonomy archives, JSON page data, and rescued media
- optional Wayback checks for archive availability, missing upload recovery, and legacy URL discovery
- run recording plus JSON artifacts for compatibility analysis

## Analyze A Source

Defaults are wired to the provided sample:

- SQL dump: `~/Projects/old/odinns/old-2/odinns_dk_db_tantraviking.sql.gz`
- site tree: `~/Projects/old/odinns/old-2/tantraviking-old`

You can override them with:

```bash
php artisan gjallr:analyze-source \
  --sql-dump=/path/to/site.sql.gz \
  --site-path=/path/to/site-root \
  --source-label="my old site"
```

Add `--json` if you want the full profile dumped to stdout.

Artifacts are written under `storage/app/gjallr/analysis/`.

Add `--with-wayback` to run a bounded Internet Archive availability check for the detected `home_url` or `site_url`:

```bash
php artisan gjallr:analyze-source \
  --sql-dump=/path/to/site.sql.gz \
  --site-path=/path/to/site-root \
  --source-label="my old site" \
  --with-wayback
```

Set a real user agent before making real archive requests:

```env
WAYBACK_MACHINE_USER_AGENT="gjallr-site-rescue/1.0 (you@example.com)"
```

## Import And Serve

```bash
php artisan gjallr:import \
  --sql-dump=/path/to/site.sql.gz \
  --site-path=/path/to/site-root \
  --source-label="my old site"
```

Gjallr stores media URLs as `/rescued-media/{path}`. At runtime it serves the old local WordPress upload first. If that file is gone, it checks Gjallr-owned recovered storage second. If neither exists, it returns 404. No WordPress boot, no theme execution, no nostalgia tax.

## Wayback Rescue

Recover missing uploads:

```bash
php artisan gjallr:wayback:recover-media \
  --source-label="my old site" \
  --limit=50
```

Use `--dry-run` first if you want the plan without downloaded files. Use `--force` only when replacing already recovered files is intentional.

Find archived HTML paths that are not represented by current content, taxonomy routes, or redirect rules:

```bash
php artisan gjallr:wayback:discover-urls \
  --source-label="my old site" \
  --limit=500
```

This writes suggestions only. It does not create redirects. Archive evidence is useful, not divine law.

## What The Analyzer Detects

- core version and DB version signals
- table prefix and core table footprint
- active theme and permalink structure
- plugin and theme inventory from the site tree
- likely rescue capabilities such as comments, menus, uploads, terms, and SEO metadata
- suspicious markers in the dump that may indicate spam or compromise residue

## Development

```bash
composer test
./vendor/bin/pint
./vendor/bin/phpstan analyse
./vendor/bin/rector process
```
