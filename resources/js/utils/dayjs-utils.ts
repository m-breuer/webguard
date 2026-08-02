import dayjs from 'dayjs';
import duration from 'dayjs/plugin/duration';
import 'dayjs/locale/de';
import 'dayjs/locale/en';

dayjs.extend(duration);

export function getCurrentDayjsLocale(): string {
    const locale = window.App.locale || 'en';
    dayjs.locale(locale);

    return locale;
}

type DateInput = dayjs.Dayjs | Date | string | null;

function toDate(date: DateInput): Date | null {
    if (!date) {
        return null;
    }

    const value = dayjs(date);

    return value.isValid() ? value.toDate() : null;
}

function format(date: DateInput, options: Intl.DateTimeFormatOptions): string | null {
    const value = toDate(date);

    return value ? new Intl.DateTimeFormat(getCurrentDayjsLocale(), options).format(value) : null;
}

export function formatDate(date: DateInput): string | null {
    const value = typeof date === 'string' && /^\d{4}-\d{2}-\d{2}/.test(date)
        ? new Date(`${date.slice(0, 10)}T12:00:00`)
        : toDate(date);

    return value ? new Intl.DateTimeFormat(getCurrentDayjsLocale(), {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
    }).format(value) : null;
}

export function formatDateTime(date: DateInput, includeSeconds = false, timeZone?: string): string | null {
    return format(date, {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        ...(includeSeconds ? { second: '2-digit' } : {}),
        ...(timeZone ? { timeZone } : {}),
    });
}

export function formatMonthYear(date: DateInput): string | null {
    return format(date, {
        year: 'numeric',
        month: 'long',
    });
}

export function humanizeDuration(value: number, unit: dayjs.ManipulateType): string {
    const durationValue = dayjs.duration(value, unit);
    const days = durationValue.days();
    const hours = durationValue.hours();
    const minutes = durationValue.minutes();
    const locale = getCurrentDayjsLocale();
    const formatUnit = (amount: number, unit: Intl.NumberFormatOptions['unit']): string => new Intl.NumberFormat(locale, {
        style: 'unit',
        unit,
        unitDisplay: 'long',
    }).format(amount);
    const parts: string[] = [];

    if (days > 0) {
        parts.push(formatUnit(days, 'day'));
    }
    if (hours > 0) {
        parts.push(formatUnit(hours, 'hour'));
    }
    if (minutes > 0) {
        parts.push(formatUnit(minutes, 'minute'));
    }

    if (parts.length === 0) {
        return formatUnit(0, 'minute');
    }

    return new Intl.ListFormat(locale, { style: 'long', type: 'unit' }).format(parts);
}
