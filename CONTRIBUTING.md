# Contributing

Gjallr is a rescue tool, not a CMS.

Good contributions keep the runtime clean, make imports safer, or make the builder handoff clearer. Weak contributions recreate WordPress behavior because it is familiar. Familiar is not a reason.

## Local Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Configure the app and WordPress source databases in `.env`.

## Checks

```bash
composer test:all
```

That runs Composer validation, Pint, PHPStan, Rector dry-run, and Pest.

## Boundaries

- WordPress logic stays in `app/Ingestion/WordPress/`.
- Wayback logic stays in `app/Ingestion/Wayback/`.
- Runtime code must not know WordPress internals.
- `gjallr:build` writes reconstruction evidence. It must not pretend to design the finished site.

## Pull Requests

Include the problem, the change, and the checks you ran. If you cross an ingestion/runtime boundary, explain why.
