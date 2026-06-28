# Skill: Implement Feature

## Purpose

Add new WebGuard Core behavior with minimal, tested, reviewable changes.

## When to Use

Use when adding a new dashboard, admin, public page, API, monitoring, notification, team, package, or legal/content behavior.

## When Not to Use

Do not use for pure bug fixes, dependency updates, security-only reviews, or documentation-only work.

## Required Context

Read the task, relevant routes/controllers/services/views/tests, related docs, and any narrower skill such as API Change, Frontend Change, Database Change, or Write Tests.

## Relevant Project Areas

`app/`, `routes/`, `resources/`, `database/`, `tests/`, `docs/`.

## Procedure

1. Identify the smallest existing pattern that matches the feature.
2. Place business logic in services, requests, jobs, commands, support classes, enums, or data classes rather than controllers or views.
3. Preserve authorization, ownership, localization, queue, and notification patterns.
4. Update tests and docs where behavior or setup changes.

## Validation

Run targeted Pest tests first. Add `composer analyse`, full `composer test`, or `bun run build` when the change touches shared backend, API, or frontend behavior.

## Expected Output

Report changed behavior, files, validations, and remaining risks only.

## Constraints

Do not implement `webguard-instance` scanning-node behavior in this repository unless changing the core contract.

## Completion Criteria

The feature is implemented, covered by relevant tests, documented when needed, and validated with the narrowest sufficient checks.
