
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import { NConfigProvider, NGlobalStyle, NMessageProvider, NModalProvider } from 'naive-ui';
import { naiveThemeOverrides } from "@/lib/naiveTheme.js";

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        return createApp({
            render: () =>
                h(
                    NConfigProvider,
                    {
                        themeOverrides: naiveThemeOverrides,
                    },
                    {
                        default: () => [
                            h(
                                NMessageProvider,
                                null,
                                {
                                    default: () =>
                                        h(
                                            NModalProvider,
                                            null,
                                            {
                                                default: () =>
                                                    h(App, props),
                                            }
                                        ),
                                }
                            ),
                        ]
                    }
                )
        })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
