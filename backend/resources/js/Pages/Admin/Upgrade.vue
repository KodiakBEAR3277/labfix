<script setup>
// Pages/Admin/Upgrade.vue
// Path: resources/js/Pages/Admin/Upgrade.vue
//
// Tier cards built straight from config/plans.php (via UpgradeController),
// not a separately-maintained copy of Pricing.vue's marketing text. That
// means this page can never show a limit that doesn't match what
// LabController/EquipmentController/UserController actually enforce —
// if the config changes, this page changes with it automatically.
//
// No payment processing — "upgrading" is a manual, talk-to-us step for
// now, same as a new institution's approval already is. The CTA goes to
// the existing Contact page rather than pretending to process anything.

import AppLayout from '../../Layouts/AppLayout.vue'
import NavAdmin from '../../Components/Nav/NavAdmin.vue'
import { Link } from '@inertiajs/vue3'
import Icon from '../../Components/Icon.vue'

const props = defineProps({
  currentPlan: String,
  tiers:       Array,
  usage:       Object,
})

function usageText(u) {
  return `${u.current} / ${u.max ?? '∞'}`
}

function limitLine(value, singular, plural) {
  if (value === null) return `Unlimited ${plural}`
  return `${value} ${value === 1 ? singular : plural}`
}

function features(tier) {
  const list = [
    limitLine(tier.max_labs, 'lab', 'labs'),
    limitLine(tier.max_equipment, 'piece of equipment', 'pieces of equipment'),
    limitLine(tier.max_users, 'user', 'users'),
  ]
  if (tier.auto_rules) list.push('Auto-close & auto-escalate rules')
  if (tier.knowledge_base) list.push('Knowledge base')
  return list
}

function cardStyle(tier) {
  const current = tier.slug === props.currentPlan
  return {
    background: 'var(--color-surface-raised, rgba(0,0,0,0.03))',
    border: current ? '2px solid var(--color-primary)' : '1px solid var(--color-border)',
    boxShadow: current ? '0 0 24px rgba(45,212,191,0.18)' : 'none',
    borderRadius: 'var(--radius-lg, 12px)',
    padding: '1.75rem 1.5rem',
    display: 'flex',
    flexDirection: 'column',
    position: 'relative',
  }
}
</script>

<template>
  <AppLayout>
    <template #nav><NavAdmin /></template>

    <div class="container">
      <div class="page-header">
        <div>
          <h1>Upgrade Plan</h1>
          <p style="color:var(--color-text-muted);">
            Your institution is currently on the
            <strong style="color:var(--color-primary-dark);">
              {{ tiers.find(t => t.slug === currentPlan)?.label ?? 'Free' }}
            </strong> plan.
          </p>
        </div>
      </div>

      <div v-if="usage" class="stats-row" style="margin-top:1rem;">
        <div class="stat-card">
          <div class="stat-label">Labs used</div>
          <div class="stat-value">{{ usageText(usage.labs) }}</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Equipment used</div>
          <div class="stat-value">{{ usageText(usage.equipment) }}</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Users</div>
          <div class="stat-value">{{ usageText(usage.users) }}</div>
        </div>
      </div>

      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:1.5rem;margin-top:1.5rem;">
        <div v-for="tier in tiers" :key="tier.slug" :style="cardStyle(tier)">

          <div
            v-if="tier.slug === currentPlan"
            style="position:absolute;top:-0.75rem;left:50%;transform:translateX(-50%);background:var(--color-primary);color:#fff;font-size:0.75rem;font-weight:700;padding:0.25rem 0.75rem;border-radius:999px;"
          >
            Current Plan
          </div>

          <h3 style="font-size:1.1rem;font-weight:700;color:var(--color-text-primary);margin-bottom:1rem;">
            {{ tier.label }}
          </h3>

          <ul style="list-style:none;padding:0;margin:0 0 1.5rem;flex-grow:1;">
            <li
              v-for="feature in features(tier)"
              :key="feature"
              style="display:flex;align-items:flex-start;gap:0.5rem;font-size:0.9rem;color:var(--color-text-secondary);margin-bottom:0.65rem;"
            >
              <span style="color:var(--color-primary);font-weight:700;"><Icon name="check" /></span> {{ feature }}
            </li>
          </ul>

          <span
            v-if="tier.slug === currentPlan"
            class="btn btn-secondary"
            style="display:block;text-align:center;opacity:0.6;cursor:default;"
          >Your plan</span>
          <Link
            v-else
            href="/contact"
            class="btn btn-primary"
            style="display:block;text-align:center;text-decoration:none;"
          >Contact us to upgrade</Link>

        </div>
      </div>

      <p style="text-align:center;color:var(--color-text-muted);margin-top:2rem;font-size:0.85rem;">
        Plan changes are handled by the LabFix team for now — reach out and we'll get it sorted.
      </p>
    </div>
  </AppLayout>
</template>