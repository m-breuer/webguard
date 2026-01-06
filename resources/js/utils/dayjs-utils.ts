import dayjs from 'dayjs';
import localizedFormat from 'dayjs/plugin/localizedFormat';
import duration from 'dayjs/plugin/duration';
import relativeTime from 'dayjs/plugin/relativeTime';
import 'dayjs/locale/de'; // Import German locale data

dayjs.extend(localizedFormat);
dayjs.extend(duration);
dayjs.extend(relativeTime);

declare global {
    interface Window {
        App: {
            locale: string;
        };
    }
}

export function getCurrentDayjsLocale(): string {
    const locale = window.App.locale || 'en';
    dayjs.locale(locale); // Set global locale for dayjs
    return locale;
}

export function formatDateForDisplay(date: dayjs.Dayjs | string | null, formatStr: string): string | null {
    if (!date) {
        return null;
    }
    return dayjs(date).locale(getCurrentDayjsLocale()).format(formatStr);
}

export function formatDurationForDisplay(minutes: number): string {
    // dayjs duration can directly convert minutes to humanized string
    return dayjs.duration(minutes, 'minutes').locale(getCurrentDayjsLocale()).humanize();
}

export function formatDistanceForDisplay(date: dayjs.Dayjs | string): string {
    return dayjs(date).locale(getCurrentDayjsLocale()).fromNow();
}

