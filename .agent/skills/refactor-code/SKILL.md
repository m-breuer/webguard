# Skill: Refactor Code

## Purpose

Improve structure while preserving WebGuard Core behavior.

## When to Use

Use when simplifying duplication, moving logic to established layers, or improving maintainability without changing outcomes.

## When Not to Use

Do not use when behavior change is required unless paired with Implement Feature or Fix Bug.

## Required Context

Read the code being changed, its tests, callers, routes, views, and public API surfaces.

## Relevant Project Areas

Any application area, especially `app/Services`, `app/Support`, `app/Http`, `resources/views`, and `resources/js`.

## Procedure

1. Define the preserved behavior before editing.
2. Prefer existing abstractions and naming patterns.
3. Keep moves and edits small enough for review.
4. Avoid formatting unrelated files.
5. Update tests only for changed structure or improved behavior assertions.

## Validation

Run existing targeted tests for the affected behavior and static analysis when PHP contracts change.

## Expected Output

Describe the structural change and validation.

## Constraints

Do not introduce new abstraction layers without concrete benefit.

## Completion Criteria

Behavior is unchanged, code is simpler or clearer, and relevant tests still pass.
