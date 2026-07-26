import { getDashboard, type DashboardService } from '../api/internal-ui-client';
import { ACTION_SOLID, escapeHtml, TEXT_HEADING } from './dashboard-markup';
import { renderDashboard, type DashboardCopy } from './dashboard-renderer';

interface DashboardLoaderComponent {
    controller: AbortController | null;
    init(this: DashboardLoaderComponent): Promise<void>;
    load(this: DashboardLoaderComponent, root: HTMLElement, servicePage?: number): Promise<void>;
    showError(this: DashboardLoaderComponent, root: HTMLElement): void;
    bindInteractions(this: DashboardLoaderComponent, root: HTMLElement, services: DashboardService[], copy: DashboardCopy): void;
}

export default (): DashboardLoaderComponent => ({
    controller: null,

    async init(this: DashboardLoaderComponent): Promise<void> {
        const root = (this as any).$el as HTMLElement;
        await this.load(root);
    },

    async load(this: DashboardLoaderComponent, root: HTMLElement, servicePage?: number): Promise<void> {
        const endpoint = root.dataset.apiEndpoint;
        if (!endpoint) {
            this.showError(root);
            return;
        }

        this.controller?.abort();
        const controller = new AbortController();
        this.controller = controller;
        root.setAttribute('aria-busy', 'true');

        try {
            const response = await getDashboard(endpoint, servicePage ?? null, controller.signal);
            const copy = JSON.parse(root.dataset.copy ?? '{}') as DashboardCopy;

            if (this.controller !== controller) {
                return;
            }

            root.innerHTML = renderDashboard(response, copy);
            this.bindInteractions(root, response.data.services, copy);
            window.Alpine?.initTree(root);
        } catch {
            if (this.controller === controller) {
                this.showError(root);
            }
        } finally {
            if (this.controller === controller) {
                root.removeAttribute('aria-busy');
            }
        }
    },

    showError(this: DashboardLoaderComponent, root: HTMLElement): void {
        const error = root.querySelector<HTMLElement>('[data-dashboard-error]');
        if (error) {
            root.querySelector<HTMLElement>('[data-dashboard-loading]')?.setAttribute('hidden', 'hidden');
            error.removeAttribute('hidden');

            return;
        }

        const errorMessage = document.createElement('p');
        errorMessage.dataset.dashboardError = '';
        errorMessage.className = 'text-sm font-semibold text-red-600 dark:text-red-300';
        errorMessage.textContent = root.dataset.errorMessage ?? '';
        root.replaceChildren(errorMessage);
    },

    bindInteractions(this: DashboardLoaderComponent, root: HTMLElement, services: DashboardService[], copy: DashboardCopy): void {
        root.querySelectorAll<HTMLElement>('[data-dashboard-service-page]').forEach((element) => {
            element.addEventListener('click', (event) => {
                event.preventDefault();
                const servicePage = Number(element.dataset.dashboardServicePage);
                if (Number.isInteger(servicePage) && servicePage > 0) {
                    void this.load(root, servicePage);
                }
            });
        });

        const search = root.querySelector<HTMLInputElement>('[data-dashboard-service-search]');
        const rows = root.querySelectorAll<HTMLElement>('[data-dashboard-service-row]');

        let activeFilter = 'all';
        const applyFilter = (): void => {
            rows.forEach((row) => {
                const status = row.dataset.dashboardServiceStatus;
                const queryMatches = search?.value.trim() === '' || row.textContent?.toLocaleLowerCase().includes(search?.value.trim().toLocaleLowerCase() ?? '');
                const statusMatches = activeFilter === 'all'
                    || (activeFilter === 'attention' && ['down', 'unknown'].includes(status ?? ''))
                    || status === activeFilter;

                row.toggleAttribute('hidden', !(queryMatches && statusMatches));
            });
        };

        search?.addEventListener('input', applyFilter);
        root.querySelectorAll<HTMLButtonElement>('[data-signal-filter]').forEach((button) => {
            button.addEventListener('click', () => {
                activeFilter = button.dataset.signalFilter ?? 'all';
                applyFilter();
            });
        });

        const showDetail = (service: DashboardService, mobile: boolean): void => {
            const detail = detailMarkup(service, copy);
            const target = mobile
                ? root.querySelector<HTMLElement>('[data-signal-mobile-detail]')
                : root.querySelector<HTMLElement>('aside [data-signal-detail]');

            if (!target) {
                return;
            }

            target.innerHTML = detail;
            target.querySelectorAll<HTMLButtonElement>('[data-signal-tab]').forEach((tab) => {
                tab.addEventListener('click', () => {
                    const activeTab = tab.dataset.signalTab;
                    target.querySelectorAll<HTMLElement>('[data-signal-tab-panel]').forEach((panel) => {
                        panel.hidden = panel.dataset.signalTabPanel !== activeTab;
                    });
                    target.querySelectorAll<HTMLButtonElement>('[data-signal-tab]').forEach((button) => {
                        button.setAttribute('aria-selected', String(button === tab));
                    });
                });
            });

            if (mobile) {
                root.querySelector<HTMLElement>('[data-signal-mobile-sheet]')?.removeAttribute('hidden');
            }
        };

        rows.forEach((row) => {
            row.addEventListener('click', () => {
                const service = services.find((candidate) => candidate.id === row.dataset.signalService);
                if (service) {
                    showDetail(service, !window.matchMedia('(min-width: 1024px)').matches);
                }
            });
        });

        const closeMobileDetail = (): void => root.querySelector<HTMLElement>('[data-signal-mobile-sheet]')?.setAttribute('hidden', 'hidden');
        window.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeMobileDetail();
            }
        }, { once: true });

        if (window.matchMedia('(min-width: 1024px)').matches && services[0]) {
            showDetail(services[0], false);
        }
    },
});

