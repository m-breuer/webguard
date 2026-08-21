# SvelteKit component system

The SvelteKit workspace owns reusable UI primitives under
`frontend/src/lib/components`. Feature routes compose those components rather
than recreating controls, dialog behavior, or mutation state.

## Tokens and appearance

`frontend/src/app.css` imports Tailwind CSS and maps the shared color, focus,
surface, and motion tokens into Tailwind utilities. New and migrated Svelte
components use those utilities instead of local style blocks or inline styles.
The tokens mirror the established WebGuard purple navigation and light/dark
surface contrast without depending on the Laravel Vite pipeline. Appearance is
applied in `app.html` before SvelteKit hydrates, then persisted by
`AppearanceSelector.svelte` through `PATCH /api/v1/internal/ui/appearance`.

## Primitive contracts

| Component | Variants and behavior | Accessibility contract |
| --- | --- | --- |
| `Button` | `primary`, `secondary`, `danger`, `quiet`; disabled loading state | Native button semantics, `aria-busy`, visible focus |
| `Field` | Label, optional hint, required marker, and error | Associated label and alert error text |
| `Dialog` | Bindable open state, title, description, backdrop close | Native modal dialog focus trap, Escape close, focus restoration |
| `MutationForm` | One in-flight request, JSON or multipart request body, field-error response | Blocks duplicate submits, announces success/error state |
| `AppearanceSelector` | Light, dark, system selection with optimistic persistence | Pressed state per option, disabled while saving, error alert |
| `AppShell` | Responsive sidebar with Operations, Collaboration, and admin-only Administration sections | Semantic navigation, collapse persistence, mobile navigation control |
| `NavIcon` | Project-owned navigation icon set | Decorative SVGs are hidden from assistive technology |
| `Card` and `DataTable` | Responsive content containers and scrollable data tables | Semantic section/table structure and visually hidden captions |
| `StatusBadge` | Healthy, degraded, danger, neutral, and paused status tones | Text label never relies on color alone |
| `EmptyState` and `LoadingState` | Standard no-data and asynchronous feedback | Polite live-region feedback where appropriate |
| `Pagination` | Link-based page navigation | Current page and unavailable controls are exposed semantically |
| `ToastRegion` | Success, error, and informational feedback | Polite or assertive live announcements with dismiss controls |

All first-party mutations use `requestFirstPartyApi`. It obtains the existing
Sanctum CSRF cookie once, sends the XSRF token on unsafe requests, and exposes
structured Laravel validation errors through `FirstPartyApiError`.

`frontend/src/lib/routes.ts` is the single source of browser route helpers.
`publicStatusRoute(identifier)` always produces `/status/{identifier}`; `/status`
itself remains Laravel's health endpoint.

`AppShell` keeps Administration outside the profile menu and renders it only for
users with the `admin` role. Appearance is available in the persistent sidebar:
the compact shell exposes it through a labelled settings control rather than
removing it when the sidebar collapses.

## Authenticated route boundary

Routes placed below `frontend/src/routes/(app)` load the Laravel-owned
first-party session before rendering `AppShell`. An unauthenticated response
redirects to Laravel's `/login`; other failed responses render the route-group
error boundary. The shell ends a session through the first-party JSON contract
and then performs a full navigation to `/login`, so Laravel remains the owner
of authentication transitions during the migration.

## Development preview

`/_ui` is a development-only component preview. It returns `404` in production
and provides a manual keyboard and responsive check for the primitive baseline.
