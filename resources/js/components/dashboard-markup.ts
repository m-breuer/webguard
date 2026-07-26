/**
 * Shared chrome for dashboard sections rendered client-side, mirroring the
 * classes emitted by `x-dashboard.panel`, `x-dashboard.action-link`, and
 * `x-heading` so client-rendered panels match server-rendered ones elsewhere
 * in the app (e.g. `resources/views/monitorings/show.blade.php`).
 */

export const TEXT_HEADING = 'text-gray-900 dark:text-gray-100';

export const PANEL_SHELL = 'overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800';

export const PANEL_HEADER_BORDER = 'border-b border-gray-200 px-5 py-4 dark:border-gray-700 sm:px-6';

export const PANEL_HEADER = `flex items-center justify-between gap-4 ${PANEL_HEADER_BORDER}`;

export const PANEL_TITLE = `text-lg font-bold ${TEXT_HEADING}`;

export const PANEL_BODY_DIVIDED = 'divide-y divide-gray-100 dark:divide-gray-700';

export const PANEL_ROW = 'flex items-center justify-between gap-3 p-5 text-sm transition hover:bg-purple-50 dark:hover:bg-purple-950/20';

export const PANEL_EMPTY = 'p-5 text-sm text-gray-500 dark:text-gray-400';

export const ACTION_SOLID = 'inline-flex items-center justify-center gap-2 rounded-2xl bg-purple-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-purple-700 focus:outline-hidden focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 dark:hover:bg-purple-500';

export function chevronIcon(className = 'h-4 w-4 shrink-0'): string {
    return `<svg class="${className}" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 0 1 .02-1.06L10.94 10 7.23 6.29a.75.75 0 1 1 1.06-1.06l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 0 1-1.06-.02Z" clip-rule="evenodd" /></svg>`;
}

export function escapeHtml(value: string | number): string {
    return String(value).replace(/[&<>'"]/g, (character) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        "'": '&#039;',
        '"': '&quot;',
    })[character] ?? character);
}

export function escapeAttribute(value: string): string {
    return escapeHtml(value);
}
