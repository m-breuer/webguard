import { page } from "$app/state";

export type DateTimeFormatOptions = Intl.DateTimeFormatOptions;

function interfaceLocale(): string {
    return page.data.locale === "de" ? "de-DE" : "en-US";
}

export function formatDateTime(
    value: string | null | undefined,
    fallback: string,
    options: DateTimeFormatOptions = { dateStyle: "medium", timeStyle: "short" },
): string {
    if (!value) {
        return fallback;
    }

    const date = new Date(value);

    return Number.isNaN(date.getTime()) ? fallback : new Intl.DateTimeFormat(interfaceLocale(), options).format(date);
}

export function formatMonthYear(value: Date): string {
    return new Intl.DateTimeFormat(interfaceLocale(), { month: "long", year: "numeric" }).format(value);
}
