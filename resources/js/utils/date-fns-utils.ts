import { format, formatDistanceToNowStrict, formatDuration, Locale } from 'date-fns';
import { enUS, de } from 'date-fns/locale'; // Import locales as needed

// Map Laravel locales to date-fns locales
const dateFnsLocales: { [key: string]: Locale } = {
    'en': enUS,
    'de': de,
    // Add other locales as needed
};

declare global {
    interface Window {
        App: {
            locale: string;
        };
    }
}

export function getCurrentDateFnsLocale(): Locale {
    return dateFnsLocales[window.App.locale] || enUS;
}

export function formatDateForDisplay(date: Date | string | null, formatStr: string): string | null {
    if (!date) {
        return null;
    }
    const parsedDate = typeof date === 'string' ? new Date(date) : date;
    return format(parsedDate, formatStr, { locale: getCurrentDateFnsLocale() });
}

export function formatDurationForDisplay(minutes: number): string {
    if (minutes < 0) return formatDuration({ minutes: 0 }, { locale: getCurrentDateFnsLocale() });
    return formatDuration({ minutes: minutes }, { locale: getCurrentDateFnsLocale() });
}

export function formatDistanceForDisplay(date: Date | string): string {
    const parsedDate = typeof date === 'string' ? new Date(date) : date;
    return formatDistanceToNowStrict(parsedDate, { addSuffix: true, locale: getCurrentDateFnsLocale() });
}
