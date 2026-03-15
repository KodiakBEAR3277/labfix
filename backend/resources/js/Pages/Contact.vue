<script setup>
// Contact.vue
// Mirrors: resources/views/contact.blade.php
// Layout:  LandingLayout.vue
//
// Fetches live contact info (email, phone, system name) from the Laravel
// API endpoint GET /api/contact-info, which reads from the settings table.
// Falls back to sensible defaults while loading or if the request fails.

import { ref, onMounted } from 'vue'
import LandingLayout from '../Layouts/LandingLayout.vue'

// Reactive contact info — starts with fallbacks, replaced on load
const contactInfo = ref({
    system_name:   'LabFix',
    support_email: 'support@labfix.edu',
    support_phone: '',
})
const loading = ref(true)
const error   = ref(false)

onMounted(async () => {
    try {
        const res  = await fetch('/api/contact-info')
        if (!res.ok) throw new Error('Non-OK response')
        const data = await res.json()
        contactInfo.value = data
    } catch (e) {
        // Non-fatal: fallback values are still shown
        error.value = true
    } finally {
        loading.value = false
    }
})

// Static contact cards — email/phone slots are filled from contactInfo
// The structure mirrors the original contact.blade.php layout exactly
const socialLinks = [
    { icon: '📘', label: 'Facebook',  href: '#' },
    { icon: '🐦', label: 'Twitter',   href: '#' },
    { icon: '💼', label: 'LinkedIn',  href: '#' },
    { icon: '📸', label: 'Instagram', href: '#' },
]
</script>

<template>
    <div class="contact-page">
        <div class="contact-container">

            <!-- Back link -->
            <a href="/" class="back-link">← Back to Home</a>

            <!-- Header -->
            <div class="contact-header">
                <h1>Contact Us</h1>
                <p>
                    Get in touch with the
                    <!-- Show system name from settings once loaded -->
                    <span v-if="!loading">{{ contactInfo.system_name }}</span>
                    <span v-else>LabFix</span>
                    support team
                </p>
            </div>

            <!-- Contact cards -->
            <div class="contact-grid">

                <!-- Email card — data from settings -->
                <div class="contact-card">
                    <div class="contact-icon">📧</div>
                    <h3>Email Support</h3>
                    <p>Send us an email and we'll get back to you within 24 hours during business days.</p>
                    <a
                        v-if="!loading"
                        :href="`mailto:${contactInfo.support_email}`"
                        class="contact-link"
                    >
                        {{ contactInfo.support_email }}
                    </a>
                    <!-- Skeleton while loading -->
                    <span v-else class="contact-link" style="opacity: 0.4; cursor: default;">
                        Loading...
                    </span>
                </div>

                <!-- Phone card — data from settings -->
                <div class="contact-card">
                    <div class="contact-icon">📞</div>
                    <h3>Phone Support</h3>
                    <p>Call us during office hours, Monday to Friday, 8AM to 5PM.</p>
                    <a
                        v-if="!loading && contactInfo.support_phone"
                        :href="`tel:${contactInfo.support_phone}`"
                        class="contact-link"
                    >
                        {{ contactInfo.support_phone }}
                    </a>
                    <span v-else-if="loading" class="contact-link" style="opacity: 0.4; cursor: default;">
                        Loading...
                    </span>
                    <span v-else class="contact-link" style="opacity: 0.5; cursor: default;">
                        Not available
                    </span>
                </div>

                <!-- Location card — static -->
                <div class="contact-card">
                    <div class="contact-icon">📍</div>
                    <h3>Visit Us</h3>
                    <p>Find us at the IT Support office on campus during office hours.</p>
                    <span class="contact-link">IT Department Office</span>
                </div>

            </div>

            <!-- Social links -->
            <div class="social-section">
                <h2>Follow Us</h2>
                <div class="social-links">
                    <a
                        v-for="social in socialLinks"
                        :key="social.label"
                        :href="social.href"
                        :title="social.label"
                        class="social-btn"
                    >
                        {{ social.icon }}
                    </a>
                </div>
            </div>

            <!-- Quick help CTA -->
            <div class="quick-help">
                <h2>Need Quick Help?</h2>
                <p>
                    Before reaching out, check our Knowledge Base — many common lab
                    equipment issues are already documented with step-by-step fixes.
                </p>
                <a href="/login" class="btn-help">Browse Knowledge Base</a>
            </div>

        </div>
    </div>
</template>