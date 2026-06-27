# Skill: Frontend Change

## Purpose

Change Blade UI, TypeScript behavior, CSS, assets, or frontend build inputs.

## When to Use

Use for pages, components, layouts, translations, Alpine/TypeScript components, Tailwind classes, Chart.js usage, or Vite assets.

## When Not to Use

Do not use for backend-only behavior with no rendered or asset impact.

## Required Context

Read relevant Blade views, components, translations, TypeScript files, styles, tests, and existing design patterns.

## Relevant Project Areas

`resources/views`, `resources/js`, `resources/css`, `resources/lang`, `resources/images`, `tests/Feature`, `tests/Browser`.

## Procedure

1. Reuse existing Blade components and TypeScript utilities.
2. Keep UI text localized where surrounding code uses translations.
3. Preserve accessibility, responsive behavior, theme behavior, and validation errors.
4. Keep interactive code typed and scoped to clear DOM ownership.
5. Add feature or browser tests for rendered or interactive behavior.

## Validation

Run `bun run build` for asset changes and targeted feature/browser tests for UI behavior.

## Expected Output

Report UI behavior changed, files, build/test validation, and residual visual risks.

## Constraints

Do not add unnecessary frontend dependencies or bundle weight.

## Completion Criteria

The UI change renders correctly, builds successfully, and relevant behavior is tested.
