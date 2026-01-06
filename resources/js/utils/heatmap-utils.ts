export function getThemeColors(): { up: string; down: string; unknown: string } {
    const htmlElement = document.documentElement;
    const isDark = htmlElement.classList.contains('dark');
    return {
        up: 'bg-green-500',
        down: 'bg-red-500',
        unknown: isDark ? 'bg-gray-400' : 'bg-gray-300'
    };
}

export function renderHeatmap(container: HTMLElement, heatmapData: { uptime: number; downtime: number }[]): void {
    if (!container) return;

    container.innerHTML = '';
    let capped = Array.isArray(heatmapData) ? heatmapData.slice(0, 24) : [];
    while (capped.length < 24) {
        capped.push({ uptime: 0, downtime: 0 });
    }

    const colors = getThemeColors();

    capped.forEach((point: { uptime: number; downtime: number }) => {
        const statusDot = document.createElement('div');
        let bgColor;
        if (point.uptime > point.downtime) {
            bgColor = colors.up;
        } else if (point.uptime < point.downtime) {
            bgColor = colors.down;
        } else {
            bgColor = colors.unknown;
        }
        statusDot.className = `h-6 w-3 rounded-xs ${bgColor}`;
        container.appendChild(statusDot);
    });
}
