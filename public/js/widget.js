if (!window.webguardWidgetInitialized) {
    window.webguardWidgetInitialized = true;

    (() => {
        const widgetContainer = document.getElementById('webguard-widget');
        const monitoringId = widgetContainer.dataset.monitoring;

        if (!monitoringId) {
            console.error("WebGuard Widget: 'data-monitoring' attribute is missing on the script tag.");
            return;
        }

        if (!widgetContainer) {
            console.error("WebGuard Widget: Element with ID 'webguard-widget' not found.");
            return;
        }

        // Inject CSS
        const style = document.createElement('style');
        style.innerHTML = `
            .wg-widget-container {
                font-family: 'Inter', sans-serif;
                border: 1px solid #e0e0e0;
                border-radius: 12px; /* Slightly more rounded corners */
                padding: 1.5rem;
                display: flex;
                flex-direction: column;
                gap: 1rem; /* Increased gap for better spacing */
                box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08); /* More pronounced shadow */
                background-color: #ffffff;
                color: #333333;
                max-width: 340px; /* Slightly wider for better readability */
                line-height: 1.5;
            }
            .wg-widget-container p {
                margin: 0;
            }
            .wg-heading {
                font-size: 1.6rem; /* Larger heading */
                font-weight: 700;
                margin-bottom: 0.5rem; /* Space below heading */
                color: #222222;
                border-bottom: 1px solid #f0f0f0; /* Subtle separator */
                padding-bottom: 0.5rem;
            }
            .wg-status-line {
                display: flex;
                align-items: center;
                gap: 0.75rem;
                font-size: 1.1rem;
                font-weight: 600;
            }
            .wg-label {
                font-weight: 600;
                color: #555555;
            }
            .wg-status-text {
                font-weight: 800;
                text-transform: uppercase;
                padding: 0.25rem 0.6rem;
                border-radius: 6px;
                display: inline-block;
            }
            .wg-status-up {
                color: #1e7e34;
                background-color: #e6ffe6;
            }
            .wg-status-down {
                color: #a71d2a;
                background-color: #ffe6e6;
            }
            .wg-info-line {
                font-size: 0.95rem;
                color: #555555;
            }
            .wg-uptime-grid {
                display: flex;
                flex-wrap: wrap;
                gap: 0.75rem;
                margin-top: 0.5rem;
            }
            .wg-uptime-item {
                font-size: 0.9rem;
                color: #666666;
                background-color: #f0f0f0;
                padding: 0.5rem 1rem;
                border-radius: 6px;
                border: 1px solid #e5e5e5;
                flex-grow: 1;
                text-align: center;
            }
            .wg-error-message {
                font-family: 'Inter', sans-serif;
                color: #dc3545;
                padding: 1rem;
                border: 1px solid #f5c6cb;
                background-color: #f8d7da;
                border-radius: 8px;
            }
            .wg-footer {
                margin-top: 1.5rem;
                text-align: center;
                padding-top: 1rem;
                border-top: 1px solid #f0f0f0;
            }
            .wg-footer-link {
                font-size: 0.85rem;
                color: #888888;
                text-decoration: none;
                transition: color 0.2s ease-in-out;
            }
            .wg-footer-link:hover {
                color: #555555;
            }
        `;
        document.head.appendChild(style);

        const apiUrl = `https://webguard.m-breuer.dev/api/v1/monitorings/${monitoringId}/widget/`;

        const fetchAndRenderWidget = () => {
            widgetContainer.innerHTML = '<p class="wg-info-line">Loading WebGuard Widget...</p>';

            fetch(apiUrl)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    widgetContainer.innerHTML = `
                        <div class="wg-widget-container">
                            <h3 class="wg-heading">${data.name}</h3>
                            <p class="wg-status-line"><span class="wg-label">Status:</span> <span class="wg-status-text wg-status-${data.status === 'UP' ? 'up' : 'down'}">${data.status}</span></p>
                            <p class="wg-info-line"><span class="wg-label">Last Checked:</span> ${data.last_checked_at}</p>
                            <div class="wg-uptime-grid">
                                <p class="wg-uptime-item"><span class="wg-label">Uptime (7 Days):</span> ${data.uptime['7_days']}%</p>
                                <p class="wg-uptime-item"><span class="wg-label">Uptime (30 Days):</span> ${data.uptime['30_days']}%</p>
                                <p class="wg-uptime-item"><span class="wg-label">Uptime (365 Days):</span> ${data.uptime['365_days']}%</p>
                            </div>
                            <div class="wg-footer">
                                <a href="https://webguard.m-breuer.dev" target="_blank" class="wg-footer-link">Powered by WebGuard</a>
                            </div>
                        </div>
                    `;
                })
                .catch(error => {
                    console.error('WebGuard Widget: Error fetching data:', error);
                    widgetContainer.innerHTML = '<p class="wg-error-message">Error loading widget data.</p>';
                });
        };

        // Initial render
        fetchAndRenderWidget();

        // Refresh every 15 minutes
        setInterval(fetchAndRenderWidget, 900000);
    })();
}
