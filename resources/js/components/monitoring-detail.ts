import { formatDate, getCurrentDayjsLocale, humanizeDistance, humanizeDuration } from '@/utils/dayjs-utils';
import Chart from 'chart.js/auto';
import dayjs from 'dayjs';

interface MonitoringDetailComponent {
    sinceDate: any;
    incidents: any[];
    recentChecks: Array<{
        id: string;
        checkedAt: string;
        checkedAtHuman: string | null;
        status: string;
        httpStatusCode: number | null;
        responseTime: number | null;
        statusIdentifier: string;
        source: string;
    }>;
    status: string | null;
    statusCode: number | null;
    since: string | null;
    heatmap: any[];
    loading: boolean;
    incidentsLoading: boolean;
    recentChecksLoading: boolean;
    recentChecksLoadingMore: boolean;
    recentChecksHasMore: boolean;
    recentChecksOffset: number;
    recentChecksPageSize: number;
    lastCheckedAt: string | null;
    nextCheckIn: string | null;
    lastCheckedAtDate: Date | null;
    nextCheckInDate: Date | null;
    lastCheckedAtHuman: string | null;
    interval: number | null;
    intervalHuman: string | null;
    countdown: number | null;
    uptimeStats: Record<string, any>;
    sslValid: boolean | null;
    sslExpiration: string | null;
    sslIssuer: string | null;
    sslIssueDate: string | null;
    performanceChartInstance: Chart | null;
    responseStats: Record<string, any>;
    chartLoading: boolean;
    responseStatsLoaded: Record<string, boolean>;
    totalDowntime: string | null;
    isDarkMode: boolean;
    responseTimeRange: string;
    incidentsRange: string;
    uptimeCalendarData: any[];
    uptimeCalendarLoading: boolean;
    deferredDataInitialized: boolean;
    uptimeCalendarLoaded: boolean;
    chartLabels: Record<string, string>;
    currentLocale: string;
    loadStatus(this: MonitoringDetailComponent): Promise<void>;
    loadIncidents(this: MonitoringDetailComponent, days?: string | number | null): Promise<void>;
    loadChecks(this: MonitoringDetailComponent, days?: string | number | null, append?: boolean): Promise<void>;
    loadMoreChecks(this: MonitoringDetailComponent): Promise<void>;
    loadHeatmap(this: MonitoringDetailComponent): Promise<void>;
    loadUptime(this: MonitoringDetailComponent): Promise<void>;
    loadSslStatus(this: MonitoringDetailComponent): Promise<void>;
    loadPerformanceChart(this: MonitoringDetailComponent, days?: string | number): Promise<void>;
    loadUptimeCalendar(this: MonitoringDetailComponent): Promise<void>;
    initializeDeferredLoads(this: MonitoringDetailComponent): void;
    resolveCheckStatusLabel(this: MonitoringDetailComponent, statusIdentifier: string): string;
    resolveCheckStatusClass(this: MonitoringDetailComponent, statusIdentifier: string): string;
    resolveCheckSourceLabel(this: MonitoringDetailComponent, source: string): string;
    formatResponseTime(this: MonitoringDetailComponent, responseTime: number | null): string;
}

interface AlpineThisContext extends MonitoringDetailComponent {
    $nextTick: (callback?: () => void) => Promise<void>;
}

