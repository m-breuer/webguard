# Skill: Documentation Change

## Purpose

Update repository documentation accurately and concisely.

## When to Use

Use for changes to setup, Docker operations, testing, deployment, APIs, public behavior, contribution workflow, or architecture docs.

## When Not to Use

Do not use for generated documentation unless the task explicitly includes generation.

## Required Context

Read the user-visible behavior or config being documented and the relevant existing docs.

## Relevant Project Areas

`README.md`, `docs/`, `AGENTS.md`, `.agent/skills/`, supported adapter files.

## Procedure

1. Update the narrowest relevant document.
2. Keep root `README.md` concise and link details in `docs/`.
3. Verify commands and paths exist before documenting them.
4. Avoid duplicating content across docs.
5. Keep language factual and repository-specific.

## Validation

Run documentation structure tests when links, required topics, or doc organization change.

## Expected Output

Report docs changed and any validation skipped.

## Constraints

Do not make unverified claims or document nonexistent commands.

## Completion Criteria

Documentation matches the implemented repository behavior and remains concise.
