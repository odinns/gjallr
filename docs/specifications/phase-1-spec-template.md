# Phase 1 Spec Template

Use this for phase-1 implementation specs that define a bounded import or ingestion slice. This is derived from the recurring structure in Nornir's phase-1 specifications, not its plan files.

## Core spec shape

Write the implementation spec with these sections, in this order:

### 1. Goal
One paragraph. Say exactly what this phase-1 slice imports or builds, and what kind of output it produces.

### 2. Canonical Source
Name the only accepted source surface for this phase.

Examples:
- approved dump root
- specific export archive structure
- database connection plus explicit table allowlist

### 3. Inputs
List required inputs and supported flags.

Examples:
- source path
- database connection
- `--dry-run`
- `--validate-only`

If phase 1 only supports a subset of the source, name the required and optional inputs explicitly.

### 4. Output Structure
Define what the phase writes.

Examples:
- canonical application tables
- run artifacts under a source-specific directory
- downstream handoff payloads

### 5. Data Model
List the entities and fields phase 1 must preserve. Keep it to the accepted slice only.

Important rule:
- store only what phase 1 genuinely supports
- do not invent junk-drawer tables for unrelated source categories

### 6. Import Rules
Spell out the traversal and transformation rules.

Typical rules:
- allowlist-first import
- file-specific or table-specific extractors
- no generic “ingest everything” pass
- preserve original identifiers and timestamps
- keep binaries reference-only unless the phase explicitly owns copying

### 7. Incremental Behavior
Define rerun semantics.

Minimum bar:
- idempotent upserts by stable source identity
- later incomplete sources do not imply deletion
- reruns may enrich metadata without destroying earlier valid history

### 8. Validation
List what must be checked before and during import.

Examples:
- required source shape
- required file or table presence
- optional surfaces allowed to be absent
- timestamp normalization without timezone drift
- broken references reported clearly

### 9. Downstream Handoff
Define what later phases may consume from this phase, and from where.

Rule:
- downstream handoff must come from canonical rows only, never from raw rescans

### 10. Forbidden Behavior
This is the part that saves the repo from “just one more thing.”

Examples:
- no scope widening because the source contains more data
- no cross-boundary leakage into runtime/domain concepts
- no speculative abstraction
- no binary copying if the phase is reference-only

### 11. Review Checklist
List the things reviewers must verify are still true.

Examples:
- accepted phase-1 surfaces are named explicitly
- deferred surfaces are named explicitly
- rerun behavior does not depend on source completeness
- source-specific logic stays source-specific

### 12. Acceptance Checks
End with concrete proof that the spec is implementable without policy invention.

Examples:
- the importer can be built from this spec without guessing source rules
- accepted phase-1 data is queryable from canonical storage
- deferred datasets do not accidentally leak into phase 1

## Companion source-navigation spec

If the source shape is messy, pair the implementation spec with a source-navigation spec using this shape:

1. `Start here`
2. `Canonical source`
3. `Source layout or access model`
4. `Important entities`
5. `Traversal rules`
6. `Safe access rules`
7. `Parser notes`
8. `Bottom line`

That split is useful:
- source-navigation says what the source really is
- implementation spec says what phase 1 is allowed to do with it

## Phase-1 bias

Phase 1 should be narrow, explicit, and a bit suspicious of ambition.

Good phase-1 specs:
- accept a small real slice
- name deferred surfaces plainly
- preserve source truth without dragging source semantics into the runtime model
- define reruns before anyone writes the command

Bad phase-1 specs:
- pretend everything in the source is in scope
- hide uncertainty behind “generic import”
- blur ingestion and runtime concerns
- leave deletion or rerun behavior unspecified
