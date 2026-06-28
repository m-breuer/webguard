# Skill: CI/CD Change

## Purpose

Change GitHub Actions workflows or release/dependency automation safely.

## When to Use

Use for `.github/workflows`, `.github/scripts`, CI checks, tag/release automation, or dependency update workflows.

## When Not to Use

Do not use for application code changes unless CI configuration also changes.

## Required Context

Read the target workflow, related scripts, package managers, Docker assumptions, and validation commands.

## Relevant Project Areas

`.github/workflows/ci.yml`, `.github/workflows/tag.yml`, `.github/workflows/weekly-dependency-update.yml`, `.github/scripts/`, `composer.json`, `package.json`.

## Procedure

1. Keep workflow changes minimal and scoped.
2. Preserve required CI coverage: frontend build, PHPStan, and Pest.
3. Keep permissions least-privilege.
4. Avoid exposing secrets in logs.
5. Validate YAML and script syntax where possible.

## Validation

Run local equivalents for changed commands when possible, such as `composer analyse`, `composer test`, or `bun run build`.

## Expected Output

Report workflow changes, local equivalents run, and any checks only CI can validate.

## Constraints

Do not change release, tag, secret, or deployment behavior without explicit task need.

## Completion Criteria

Workflow changes are syntactically valid, least-privilege, and preserve repository quality gates.
