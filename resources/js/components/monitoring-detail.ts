import Chart from 'chart.js/auto';
import { format, formatDistanceToNowStrict, Locale } from 'date-fns';
import { enUS, de } from 'date-fns/locale'; // Import locales as needed

// Map Laravel locales to date-fns locales
const dateFnsLocales: { [key: string]: Locale } = {
    'en': enUS,
    'de': de,
    // Add other locales as needed
};

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
    nextCheckInHuman: string | null;
    interval: number | null;
    countdown: number | null;
    uptimeDowntimeData: Record<string, any>;
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
    getThemeColors(): {};
    currentLocale: Locale;
    _formatDurationFromMinutes(minutes: number): string;
    loadStatusChanged(this: MonitoringDetailComponent): Promise<void>;
    loadIncidents(this: MonitoringDetailComponent, days?: string | number | null): Promise<void>;
    loadHeatmap(this: MonitoringDetailComponent): Promise<void>;
    loadLastCheck(this: MonitoringDetailComponent): Promise<void>;
    loadUptime(this: MonitoringDetailComponent): Promise<void>;
    loadSslStatus(this: MonitoringDetailComponent): Promise<void>;
    loadPerformanceChart(this: MonitoringDetailComponent, days?: string | number): Promise<void>;
    loadUptimeCalendar(this: MonitoringDetailComponent): Promise<void>;
    startCountdown(this: MonitoringDetailComponent): void;
    beforeDestroy(this: MonitoringDetailComponent): void;
    init(this: MonitoringDetailComponent): void;
}

interface AlpineThisContext extends MonitoringDetailComponent {
    $nextTick: (callback?: () => void) => Promise<void>;
}

