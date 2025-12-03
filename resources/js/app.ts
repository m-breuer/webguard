import.meta.glob([
    '../images/**',
    '../fonts/**'
]);

import './bootstrap';

import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';

import './components/ThemeSwitcher';

import monitoringCardLoader from './components/monitoring-cards';
import monitoringDetail from './components/monitoring-detail';
import uptimeCalendar from './components/uptime-calendar';
import guestLogin from './components/guestLogin';

Alpine.data('monitoringDetail', monitoringDetail);
Alpine.data('monitoringCardLoader', monitoringCardLoader);
Alpine.data('uptimeCalendar', uptimeCalendar);
Alpine.data('guestLogin', guestLogin);

window.Alpine = Alpine;
window.Chart = Chart;

Alpine.start();
