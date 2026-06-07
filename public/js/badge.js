(() => {
    const containers = Array.from(new Set([
        ...document.querySelectorAll('[data-webguard-sla-badge]'),
        ...document.querySelectorAll('#webguard-sla-badge'),
    ]));

    if (containers.length === 0) {
        console.error('WebGuard SLA Badge: No badge container found.');
        return;
    }

    const scriptSource = document.currentScript?.src;

    if (!scriptSource) {
        console.error('WebGuard SLA Badge: Unable to determine the badge script source.');
        return;
    }

    const appBaseUrl = new URL(scriptSource);

    if (!document.getElementById('webguard-sla-badge-styles')) {
        const style = document.createElement('style');
        style.id = 'webguard-sla-badge-styles';
        style.textContent = `
            .wg-sla-badge {
                box-sizing: border-box;
                display: inline-flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 10px;
                max-width: 100%;
                border: 1px solid #d7dde8;
                border-radius: 999px;
                background: #ffffff;
                color: #172033;
                font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
                font-size: 13px;
                line-height: 1.25;
                padding: 8px 12px;
                text-decoration: none;
                box-shadow: 0 6px 18px rgba(15, 23, 42, 0.08);
            }
            .wg-sla-badge:hover {
                border-color: #aeb8c9;
            }
            .wg-sla-regular {
                border-radius: 12px;
                align-items: flex-start;
                flex-direction: column;
                min-width: 240px;
                padding: 12px;
            }
            .wg-sla-dark {
                border-color: #334155;
                background: #111827;
                color: #f8fafc;
                box-shadow: 0 6px 18px rgba(15, 23, 42, 0.24);
            }
            @media (prefers-color-scheme: dark) {
                .wg-sla-auto {
                    border-color: #334155;
                    background: #111827;
                    color: #f8fafc;
                    box-shadow: 0 6px 18px rgba(15, 23, 42, 0.24);
                }
            }
            .wg-sla-row {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 8px;
                min-width: 0;
            }
            .wg-sla-main {
                font-weight: 700;
                white-space: nowrap;
            }
            .wg-sla-meta {
                color: #64748b;
                white-space: nowrap;
            }
            .wg-sla-dark .wg-sla-meta {
                color: #cbd5e1;
            }
            @media (prefers-color-scheme: dark) {
                .wg-sla-auto .wg-sla-meta {
                    color: #cbd5e1;
                }
            }
            .wg-sla-dot {
                width: 9px;
                height: 9px;
                border-radius: 999px;
                flex: 0 0 auto;
                background: #94a3b8;
            }
            .wg-sla-status-up .wg-sla-dot,
            .wg-sla-status-success .wg-sla-dot {
                background: #10b981;
            }
            .wg-sla-status-down .wg-sla-dot,
            .wg-sla-status-danger .wg-sla-dot {
                background: #ef4444;
            }
            .wg-sla-status-maintenance .wg-sla-dot,
            .wg-sla-status-warning .wg-sla-dot {
                background: #f59e0b;
            }
            .wg-sla-brand {
                color: #475569;
                font-size: 11px;
                font-weight: 600;
                white-space: nowrap;
            }
            .wg-sla-dark .wg-sla-brand {
                color: #e2e8f0;
            }
            @media (prefers-color-scheme: dark) {
                .wg-sla-auto .wg-sla-brand {
                    color: #e2e8f0;
                }
            }
            .wg-sla-details {
                display: flex;
                flex-wrap: wrap;
                gap: 6px 10px;
                color: #64748b;
                font-size: 12px;
            }
            .wg-sla-dark .wg-sla-details {
                color: #cbd5e1;
            }
            @media (prefers-color-scheme: dark) {
                .wg-sla-auto .wg-sla-details {
                    color: #cbd5e1;
                }
            }
            .wg-sla-error {
                color: #b91c1c;
                font-family: Inter, ui-sans-serif, system-ui, sans-serif;
                font-size: 13px;
            }
        `;
        document.head.appendChild(style);
    }

    const allowedRanges = new Set(['30', '90', '365']);
    const formatUptime = (value) => typeof value === 'number' ? `${value.toFixed(2)}% uptime` : 'No uptime data';
    const normalizeStatusTone = (data) => {
        if (data.status_identifier === 'status.maintenance' || data.maintenance?.active) {
            return 'maintenance';
        }

        return typeof data.status === 'string' ? data.status : 'unknown';
    };
    const statusLabel = (data) => {
        if (data.status_identifier === 'status.maintenance' || data.maintenance?.active) {
            return 'Maintenance';
        }

        return typeof data.status_label === 'string' ? data.status_label : 'UNKNOWN';
    };

    const appendText = (parent, className, text) => {
        const element = document.createElement('span');
        element.className = className;
        element.textContent = text;
        parent.appendChild(element);

        return element;
    };

    const renderBadge = (container, data) => {
        const range = allowedRanges.has(container.dataset.range) ? container.dataset.range : '90';
        const theme = ['light', 'dark', 'auto'].includes(container.dataset.theme) ? container.dataset.theme : 'auto';
        const size = container.dataset.size === 'regular' ? 'regular' : 'compact';
        const layout = container.dataset.layout === 'rich' ? 'rich' : 'badge';
        const uptimeKey = `${range}_days`;
        const uptime = data.uptime?.[uptimeKey];
        const incidentsCount = data.incidents?.[uptimeKey];
        const checkedAt = data.checked_at_human ?? 'No checks yet';

        const badge = document.createElement('a');
        badge.className = [
            'wg-sla-badge',
            `wg-sla-${theme}`,
            `wg-sla-${size}`,
            `wg-sla-status-${normalizeStatusTone(data)}`,
        ].join(' ');
        badge.href = data.public_url ?? appBaseUrl.origin;
        badge.target = '_blank';
        badge.rel = 'noopener noreferrer';
        badge.setAttribute('aria-label', `${data.name ?? 'WebGuard monitor'} SLA status`);

        const row = document.createElement('span');
        row.className = 'wg-sla-row';
        appendText(row, 'wg-sla-dot', '');
        appendText(row, 'wg-sla-main', `${statusLabel(data)} · ${formatUptime(uptime)}`);
        appendText(row, 'wg-sla-meta', `${range} days`);
        badge.appendChild(row);

        if (layout === 'rich' || size === 'regular') {
            const details = document.createElement('span');
            details.className = 'wg-sla-details';
            appendText(details, '', `Last checked ${checkedAt}`);

            if (typeof incidentsCount === 'number') {
                appendText(details, '', incidentsCount === 0 ? `0 incidents in ${range} days` : `${incidentsCount} incidents in ${range} days`);
            }

            if (data.ssl?.expires_at) {
                appendText(details, '', `SSL valid until ${new Date(data.ssl.expires_at).toLocaleDateString()}`);
            }

            if (data.domain?.expires_at) {
                appendText(details, '', `Domain valid until ${new Date(data.domain.expires_at).toLocaleDateString()}`);
            }

            badge.appendChild(details);
        }

        appendText(badge, 'wg-sla-brand', 'Verified by WebGuard');
        container.replaceChildren(badge);
    };

    const renderError = (container) => {
        const error = document.createElement('span');
        error.className = 'wg-sla-error';
        error.textContent = 'WebGuard SLA Badge unavailable.';
        container.replaceChildren(error);
    };

    const fetchAndRender = (container) => {
        const monitoringId = container.dataset.monitoring;

        if (!monitoringId) {
            console.error("WebGuard SLA Badge: 'data-monitoring' attribute is missing.");
            renderError(container);
            return;
        }

        const apiUrl = new URL(`/api/public/monitorings/${encodeURIComponent(monitoringId)}/badge`, appBaseUrl).toString();

        fetch(apiUrl)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                return response.json();
            })
            .then(data => renderBadge(container, data))
            .catch(error => {
                console.error('WebGuard SLA Badge: Error fetching data:', error);
                renderError(container);
            });
    };

    containers.forEach(container => {
        fetchAndRender(container);
        setInterval(() => fetchAndRender(container), 300000);
    });
})();
