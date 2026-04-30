# Phase N: [Name]

## Summary

State the phase in one short paragraph:

- what gets built
- what it depends on
- what it explicitly does not try to solve yet

If this phase starts only after another one is complete, say so plainly.

Example:

> Build the first import slice for posts and pages after the Laravel scaffold exists. This phase is limited to bounded WordPress ingestion, clean domain mapping, and read-only rendering handoff. It does not include menus, comments, media migration, or editing UI.

## Focus

- name the 3-6 things this phase is actually about
- phrase them as outcomes, not vague themes
- keep this section narrow enough that obvious non-goals stand out

Example:

- bounded WordPress intake
- canonical content mapping
- URL preservation rules
- additive reruns
- read-only rendering handoff

## Implementation Sequence

Write the intended order. This keeps the phase from becoming a junk drawer.

1. Verify the relevant spec against real input data before coding if the source shape is uncertain.
2. Lock the minimal input and output contract for this phase.
3. Build storage and domain mapping only for the accepted slice.
4. Add CLI or orchestration entry points.
5. Emit the downstream handoff from canonical/domain data only.
6. Cover happy path, reruns, malformed input, and boundary failures with tests.

## Acceptance Scenarios

- happy path on real or representative data
- additive, non-destructive rerun behavior
- malformed or incomplete input fails soft and logs clearly
- deferred datasets stay deferred even when present
- downstream handoff is stable and deterministic

Make these concrete. If it cannot be tested, it is probably still fog.

## Out Of Scope

List the things nearby that this phase must not quietly absorb.

- later-phase datasets
- UI polish unrelated to the phase goal
- speculative abstractions
- cross-boundary leakage

## Gate To Next Phase

Define the condition that must be true before moving on.

Example:

> The next phase starts only after this phase emits stable handoffs, passes the quality gates, and has no unresolved boundary drift.

## Specifications Used

List only the specs needed for this phase. Do not reference the whole cathedral.

- `specification.md`
- other phase-specific contracts or source-shape notes

## Assumptions

- call out blocked inputs, provisional specs, or real-data verification needs
- state any allowed holdouts explicitly
- note what remains intentionally deferred

## Phase Checklist

Before starting:

- branch from `main`
- reread the relevant spec slice
- define tests first or lock expected behavior before implementation

Before calling the phase done:

- simplify the changed code
- run `./vendor/bin/pint`
- run `./vendor/bin/rector process`
- run `./vendor/bin/phpstan analyse`
- run `./vendor/bin/pest`
- review for ingestion/transformation/domain boundary drift
- commit the phase as a single-purpose change
