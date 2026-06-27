# Skill: Write Tests

## Purpose

Add focused Pest coverage for WebGuard Core behavior.

## When to Use

Use when tests are requested directly or implementation changes need coverage.

## When Not to Use

Do not use for documentation-only changes unless documentation structure or links are tested.

## Required Context

Read `docs/test-concept.md`, `tests/Pest.php`, `tests/TestCase.php`, relevant factories, and nearby tests for the same feature area.

## Relevant Project Areas

`tests/Unit`, `tests/Feature`, `tests/Browser`, `database/factories`, `app/`, `routes/`, `resources/`.

## Procedure

1. Choose Unit, Feature, or Browser based on observable risk.
2. Use factories, fakes, named routes, and explicit assertions.
3. Cover authorization, validation, isolation, edge cases, and failure paths where relevant.
4. Keep test data minimal and deterministic.

## Validation

Run the targeted Pest file or filter inside Docker when possible.

## Expected Output

Report coverage added and validation result.

## Constraints

Avoid external network access, order-dependent tests, broad fixtures, and implementation-only assertions.

## Completion Criteria

Tests fail for the intended broken behavior where applicable, pass after implementation, and do not weaken existing coverage.
