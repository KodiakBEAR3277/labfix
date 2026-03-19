// ============================================
// LabFix - Entry Point
// resources/js/app.js
// ============================================
//
// This file boots BOTH:
//   1. Vue Router (for public pages: /, /contact, /login, /register)
//      → served by entry.blade.php via the catch-all route
//   2. Inertia (for authenticated CRUD pages: /user/*, /profile/*, etc.)
//      → served by inertia.blade.php via their specific web.php routes
//
// Inertia detects its own root div (#app with data-page attribute).
// Vue Router detects the absence of that attribute.
// They do not conflict.

import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { createRouter, createWebHistory } from 'vue-router'

// ── Inertia pages (authenticated zone) ──────────────────────────────────────
// resolvePageComponent looks in resources/js/Pages/ automatically.
// So Inertia::render('User/Dashboard') maps to Pages/User/Dashboard.vue

const isInertiaPage = document.getElementById('app')?.hasAttribute('data-page')

if (isInertiaPage) {
    createInertiaApp({
        resolve: name =>
            resolvePageComponent(
                `./Pages/${name}.vue`,
                import.meta.glob('./Pages/**/*.vue')
            ),
        setup({ el, App, props, plugin }) {
            createApp({ render: () => h(App, props) })
                .use(plugin)
                .mount(el)
        },
    })
} else {
    // ── Vue Router (public pages) ────────────────────────────────────────────
    import('./router/index.js').then(({ default: router }) => {
        import('./App.vue').then(({ default: App }) => {
            createApp(App).use(router).mount('#app')
        })
    })
}