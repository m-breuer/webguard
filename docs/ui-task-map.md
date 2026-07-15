# UI task map and baseline

This baseline records repository evidence after the first #441 implementation
wave. It separates observed implementation facts from usability hypotheses that
still need validation with representative operators.

## Evidence boundary

Observed evidence comes from the current routes, Blade structure, feature tests,
browser-test coverage, and the implemented navigation, dashboard, monitoring,
form, and incident-workbench changes. No analytics or user tracking was added.
The task times below are therefore interaction baselines, not measured user-study
times; they are targets for a later moderated or scripted usability pass.

## Primary task map

| Task | Entry point | Meaningful first action | Current interaction baseline | Main friction / hypothesis |
| --- | --- | --- | --- | --- |
| Find service status | `/dashboard` | Open an attention item or monitor | 1 route load + 1 primary action | Operators may still need account-wide rather than paginated status counts. |
| Identify failure impact | `/monitorings` → Needs attention | Select the attention preset | 1 route load + 1 preset click | Failure impact may require regional context from the detail page. |
| Open/update incident | `/status-pages/{statusPage}` | Expand the incident workbench or public update area | 1 route load + 1 disclosure + 1 action | Public and internal audiences need different wording and ownership cues. |
| Schedule maintenance | `/maintenances` | Choose monitoring/group and time window | 1 route load + form completion | Maintenance scope and resulting monitor state should be previewed before save. |
| Create HTTP monitor | `/monitorings/create` | Enter type, name, target, then expand advanced settings only if needed | 1 route load + 3 essential fields | HTTP defaults and expected status semantics need user validation. |
| Share public status page | `/status-pages` → public link | Copy/open the public URL | 1 route load + 1 link action | Public/private labels must remain obvious on narrow screens. |

The first five tasks have desktop route and markup coverage in feature tests;
the mobile baseline is currently a responsive acceptance target at 390px, not a
claim of observed participant behavior.

## Ranked usability risks

1. **Account-wide health visibility** — the monitoring summary is currently scoped
   to the paginated result page; operators may misread it as an account total.
2. **Incident audience separation** — public updates and internal response tools
   are now visually separated, but the distinction should be validated with
   responders and communicators.
3. **Form defaults and terminology** — HTTP timeout, expected status ranges, and
   failure confirmation need plain-language validation with first-time users.
4. **Maintenance scope** — group maintenance can affect multiple monitors and
   deserves a clear before-save summary.
5. **Mobile action discoverability** — primary actions and filter disclosures
   should be verified at 320px–390px widths with keyboard and touch input.

## Terminology recommendations

| Concept | English recommendation | German recommendation | Reason |
| --- | --- | --- | --- |
| Monitoring overview | Dashboard / Monitorings | Übersicht / Überwachungen | Separate the operational overview from the resource list. |
| Needs attention | Needs attention | Aufmerksamkeit nötig | Describes an action, not an implementation status. |
| Maintenance | In maintenance | In Wartung | Makes the current state explicit. |
| Incident workbench | Internal response workbench | Interner Response-Arbeitsbereich | Distinguishes private response work from public updates. |
| Advanced settings | Advanced settings | Erweiterte Einstellungen | Signals progressive disclosure without hiding capability. |

## Recommended next validation

Run a five- to six-task moderated or scripted usability pass with at least one
desktop and one mobile participant profile. Record task completion, wrong turns,
time to first meaningful action, terminology feedback, and whether the user
understands the public/private boundary. Compare results against this map before
adding account-wide aggregation or another navigation layer.