function detailMarkup(service: DashboardService, copy: DashboardCopy): string {
    const status = escapeHtml(copy.statusLabels[service.status] ?? service.status);
    const responseTime = service.response_time_ms === null ? '—' : `${Math.round(service.response_time_ms)} ms`;
    const checkedAt = service.last_checked_at ? new Date(service.last_checked_at).toLocaleString() : '—';

    return `
        <div class="p-5 sm:p-6">
            <h3 class="truncate text-xl font-black ${TEXT_HEADING}">${escapeHtml(service.name)}</h3>
            <div class="mt-5 flex gap-1 rounded-xl bg-gray-100 p-1 dark:bg-gray-700" role="tablist">
                ${[
        ['signal', copy.signalTab],
        ['checks', copy.checksTab],
        ['incidents', copy.incidentsTab],
        ['history', copy.historyTab],
    ].map(([tab, label], index) => `<button type="button" data-signal-tab="${tab}" role="tab" aria-selected="${index === 0}" class="flex-1 rounded-lg px-2 py-2 text-center text-[11px] font-bold">${escapeHtml(label)}</button>`).join('')}
            </div>
            <div data-signal-tab-panel="signal" class="mt-5 rounded-2xl border border-gray-200 bg-gray-50 p-4 text-sm dark:border-gray-700 dark:bg-gray-900/40">${status}</div>
            <div data-signal-tab-panel="checks" hidden class="mt-5 rounded-2xl border border-gray-200 bg-gray-50 p-4 text-sm dark:border-gray-700 dark:bg-gray-900/40">${escapeHtml(responseTime)} · ${escapeHtml(checkedAt)}</div>
            <div data-signal-tab-panel="incidents" hidden class="mt-5 rounded-2xl border border-gray-200 bg-gray-50 p-4 text-sm dark:border-gray-700 dark:bg-gray-900/40">${service.open_incident ? escapeHtml(copy.incidents) : escapeHtml(copy.noIncidents)}</div>
            <div data-signal-tab-panel="history" hidden class="mt-5 rounded-2xl border border-gray-200 bg-gray-50 p-4 text-sm dark:border-gray-700 dark:bg-gray-900/40">${escapeHtml(checkedAt)}</div>
            <a href="/monitorings/${encodeURIComponent(service.id)}" class="mt-5 ${ACTION_SOLID}">${escapeHtml(copy.fullDetails)}</a>
        </div>
    `;
}
