import type { DashboardProjection, DashboardResponse, DashboardService } from '../api/internal-ui-client';
import {
    ACTION_SOLID,
    chevronIcon,
    escapeAttribute,
    escapeHtml,
    PANEL_BODY_DIVIDED,
    PANEL_EMPTY,
    PANEL_HEADER,
    PANEL_HEADER_BORDER,
    PANEL_ROW,
    PANEL_SHELL,
    PANEL_TITLE,
    TEXT_HEADING,
} from './dashboard-markup';

export type DashboardCopy = {
    emptyTitle: string;
    emptyDescription: string;
    createMonitoring: string;
    serviceLandscape: string;
    signalRoomHeading: string;
    activeServices: string;
    searchPlaceholder: string;
    allFilter: string;
    attentionFilter: string;
    maintenanceFilter: string;
    pausedFilter: string;
    signalTab: string;
    checksTab: string;
    incidentsTab: string;
    historyTab: string;
    fullDetails: string;
    healthy: string;
    down: string;
    unknown: string;
    paused: string;
    maintenance: string;
    attention: string;
    incidents: string;
    notifications: string;
    noAttention: string;
    noMaintenance: string;
    recentIncidents: string;
    noIncidents: string;
    trend: string;
    noTrendData: string;
    previous: string;
    next: string;
    statusLabels: Record<string, string>;
};

export function renderDashboard(response: DashboardResponse, copy: DashboardCopy): string {
    const { data, meta } = response;

    if (data.summary.total === 0) {
        return emptyState(data, copy);
    }

    return `
        <div data-dashboard-content class="space-y-6">
            ${summary(data, copy)}
            ${services(data, meta, copy)}
            <div class="grid gap-6 xl:grid-cols-2">
                ${attention(data, copy)}
                ${maintenance(data, copy)}
                ${incidents(data, copy)}
                ${trend(data, copy)}
            </div>
            ${deliveryWarning(data, copy)}
        </div>
    `;
}

function emptyState(data: DashboardProjection, copy: DashboardCopy): string {
    return `
        <section data-dashboard-content class="overflow-hidden rounded-3xl border border-purple-200 bg-white shadow-sm dark:border-purple-900/60 dark:bg-gray-800">
            <div class="grid gap-8 px-6 py-10 sm:px-10 sm:py-14 lg:grid-cols-[1.1fr_0.9fr] lg:items-center">
                <div>
                    <div class="mb-6 flex h-14 w-14 items-center justify-center rounded-2xl bg-purple-100 text-3xl font-light text-purple-700 dark:bg-purple-950/50 dark:text-purple-300" aria-hidden="true">+</div>
                    <h2 class="text-2xl font-bold ${TEXT_HEADING}">${escapeHtml(copy.emptyTitle)}</h2>
                    <p class="mt-4 max-w-xl text-base leading-7 text-gray-600 dark:text-gray-300">${escapeHtml(copy.emptyDescription)}</p>
                    ${data.capabilities.can_create_monitoring ? actionLink('/monitorings/create', copy.createMonitoring, true) : ''}
                </div>
            </div>
        </section>
    `;
}

function summary(data: DashboardProjection, copy: DashboardCopy): string {
    const metrics: Array<[keyof DashboardProjection['summary'], string, string]> = [
        ['healthy', copy.healthy, 'text-emerald-600'],
        ['down', copy.down, 'text-red-600'],
        ['unknown', copy.unknown, 'text-amber-600'],
        ['paused', copy.paused, 'text-gray-600'],
        ['maintenance', copy.maintenance, 'text-purple-600'],
    ];
    const percentage = data.summary.total === 0 ? 0 : Math.round((data.summary.healthy / data.summary.total) * 100);

    return `
        <section class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-8" aria-labelledby="dashboard-health-heading">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.12em] text-gray-400 dark:text-gray-500">${escapeHtml(copy.healthy)} · ${percentage}%</p>
                    <h2 id="dashboard-health-heading" class="mt-2 text-2xl font-bold ${TEXT_HEADING}">${escapeHtml(stateLabel(data.overall_state, copy))}</h2>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">${data.summary.total} ${escapeHtml(copy.activeServices)}</p>
                </div>
                ${data.capabilities.can_create_monitoring ? actionLink('/monitorings/create', copy.createMonitoring, true) : ''}
            </div>
            <dl class="mt-7 grid grid-cols-2 gap-3 border-t border-gray-100 pt-5 sm:grid-cols-5 dark:border-gray-700">
                ${metrics.map(([key, label, tone]) => `
                    <div class="rounded-2xl bg-gray-50 p-3 dark:bg-gray-900/30">
                        <dt class="text-xs font-semibold text-gray-500 dark:text-gray-400">${escapeHtml(label)}</dt>
                        <dd class="mt-1 text-xl font-black ${tone}">${data.summary[key]}</dd>
                    </div>
                `).join('')}
            </dl>
        </section>
    `;
}

