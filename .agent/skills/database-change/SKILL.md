# Skill: Database Change

## Purpose

Change schema, persistence models, factories, or seed data safely.

## When to Use

Use for migrations, model casts/relationships, factories, seeders, database indexes, or persistence behavior.

## When Not to Use

Do not use for read-only service changes that do not affect stored data.

## Required Context

Read relevant migrations, models, factories, tests, and any MySQL compatibility tests.

## Relevant Project Areas

`database/migrations`, `database/factories`, `database/seeders`, `app/Models`, `app/Services`, `tests/Feature`.

## Procedure

1. Add new migrations rather than editing shared migrations.
2. Preserve MySQL compatibility and note SQLite test limitations.
3. Update models, casts, factories, validation, and API responses together.
4. Use constraints, indexes, and defaults deliberately.
5. Add migration or feature tests for meaningful behavior.

## Validation

Run targeted tests and `php artisan migrate`. Use MySQL-backed validation when SQLite cannot represent the risk.

## Expected Output

Report schema/data changes, tests, migrations run, and compatibility concerns.

## Constraints

Do not use production data in seeds, tests, logs, or fixtures.

## Completion Criteria

Schema and model behavior are consistent, migration path is valid, and relevant tests pass.
