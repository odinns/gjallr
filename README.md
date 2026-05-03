# Gjallr

Gjallr rescues dead WordPress sites into a clean Laravel application.

It is not a CMS. It is not a WordPress clone. It pulls the useful material out of WordPress, keeps the runtime clean, and gives a human builder enough evidence to rebuild the site properly.

Project site: <https://odinns.github.io/gjallr/>

## What It Does

- Profiles WordPress dumps and site trees.
- Imports posts, pages, comments, terms, menus, media records, SEO fields, and legacy query redirects.
- Serves a read-only Laravel runtime without booting WordPress.
- Recovers missing upload evidence from the Internet Archive when asked.
- Writes build dossiers for human and AI reconstruction work.

## What It Does Not Do

- Repair WordPress.
- Emulate plugins or themes.
- Generate a finished website by magic.
- Turn into a CMS with a fake moustache.

## Analyze A Source

Defaults are wired to the local sample paths in `config/gjallr.php`, but real work should pass paths explicitly:

```bash
php artisan gjallr:analyze-source \
  --sql-dump=/path/to/site.sql.gz \
  --site-path=/path/to/site-root \
  --source-label="my old site"
```

Add `--json` for the full profile on stdout. Artifacts are written under `storage/app/gjallr/analysis/`.

Add `--with-wayback` to run a bounded Internet Archive availability check for the detected `home_url` or `site_url`:

```bash
php artisan gjallr:analyze-source \
  --sql-dump=/path/to/site.sql.gz \
  --site-path=/path/to/site-root \
  --source-label="my old site" \
  --with-wayback
```

Set a real user agent before making archive requests:

```env
WAYBACK_MACHINE_USER_AGENT="gjallr-site-rescue/1.0 (you@example.com)"
```

## Import And Browse The Rescued Site

```bash
php artisan gjallr:import \
  --sql-dump=/path/to/site.sql.gz \
  --site-path=/path/to/site-root \
  --source-label="my old site"
```

After import, Gjallr is already a runnable Laravel app. It serves the rescued homepage, content pages, taxonomy archives, navigation menus, comments, and media through normal Laravel routes. No WordPress boot. No plugin roulette. Just the rescued material in a clean runtime.

Open the app at whatever host you use for local development or deployment:

```text
https://your-rescue-app.test/
```

Each content page also exposes AI-friendly JSON:

```text
https://your-rescue-app.test/some-page?format=json
```

That JSON includes the site metadata, page body HTML, SEO fields, route path, taxonomies, media, comments, and menus. It is meant for builders and AI agents that need structured evidence while rebuilding the site as a real Laravel application.

Gjallr stores media URLs as `/rescued-media/{path}`. Runtime media lookup checks the old local WordPress upload first, then Gjallr-owned recovered storage. If neither exists, it returns 404.

## Wayback Rescue

Recover missing uploads:

```bash
php artisan gjallr:wayback:recover-media \
  --source-label="my old site" \
  --limit=50
```

Use `--dry-run` before downloading. Use `--force` only when replacing already recovered files is intentional.

Find archived HTML paths that are not represented by current content, taxonomy routes, or redirect rules:

```bash
php artisan gjallr:wayback:discover-urls \
  --source-label="my old site" \
  --limit=500
```

This writes suggestions only. It does not create redirects. Archive evidence is useful, not law.

## Build Dossier

`gjallr:build` is the handoff from salvage to reconstruction.

It does not generate the final site. It writes a dossier for a builder, or for a builder working with AI, so the revived Laravel app can be designed deliberately instead of becoming a WordPress-shaped shadow.

```bash
php artisan gjallr:build \
  --source-label="my old site" \
  --format=both
```

The dossier includes:

- site metadata
- content and route inventory
- taxonomy paths
- media paths and local availability
- redirect rules
- builder notes

Artifacts are written under `storage/app/gjallr/build/`.

## Development

```bash
composer install
composer test
composer analyse
composer format:test
composer refactor:test
composer test:all
```

`composer test:all` is the release gate.

## Versioning

Versions come from Git tags. Do not add a `version` field to `composer.json`.

## Contributing

Keep changes small. Preserve the ingestion, transformation, and runtime boundaries. If a change makes Gjallr behave more like a CMS, it is probably wrong.

## Security

Report security issues privately. Downloaded archives and imported WordPress data are untrusted input.

## License

MIT.