function services(data: DashboardProjection, meta: DashboardResponse['meta'], copy: DashboardCopy): string {
    const grouped = data.services.reduce<Record<string, DashboardService[]>>((groups, service) => {
        (groups[service.group] ??= []).push(service);

        return groups;
    }, {});

    return `
        <section id="dashboard-service-list" data-signal-room class="${PANEL_SHELL}" aria-labelledby="dashboard-services-heading">
            <div class="flex flex-col gap-4 ${PANEL_HEADER_BORDER} sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-[11px] font-black uppercase tracking-[0.18em] text-purple-600 dark:text-purple-300">${escapeHtml(copy.signalRoomHeading)}</p>
                    <h2 id="dashboard-services-heading" class="mt-1 text-xl font-black ${TEXT_HEADING}">${escapeHtml(copy.serviceLandscape)}</h2>
                    <p class="mt-1 text-xs font-semibold uppercase tracking-[0.12em] text-gray-400 dark:text-gray-500">${meta.service_pagination.total} ${escapeHtml(copy.activeServices)}</p>
                </div>
                <label class="relative block w-full sm:max-w-sm">
                    <span class="sr-only">${escapeHtml(copy.searchPlaceholder)}</span>
                    <input data-dashboard-service-search type="search" class="w-full rounded-xl border-gray-200 bg-white px-3 py-2.5 text-sm shadow-sm placeholder:text-gray-400 focus:border-purple-500 focus:ring-purple-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white" placeholder="${escapeAttribute(copy.searchPlaceholder)}">
                </label>
            </div>
            <div class="flex gap-2 overflow-x-auto px-5 py-4 sm:px-6" role="tablist" aria-label="${escapeAttribute(copy.serviceLandscape)}">
                ${filterButton('all', copy.allFilter)}
                ${filterButton('attention', copy.attentionFilter)}
                ${filterButton('maintenance', copy.maintenanceFilter)}
                ${filterButton('paused', copy.pausedFilter)}
            </div>
            <div data-dashboard-services class="${PANEL_BODY_DIVIDED}">
                ${Object.entries(grouped).sort(([first], [second]) => first.localeCompare(second)).map(([group, services]) => `
                    <div data-dashboard-service-group class="px-5 py-4 sm:px-6">
                        <p class="mb-2 text-[11px] font-black uppercase tracking-[0.18em] text-gray-400 dark:text-gray-500">${escapeHtml(group)}</p>
                        <div class="space-y-2">
                            ${services.map((service) => serviceRow(service, copy)).join('')}
                        </div>
                    </div>
                `).join('')}
            </div>
            ${pagination(meta.service_pagination, copy)}
            <aside class="hidden lg:block border-t border-gray-200 dark:border-gray-700"><div data-signal-detail class="p-5 text-sm text-gray-500 dark:text-gray-400">${escapeHtml(copy.searchPlaceholder)}</div></aside>
            <div data-signal-mobile-sheet class="lg:hidden" hidden><div data-signal-mobile-detail class="fixed inset-x-0 bottom-0 z-50 max-h-[88vh] overflow-y-auto rounded-t-3xl border border-gray-200 bg-white shadow-2xl dark:border-gray-700 dark:bg-gray-800"></div></div>
        </section>
    `;
}

