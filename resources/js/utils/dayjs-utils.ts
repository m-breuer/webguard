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
    return dayjs.duration(value, unit).locale(getCurrentDayjsLocale()).humanize();
}

export function humanizeDistance(date: dayjs.Dayjs | string, options?: { withoutSuffix?: boolean }): string {
    return dayjs(date).locale(getCurrentDayjsLocale()).fromNow(options?.withoutSuffix);
}

