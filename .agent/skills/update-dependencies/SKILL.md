# Skill: Update Dependencies

## Purpose

Change Composer or Bun dependencies safely.

## When to Use

Use when `composer.json`, `composer.lock`, `package.json`, or `bun.lock` intentionally changes.

## When Not to Use

Do not use for code-only changes that do not alter dependencies.

## Required Context

Read the dependency request, manifests, lockfile impact, and relevant CI workflow expectations.

## Relevant Project Areas

`composer.json`, `composer.lock`, `package.json`, `bun.lock`, `.github/workflows/`, `Dockerfile`, `docker/node/Dockerfile`.

## Procedure

1. Confirm the package manager and intended update scope.
2. Avoid unrelated upgrades and unrequested major versions.
3. Review security, maintenance, license, size, and overlap with existing packages.
4. Update code or config for changed APIs when needed.
5. Review lockfile changes.

## Validation

Run install/update commands in Docker where possible, then relevant tests and `bun run build` for frontend dependencies.

## Expected Output

Report packages changed, lockfiles changed, validations, and risk.

## Constraints

Do not change lockfiles without a dependency operation or dependency-related reason.

## Completion Criteria

Dependencies are updated intentionally, lockfiles are consistent, and relevant validation passes.
