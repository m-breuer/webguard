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
