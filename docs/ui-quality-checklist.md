# WebGuard UI quality checklist

Apply this checklist to every user-facing change in navigation, dashboards,
monitoring list/detail pages, monitoring forms, status pages, and incident workbenches.

## Structure and interaction

- [ ] The primary task and next action are visible in the first viewport.
- [ ] Heading levels describe the page hierarchy and do not skip levels.
- [ ] Every interactive control has a visible label or accessible name.
- [ ] Disclosure controls are keyboard accessible and expose their expanded state.
- [ ] Keyboard focus is visible, follows a logical order, and never becomes trapped.
- [ ] Destructive actions are separated from primary actions and require confirmation.

## States and semantics

- [ ] Loading, empty, validation-error, and request-error states are present where async or conditional content exists.
- [ ] Status is communicated with text or an accessible label in addition to color.
- [ ] External links identify their destination and use safe target behavior.
- [ ] Internal/private content is never rendered in public pages or public API responses.
- [ ] English and German copy remain understandable with longer translated labels.

## Responsive and visual quality

- [ ] The page has no horizontal overflow at 320px, 375px, and 390px widths.
- [ ] Primary controls remain touch-friendly and wrap without overlapping.
- [ ] Dark mode preserves readable contrast, focus visibility, and status meaning.
- [ ] Spacing, button hierarchy, badges, and disclosure patterns reuse existing components.
- [ ] Reduced-motion preferences do not make the workflow unusable.

## Required smoke checks

For affected surfaces, run the relevant browser checks at 1280px and 390px:

- navigate to the page and verify the primary action;
- tab through navigation, filters/forms, disclosures, and the primary action;
- verify no console errors and no horizontal overflow;
- repeat the visible-content check in English and German;
- verify public status pages do not expose internal incident metadata.

Record any skipped check and create a follow-up issue for unresolved accessibility,
responsive, contrast, localization, or console regressions.
