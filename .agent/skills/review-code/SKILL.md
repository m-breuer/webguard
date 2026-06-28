# Skill: Review Code

## Purpose

Assess a change set for defects, regressions, security risks, and missing tests.

## When to Use

Use when asked to review code, a diff, a pull request, or local changes.

## When Not to Use

Do not use as the primary workflow for implementing new behavior unless the task also requests fixes.

## Required Context

Read the diff, touched files, relevant tests, and applicable architecture or docs.

## Relevant Project Areas

Changed files and their directly related callers, tests, routes, views, migrations, and configs.

## Procedure

1. Identify behavior, security, data, compatibility, and validation risks.
2. Prioritize concrete findings by severity.
3. Include file and line references.
4. Separate findings from questions and summaries.

## Validation

Run targeted checks only if the review asks for validation or local execution is needed to confirm a finding.

## Expected Output

Findings first, then open questions or assumptions, then a brief summary only if useful.

## Constraints

Do not report style preferences unless they cause maintainability, correctness, or security risk.

## Completion Criteria

The review identifies actionable issues or states clearly that none were found, with test gaps or residual risk noted.