declare global {
    interface Window {
        App: {
            locale: string;
        };
    }
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
    nextCheckInHuman: null,
    interval: null,
    countdown: null,
    uptimeDowntimeData: {} as Record<string, any>,
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

    currentLocale: dateFnsLocales[window.App.locale] || enUS,

    _formatDurationFromMinutes(minutes: number): string {
        if (minutes < 0) return formatDuration({ minutes: 0 }, { locale: this.currentLocale });
        return formatDuration({ minutes: minutes }, { locale: this.currentLocale });
    },

    getThemeColors(this: MonitoringDetailComponent) {
        // This function was empty in the original JS, keeping it as is.
        return {};
    },

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

            this.incidents = responseData.map((incident: any) => ({
                ...incident,
                down_at: incident.down_at ? format(new Date(incident.down_at), 'dd.MM.yyyy HH:mm', { locale: this.currentLocale }) : null,
                up_at: incident.up_at ? format(new Date(incident.up_at), 'dd.MM.yyyy HH:mm', { locale: this.currentLocale }) : null,
                duration: this._formatDurationFromMinutes(incident.duration),
            }));
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
            let capped = responseData.slice(0, 24);
            if (capped.length < 24) {
                const missing = 24 - capped.length;
                // Fill missing hours with zero uptime and downtime
                const filler = Array.from({ length: missing }, () => ({ uptime: 0, downtime: 0 }));
                capped = [...capped, ...filler];
            }

            // Replace heatmap data with the capped and possibly filled array
            this.heatmap.splice(0, this.heatmap.length, ...capped);

            // Manually update heatmap dots if they are rendered outside Alpine's direct control
            const heatmapContainer = document.getElementById('monitoring-heatmap-detail');
            if (heatmapContainer) {
                heatmapContainer.innerHTML = ''; // Clear existing dots
                capped.forEach((point: { uptime: number; downtime: number; }) => {
                    const statusDot = document.createElement('div');

                    let bgColor;
                    if (point.uptime > point.downtime) {
                        bgColor = 'bg-green-500';
                    } else if (point.uptime < point.downtime) {
                        bgColor = 'bg-red-500';
                    } else {
                        bgColor = 'bg-gray-400';
                    }
                    statusDot.className = `h-6 w-3 rounded-xs ${bgColor}`;
                    heatmapContainer.appendChild(statusDot);
                });
            }

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

        this.countdown = setInterval(() => {
            if (this.lastCheckedAtDate) {
                const now = new Date();
                const diffInMinutes = Math.round((now.getTime() - this.lastCheckedAtDate.getTime()) / (1000 * 60));
                this.lastCheckedAt = this._formatDurationFromMinutes(diffInMinutes);
            }

            if (this.sinceDate) {
                const now = new Date();
                const diffInMinutes = Math.round((now.getTime() - this.sinceDate.getTime()) / (1000 * 60));
                this.since = this._formatDurationFromMinutes(diffInMinutes);
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
                this.lastCheckedAtHuman = format(new Date(responseData.checked_at), 'dd.MM.yyyy HH:mm', { locale: this.currentLocale });
            }

            if (responseData.next) {
                this.nextCheckInDate = new Date(responseData.next);
                this.nextCheckInHuman = format(new Date(responseData.next), 'dd.MM.yyyy HH:mm', { locale: this.currentLocale });
            }

            if (responseData.interval) {
                this.interval = responseData.interval;
            }

            this.startCountdown();
        } catch (_) {
            this.lastCheckedAt = null;
            this.nextCheckIn = null;
        }
    },

    // Loads uptime data for predefined intervals and supplements it with downtime duration
    async loadUptime(this: MonitoringDetailComponent): Promise<void> {
        const intervals = {
            '7': 7,
            '30': 30,
            '90': 90,
        };

        const promises = Object.entries(intervals).map(([label, days]) => fetch(`/api/monitorings/${monitoringId}/uptime-downtime?days=${days}`)
            .then(res => res.ok ? res.json() : null)
            .then(data => {
                if (data && data[label]) {
                    if (data[label].downtime && data[label].downtime.total_human !== undefined) {
                        data[label].downtime.total_human = this._formatDurationFromMinutes(data[label].downtime.total_human);
                    }
                    if (data[label].uptime && data[label].uptime.total_human !== undefined) {
                        data[label].uptime.total_human = this._formatDurationFromMinutes(data[label].uptime.total_human);
                    }
                }
                return { [label]: data };
            })
            .catch(() => ({ [label]: null }))
        );

        const results = await Promise.all(promises);

        this.uptimeDowntimeData = Object.assign({}, ...results);
    },

    // Loads SSL certificate status and related metadata
    async loadSslStatus(this: MonitoringDetailComponent): Promise<void> {
        try {
            const response = await fetch(`/api/monitorings/${monitoringId}/ssl`);
            const responseData = await response.json();
            this.sslValid = responseData.valid;
            this.sslExpiration = responseData.expiration;
            this.sslIssuer = responseData.issuer;
            this.sslIssueDate = responseData.issue_date;
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
                    labels: responseData.data.map((entry: { date: string; }) => entry.date),
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

        const formatDate = (date: Date) => date.toISOString().split('T')[0];

        try {
            const response = await fetch(`/api/monitorings/${monitoringId}/uptime-calendar?start_date=${formatDate(startDate)}&end_date=${formatDate(endDate)}`);
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            const responseData = await response.json();
            this.uptimeCalendarData = Object.keys(responseData).reduce((acc, monthYear) => {
                acc[monthYear] = {
                    ...responseData[monthYear],
                    days: responseData[monthYear].days.map((day: any) => ({
                        ...day,
                        date: format(new Date(day.date), 'dd.MM.yyyy', { locale: this.currentLocale }),
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

    beforeDestroy(this: MonitoringDetailComponent) {
        if (this.countdown) {
            clearInterval(this.countdown);
        }
    },

    init(this: MonitoringDetailComponent) {
        // Observe changes to the 'class' attribute of the <html> element
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                    // Re-load heatmap and chart when the theme changes
                    this.loadHeatmap();
                    this.loadPerformanceChart();
                }
            });
        });

        observer.observe(document.documentElement, { attributes: true });

        // Clear the interval when the component is destroyed
        window.addEventListener('beforeunload', () => this.beforeDestroy());
    },
    chartLabels: chartLabels
});
