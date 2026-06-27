# Skill: API Change

## Purpose

Change REST, mobile, public, or internal instance API behavior safely.

## When to Use

Use for API routes, controllers, requests, resources, tokens, response payloads, internal instance compatibility, or public badge/card endpoints.

## When Not to Use

Do not use for purely server-rendered Blade changes with no API contract impact.

## Required Context

Read relevant routes, controllers, requests, resources/data payloads, auth middleware, tests, and Scribe-related docs if generation is part of the task.

## Relevant Project Areas

`routes/api.php`, `routes/api/`, `app/Http/Controllers/Api`, `app/Http/Requests/Api`, `app/Http/Resources`, `app/Data`, `tests/Feature/Api`.

## Procedure

1. Identify whether the endpoint is public, authenticated, mobile, admin, or internal instance-facing.
2. Preserve stable response shapes unless a breaking change is explicit.
3. Validate input and enforce ownership, roles, tokens, or instance authentication.
4. Keep internal instance APIs compatible with `webguard-instance`.
5. Add feature tests for auth, validation, success, failure, and data isolation.

## Validation

Run targeted API feature tests and static analysis for shared payload changes.

## Expected Output

Report contract changes, tests, and compatibility risks.

## Constraints

Do not leak private monitoring, team, token, or notification data through public endpoints.

## Completion Criteria

The API behavior is validated, authorized, documented when required, and compatibility risks are stated.
