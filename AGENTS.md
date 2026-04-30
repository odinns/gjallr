# Repository Guidelines

## Project Shape
Gjallr is a Laravel app for rescuing dead WordPress sites.

The goal is not to recreate WordPress. The goal is to extract content, comments, URLs, media evidence, and SEO context into a clean Laravel runtime that a builder can revive properly.

Keep the zones sharp:
- `app/Ingestion/WordPress/` can know WordPress tables, options, quirks, and source mess.
- `app/Ingestion/Wayback/` can know Internet Archive mechanics.
- `app/Transformation/` maps source material into clean runtime concepts.
- `app/Domain/`, runtime models, controllers, routes, and views must not depend on WordPress or Wayback ingestion internals.
- `resources/views/` renders the read-only rescue runtime.
- `routes/console.php` is for CLI entry points that do not need full command classes.

If `wp_*`, WordPress table names, or archive mechanics leak into the runtime layer, stop. That is future rubble.

## CLI Surface
Current project commands:
- `php artisan gjallr:analyze-source`
- `php artisan gjallr:import`
- `php artisan gjallr:build`
- `php artisan gjallr:wayback:recover-media`
- `php artisan gjallr:wayback:discover-urls`

`gjallr:build` writes reconstruction dossiers. It does not generate a finished website. Human and AI can use the dossier to rebuild the site as a real Laravel app.

## Development Commands
- `composer install`
- `composer test`
- `composer analyse`
- `composer format`
- `composer format:test`
- `composer refactor:test`
- `composer test:all`

Use `composer test:all` before release work. It is the project gate.

## Coding Style
- Follow Laravel conventions.
- Use Pint / PSR-12.
- Use 4-space indentation for PHP.
- Keep names plain: `ContentItem`, `Taxonomy`, `RedirectRule`, `RescuedSite`.
- Avoid clever abstractions. Delete abstraction before adding more.
- Keep controllers thin. Import and archive work belong in services/actions.

## Testing
Use Pest for tests.

Focus coverage on:
- import correctness
- URL preservation
- read-only rendering
- comments and nested comments
- media fallback behavior
- Wayback requests staying opt-in and bounded
- architecture boundaries
- build dossier output

Browser tests can wait until there is a UI surface worth guarding.

## Release Rules
- Do not tag until CI is green.
- Do not add a Composer `version` field.
- Keep `composer.lock` committed; this is an app, not a reusable package.
- Do not commit generated storage files, logs, cached views, sessions, recovered media, or local analysis artifacts.
- If a public release needs GitHub, create the remote, push the branch, wait for CI, then tag.

## Pull Requests
Include:
- what changed
- what spec phase or requirement it supports
- test evidence
- screenshots only for visible rendering changes

Call out any change that crosses the ingestion/runtime boundary. That is where bad ideas like to breed.
