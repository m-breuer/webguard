import './components/ThemeSwitcher';

import.meta.glob([
    '../images/**',
    '../fonts/**',
]);

import './bootstrap';

import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';

import monitoringCardLoader from './components/monitoring-cards';
import monitoringDetail from './components/monitoring-detail';
import uptimeCalendar from './components/uptime-calendar';
import demoLogin from './components/demoLogin';
import asyncTable from './components/async-table';
import maintenancePage from './components/maintenance-page';
import confirmDialog, { registerConfirmableForms } from './components/confirm-dialog';
import formModalLoader from './components/form-modal-loader';
import signalRoom from './components/signal-room';
import serviceMapLoader from './components/service-map-loader';
import incidentAnalyticsLoader from './components/incident-analytics-loader';

const decodeImprintPayload = (payload: string): string => {
    try {
        const raw = atob(payload);
        const reversed = raw.split('').reverse().join('');

        return reversed.replace(/[a-zA-Z]/g, (char: string) => {
            const base = char <= 'Z' ? 65 : 97;
            return String.fromCharCode(((char.charCodeAt(0) - base + 13) % 26) + base);
        });
    } catch {
        return '';
    }
};

document.addEventListener('click', (event: MouseEvent): void => {
    const target = event.target;
    if (!(target instanceof Element)) {
        return;
    }

    const revealButton = target.closest<HTMLElement>('[data-imprint-reveal]');
    if (!revealButton) {
        return;
    }

    const email = decodeImprintPayload(revealButton.dataset.emailPayload ?? '');
    const phone = decodeImprintPayload(revealButton.dataset.phonePayload ?? '');
    const emailTarget = document.getElementById('imprint-email');
    const phoneTarget = document.getElementById('imprint-phone');

    if (emailTarget && email !== '') {
        emailTarget.innerHTML =
            `<a href="mailto:${email}" class="text-emerald-700 underline-offset-4 hover:underline dark:text-emerald-300">${email}</a>`;
    }

    if (phoneTarget && phone !== '') {
        const phoneHref = phone.replace(/[^0-9+]/g, '');
        phoneTarget.innerHTML =
            `<a href="tel:${phoneHref}" class="text-emerald-700 underline-offset-4 hover:underline dark:text-emerald-300">${phone}</a>`;
    }

    revealButton.remove();
});

Alpine.data('monitoringDetail', monitoringDetail);
Alpine.data('monitoringCardLoader', monitoringCardLoader);
Alpine.data('uptimeCalendar', uptimeCalendar);
Alpine.data('demoLogin', demoLogin);
Alpine.data('asyncTable', asyncTable);
Alpine.data('maintenancePage', maintenancePage);
Alpine.data('confirmDialog', confirmDialog);
Alpine.data('formModalLoader', formModalLoader);
Alpine.data('signalRoom', signalRoom);
Alpine.data('serviceMapLoader', serviceMapLoader);
Alpine.data('incidentAnalyticsLoader', incidentAnalyticsLoader);

window.Alpine = Alpine;
window.Chart = Chart;

registerConfirmableForms({
    title: document.documentElement.dataset.confirmTitle ?? 'Confirm action',
    confirm: document.documentElement.dataset.confirmConfirm ?? 'Confirm',
    cancel: document.documentElement.dataset.confirmCancel ?? 'Cancel',
});

Alpine.start();
