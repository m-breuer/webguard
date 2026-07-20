import { getCurrentDayjsLocale, humanizeDistance } from "@/utils/dayjs-utils";
import { renderHeatmap } from "@/utils/heatmap-utils";

interface MonitoringCardLoaderComponent {
    monitoringIds: string[];
    monitoringNames: Record<string, string>;
    monitoringTargets: Record<string, string>;
    monitoringTypes: Record<string, string>;
    monitoringStatusMap: Record<string, string>;
    monitoringPublicLabelMap: Record<string, boolean>;
    maintenanceStatusMap: Record<string, boolean>;
    summaryMonitoringIds: string[];
    hasMonitorings: boolean;
    statusMap: Record<string, string>;
    sinceMap: Record<string, string>;
    sinceDateMap: Record<string, string | null>;
    lastCheckMap: Record<string, string>;
    summaryReady: boolean;
    healthyCount: number;
    attentionCount: number;
    pausedCount: number;
    maintenanceCount: number;
    currentLocale: string;
    updateSummary(this: MonitoringCardLoaderComponent): void;
    updateSince(this: MonitoringCardLoaderComponent): void;
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
    maintenanceStatusMap: Record<string, boolean>,
    summaryMonitoringIds: string[]
): MonitoringCardLoaderComponent => ({
    monitoringIds: monitoringIds,
    monitoringNames: monitoringNames,
    monitoringTargets: monitoringTargets,
    monitoringTypes: monitoringTypes,
    monitoringStatusMap: monitoringStatusMap,
    monitoringPublicLabelMap: monitoringPublicLabelMap,
    maintenanceStatusMap: maintenanceStatusMap,
    summaryMonitoringIds: summaryMonitoringIds,
    hasMonitorings: monitoringIds.length > 0,
    statusMap: {} as Record<string, string>,
    sinceMap: {} as Record<string, string>,
    sinceDateMap: {} as Record<string, string | null>,
    lastCheckMap: {} as Record<string, string>,
    summaryReady: false,
    healthyCount: 0,
    attentionCount: 0,
    pausedCount: 0,
    maintenanceCount: 0,

    currentLocale: getCurrentDayjsLocale(),

    async loadAll(this: MonitoringCardLoaderComponent): Promise<void> {
        this.hasMonitorings = this.monitoringIds.length > 0;
        if (!this.hasMonitorings) return;

        const summaryBatches = Array.from({ length: Math.ceil(this.summaryMonitoringIds.length / 50) }, (_, index) =>
            this.summaryMonitoringIds.slice(index * 50, (index + 1) * 50)
        );

        const loadBatch = async (ids: string[], summaryIds: string[] = []): Promise<{
            data?: Record<string, { status?: string; since?: string | null; heatmap?: unknown[] }>;
            summary?: { attention: number; healthy: number; paused: number; maintenance: number };
        } | null> => {
            const query = new URLSearchParams();
            ids.forEach((id: string) => query.append('ids[]', id));
            summaryIds.forEach((id: string) => query.append('summary_ids[]', id));

            const response = await fetch(`/api/monitorings/card-data?${query.toString()}`).catch(() => null);
            if (!response?.ok) return null;

            return await response.json() as {
                data?: Record<string, { status?: string; since?: string | null; heatmap?: unknown[] }>;
                summary?: { attention: number; healthy: number; paused: number; maintenance: number };
            };
        };

        const [cardPayload, ...summaryPayloads] = await Promise.all([
            loadBatch(this.monitoringIds),
            ...summaryBatches.map((batch) => loadBatch([], batch)),
        ]);

        if (cardPayload) {
            const cardData = cardPayload.data ?? {};

            for (const monitoringId of this.monitoringIds) {
                const monitoringCardData = cardData[monitoringId];
                if (!monitoringCardData) continue;

                this.statusMap = { ...this.statusMap, [monitoringId]: monitoringCardData.status ?? '' };
                this.sinceDateMap = { ...this.sinceDateMap, [monitoringId]: monitoringCardData.since ?? null };
                this.sinceMap = {
                    ...this.sinceMap,
                    [monitoringId]: monitoringCardData.since ? humanizeDistance(monitoringCardData.since, { withoutSuffix: true }) : '',
                };

                const heatmapContainer = document.getElementById(`monitoring-heatmap-${monitoringId}`);
                if (heatmapContainer && monitoringCardData.heatmap) {
                    renderHeatmap(heatmapContainer, monitoringCardData.heatmap);
                }
            }
        }

        const summaries = summaryPayloads.map((payload) => payload?.summary).filter((summary): summary is NonNullable<typeof summary> => summary !== undefined);
        if (summaryBatches.length > 0 && summaries.length === summaryBatches.length) {
            this.attentionCount = summaries.reduce((total, summary) => total + summary.attention, 0);
            this.healthyCount = summaries.reduce((total, summary) => total + summary.healthy, 0);
            this.pausedCount = summaries.reduce((total, summary) => total + summary.paused, 0);
            this.maintenanceCount = summaries.reduce((total, summary) => total + summary.maintenance, 0);
            this.summaryReady = true;
        } else if (summaryBatches.length === 0) {
            this.updateSummary();
        }
    },

    updateSummary(this: MonitoringCardLoaderComponent): void {
        this.healthyCount = this.monitoringIds.filter((id) => this.statusMap[id] === 'up').length;
        this.attentionCount = this.monitoringIds.filter((id) => ['down', 'unknown'].includes(this.statusMap[id])).length;
        this.pausedCount = this.monitoringIds.filter((id) => this.monitoringStatusMap[id] === 'paused').length;
        this.maintenanceCount = this.monitoringIds.filter((id) => this.maintenanceStatusMap[id]).length;
        this.summaryReady = true;
    },

    updateSince(this: MonitoringCardLoaderComponent): void {
        for (const monitoringId in this.sinceDateMap) {
            const sinceDate = this.sinceDateMap[monitoringId];
            this.sinceMap[monitoringId] = sinceDate ? humanizeDistance(sinceDate, { withoutSuffix: true }) : '';
        }
    },

    init(this: MonitoringCardLoaderComponent) {
        this.loadAll();
    }
});
