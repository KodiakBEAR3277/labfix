<script setup>
// Landing.vue
// Mirrors: resources/views/landing.blade.php
// Layout:  LandingLayout.vue (which wraps NavLanding)
//
// All class names match your existing landing.css and main.css exactly,
// so no styling changes are needed — Vite will bundle those CSS files in.

import LandingLayout from '../Layouts/LandingLayout.vue'

// Static feature cards data — keeps the template clean
const features = [
  {
    icon: '🎫',
    title: 'Easy Ticket Submission',
    description: 'Submit lab equipment issues in minutes with our guided multi-step form. Attach photos and specify the exact equipment and location.'
  },
  {
    icon: '⚡',
    title: 'Real-Time Tracking',
    description: 'Track your ticket status in real time from submission to resolution. Get notified at every step of the repair process.'
  },
  {
    icon: '🔧',
    title: 'IT Support Queue',
    description: 'IT staff get a prioritized queue with smart assignment. No more lost emails — every issue is tracked and accountable.'
  },
  {
    icon: '📚',
    title: 'Knowledge Base',
    description: 'Browse self-help articles before submitting a ticket. Common fixes are documented so you can resolve minor issues yourself.'
  },
  {
    icon: '🖥️',
    title: 'Lab Status Monitor',
    description: "Check which labs are operational before heading in. See equipment availability and any known ongoing issues at a glance."
  },
  {
    icon: '📊',
    title: 'Admin Oversight',
    description: 'Admins get full visibility into ticket volume, IT performance, and equipment health across all labs with detailed reports.'
  }
]

// Mockup ticket data for the hero visual — mirrors what the blade had
const mockTickets = [
  { title: "PC-03 won't boot", lab: 'Lab A', priority: 'High', status: 'In Progress' },
  { title: 'Projector cable missing', lab: 'Lab B', priority: 'Medium', status: 'Open' },
  { title: 'Mouse not responding', lab: 'Lab C', priority: 'Low', status: 'Assigned' },
]
</script>

<template>
  <LandingLayout>

    <!-- ==================== HERO ==================== -->
    <section class="hero container">
      <div class="">
        <div class="hero-content">

          <!-- Left: Text + CTA -->
          <div class="hero-text">
            <h1>Streamline Your Computer Lab Issue Tracking</h1>
            <p>
              Report, track, and resolve computer lab equipment problems faster.
              LabFix connects students and staff with IT support through a simple,
              transparent ticketing system.
            </p>
            <div class="cta-buttons">
              <a href="/register" class=" btn-primary">Get Started</a>
              <a href="#features"  class=" btn-secondary">Learn More</a>
            </div>
          </div>

          <!-- Right: Dashboard mockup visual -->
          <div class="hero-visual">
            <div class="dashboard-mockup">
              <div class="mockup-header">
                <span class="mockup-title">LabFix</span>
                <span class="status-badge">● Live</span>
              </div>

              <div
                v-for="ticket in mockTickets"
                :key="ticket.title"
                class="ticket-item"
              >
                <div class="ticket-title">{{ ticket.title }}</div>
                <div class="ticket-meta">{{ ticket.lab }} · {{ ticket.priority }} · {{ ticket.status }}</div>
              </div>
            </div>

            <!-- Decorative floating blobs (matching landing.css animations) -->
            <div class="floating-element floating-1"></div>
            <div class="floating-element floating-2"></div>
            <div class="floating-element floating-3"></div>
          </div>

        </div>
      </div>
    </section>

    <!-- ==================== FEATURES ==================== -->
    <section id="features" class="features">
      <div class="feature-container">
        <h2>Everything You Need</h2>
        <div class="features-grid">
          <div
            v-for="feature in features"
            :key="feature.title"
            class="feature-card"
          >
            <div class="feature-icon">{{ feature.icon }}</div>
            <h3>{{ feature.title }}</h3>
            <p>{{ feature.description }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- ==================== ABOUT ==================== -->
    <section id="about" class="features" style="background: transparent;">
      <div class="feature-container" style="text-align: center; max-width: 700px;">
        <h2>About LabFix</h2>
        <p style="color: var(--color-text-secondary); font-size: var(--font-size-lg); line-height: var(--line-height-relaxed);">
          LabFix is an academic project built to streamline equipment maintenance
          reporting in computer laboratories. It replaces informal email chains and
          walk-up requests with a structured, trackable workflow — from first report
          to final resolution.
        </p>
        <div class="cta-buttons" style="justify-content: center; margin-top: 2rem;">
          <a href="/register" class="btn btn-primary">Create an Account</a>
          <a href="/contact"  class="btn btn-secondary">Contact Us</a>
        </div>
      </div>
    </section>

    <!-- ==================== FOOTER ==================== -->
    <template #footer>
      <div style="
        border-top: 1px solid var(--color-border);
        padding: 2rem;
        text-align: center;
        color: var(--color-text-muted);
        font-size: var(--font-size-sm);
      ">
        © {{ new Date().getFullYear() }} LabFix. Academic Project.
      </div>
    </template>

  </LandingLayout>
</template>