function serviceRow(service: DashboardService, copy: DashboardCopy): string {
    const tone = service.status === 'down' ? 'bg-red-500' : service.status === 'unknown' ? 'bg-amber-500' : service.status === 'maintenance' ? 'bg-purple-500' : service.status === 'paused' ? 'bg-gray-400' : 'bg-emerald-500';

    return `
        <button type="button" data-dashboard-service-row data-signal-service="${escapeAttribute(service.id)}" data-dashboard-service-status="${escapeAttribute(service.status)}" class="flex w-full items-center gap-3 rounded-2xl border border-gray-200 px-3.5 py-3 text-start transition hover:border-purple-200 hover:bg-purple-50/50 dark:border-gray-700 dark:hover:border-purple-900 dark:hover:bg-purple-950/20">
            <span class="h-2.5 w-2.5 shrink-0 rounded-full ${tone}" aria-hidden="true"></span>
            <span class="min-w-0 flex-1">
                <span data-dashboard-service-name class="block truncate text-sm font-extrabold ${TEXT_HEADING}">${escapeHtml(service.name)}</span>
                <span class="mt-1 block truncate text-xs text-gray-500 dark:text-gray-400">${escapeHtml(service.target)}</span>
            </span>
            <span class="shrink-0 text-end">
                <span class="block text-xs font-bold text-gray-700 dark:text-gray-200">${escapeHtml(copy.statusLabels[service.status] ?? service.status)}</span>
                <span class="mt-1 block text-[11px] text-gray-400 dark:text-gray-500">${escapeHtml(relativeTime(service.last_checked_at))}</span>
            </span>
        </button>
    `;
}

function attention(data: DashboardProjection, copy: DashboardCopy): string {
    const content = data.attention.length === 0
        ? emptyMessage(copy.noAttention)
        : `<div class="${PANEL_BODY_DIVIDED}">${data.attention.slice(0, 5).map((item) => {
            const href = item.type === 'delivery' ? '/notifications' : item.monitoring_id ? `/monitorings/${encodeURIComponent(item.monitoring_id)}` : '/incidents/analytics';
            const name = item.monitoring_name ?? `${item.count ?? 0}`;

            return itemLink(href, `${item.type}: ${name}`, item.monitoring_target);
        }).join('')}</div>`;

    return panel(copy.attention, content);
}

function maintenance(data: DashboardProjection, copy: DashboardCopy): string {
    const content = data.maintenance.length === 0
        ? emptyMessage(copy.noMaintenance)
        : `<div class="${PANEL_BODY_DIVIDED}">${data.maintenance.slice(0, 5).map((item) => itemLink('/maintenance', item.monitoring_name, relativeTime(item.starts_at))).join('')}</div>`;

    return panel(copy.maintenance, content);
}

function incidents(data: DashboardProjection, copy: DashboardCopy): string {
    const content = data.recent_incidents.length === 0
        ? emptyMessage(copy.noIncidents)
        : `<div class="${PANEL_BODY_DIVIDED}">${data.recent_incidents.slice(0, 5).map((item) => itemLink(
            item.monitoring_id ? `/monitorings/${encodeURIComponent(item.monitoring_id)}` : '/incidents/analytics',
            item.monitoring_name ?? copy.incidents,
            item.down_at ? new Date(item.down_at).toLocaleString() : '',
        )).join('')}</div>`;

    return panel(copy.recentIncidents, content);
}

function trend(data: DashboardProjection, copy: DashboardCopy): string {
    const points = data.trend.filter((point) => point.has_data);

    const content = points.length === 0
        ? emptyMessage(copy.noTrendData)
        : `<div class="space-y-3 p-5">${points.map((point) => `
            <div class="flex items-center gap-3 text-sm">
                <span class="w-12 text-xs font-semibold text-gray-500 dark:text-gray-400">${escapeHtml(point.label)}</span>
                <span class="h-2 flex-1 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-700"><span class="block h-full rounded-full bg-purple-600" style="width:${Math.max(0, Math.min(100, point.uptime_percentage ?? 0))}%"></span></span>
                <span class="w-12 text-end font-bold text-gray-800 dark:text-gray-100">${point.uptime_percentage?.toFixed(2) ?? '—'}%</span>
            </div>
        `).join('')}</div>`;

    return panel(copy.trend, content);
}

