import './bootstrap';
import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import LoadingOverlay from './Components/LoadingOverlay.vue';

createInertiaApp({
    title: (title) => `${title} — Payproxy`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const RootComponent = {
            render: () => [h(App, props), h(LoadingOverlay)],
        };

        return createApp(RootComponent)
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: false,
});
