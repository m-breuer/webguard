import { page } from "$app/state";

export type DateTimeFormatOptions = Intl.DateTimeFormatOptions;

function interfaceLocale(): string {
    return page.data.locale === "de" ? "de-DE" : "en-US";
}

export function formatDateTime(
    value: string | null,
    fallback: string,
    options: DateTimeFormatOptions = { dateStyle: "medium", timeStyle: "short" },
): string {
    return value ? new Intl.DateTimeFormat(interfaceLocale(), options).format(new Date(value)) : fallback;
}

export function formatMonthYear(value: Date): string {
    return new Intl.DateTimeFormat(interfaceLocale(), { month: "long", year: "numeric" }).format(value);
}
