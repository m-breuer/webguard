# Shared Agent Skills

Read the applicable `AGENTS.md` first. Identify relevant skills, read only those skills, and keep `AGENTS.md` authoritative. If instructions conflict, report the conflict and apply the stricter or safer rule.

| Skill | Purpose | Use when | File |
| ----- | ------- | -------- | ---- |
| Implement Feature | Add new repository behavior | A task adds user-facing or API behavior | `implement-feature/SKILL.md` |
| Fix Bug | Repair incorrect behavior | A defect, regression, or failing test must be fixed | `fix-bug/SKILL.md` |
| Write Tests | Add or improve coverage | Tests are the primary task or coverage is missing | `write-tests/SKILL.md` |
| Refactor Code | Improve structure without behavior changes | Existing code needs simplification or reshaping | `refactor-code/SKILL.md` |
| Review Code | Review a change set | The task asks for review or risk assessment | `review-code/SKILL.md` |
| Update Dependencies | Change Composer or Bun dependencies | Dependencies or lockfiles are intentionally updated | `update-dependencies/SKILL.md` |
| Database Change | Change schema, migrations, models, or factories | Persistence behavior changes | `database-change/SKILL.md` |
| API Change | Change REST or internal instance contracts | API routes, controllers, requests, resources, or auth change | `api-change/SKILL.md` |
| Frontend Change | Change Blade, TypeScript, CSS, or assets | UI, frontend behavior, localization, or build inputs change | `frontend-change/SKILL.md` |
| Security Review | Assess security risk | The task asks for a security review or hardening | `security-review/SKILL.md` |
| Documentation Change | Update repository docs | Setup, usage, testing, deployment, or behavior docs change | `documentation-change/SKILL.md` |
| CI/CD Change | Change GitHub workflows or release automation | CI, tag, dependency update, or workflow files change | `ci-cd-change/SKILL.md` |
| Docker Change | Change container build or compose runtime | Dockerfile, compose, scripts, healthchecks, or container env change | `docker-change/SKILL.md` |