function deliveryWarning(data: DashboardProjection, copy: DashboardCopy): string {
    if (data.failed_delivery_count === 0) {
        return '';
    }

    return `<div class="${PANEL_SHELL}">${itemLink('/notifications', `${data.failed_delivery_count} ${copy.notifications}`, copy.notifications)}</div>`;
}

function panel(title: string, content: string): string {
    return `
        <section class="${PANEL_SHELL}">
            <div class="${PANEL_HEADER}">
                <h2 class="${PANEL_TITLE}">${escapeHtml(title)}</h2>
            </div>
            ${content}
        </section>
    `;
}

function itemLink(href: string, title: string, context: string | null): string {
    return `
        <a href="${escapeAttribute(href)}" class="${PANEL_ROW}">
            <span class="min-w-0 flex-1">
                <span class="block truncate font-bold ${TEXT_HEADING}">${escapeHtml(title)}</span>
                ${context ? `<span class="mt-1 block truncate text-xs text-gray-500 dark:text-gray-400">${escapeHtml(context)}</span>` : ''}
            </span>
            ${chevronIcon('h-4 w-4 shrink-0 text-gray-400')}
        </a>
    `;
}

function pagination(pagination: DashboardResponse['meta']['service_pagination'], copy: DashboardCopy): string {
    if (pagination.last_page <= 1) {
        return '';
    }

    return `
        <nav id="dashboard-service-pagination" class="flex items-center justify-between border-t border-gray-200 px-5 py-4 dark:border-gray-700 sm:px-6" aria-label="${escapeAttribute(copy.serviceLandscape)}">
            ${pagination.current_page <= 1
        ? `<span class="rounded-xl border border-gray-200 px-3 py-2 text-sm font-bold opacity-50 dark:border-gray-700">${escapeHtml(copy.previous)}</span>`
        : `<a href="/dashboard?service_page=${pagination.current_page - 1}" data-dashboard-service-page="${pagination.current_page - 1}" data-pagination-async class="rounded-xl border border-gray-200 px-3 py-2 text-sm font-bold dark:border-gray-700">${escapeHtml(copy.previous)}</a>`}
            <span class="text-sm font-semibold text-gray-500 dark:text-gray-400">${pagination.current_page} / ${pagination.last_page}</span>
            ${pagination.current_page >= pagination.last_page
        ? `<span class="rounded-xl border border-gray-200 px-3 py-2 text-sm font-bold opacity-50 dark:border-gray-700">${escapeHtml(copy.next)}</span>`
        : `<a href="/dashboard?service_page=${pagination.current_page + 1}" data-dashboard-service-page="${pagination.current_page + 1}" data-pagination-async class="rounded-xl border border-gray-200 px-3 py-2 text-sm font-bold dark:border-gray-700">${escapeHtml(copy.next)}</a>`}
        </nav>
    `;
}

function actionLink(href: string, label: string, modal = false): string {
    return `<a href="${escapeAttribute(href)}" ${modal ? 'data-form-modal-trigger data-form-modal-name="monitoring-form-modal"' : ''} class="mt-6 ${ACTION_SOLID}">
        <span>${escapeHtml(label)}</span>
        ${chevronIcon()}
    </a>`;
}

function filterButton(filter: string, label: string): string {
    return `<button type="button" data-signal-filter="${filter}" class="whitespace-nowrap rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-xs font-bold text-gray-600 transition hover:border-purple-200 hover:text-purple-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">${escapeHtml(label)}</button>`;
}

function stateLabel(state: string, copy: DashboardCopy): string {
    return copy.statusLabels[state] ?? state;
}

function relativeTime(value: string | null): string {
    if (!value) {
        return '—';
    }

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return '—';
    }

    const minutes = Math.round((date.getTime() - Date.now()) / 60_000);
    if (Math.abs(minutes) < 60) {
        return new Intl.RelativeTimeFormat(undefined, { numeric: 'auto' }).format(minutes, 'minute');
    }

    return date.toLocaleString();
}

function emptyMessage(message: string): string {
    return `<p class="${PANEL_EMPTY}">${escapeHtml(message)}</p>`;
}
