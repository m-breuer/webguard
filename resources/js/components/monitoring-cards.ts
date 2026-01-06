import { formatDistanceToNowStrict, Locale } from 'date-fns';
import { enUS, de } from 'date-fns/locale'; // Import locales as needed

// Map Laravel locales to date-fns locales
const dateFnsLocales: { [key: string]: Locale } = {
    'en': enUS,
    'de': de,
    // Add other locales as needed
};

declare global {
    interface Window {
        App: {
            locale: string;
        };
    }
}

interface MonitoringCardLoaderComponent {
    monitoringIds: string[];
    monitoringNames: Record<string, string>;
    monitoringTargets: Record<string, string>;
    monitoringTypes: Record<string, string>;
    monitoringStatusMap: Record<string, string>;
    monitoringPublicLabelMap: Record<string, boolean>;
    maintenanceStatusMap: Record<string, boolean>;
    hasMonitorings: boolean;
    statusMap: Record<string, string>;
    sinceMap: Record<string, string>;
    sinceDateMap: Record<string, string | null>;
    lastCheckMap: Record<string, string>;
    currentLocale: Locale;
    getThemeColors(this: MonitoringCardLoaderComponent): { up: string; down: string; unknown: string };
    updateSince(this: MonitoringCardLoaderComponent): void;
    loadCard(this: MonitoringCardLoaderComponent, monitoringId: string): Promise<void>;
    loadAll(this: MonitoringCardLoaderComponent): Promise<void>;
    init(this: MonitoringCardLoaderComponent): void;
}

export default (
    monitoringIds: string[],
    monitoringNames: Record<string, string>,
    monitoringTargets: Record<string, string>,
    monitoringTypes: Record<string, string>,
    monitoringStatusMap: Record<string, string>,
    monitoringPublicLabelMap: Record<string, boolean>,
    maintenanceStatusMap: Record<string, boolean>
): MonitoringCardLoaderComponent => ({
    monitoringIds: monitoringIds,
    monitoringNames: monitoringNames,
    monitoringTargets: monitoringTargets,
    monitoringTypes: monitoringTypes,
    monitoringStatusMap: monitoringStatusMap,
    monitoringPublicLabelMap: monitoringPublicLabelMap,
    maintenanceStatusMap: maintenanceStatusMap,
    hasMonitorings: monitoringIds.length > 0,
    statusMap: {} as Record<string, string>,
    sinceMap: {} as Record<string, string>,
    sinceDateMap: {} as Record<string, string | null>,
    lastCheckMap: {} as Record<string, string>,

    currentLocale: dateFnsLocales[window.App.locale] || enUS,

    formatSinceDate(this: MonitoringCardLoaderComponent, isoTimestamp: string | null): string | null {
        if (!isoTimestamp) {
            return null;
        }
        return formatDistanceToNowStrict(new Date(isoTimestamp), { addSuffix: true, locale: this.currentLocale });
    },

    getThemeColors(this: MonitoringCardLoaderComponent): { up: string; down: string; unknown: string } {
        const htmlElement = document.documentElement;
        const isDark = htmlElement.classList.contains('dark');
        return {
            up: 'bg-green-500',
            down: 'bg-red-500',
            unknown: isDark ? 'bg-gray-400' : 'bg-gray-300'
        };
    },

    async loadCard(this: MonitoringCardLoaderComponent, monitoringId: string): Promise<void> {
        const statusPromise = fetch(`/api/monitorings/${monitoringId}/status-since`).then(res => res.ok ? res.json() : null).catch(() => null);
        const heatmapPromise = fetch(`/api/monitorings/${monitoringId}/heatmap`).then(res => res.ok ? res.json() : null).catch(() => null);

        const [statusData, heatmapData] = await Promise.all([statusPromise, heatmapPromise]);

        if (statusData) {
            this.statusMap[monitoringId] = statusData.status;
            this.sinceDateMap[monitoringId] = statusData.since;
            this.sinceMap[monitoringId] = this.formatSinceDate(statusData.since) ?? '';
        }

        if (heatmapData) {
            const heatmapContainer = document.getElementById(`monitoring-heatmap-${monitoringId}`);

            if (heatmapContainer) {
                heatmapContainer.innerHTML = '';
                const capped = Array.isArray(heatmapData) ? heatmapData.slice(0, 24) : [];
                while (capped.length < 24) {
                    capped.push({ uptime: 0, downtime: 0 });
                }

                const colors = this.getThemeColors();

                capped.forEach((point: { uptime: number; downtime: number }) => {
                    const statusDot = document.createElement('div');
                    let bgColor;
                    if (point.uptime > point.downtime) {
                        bgColor = colors.up;
                    } else if (point.uptime < point.downtime) {
                        bgColor = colors.down;
                    } else {
                        bgColor = colors.unknown;
                    }
                    statusDot.className = `h-6 w-3 rounded-xs ${bgColor}`;
                    heatmapContainer.appendChild(statusDot);
                });
            }
        }
    },

    async loadAll(this: MonitoringCardLoaderComponent): Promise<void> {
        this.hasMonitorings = this.monitoringIds.length > 0;
        if (!this.hasMonitorings) return;

        await Promise.all(this.monitoringIds.map((id: string) => this.loadCard(id)));
    },

    updateSince(this: MonitoringCardLoaderComponent): void {
        for (const monitoringId in this.sinceDateMap) {
            this.sinceMap[monitoringId] = this.formatSinceDate(this.sinceDateMap[monitoringId]) ?? '';
        }
    },

    init(this: MonitoringCardLoaderComponent) {
        this.loadAll();

        setInterval(() => {
            this.updateSince();
        }, 1000);

        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                    this.loadAll();
                }
            });
        });

        observer.observe(document.documentElement, { attributes: true });
    }
});
