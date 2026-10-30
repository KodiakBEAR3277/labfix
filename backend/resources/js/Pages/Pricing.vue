<script setup>
// Pages/Pricing.vue
// Path: resources/js/Pages/Pricing.vue
//
// Public pricing page for institutions considering LabFix. Page only for
// now, deliberately — no route, no nav link, and no backend behind the
// CTAs yet. Buttons currently point at the existing /register flow;
// once the approval-request + plan-selection backend exists, these will
// need to carry the chosen plan through to that flow instead of just
// linking to register directly.
//
// No real billing anywhere here — the annual/monthly toggle is purely
// cosmetic, client-side only, since nothing is actually charged yet.

import { ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import LandingLayout from '../Layouts/LandingLayout.vue'
import Icon from '../Components/Icon.vue'

const annual = ref(false)

const tiers = [
  {
    name: 'Free',
    slug: 'free',
    monthly: 0,
    annualMonthly: 0,
    tagline: 'Forever',
    description: 'For a single department piloting LabFix',
    features: [
      '1 lab',
      'Up to 30 pieces of equipment',
      'Up to 50 users',
      'Core ticket tracking',
      'Community support',
    ],
    cta: 'Get started',
    highlight: false,
  },
  {
    name: 'Standard',
    slug: 'standard',
    monthly: 19,
    annualMonthly: 15,
    tagline: 'Per institution, per month',
    description: 'For a growing IT department',
    features: [
      'Up to 5 labs',
      'Up to 300 pieces of equipment',
      'Up to 300 users',
      'Auto-escalation & auto-close rules',
      'Knowledge base',
      'Email support',
    ],
    cta: 'Get Standard',
    highlight: false,
  },
  {
    name: 'Pro',
    slug: 'pro',
    monthly: 49,
    annualMonthly: 39,
    tagline: 'Per institution, per month',
    description: 'For a full campus IT operation',
    features: [
      'Unlimited labs & equipment',
      'Unlimited users',
      'Mobile app with voice-assisted reporting',
      'Full transaction & audit history',
      'Priority support',
    ],
    cta: 'Get Pro',
    highlight: true,
  },
  {
    name: 'Enterprise',
    slug: 'enterprise',
    monthly: null,
    annualMonthly: null,
    tagline: 'Custom',
    description: 'For a university system with multiple campuses',
    features: [
      'Everything in Pro',
      'Dedicated onboarding',
      'Custom SLA',
      'Priority feature requests',
    ],
    cta: 'Contact us',
    highlight: false,
  },
]

function displayPrice(tier) {
  if (tier.monthly === null) return 'Custom'
  const price = annual.value ? tier.annualMonthly : tier.monthly
  return price === 0 ? '$0' : `$${price}`
}

function cardStyle(tier) {
  return {
    background: 'var(--color-surface-raised, rgba(0,0,0,0.03))',
    border: tier.highlight ? '2px solid var(--color-primary)' : '1px solid var(--color-border)',
    boxShadow: tier.highlight ? '0 0 24px rgba(45,212,191,0.18)' : 'none',
    borderRadius: 'var(--radius-lg, 12px)',
    padding: '1.75rem 1.5rem',
    display: 'flex',
    flexDirection: 'column',
    position: 'relative',
  }
}
</script>

<template>
  <LandingLayout>

    <section class="hero" style="padding-bottom: 1rem;">
      <div class="container" style="text-align: center;">
        <p style="color: var(--color-primary); font-weight: 600; font-size: var(--font-size-sm, 0.9rem); text-transform: uppercase; letter-spacing: 0.05em;">
          Institution Pricing
        </p>
        <h1 style="margin-top: 0.5rem;">Start free, upgrade when your campus needs more</h1>
        <p style="color: var(--color-text-secondary); max-width: 600px; margin: 1rem auto 0;">
          Every plan gets the full ticketing system. Higher tiers raise the limits and add
          more of what a growing IT department needs.
        </p>

        <div style="display: flex; align-items: center; justify-content: center; gap: 0.75rem; margin-top: 2rem;">
          <span :style="{ color: !annual ? 'var(--color-text-primary)' : 'var(--color-text-muted)', fontWeight: 600 }">
            Monthly
          </span>
          <button
            type="button"
            @click="annual = !annual"
            :style="{
              width: '46px', height: '26px', borderRadius: '999px', border: 'none', cursor: 'pointer',
              background: annual ? 'var(--color-primary)' : 'var(--color-border)',
              position: 'relative', transition: 'background 0.2s', padding: 0,
            }"
          >
            <span :style="{
              position: 'absolute', top: '3px', left: annual ? '23px' : '3px',
              width: '20px', height: '20px', borderRadius: '50%', background: '#fff',
              transition: 'left 0.2s', display: 'block',
            }" />
          </button>
          <span :style="{ color: annual ? 'var(--color-text-primary)' : 'var(--color-text-muted)', fontWeight: 600 }">
            Annual <span style="color: var(--color-primary);">(save ~20%)</span>
          </span>
        </div>
      </div>
    </section>

    <section class="features" style="padding-top: 1rem;">
      <div class="feature-container">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 1.5rem; margin-top: 1rem;">
          <div
            v-for="tier in tiers"
            :key="tier.name"
            :style="cardStyle(tier)"
          >
            <div
              v-if="tier.highlight"
              style="position: absolute; top: -0.75rem; left: 50%; transform: translateX(-50%); background: var(--color-primary); color: #fff; font-size: 0.75rem; font-weight: 700; padding: 0.25rem 0.75rem; border-radius: 999px;"
            >
              Most Popular
            </div>

            <h3 style="font-size: var(--font-size-lg, 1.1rem); font-weight: 700; color: var(--color-text-primary); margin-bottom: 0.5rem;">
              {{ tier.name }}
            </h3>

            <div style="font-size: 2rem; font-weight: 800; color: var(--color-text-primary);">
              {{ displayPrice(tier) }}
              <span v-if="tier.monthly !== null" style="font-size: 0.9rem; color: var(--color-text-muted); font-weight: 500;">/mo</span>
            </div>

            <p style="font-size: 0.8rem; color: var(--color-text-muted); margin-bottom: 1rem;">
              {{ tier.tagline }}
            </p>

            <p style="font-size: 0.9rem; color: var(--color-text-secondary); margin-bottom: 1.25rem; min-height: 2.5em;">
              {{ tier.description }}
            </p>

            <ul style="list-style: none; padding: 0; margin: 0 0 1.5rem; flex-grow: 1;">
              <li
                v-for="feature in tier.features"
                :key="feature"
                style="display: flex; align-items: flex-start; gap: 0.5rem; font-size: 0.9rem; color: var(--color-text-secondary); margin-bottom: 0.65rem;"
              >
                <Icon name="check" style="color: var(--color-primary); flex-shrink: 0; margin-top: 1px;" /> {{ feature }}
              </li>
            </ul>

            <Link
              :href="tier.cta === 'Contact us' ? '/contact' : `/register?plan=${tier.slug}`"
              :class="tier.highlight ? 'btn-primary' : 'btn-secondary'"
              style="display: block; text-align: center; text-decoration: none;"
            >
              {{ tier.cta }}
            </Link>
          </div>
        </div>

        <p style="text-align: center; color: var(--color-text-muted); margin-top: 2rem; font-size: 0.85rem;">
          New institutions are reviewed before going live, so support stays focused on real campuses.
        </p>
      </div>
    </section>

  </LandingLayout>
</template>