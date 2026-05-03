---
name: dead-site-rescue
description: Analyse random dead backed-up websites, pair likely source folders with SQL dumps, classify the site type, and prepare a Laravel rescue dossier or rebuild plan. Use when rescuing old web projects, archive folders, WordPress sites, custom PHP apps, static sites, or messy backup volumes.
---

# Dead Site Rescue

Use this skill when the source is an old backed-up website and the first job is understanding the wreckage.

The goal is not to force every site through the WordPress importer. WordPress is one detector. The real job is:

```text
Dead backed-up website in, analysed rescue dossier and Laravel reconstruction path out.
```

## Rules

- Do not assume WordPress from a folder name, old URL, or SQL dump name.
- Inspect filesystem signals and SQL schema before choosing an importer.
- Pair source folders and database dumps by path, names, timestamps, table prefixes, config values, and repeated archive copies.
- Treat duplicates across volumes as evidence, not separate sites until proven otherwise.
- Preserve uncertainty. If a claim is a guess, label it.
- Keep the output useful to a builder directing a frontend-capable AI agent.

## Workflow

1. Find candidate site roots.
   - Look for public web roots: `public_html`, `www`, `htdocs`, project folders, dated backups, and restored hosting archives.
   - Prefer roots with entrypoints such as `index.php`, `index.html`, `.htaccess`, `wp-config.php`, `admin/`, `includes/`, `assets/`, `images/`, `css/`, or `js/`.

2. Find database dumps.
   - Include `*.sql`, `*.sql.gz`, `*.sql.zip`, `*.sql.bz2`, and `*.sql.xz`.
   - Check nearby folders first, then sibling backup folders, dated snapshots, and duplicated volume paths.
   - Record compression format, size, modified time, and likely project name.

3. Classify the site.
   - WordPress signals: `wp-config.php`, `wp-content/`, `wp_posts`, `wp_options`, `wp_users`.
   - Generic PHP signals: `config.php`, `database.php`, `admin/`, `include/`, `includes/`, `members/`, `template/`, custom table prefixes.
   - Static or mixed signals: `index.html`, asset folders, generated pages, report exports, proof folders, and no obvious database dependency.

4. Inspect SQL before deciding.
   - List tables and prefixes.
   - Find content-like tables, user/member tables, settings tables, media/file tables, routes/slugs, and timestamps.
   - Compare database names, table prefixes, and config credentials with source files.

5. Choose the rescue target.
   - Content import: structured content can map into Laravel models.
   - Static preservation: generated HTML and assets are the source of truth.
   - Read-only runtime: Gjallr can serve rescued material for inspection.
   - Full rebuild: a builder uses the dossier, screenshots, Wayback evidence, and AI frontend work to produce the finished Laravel site.

## Daddy's Birthday Giveaway Check

Use Daddy's Birthday / Daddy's Day Giveaway as the reminder case.

It has old website folders and SQL dumps, but no obvious `wp-config.php`. The WordPress-only detector missed it because the site appears to be an older custom PHP project.

Known dump examples include:

```text
/Volumes/LIMA-2/Business/Projects/Daddys Day Giveaway/daddysda_giveaway.sql
/Volumes/Odinns 2TB/Business/Projects/Daddys Day Giveaway/daddysda_giveaway.sql
/Volumes/DATA/Business/Projects/Daddys Day Giveaway/daddysda_giveaway.sql
/Volumes/OS/Users/Odinn/Business/Projects/Daddys Day Giveaway/daddysda_giveaway.sql
/Volumes/LIMA-2/Business/Projects/Daddys Birthday/backup-20080219-0208/daddysbi_giveaway.sql.zip
/Volumes/LIMA-2/Business/Projects/Daddys Birthday/giveaway40/daddysbi_giveaway40-20090303.sql
/Volumes/LIMA-2/Business/Projects/Daddys Birthday/giveaway40/daddysbi_giveaway40.sql
/Volumes/LIMA-2/Business/Projects/Daddys Birthday/giveaway40/odinnsorensen-giveaway.sql
```

If a task calls Daddy's Birthday Giveaway a WordPress proof case, correct it. It is the example that proves Gjallr must be broader than WordPress.

## Dossier Format

Return a dossier with these sections:

```markdown
# Rescue Dossier: <site/project name>

## Verdict
- Likely site type:
- Confidence:
- Best rescue target:
- Main uncertainty:

## Evidence
- Candidate source roots:
- Candidate SQL dumps:
- Matching signals:
- Duplicate copies:
- Wayback or screenshot evidence:

## Schema Notes
- Tables:
- Prefixes:
- Content-like data:
- User/member data:
- Media/file data:
- Settings/config matches:

## Rescue Plan
- Immediate next command or inspection:
- Importer or custom mapping needed:
- Laravel runtime shape:
- Frontend rebuild inputs for AI:
- Risks:

## Do Not Do
- Do not assume:
- Do not import:
- Do not rebuild:
```

Keep it concrete. A useful dossier beats a confident fairy tale.
