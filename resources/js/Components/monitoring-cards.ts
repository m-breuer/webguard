interface MonitoringCardLoaderComponent {
    monitoringIds: string[];
    monitoringNames: Record<string, string>;
    monitoringTargets: Record<string, string>;
    monitoringTypes: Record<string, string>;
    monitoringStatusMap: Record<string, string>;
    monitoringPublicLabelMap: Record<string, boolean>;
    hasMonitorings: boolean;
    statusMap: Record<string, string>;
    sinceMap: Record<string, string>;
    lastCheckMap: Record<string, string>;
    getThemeColors(this: MonitoringCardLoaderComponent): { up: string; down: string; unknown: string };
    formatSinceDate(this: MonitoringCardLoaderComponent, isoTimestamp: string | null): string | null;
    _formatTime(this: MonitoringCardLoaderComponent, seconds: number): string;
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
    monitoringPublicLabelMap: Record<string, boolean>
): MonitoringCardLoaderComponent => ({
    monitoringIds: monitoringIds,
    monitoringNames: monitoringNames,
    monitoringTargets: monitoringTargets,
    monitoringTypes: monitoringTypes,
    monitoringStatusMap: monitoringStatusMap,
    monitoringPublicLabelMap: monitoringPublicLabelMap,
    hasMonitorings: monitoringIds.length > 0,
    statusMap: {} as Record<string, string>,
    sinceMap: {} as Record<string, string>,
    lastCheckMap: {} as Record<string, string>,

    // Utility function to format seconds into a human-readable string (e.g., "1d 2h 3m 4s")
    _formatTime(seconds: number): string {
        if (seconds < 0) {
            return '0s';
        }

        const d = Math.floor(seconds / (3600 * 24));
        const h = Math.floor(seconds % (3600 * 24) / 3600);
        const m = Math.floor(seconds % 3600 / 60);
        const s = Math.floor(seconds % 60);

        let result = '';
        if (d > 0) {
            result += `${d}d `;
        }
        if (h > 0) {
            result += `${h}h `;
        }
        if (m > 0) {
            result += `${m}m `;
        }
        if (s > 0 || result === '') { // Always show seconds if no other unit, or if it's the only unit
            result += `${s}s`;
        }

        return result.trim();
    },

    formatSinceDate(this: MonitoringCardLoaderComponent, isoTimestamp: string | null): string | null {
        if (!isoTimestamp) {
            return null;
        }
        const date = new Date(isoTimestamp);
        const now = new Date();
        const diffInSeconds = Math.round((now.getTime() - date.getTime()) / 1000);
        return this._formatTime(diffInSeconds);
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

    init(this: MonitoringCardLoaderComponent) {
        this.loadAll();

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
