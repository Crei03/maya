import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import VueApexCharts from 'vue3-apexcharts';
import { initializeThemeOnLoad } from './Composables/useTheme.js';
import FontAwesomePlugin from './Plugins/fontawesome.js';

// Inicializar tema inmediatamente para evitar flash de tema incorrecto
initializeThemeOnLoad();

const appName = import.meta.env.VITE_APP_NAME || 'MAYA';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(VueApexCharts)
            .use(FontAwesomePlugin)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
