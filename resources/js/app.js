import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import { createPinia } from 'pinia';
import { startOfflineSyncEngine } from '@/offline/syncEngine';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

window.addEventListener('unhandledrejection', (event) => {
    const message = event?.reason?.message || String(event?.reason || '');
    if (message.includes('No checkout popup config found')) {
        event.preventDefault();
    }
});

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(createPinia());

        const disableVueDevtools = String(import.meta.env.VITE_DISABLE_VUE_DEVTOOLS ?? 'true') !== 'false';
        if (disableVueDevtools) {
            app.config.devtools = false;
        }

        return app.mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

const offlineReceiptsEnabled = String(import.meta.env.VITE_OFFLINE_RECEIPTS_ENABLED ?? 'true') !== 'false';

if (offlineReceiptsEnabled) {
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/sw.js').catch(() => {});
        });
    }

    startOfflineSyncEngine();
}
