<script setup>
// Contact.vue
// Mirrors: resources/views/contact.blade.php
// Layout:  LandingLayout.vue
//
// Public SPA zone — uses Vue Router's <RouterLink> for navigation within
// the public zone (/contact → /). Cross-zone links like /login remain
// plain <a> since they need a full reload to boot Inertia.

import { ref, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import LandingLayout from '../Layouts/LandingLayout.vue'

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
        error.value = true
    } finally {
        loading.value = false
    }
})

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

            <!-- RouterLink: stays within the public Vue Router SPA zone -->
            <RouterLink to="/" class="back-link">← Back to Home</RouterLink>

            <div class="contact-header">
                <h1>Contact Us</h1>
                <p>
                    Get in touch with the
                    <span v-if="!loading">{{ contactInfo.system_name }}</span>
                    <span v-else>LabFix</span>
                    support team
                </p>
            </div>

            <div class="contact-grid">

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
                    <span v-else class="contact-link" style="opacity:0.4;cursor:default;">
                        Loading...
                    </span>
                </div>

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
                    <span v-else-if="loading" class="contact-link" style="opacity:0.4;cursor:default;">
                        Loading...
                    </span>
                    <span v-else class="contact-link" style="opacity:0.5;cursor:default;">
                        Not available
                    </span>
                </div>

                <div class="contact-card">
                    <div class="contact-icon">📍</div>
                    <h3>Visit Us</h3>
                    <p>Find us at the IT Support office on campus during office hours.</p>
                    <span class="contact-link">IT Department Office</span>
                </div>

            </div>

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

            <div class="quick-help">
                <h2>Need Quick Help?</h2>
                <p>
                    Before reaching out, check our Knowledge Base — many common lab
                    equipment issues are already documented with step-by-step fixes.
                </p>
                <!-- /login crosses into the Inertia zone — plain <a> required -->
                <a href="/login" class="btn-help">Browse Knowledge Base</a>
            </div>

        </div>
    </div>
</template>