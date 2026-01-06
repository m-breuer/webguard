import { getCurrentDayjsLocale } from "@/utils/dayjs-utils";
import dayjs from "dayjs";
import { renderHeatmap } from "@/utils/heatmap-utils";

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
    currentLocale: string;
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

    currentLocale: getCurrentDayjsLocale(),

    async loadCard(this: MonitoringCardLoaderComponent, monitoringId: string): Promise<void> {
        const statusPromise = fetch(`/api/monitorings/${monitoringId}/status-since`).then(res => res.ok ? res.json() : null).catch(() => null);
        const heatmapPromise = fetch(`/api/monitorings/${monitoringId}/heatmap`).then(res => res.ok ? res.json() : null).catch(() => null);

        const [statusData, heatmapData] = await Promise.all([statusPromise, heatmapPromise]);

        if (statusData) {
            this.statusMap[monitoringId] = statusData.status;
            this.sinceDateMap[monitoringId] = statusData.since;
            this.sinceMap[monitoringId] = statusData.since ? dayjs(statusData.since).locale(this.currentLocale).fromNow(true) : '';
        }

        if (heatmapData) {
            const heatmapContainer = document.getElementById(`monitoring-heatmap-${monitoringId}`);
            if (heatmapContainer) {
                renderHeatmap(heatmapContainer, heatmapData);
            }
        }
    },

    async loadAll(this: MonitoringCardLoaderComponent): Promise<void> {
        this.hasMonitorings = this.monitoringIds.length > 0;
        if (!this.hasMonitorings) return;

        await Promise.all(this.monitoringIds.map((id: string) => this.loadCard(id)));
    },

    updateSince(this: MonitoringCardLoaderComponent): void {
        dayjs.locale(this.currentLocale);
        for (const monitoringId in this.sinceDateMap) {
            const sinceDate = this.sinceDateMap[monitoringId];
            this.sinceMap[monitoringId] = sinceDate ? dayjs(sinceDate).fromNow(true) : '';
        }
    },

    init(this: MonitoringCardLoaderComponent) {
        this.loadAll();

        setInterval(() => {
            this.updateSince();
        }, 60000);
    }
});
