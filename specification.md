# Gjallr — MVP Specification

## Motivation (Project Spine)

The web is full of abandoned, fragile, or compromised WordPress sites.

They still contain value:
- content
- history
- backlinks
- SEO weight
- personal or business meaning

But they are often trapped in:
- outdated themes
- plugin dependency chains
- brittle environments
- unmaintained PHP versions

And sometimes worse:

- hacked installations
- injected malware
- SEO spam
- hijacked redirects
- unknown or untrusted code execution

At that point, the question is no longer:
“how do I maintain this?”

It becomes:
“how do I get my site back?”

Rebuilding from scratch is expensive.  
Cleaning a compromised WordPress install is uncertain and time-consuming.  
Continuing to run it is often not acceptable.

Gjallr exists to create a third path:

> Extract the site, preserve what matters, and leave WordPress behind entirely.

Gjallr is a rescue tool.

It is designed for situations like:

- “my WordPress site got infected”
- “something hijacked my site”
- “I no longer trust this installation”
- “I just want my content back in a clean system”

Gjallr does not attempt to repair WordPress.  
It removes the dependency on it.

This project is not driven by building a business.

It is driven by a practical need:

> make something that works, especially for my own legacy sites.

If it works well for that, it works.

A second practical principle also matters:

> start with a strong Laravel foundation and include the best standard building blocks from day one.

Gjallr should not reinvent solved problems badly.  
Where mature Laravel or PHP packages already exist, prefer the best and most featureful option.

The goal is not to migrate a CMS.

The goal is to **rescue a site and give it a second life as a real Laravel application**.

---

## Purpose

Gjallr is a WordPress rescue platform.

It imports a legacy WordPress site and produces a clean, read-only Laravel application that:

- preserves content
- preserves URLs
- preserves visible comments
- provides a stable foundation for further development

Gjallr is not a CMS.  
Gjallr is not a WordPress replacement.  
Gjallr is a rescue and transition system.

---

## Core Principles

### 1. Do Not Replicate WordPress

Translate WordPress into a clean internal domain model.

---

### 2. Source Isolation Principle

WordPress is treated as a legacy ingestion source only.

It must not leak into the runtime Laravel application.

The final application must not contain:

- WordPress-specific naming
- WordPress table structures
- WordPress concepts or semantics
- any `wp_*` or `wordpress_*` artifacts

All WordPress-specific logic must live in an isolated ingestion boundary.

That boundary must be removable without affecting the runtime system.

The final application should behave as if WordPress never existed.

---

### 3. Prefer Mature Building Blocks

- use best-in-class Laravel/PHP packages where appropriate
- prefer Spatie ecosystem where it fits
- avoid reinventing solved problems
- do not add packages without clear value

---

### 4. Keep It Laravel-Native

- follow Laravel conventions
- avoid over-engineering
- avoid framework-within-a-framework patterns

---

## Build Approach

Gjallr must be built in phases.

Each phase begins with intent and constraints.

### At Phase Start

- apply TDD skill
- define expected behavior first
- write or outline tests before implementation

### During Phase

- apply simplify skill continuously
- keep implementations minimal
- avoid speculative abstraction
- apply reviewer-odinn skill for critical review

### Engineering Discipline

- Pint for formatting
- Rector for continuous improvement
- PHPStan for static analysis
- Pest for tests
- Pest browser testing for critical flows

---

## Non-Goals (MVP)

Do not implement:

- content editing UI
- comment writing
- user accounts or authentication
- plugin compatibility
- WooCommerce or e-commerce
- page builder fidelity
- full Gutenberg support
- multisite

---

## Target Input

Single-site WordPress installation.

Input:

- MySQL dump or connection

Assumptions:

- wp_posts, wp_terms, wp_comments etc.
- content includes HTML and legacy markup
- may contain shortcodes and broken structures

---

## System Architecture

Gjallr consists of three distinct zones:

### 1. Ingestion Zone (Disposable)

Purpose:

- read WordPress data
- understand WordPress structures

Allowed:

- wp_* table awareness
- WordPress-specific quirks

Constraint:

- must be fully removable

---

### 2. Transformation Zone (Boundary)

Purpose:

- translate WordPress data into domain model
- normalize content

Responsibilities:

- mapping
- cleanup
- structural decisions

---

### 3. Domain Zone (Permanent)

Purpose:

- represent the real application

Must contain:

- zero WordPress knowledge
- only clean domain concepts

---

## Domain Model (MVP)

### ContentItem

- id
- source_type
- title
- slug
- excerpt
- body_html
- status
- published_at
- original_source_id
- seo_title (nullable)
- seo_description (nullable)

---

### Taxonomy

- id
- type (category, tag)
- name
- slug

---

### ContentTaxonomy

- content_item_id
- taxonomy_id

---

### MediaAsset

- id
- path or url
- mime_type
- alt_text (nullable)

---

### Comment

- id
- content_item_id
- author_name
- author_email (optional)
- body
- created_at
- parent_id

---

### NavigationMenu

- id
- name

---

### NavigationItem

- id
- menu_id
- label
- url or content reference
- order
- parent_id

---

### RedirectRule

- from_path
- to_path

---

## Import Scope

Must import:

### Content
- posts
- pages
- titles, slugs, bodies, excerpts
- publish dates

### Taxonomy
- categories
- tags

### Media
- image references

### Comments
- full tree

### Navigation
- menus

### SEO
- best effort extraction

---

## Content Handling

### Preserve First

- keep original HTML
- keep structure intact

### Cleanup

- minimal fixes only
- no aggressive rewriting

---

## Routing

- preserve original URLs
- map slugs directly
- fallback to redirects when needed

---

## Rendering

Read-only frontend.

Must support:

- homepage
- content pages
- taxonomy listings
- navigation menus
- comment display

No:

- editing
- submission
- authentication

---

## Comments

- visible only
- threaded
- no new comments

---

## Media

- must render correctly
- preserve paths where possible

---

## Error Handling

Handle:

- missing media
- broken links
- unknown shortcodes

Strategy:

- fail soft
- log issues

---

## CLI

System is CLI-driven.

Capabilities:

- import
- analyze
- build

Must be:

- repeatable
- scriptable

---

## AI Readiness

System must:

- expose clean domain model
- separate content from presentation
- enable AI-driven reconstruction later

---

## Package Strategy

Use mature packages where appropriate.

Focus areas:

- sitemap
- redirects
- feeds
- data structures
- media (if needed)
- SEO helpers

Spatie is a primary candidate.

---

## Phased Delivery

### Phase 1 — Foundation
- Laravel setup
- tooling
- base structure

### Phase 2 — Core Import
- posts/pages
- domain mapping
- basic rendering

### Phase 3 — Routing + Navigation
- URLs
- menus
- redirects

### Phase 4 — Media + Comments
- images
- comments

### Phase 5 — Hardening
- edge cases
- browser tests
- cleanup

---

## Output

Gjallr produces:

- a clean Laravel application
- no WordPress dependency
- clear structure
- ready for further development

---

## Success Criteria

- site imports successfully
- content accessible
- URLs preserved
- images render
- comments visible
- navigation works
- no WordPress runtime dependency

---

## Reference Case

Daddys Birthday Giveaway (~2010)

Requirements:

- preserve content
- preserve tone
- preserve URLs
- preserve comments
- tolerate messy HTML

---

## Final Constraint

If a feature risks turning Gjallr into a CMS, reject it.

Gjallr is:

- a rescue system
- a transformation step
- a foundation

Not a destination.
