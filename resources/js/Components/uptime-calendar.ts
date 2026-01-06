import { getCurrentDayjsLocale } from "@/utils/dayjs-utils";
import dayjs from "dayjs";

interface DayUptime {
    date: string;
    uptime_percentage: number | null;
}

interface MonthUptime {
    days: DayUptime[];
    monthly_average_uptime: number | null;
}

interface CalendarData {
    [monthYear: string]: MonthUptime;
}

interface UptimeCalendarComponent {
    isLoading: boolean;
    calendarData: CalendarData | null;
    monitoringId: string;
    currentLocale: string;
    fetchUptimeCalendar(this: UptimeCalendarComponent): Promise<void>;
}

export default (monitoringId: string): UptimeCalendarComponent => ({
    isLoading: true,
    calendarData: null,
    monitoringId: monitoringId,
    currentLocale: getCurrentDayjsLocale(),

    async fetchUptimeCalendar() {
        this.isLoading = true;
        const endDate = new Date();
        const startDate = new Date();
        startDate.setMonth(startDate.getMonth() - 11);
        startDate.setDate(1);

        const formatDate = (date: Date) => date.toISOString().split('T')[0];

        try {
            const response = await fetch(`/api/monitorings/${this.monitoringId}/uptime-calendar?start_date=${formatDate(startDate)}&end_date=${formatDate(endDate)}`);
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            const responseData = await response.json();
            this.calendarData = Object.keys(responseData).reduce((acc, monthYear) => {
                acc[monthYear] = {
                    ...responseData[monthYear],
                    days: responseData[monthYear].days.map((day: any) => ({
                        ...day,
                        date: dayjs(day.date).locale(this.currentLocale).format('L'),
                    })),
                };
                return acc;
            }, {});
        } catch (error) {
            console.error('There has been a problem with your fetch operation:', error);
            this.calendarData = null; // Clear data on error
        } finally {
            this.isLoading = false;
        }
    }
});