export default (monitoringId: string, chartLabels: Record<string, string>): MonitoringDetailComponent => ({
    incidents: [] as any[],
    recentChecks: [] as Array<{
        id: string;
        checkedAt: string;
        checkedAtHuman: string | null;
        status: string;
        httpStatusCode: number | null;
        responseTime: number | null;
        statusIdentifier: string;
        source: string;
    }>,
    status: null as string | null,
    statusCode: null as number | null,
    since: null as string | null,
    heatmap: [] as any[],
    loading: false,
    incidentsLoading: false,
    recentChecksLoading: false,
    recentChecksLoadingMore: false,
    recentChecksHasMore: false,
    recentChecksOffset: 0,
    recentChecksPageSize: 5,
    lastCheckedAt: null as string | null,
    nextCheckIn: null as string | null,
    lastCheckedAtDate: null,
    nextCheckInDate: null,
    lastCheckedAtHuman: null,
    interval: null,
    intervalHuman: null,
    countdown: null,
    uptimeStats: {} as Record<string, any>,
    sslValid: null as boolean | null,
    sslExpiration: null as string | null,
    sslIssuer: null as string | null,
    sslIssueDate: null as string | null,
    performanceChartInstance: null as Chart | null,
    responseStats: {} as Record<string, any>,
    chartLoading: false,
    responseStatsLoaded: {} as Record<string, boolean>,
    totalDowntime: null as string | null,
    isDarkMode: document.documentElement.classList.contains('dark'),
    responseTimeRange: '1',
    incidentsRange: '1',
    uptimeCalendarData: [] as any[],
    uptimeCalendarLoading: false,
    deferredDataInitialized: false,
    uptimeCalendarLoaded: false,

    sinceDate: null as Date | null,

    currentLocale: getCurrentDayjsLocale(),
    async loadStatus(this: MonitoringDetailComponent): Promise<void> {
        try {
            const response = await fetch(`/api/monitorings/${monitoringId}/status`);
            const responseData = await response.json();
            this.status = responseData.status;
            this.statusCode = responseData.status_code ?? null;
            if (responseData.since) {
                this.sinceDate = new Date(responseData.since);
                this.since = humanizeDistance(this.sinceDate, { withoutSuffix: true });
            }
            if (responseData.checked_at) {
                this.lastCheckedAtDate = new Date(responseData.checked_at);
                this.lastCheckedAtHuman = formatDate(this.lastCheckedAtDate, 'L LTS');
                this.lastCheckedAt = responseData.checked_at;
            }
            if (responseData.interval) {
                this.interval = responseData.interval;
                this.intervalHuman = humanizeDuration(responseData.interval, 'seconds');
            }
        } catch (_) {
            this.status = null;
            this.statusCode = null;
            this.since = null;
            this.lastCheckedAt = null;
            this.lastCheckedAtHuman = null;
            this.interval = null;
            this.intervalHuman = null;
        }
    },


    // Loads incident data for a minimum of 1 days or the specified range, and updates the corresponding cookie
    async loadIncidents(this: MonitoringDetailComponent, days: string | number | null = null): Promise<void> {
        this.incidentsLoading = true;

        let finalDays: number;

        if (days === null) {
            finalDays = parseInt(this.incidentsRange, 10);
        } else if (typeof days === 'string') {
            finalDays = parseInt(days, 10);
        } else { // days is a number
            finalDays = days;
        }

        // Ensure finalDays is at least 1, and handle NaN from parseInt
        if (isNaN(finalDays) || finalDays < 1) {
            finalDays = 1;
        }

        try {
            const response = await fetch(`/api/monitorings/${monitoringId}/incidents?days=${finalDays}`);
            const responseData = await response.json();

            this.incidents = responseData.map((incident: any) => {
                const downAt = dayjs(incident.down_at);
                const upAt = incident.up_at ? dayjs(incident.up_at) : dayjs(); // Use now if up_at is null
                const durationInMinutes = upAt.diff(downAt, 'minutes');

                return {
                    ...incident,
                    down_at: formatDate(incident.down_at, 'L LT'),
                    up_at: incident.up_at ? formatDate(incident.up_at, 'L LT') : null,
                    duration: humanizeDuration(durationInMinutes, 'minutes'),
                };
            });
        } catch (_) {
            this.incidents = [];
        } finally {
            this.incidentsLoading = false;
        }
    },

    async loadChecks(this: MonitoringDetailComponent, days: string | number | null = null, append = false): Promise<void> {
        if (append) {
            this.recentChecksLoadingMore = true;
        } else {
            this.recentChecksLoading = true;
            this.recentChecksOffset = 0;
            this.recentChecksHasMore = false;
        }

        let finalDays: number;

        if (days === null) {
            finalDays = NaN;
        } else if (typeof days === 'string') {
            finalDays = parseInt(days, 10);
        } else {
            finalDays = days;
        }

        try {
            const query = new URLSearchParams({
                limit: String(this.recentChecksPageSize),
                offset: String(append ? this.recentChecksOffset : 0),
            });

            if (!isNaN(finalDays) && finalDays >= 1) {
                query.set('days', String(finalDays));
            }

            const response = await fetch(`/api/monitorings/${monitoringId}/checks?${query.toString()}`);

            if (!response.ok) {
                throw new Error(`Checks request failed: ${response.status}`);
            }

            const responseData = await response.json() as {
                data?: Array<{
                    id: string;
                    checked_at: string;
                    status: string;
                    http_status_code: number | null;
                    response_time: number | null;
                    status_identifier: string;
                    source: string;
                }>;
                meta?: {
                    has_more?: boolean;
                    next_offset?: number | null;
                };
            };

            const checks = (responseData.data ?? []).map((check) => ({
                id: check.id,
                checkedAt: formatDate(check.checked_at, 'L LTS') ?? check.checked_at,
                checkedAtHuman: humanizeDistance(check.checked_at),
                status: check.status,
                httpStatusCode: check.http_status_code,
                responseTime: check.response_time,
                statusIdentifier: check.status_identifier,
                source: check.source,
            }));

            this.recentChecks = append ? [...this.recentChecks, ...checks] : checks;
            this.recentChecksHasMore = Boolean(responseData.meta?.has_more);
            this.recentChecksOffset = responseData.meta?.next_offset ?? this.recentChecks.length;
        } catch (_) {
            if (!append) {
                this.recentChecks = [];
                this.recentChecksHasMore = false;
                this.recentChecksOffset = 0;
            }
        } finally {
            if (append) {
                this.recentChecksLoadingMore = false;
            } else {
                this.recentChecksLoading = false;
            }
        }
    },

    async loadMoreChecks(this: MonitoringDetailComponent): Promise<void> {
        if (this.recentChecksLoading || this.recentChecksLoadingMore || !this.recentChecksHasMore) {
            return;
        }

        await this.loadChecks(null, true);
    },

    // Loads heatmap data representing uptime/downtime over the last 24 hours
    async loadHeatmap(this: MonitoringDetailComponent): Promise<void> {
        this.loading = true;

        try {
            const response = await fetch(`/api/monitorings/${monitoringId}/heatmap`);
            const responseData = await response.json();
            let capped = Array.isArray(responseData) ? responseData.slice(0, 24) : [];
            if (capped.length < 24) {
                const missing = 24 - capped.length;
                // Fill missing hours with zero uptime and downtime
                const filler = Array.from({ length: missing }, () => ({ uptime: 0, downtime: 0 }));
                capped = [...capped, ...filler];
            }

            // Replace heatmap data with the capped and possibly filled array
            this.heatmap.splice(0, this.heatmap.length, ...capped);

        } catch (_) {
            this.heatmap = [];
        } finally {
            this.loading = false;
        }
    },

    // Loads uptime data for predefined intervals and supplements it with downtime duration
    async loadUptime(this: MonitoringDetailComponent): Promise<void> {
        const query = new URLSearchParams();
        ['7', '30', '90'].forEach((days) => query.append('days[]', days));

        const response = await fetch(`/api/monitorings/${monitoringId}/uptime-downtime-summary?${query.toString()}`).catch(() => null);

        if (!response?.ok) {
            this.uptimeStats = {};

            return;
        }

        const payload = await response.json() as { data?: Record<string, any> };
        const summary = payload.data ?? {};

        this.uptimeStats = Object.fromEntries(
            Object.entries(summary).map(([label, uptimeData]) => {
                if (uptimeData && uptimeData.downtime) {
                    uptimeData.downtime.human_readable = humanizeDuration(uptimeData.downtime.minutes, 'minutes');
                    uptimeData.downtime.incidents_count = Number(uptimeData.downtime.incidents_count ?? 0);
                }

                return [label, uptimeData];
            })
        );
    },

    // Loads SSL certificate status and related metadata
    async loadSslStatus(this: MonitoringDetailComponent): Promise<void> {
        try {
            const response = await fetch(`/api/monitorings/${monitoringId}/ssl`);
            const responseData = await response.json();
            this.sslValid = responseData.valid;
            this.sslExpiration = responseData.expiration ? formatDate(responseData.expiration, 'L') : null;
            this.sslIssuer = responseData.issuer;
            this.sslIssueDate = responseData.issue_date ? formatDate(responseData.issue_date, 'L') : null;
        } catch (_) {
            this.sslValid = null;
            this.sslExpiration = null;
            this.sslIssuer = null;
            this.sslIssueDate = null;
        }
    },

    // Loads and renders the performance chart for response times over the specified range
    async loadPerformanceChart(this: AlpineThisContext, days: string | number = this.responseTimeRange): Promise<void> {
        this.responseTimeRange = days.toString();
        this.chartLoading = true; // Hide canvas and show loading indicator


        // Destroy existing chart instance if present to avoid memory leaks
        if (this.performanceChartInstance) {
            this.performanceChartInstance.destroy();
            this.performanceChartInstance = null;
        }

        // Ensure canvas is visible before attempting to get context and create chart
        this.chartLoading = false; // Show canvas
        await this.$nextTick(); // Wait for Alpine.js to update the DOM

        try {
            const response = await fetch(`/api/monitorings/${monitoringId}/response-times?days=${days}`);
            const responseData = await response.json();

            const canvas = document.getElementById('performance-chart') as HTMLCanvasElement;
            if (!canvas) {
                console.error('Canvas element not found.');
                return;
            }
            const ctx = canvas.getContext('2d');
            if (!ctx) {
                console.error('Canvas context not found.');
                return;
            }

            // Destroy existing chart instance if present
            const existingChart = Chart.getChart(canvas);
            if (existingChart) {
                existingChart.destroy();
            }

            // Clear the canvas (optional, but good for ensuring a clean slate)
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            // Create a new line chart with response time data
            this.performanceChartInstance = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: responseData.data.map((entry: { date: string; }) => formatDate(entry.date, 'L LT')),
                    datasets: [
                        {
                            label: this.chartLabels.min,
                            data: responseData.data.map((entry: { min: number; }) => entry.min),
                            fill: false,
                            borderDash: [5, 5],
                            borderColor: '#dab2ff',
                            tension: 0.1
                        },
                        {
                            label: this.chartLabels.avg,
                            data: responseData.data.map((entry: { avg: number; }) => entry.avg),
                            fill: false,
                            borderColor: this.isDarkMode ? '#9810fa' : '#9810fa',
                            tension: 0.1
                        },
                        {
                            label: this.chartLabels.max,
                            data: responseData.data.map((entry: { max: number; }) => entry.max),
                            fill: false,
                            borderDash: [5, 5],
                            borderColor: '#dab2ff',
                            tension: 0.1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            labels: {
                                color: this.isDarkMode ? '#ebe6e7' : '#4a5565',
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                color: this.isDarkMode ? '#ebe6e7' : '#4a5565'
                            },
                            grid: {
                                color: this.isDarkMode ? '#e2e8f0' : '#4a5565'
                            },
                            title: {
                                display: true,
                                text: this.chartLabels.yAxis,
                                color: this.isDarkMode ? '#ebe6e7' : '#4a5565'
                            }
                        },
                        x: {
                            ticks: {
                                display: false,
                                color: this.isDarkMode ? '#ebe6e7' : '#4a5565'
                            },
                            grid: {
                                color: 'transparent'
                            },
                            title: {
                                display: true,
                                text: this.chartLabels.xAxis,
                                color: this.isDarkMode ? '#ebe6e7' : '#4a5565'
                            }
                        }
                    }
                }
            });

            // Cache aggregated response statistics for the selected range
            this.responseStats[`${days}d`] = responseData.aggregated;
            this.responseStatsLoaded[days.toString()] = true;
        } catch (error) {
            console.error('Failed to load performance chart data:', error);
        } finally {
            this.chartLoading = false;
        }
    },

    async loadUptimeCalendar(this: MonitoringDetailComponent): Promise<void> {
        this.uptimeCalendarLoading = true;

        const endDate = new Date();
        const startDate = new Date();
        startDate.setMonth(startDate.getMonth() - 11);
        startDate.setDate(1);

        const formatDateFn = (date: Date) => date.toISOString().split('T')[0];

        try {
            const response = await fetch(`/api/monitorings/${monitoringId}/uptime-calendar?start_date=${formatDateFn(startDate)}&end_date=${formatDateFn(endDate)}`);
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            const responseData = await response.json();
            this.uptimeCalendarData = Object.keys(responseData).reduce((acc, monthYear) => {
                acc[monthYear] = {
                    ...responseData[monthYear],
                    days: responseData[monthYear].days.map((day: any) => ({
                        ...day,
                        date: formatDate(day.date, 'L'),
                    })),
                };
                return acc;
            }, {});
        } catch (error) {
            console.error('There has been a problem with your fetch operation:', error);
        } finally {
            this.uptimeCalendarLoading = false;
        }
    },

    initializeDeferredLoads(this: MonitoringDetailComponent): void {
        if (this.deferredDataInitialized) {
            return;
        }
        this.deferredDataInitialized = true;

        const loadSsl = (): void => {
            void this.loadSslStatus();
        };

        if ('requestIdleCallback' in window) {
            (window as any).requestIdleCallback(loadSsl, { timeout: 1500 });
        } else {
            window.setTimeout(loadSsl, 250);
        }

        const loadCalendar = (): void => {
            if (this.uptimeCalendarLoaded) {
                return;
            }
            this.uptimeCalendarLoaded = true;
            void this.loadUptimeCalendar();
        };

        const calendarContainer = document.getElementById(`uptime-calendar-${monitoringId}`);
        if (!calendarContainer) {
            window.setTimeout(loadCalendar, 600);
            return;
        }

        if ('IntersectionObserver' in window) {
            const observer = new IntersectionObserver((entries) => {
                if (entries.some((entry) => entry.isIntersecting)) {
                    observer.disconnect();
                    loadCalendar();
                }
            }, { rootMargin: '200px 0px' });

            observer.observe(calendarContainer);
            return;
        }

        window.setTimeout(loadCalendar, 600);
    },

    resolveCheckStatusLabel(this: MonitoringDetailComponent, statusIdentifier: string): string {
        const labels: Record<string, string> = {
            'status.success': this.chartLabels.checkStatusSuccess,
            'status.redirect': this.chartLabels.checkStatusRedirect,
            'status.client_error': this.chartLabels.checkStatusClientError,
            'status.server_error': this.chartLabels.checkStatusServerError,
            'status.maintenance': this.chartLabels.checkStatusMaintenance,
            'status.unknown': this.chartLabels.checkStatusUnknown,
        };

        return labels[statusIdentifier] ?? this.chartLabels.checkStatusUnknown;
    },

    resolveCheckStatusClass(this: MonitoringDetailComponent, statusIdentifier: string): string {
        const classes: Record<string, string> = {
            'status.success': 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200',
            'status.redirect': 'bg-sky-100 text-sky-800 dark:bg-sky-900/40 dark:text-sky-200',
            'status.client_error': 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200',
            'status.server_error': 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-200',
            'status.maintenance': 'bg-slate-200 text-slate-800 dark:bg-slate-700 dark:text-slate-100',
            'status.unknown': 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200',
        };

        return classes[statusIdentifier] ?? classes['status.unknown'];
    },

    resolveCheckSourceLabel(this: MonitoringDetailComponent, source: string): string {
        return source === 'archived'
            ? this.chartLabels.checkSourceArchived
            : this.chartLabels.checkSourceLive;
    },

    formatResponseTime(this: MonitoringDetailComponent, responseTime: number | null): string {
        if (responseTime === null) {
            return this.chartLabels.checkResponseTimeUnavailable;
        }

        return `${Math.round(responseTime)} ms`;
    },

    chartLabels: chartLabels
});
