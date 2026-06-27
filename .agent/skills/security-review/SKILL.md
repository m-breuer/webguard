# Skill: Security Review

## Purpose

Assess or improve security posture for WebGuard Core changes.

## When to Use

Use when the task asks for security review, hardening, auth checks, secret handling, privacy, webhook safety, or API exposure analysis.

## When Not to Use

Do not use for ordinary feature work unless security is the main concern; apply `AGENTS.md` security rules regardless.

## Required Context

Read the changed code, trust boundaries, routes, middleware, requests, policies or ownership services, config, and tests.

## Relevant Project Areas

`app/Http`, `app/Services`, `app/Rules`, `app/Support`, `config`, `routes`, `tests/Feature`, `resources/views`.

## Procedure

1. Trace input, authorization, persistence, output, logging, and external calls.
2. Check authentication, authorization, ownership, CSRF, signed URL, Sanctum, and role enforcement.
3. Check injection risks, escaping, sensitive logging, token exposure, and SSRF-like URL handling.
4. Add or recommend tests for confirmed risks.

## Validation

Run targeted security-relevant feature or unit tests and static analysis where useful.

## Expected Output

Report confirmed findings with impact, affected files, and remediation or validation.

## Constraints

Do not disclose secrets or create proof-of-concept payloads that risk external systems.

## Completion Criteria

Material security risks are identified or mitigated, tests cover changed protections, and residual risk is stated.
