# Repository Guidelines

## Project Structure & Module Organization
This repository is currently specification-first. The main source of truth is [specification.md](/Users/odinn/Projects/odinns/gjallr/specification.md), which defines the MVP, architecture, and delivery phases for Gjallr.

As code is added, keep the structure aligned with the spec:
- `app/Ingestion/` for disposable WordPress-specific import logic
- `app/Transformation/` for mapping and cleanup at the boundary
- `app/Domain/` for permanent runtime models with zero WordPress naming
- `app/Support/` for shared framework-level helpers that are not domain concepts
- `resources/views/` for read-only frontend rendering
- `routes/console.php` for CLI entry points such as `import`, `analyze`, and `build`
- `tests/Feature/` and `tests/Unit/` for Pest coverage

Do not let `wp_*`, WordPress table names, or WordPress semantics leak into the domain/runtime layer.

## Build, Test, and Development Commands
The Laravel app scaffold is not in the repository yet, so there are no runnable commands today. When the app is bootstrapped, contributors should prefer the standard toolchain described in the spec:
- `composer install` to install PHP dependencies
- `composer test` to run the default test suite
- `php artisan test` to run the test suite
- `./vendor/bin/pest` for direct Pest runs
- `./vendor/bin/pest --group=architecture` for boundary and architecture rules
- `./vendor/bin/pint` for formatting
- `./vendor/bin/phpstan analyse` for static analysis
- `./vendor/bin/rector process` for automated refactors

If you add commands or scripts, document them here and keep them scriptable and repeatable.

## Coding Style & Naming Conventions
Follow Laravel conventions. Use clear domain names like `ContentItem`, `Taxonomy`, and `RedirectRule`; avoid clever naming and avoid WordPress-shaped runtime models.

Style rules:
- PSR-12 formatting via Pint
- 4-space indentation for PHP
- UTF-8, LF line endings, final newline, no trailing whitespace except where Markdown needs it
- Keep controllers thin, move import logic into dedicated services/actions
- Prefer deleting abstraction over adding another layer

## Testing Guidelines
Use Pest for all new tests, with browser tests reserved for critical flows later in the project. Write tests before or alongside implementation; the spec explicitly calls for TDD at phase start. Add Pest architecture tests once the app exists to enforce the ingestion -> transformation -> domain boundary.

Test naming should describe behavior, for example:
- `it_imports_posts_and_pages()`
- `it_preserves_original_urls()`

Focus coverage on import correctness, URL preservation, read-only rendering, and soft-failure behavior for broken media or shortcodes.

## Commit & Pull Request Guidelines
There is no Git history in this snapshot, so use a simple conventional style until one exists: `feat: add content import pipeline`, `fix: preserve comment parent mapping`.

Pull requests should include:
- a short description of the change
- the phase or requirement from `specification.md` it supports
- test evidence
- screenshots only when UI/rendering changes are involved

If a change crosses the ingestion/domain boundary, explain why. That is where bad ideas like to breed.

## Phase Workflow & Review Gates
At the start of each phase:
- branch from `main`
- reread the relevant section in `specification.md`
- write the test slice first or at least lock the expected behavior before implementation

Before a phase is considered done:
- simplify the changed code
- review the changed code
- run `./vendor/bin/pint`
- run `./vendor/bin/rector process`
- run `./vendor/bin/phpstan analyse`
- run `./vendor/bin/pest`
- stop and review boundary drift before merging

Push back on changes that blur the ingestion boundary, smuggle WordPress concepts into the runtime app, or add speculative abstraction. Those are not clever shortcuts. They are future rubble.
