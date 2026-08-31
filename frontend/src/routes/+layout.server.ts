import type { LayoutServerLoad } from "./$types";

export const load: LayoutServerLoad = ({ cookies, request }) => {
    const cookieLocale = cookies.get("webguard_locale");
    const preferredLocale = request.headers.get("accept-language")?.toLowerCase().startsWith("de") ? "de" : "en";

    return { locale: cookieLocale === "de" || cookieLocale === "en" ? cookieLocale : preferredLocale };
};
