// ============================================
// LabFix - Vue Entry Point
// File: resources/js/app.js
// ============================================

import { createApp } from 'vue'
import App from './App.vue'
import router from './router/index.js'

createApp(App)
    .use(router)
    .mount('#app')