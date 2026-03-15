// ============================================
// LabFix - Vue Router
// File: resources/js/router/index.js
// ============================================
//
// Only routes that have been migrated to Vue live here.
// Blade routes (dashboards, tickets, etc.) are still handled
// by Laravel's web.php and are NOT listed here.
//
// As more pages get migrated, add them to this file.

import { createRouter, createWebHistory } from 'vue-router'

// Lazy-load each page — Vite only downloads the component when needed
const Landing = () => import('../Pages/Landing.vue')
const Contact = () => import('../Pages/Contact.vue')

// Auth pages (stubs — add the actual components when ready)
const Login    = () => import('../Pages/Auth/Login.vue')
const Register = () => import('../Pages/Auth/Register.vue')

const routes = [
    {
        path: '/',
        name: 'landing',
        component: Landing,
    },
    {
        path: '/contact',
        name: 'contact',
        component: Contact,
    },
    {
        path: '/login',
        name: 'login',
        component: Login,
    },
    {
        path: '/register',
        name: 'register',
        component: Register,
    },
]

const router = createRouter({
    // createWebHistory() uses real URLs (no hash).
    // Requires Laravel to return view('entry') for all Vue-handled paths.
    history: createWebHistory(),
    routes,

    // Scroll to top on every navigation, but honour anchor links (e.g. /#features)
    scrollBehavior(to, from, savedPosition) {
        if (savedPosition) return savedPosition
        if (to.hash) return { el: to.hash, behavior: 'smooth' }
        return { top: 0 }
    },
})

export default router