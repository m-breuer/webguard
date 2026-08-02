import { formatDateTime, getCurrentDayjsLocale } from "@/utils/dayjs-utils";
import { renderHeatmap } from "@/utils/heatmap-utils";
import { getMonitoringCards, type MonitoringCardsResponse } from "../api/internal-ui-client";

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
    summaryError: boolean;
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
    summaryError: false,

    currentLocale: getCurrentDayjsLocale(),

    async loadAll(this: MonitoringCardLoaderComponent): Promise<void> {
        this.hasMonitorings = this.monitoringIds.length > 0;
        if (!this.hasMonitorings) return;

        const summaryBatches = Array.from({ length: Math.ceil(this.summaryMonitoringIds.length / 50) }, (_, index) =>
            this.summaryMonitoringIds.slice(index * 50, (index + 1) * 50)
        );

        const loadBatch = async (ids: string[]): Promise<MonitoringCardsResponse | null> => {
            try {
                return await getMonitoringCards('/api/v1/internal/ui/monitorings/cards', ids);
            } catch {
                return null;
            }
        };

        const cardPayloadPromise = loadBatch(this.monitoringIds);
        cardPayloadPromise.then((cardPayload) => {
            if (!cardPayload) return;

            const cardData = cardPayload.data ?? {};

            for (const monitoringId of this.monitoringIds) {
                const monitoringCardData = cardData[monitoringId];
                if (!monitoringCardData) continue;

                this.statusMap = { ...this.statusMap, [monitoringId]: monitoringCardData.status ?? '' };
                this.sinceDateMap = { ...this.sinceDateMap, [monitoringId]: monitoringCardData.since ?? null };
                this.sinceMap = {
                    ...this.sinceMap,
                    [monitoringId]: monitoringCardData.since ? formatDateTime(monitoringCardData.since) ?? '' : '',
                };

                const heatmapContainer = document.getElementById(`monitoring-heatmap-${monitoringId}`);
                if (heatmapContainer && monitoringCardData.heatmap) {
                    renderHeatmap(heatmapContainer, monitoringCardData.heatmap);
                }
            }
        });

        if (summaryBatches.length === 0) {
            this.updateSummary();
            await cardPayloadPromise;
            return;
        }

        let nextBatchIndex = 0;
        let completedBatches = 0;
        const loadSummaryWorker = async (): Promise<void> => {
            while (nextBatchIndex < summaryBatches.length) {
                const batch = summaryBatches[nextBatchIndex++];
                const payload = await loadBatch(batch);
                const summary = payload?.summary;

                if (!summary) {
                    this.summaryError = true;
                } else {
                    this.attentionCount += summary.attention;
                    this.healthyCount += summary.healthy;
                    this.pausedCount += summary.paused;
                    this.maintenanceCount += summary.maintenance;
                }

                completedBatches++;
                this.summaryReady = completedBatches === summaryBatches.length;
            }
        };

        await Promise.all(Array.from(
            { length: Math.min(3, summaryBatches.length) },
            () => loadSummaryWorker(),
        ));
        await cardPayloadPromise;
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
            this.sinceMap[monitoringId] = sinceDate ? formatDateTime(sinceDate) ?? '' : '';
        }
    },

    init(this: MonitoringCardLoaderComponent) {
        this.loadAll();
    }
});
