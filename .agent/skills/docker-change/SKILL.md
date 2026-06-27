# Skill: Docker Change

## Purpose

Change container build, compose, local development, or runtime behavior safely.

## When to Use

Use for `Dockerfile`, `docker-compose.yml`, `docker-compose.override.yml`, `docker/`, or `start-dev.sh` changes.

## When Not to Use

Do not use for application-only behavior with no container impact.

## Required Context

Read Docker files, compose services, environment variables, docs/installation.md, and relevant healthcheck or entrypoint scripts.

## Relevant Project Areas

`Dockerfile`, `docker-compose.yml`, `docker-compose.override.yml`, `docker/`, `start-dev.sh`, `docs/installation.md`.

## Procedure

1. Preserve production and local-development separation between compose files.
2. Keep secrets in environment variables, not images or docs examples with real values.
3. Maintain PHP, worker, node, MySQL, Redis, Traefik, and Mailpit service expectations.
4. Update docs when setup or operations change.
5. Prefer project-defined compose commands for validation.

## Validation

Run the narrowest relevant Docker Compose config, build, startup, migration, healthcheck, test, or frontend build command.

## Expected Output

Report container behavior changed, commands run, and any environment assumptions.

## Constraints

Do not rely on host-global tools when Docker can perform the check.

## Completion Criteria

Container configuration is consistent, documented when needed, and validated or skipped with stated risk.
