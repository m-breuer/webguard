import dayjs from 'dayjs';
import localizedFormat from 'dayjs/plugin/localizedFormat';
import duration from 'dayjs/plugin/duration';
import relativeTime from 'dayjs/plugin/relativeTime';
import 'dayjs/locale/de'; // Import German locale data
import 'dayjs/locale/en';

dayjs.extend(localizedFormat);
dayjs.extend(duration);
dayjs.extend(relativeTime);

export function getCurrentDayjsLocale(): string {
    const locale = window.App.locale || 'en';
    dayjs.locale(locale); // Set global locale for dayjs
    return locale;
}

export function formatDate(date: dayjs.Dayjs | string | null, formatStr: string): string | null {
    if (!date) {
        return null;
    }
    return dayjs(date).locale(getCurrentDayjsLocale()).format(formatStr);
}

export function humanizeDuration(value: number, unit: dayjs.ManipulateType): string {
    const duration = dayjs.duration(value, unit);

    const days = duration.days();
    const hours = duration.hours();
    const minutes = duration.minutes();

    const parts = [];
    if (days > 0) {
        parts.push(`${days} day${days > 1 ? 's' : ''}`);
    }
    if (hours > 0) {
        parts.push(`${hours} hour${hours > 1 ? 's' : ''}`);
    }
    if (minutes > 0) {
        parts.push(`${minutes} minute${minutes > 1 ? 's' : ''}`);
    }

    if (parts.length === 0) {
        return 'less than a minute';
    }

    return parts.join(' ');
}

export function humanizeDistance(date: dayjs.Dayjs | string, options?: { withoutSuffix?: boolean }): string {
    return dayjs(date).locale(getCurrentDayjsLocale()).fromNow(options?.withoutSuffix);
}

