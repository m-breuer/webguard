// Helper to get theme from data-theme attribute (for Alpine.js x-data initialization)
window.getThemeFromCookie = function(): string | undefined {
    const html = document.documentElement;
    return html.dataset.theme;
};

function applyTheme(theme: string): void {
    const html = document.documentElement;
    const isSystemDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

    if (theme === 'dark' || (theme === 'system' && isSystemDark)) {
        html.classList.add('dark');
    } else {
        html.classList.remove('dark');
    }
}

function initializeTheme(): void {
    // The theme is now set by the backend in the html tag's data-theme attribute
    // We just need to make sure the correct class is applied based on this attribute
    const html = document.documentElement;
    const sessionTheme = html.dataset.theme || 'system';
    applyTheme(sessionTheme);
}

document.addEventListener('DOMContentLoaded', initializeTheme);

window.setTheme = function(theme: string): void {
    // When theme is changed by user, it's saved to the database and applied on next request
    // For immediate visual feedback, we apply it directly
    applyTheme(theme);
};

// Listen for system theme changes
window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e: MediaQueryListEvent) => {
    // If the current theme is 'system', re-apply it to reflect system changes
    const html = document.documentElement;
    const currentTheme = html.dataset.theme || 'system';
    if (currentTheme === 'system') {
        applyTheme('system');
    }
});