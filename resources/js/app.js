import '../css/app.css';
import './bootstrap';

// Prevent external browser extension errors (e.g. Chrome Web Vitals extension reportAllChanges bug) from polluting the console
if (typeof window !== 'undefined') {
    window.addEventListener('error', (e) => {
        if (e && (
            (e.message && e.message.includes('startTime')) ||
            (e.error && e.error.stack && e.error.stack.includes('reportAllChanges'))
        )) {
            e.preventDefault();
            e.stopImmediatePropagation();
            return true;
        }
    }, true);
}

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import { FontAwesomeIcon } from './plugins/fontawesome';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

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
            .component('font-awesome-icon', FontAwesomeIcon) 
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
