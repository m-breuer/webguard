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
import dashboardLoader from './components/dashboard-loader';
import incidentAnalyticsLoader from './components/incident-analytics-loader';
import { initializeMonitoringTypeFields } from './components/monitoring-type-fields';

Alpine.data('monitoringDetail', monitoringDetail);
Alpine.data('monitoringCardLoader', monitoringCardLoader);
Alpine.data('uptimeCalendar', uptimeCalendar);
Alpine.data('demoLogin', demoLogin);
Alpine.data('asyncTable', asyncTable);
Alpine.data('maintenancePage', maintenancePage);
Alpine.data('confirmDialog', confirmDialog);
Alpine.data('formModalLoader', formModalLoader);
Alpine.data('signalRoom', signalRoom);
Alpine.data('dashboardLoader', dashboardLoader);
Alpine.data('incidentAnalyticsLoader', incidentAnalyticsLoader);

window.Alpine = Alpine;
window.Chart = Chart;

registerConfirmableForms({
    title: document.documentElement.dataset.confirmTitle ?? 'Confirm action',
    confirm: document.documentElement.dataset.confirmConfirm ?? 'Confirm',
    cancel: document.documentElement.dataset.confirmCancel ?? 'Cancel',
});

Alpine.start();

initializeMonitoringTypeFields();
