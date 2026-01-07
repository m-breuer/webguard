import { formatDate, getCurrentDayjsLocale, humanizeDistance, humanizeDuration } from '@/utils/dayjs-utils';
import Chart from 'chart.js/auto';
import dayjs from 'dayjs';

interface MonitoringDetailComponent {
    sinceDate: any;
    incidents: any[];
    status: string | null;
    since: string | null;
    heatmap: any[];
    loading: boolean;
    incidentsLoading: boolean;
    lastCheckedAt: string | null;
    nextCheckIn: string | null;
    lastCheckedAtDate: Date | null;
    nextCheckInDate: Date | null;
    lastCheckedAtHuman: string | null;
    interval: number | null;
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
    selectedRange: string;
    uptimeCalendarData: any[];
    uptimeCalendarLoading: boolean;
    chartLabels: Record<string, string>;
    currentLocale: string;
    loadStatusChanged(this: MonitoringDetailComponent): Promise<void>;
    loadIncidents(this: MonitoringDetailComponent, days?: string | number | null): Promise<void>;
    loadHeatmap(this: MonitoringDetailComponent): Promise<void>;
    loadLastCheck(this: MonitoringDetailComponent): Promise<void>;
    loadUptime(this: MonitoringDetailComponent): Promise<void>;
    loadSslStatus(this: MonitoringDetailComponent): Promise<void>;
    loadPerformanceChart(this: MonitoringDetailComponent, days?: string | number): Promise<void>;
    loadUptimeCalendar(this: MonitoringDetailComponent): Promise<void>;
    startCountdown(this: MonitoringDetailComponent): void;
    init(this: MonitoringDetailComponent): void;
}

interface AlpineThisContext extends MonitoringDetailComponent {
    $nextTick: (callback?: () => void) => Promise<void>;
}

export default (monitoringId: string, chartLabels: Record<string, string>): MonitoringDetailComponent => ({
    incidents: [] as any[],
    status: null as string | null,
    since: null as string | null,
    heatmap: [] as any[],
    loading: false,
    incidentsLoading: false,
    lastCheckedAt: null as string | null,
    nextCheckIn: null as string | null,
    lastCheckedAtDate: null,
    nextCheckInDate: null,
    lastCheckedAtHuman: null,
    interval: null,
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
    selectedRange: '1', // Default value for selected range
    uptimeCalendarData: [] as any[],
    uptimeCalendarLoading: false,

    sinceDate: null as Date | null,

    currentLocale: getCurrentDayjsLocale(),
    // Loads the current status and since when this status has been active
    async loadStatusChanged(this: MonitoringDetailComponent): Promise<void> {
        try {
            const response = await fetch(`/api/monitorings/${monitoringId}/status-since`);
            const responseData = await response.json();
            this.status = responseData.status;
            if (responseData.since) {
                this.sinceDate = new Date(responseData.since);
            }
        } catch (_) {
            this.status = null;
            this.since = null;
        }
    },

    // Loads incident data for a minimum of 1 days or the specified range, and updates the corresponding cookie
    async loadIncidents(this: MonitoringDetailComponent, days: string | number | null = null): Promise<void> {
        this.incidentsLoading = true;

        let finalDays: number;

        if (days === null) {
            finalDays = parseInt(this.selectedRange, 10);
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

    // Start the countdown timers for last and next check
    startCountdown(this: MonitoringDetailComponent): void {
        if (this.countdown) {
            clearInterval(this.countdown);
        }

        this.countdown = window.setInterval(() => {
            dayjs.locale(this.currentLocale);

            if (this.lastCheckedAtDate) {
                this.lastCheckedAtHuman = formatDate(dayjs(), 'L LTS');
            }

            if (this.sinceDate) {
                this.since = humanizeDistance(this.sinceDate, { withoutSuffix: true });
            }
        }, 1000);
    },

    // Loads timestamps for the last check and the next scheduled check
    async loadLastCheck(this: MonitoringDetailComponent): Promise<void> {
        try {
            const response = await fetch(`/api/monitorings/${monitoringId}/status-now`);
            const responseData = await response.json();

            if (responseData.checked_at) {
                this.lastCheckedAtDate = new Date(responseData.checked_at);
                this.lastCheckedAtHuman = humanizeDistance(this.lastCheckedAtDate);
                this.lastCheckedAt = responseData.checked_at;
            }

            if (responseData.interval) {
                this.interval = responseData.interval;
            }

            this.startCountdown();
        } catch (_) {
            this.lastCheckedAt = null;
            this.lastCheckedAtHuman = null;
            this.interval = null;
        }
    },

    // Loads uptime data for predefined intervals and supplements it with downtime duration
    async loadUptime(this: MonitoringDetailComponent): Promise<void> {
        const intervals = {
            '7': 7,
            '30': 30,
            '90': 90,
        };

        const promises = Object.entries(intervals).map(async ([label, days]) => {
            const uptimePromise = fetch(`/api/monitorings/${monitoringId}/uptime-downtime?days=${days}`).then(res => res.ok ? res.json() : null);
            const incidentsPromise = fetch(`/api/monitorings/${monitoringId}/incidents?days=${days}`).then(res => res.ok ? res.json() : null);

            const [uptimeData, incidentsData] = await Promise.all([uptimePromise, incidentsPromise]);

            if (uptimeData && uptimeData.downtime) {
                uptimeData.downtime.human_readable = humanizeDuration(uptimeData.downtime.minutes, 'minutes');
                uptimeData.downtime.incidents_count = incidentsData ? incidentsData.length : 0;
            }

            return { [label]: uptimeData };
        });

        const results = await Promise.all(promises);

        this.uptimeStats = Object.assign({}, ...results);
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
    async loadPerformanceChart(this: AlpineThisContext, days: string | number = this.selectedRange): Promise<void> {
        this.selectedRange = days.toString();
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

    init(this: MonitoringDetailComponent) {
        this.loadHeatmap();
        this.loadPerformanceChart();
    },

    chartLabels: chartLabels
});
