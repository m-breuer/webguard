# Skill: Fix Bug

## Purpose

Repair incorrect WebGuard Core behavior without broad rewrites.

## When to Use

Use when a failing test, reported regression, exception, incorrect response, UI defect, queue issue, or data bug must be fixed.

## When Not to Use

Do not use when the task is a planned feature, pure refactor, or dependency update.

## Required Context

Read the failing output or report, the smallest relevant implementation path, related tests, and recent patterns in nearby code.

## Relevant Project Areas

Usually `app/`, `routes/`, `resources/`, `database/`, and `tests/`.

## Procedure

1. Reproduce or reason from the failing observable behavior.
2. Identify the narrow cause and avoid unrelated cleanup.
3. Add a regression test where feasible.
4. Fix the cause at the appropriate layer.
5. Check for authorization, privacy, and compatibility side effects.

## Validation

Run the regression test or smallest relevant Pest file. Run broader checks when the fix touches shared logic.

## Expected Output

State the defect fixed, tests run, and any remaining risk.

## Constraints

Do not remove or weaken tests, validation, authorization, or user-visible guarantees to make the bug disappear.

## Completion Criteria

The reported behavior is fixed, regression coverage exists or an exception is explained, and relevant validation passes.
