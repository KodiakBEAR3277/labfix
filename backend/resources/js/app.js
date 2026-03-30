// resources/js/app.js
//
// Single entry point — Inertia only. Vue Router has been removed.
// All pages (Landing, Contact, Login, Register, and all authenticated pages)
// are now served through inertia.blade.php and resolved here.
//
// Page component resolution uses Vite's import.meta.glob to automatically
// find any .vue file under resources/js/Pages/. The path Inertia passes
// from the controller (e.g. 'Landing', 'Auth/Login', 'User/Dashboard')
// maps directly to the file at Pages/{name}.vue.

import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'

createInertiaApp({
    // Resolve page components from resources/js/Pages/
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),

    // Mount the app
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el)
    },

    // Optional: sets the document <title> — uses the page's `title` prop if set,
    // falls back to 'LabFix'. Pages can override via <Head title="..."> from
    // @inertiajs/vue3 if you add that later.
    title: (title) => title ? `${title} — LabFix` : 'LabFix',
})