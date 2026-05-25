/**
 * app/(public)/index.tsx  —  Landing / Home Screen
 *
 * Mirrors: resources/js/Pages/Landing.vue
 *
 * Three sections on one scrollable screen:
 *   1. Hero      — headline, sub-copy, CTA buttons, live ticket mockup card
 *   2. Features  — 2-column grid of 6 feature cards
 *   3. About     — blurb + CTA buttons
 *
 * The PublicTabBar emits scroll events via landingScrollEmitter when the
 * "Features" or "About" tabs are tapped. This screen listens and calls
 * scrollTo() so those sections animate into view — matching the web
 * version's #features / #about hash-anchor behaviour.
 */

import React, { useRef, useEffect, useCallback } from 'react';
import {
  View,
  Text,
  ScrollView,
  TouchableOpacity,
  StyleSheet,
  StatusBar,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Link } from 'expo-router';
import { colors, spacing, radius, font } from '@/constants/theme';
import { landingScrollEmitter } from '@/utils/landingScrollEmitter';

// ─── Static data (mirrors Landing.vue) ───────────────────────────────────────

const FEATURES = [
  {
    icon: '🎫',
    title: 'Easy Ticket Submission',
    description:
      'Submit lab equipment issues in minutes with our guided multi-step form.',
  },
  {
    icon: '⚡',
    title: 'Real-Time Tracking',
    description:
      'Track your ticket status in real time from submission to resolution.',
  },
  {
    icon: '🔧',
    title: 'IT Support Queue',
    description:
      'IT staff get a prioritised queue with smart assignment. No more lost emails.',
  },
  {
    icon: '📚',
    title: 'Knowledge Base',
    description:
      'Browse self-help articles before submitting. Common fixes are documented.',
  },
  {
    icon: '🖥️',
    title: 'Lab Status Monitor',
    description:
      'Check which labs are operational before heading in.',
  },
  {
    icon: '📊',
    title: 'Admin Oversight',
    description:
      'Admins get full visibility into ticket volume and equipment health.',
  },
] as const;

const MOCK_TICKETS = [
  { title: "PC-03 won't boot",       lab: 'Lab A', priority: 'High',   status: 'In Progress' },
  { title: 'Projector cable missing', lab: 'Lab B', priority: 'Medium', status: 'Open'        },
  { title: 'Mouse not responding',    lab: 'Lab C', priority: 'Low',    status: 'Assigned'    },
] as const;

const PRIORITY_COLOR: Record<string, string> = {
  High:   colors.danger,
  Medium: colors.warning,
  Low:    colors.success,
};

// ─── Component ────────────────────────────────────────────────────────────────

export default function LandingScreen() {
  const scrollRef = useRef<ScrollView>(null);
  const featuresY = useRef(0);
  const aboutY    = useRef(0);

  const scrollToFeatures = useCallback(() => {
    scrollRef.current?.scrollTo({ y: featuresY.current, animated: true });
  }, []);

  const scrollToAbout = useCallback(() => {
    scrollRef.current?.scrollTo({ y: aboutY.current, animated: true });
  }, []);

  // Subscribe to tab bar scroll events
  useEffect(() => {
    const subFeatures = landingScrollEmitter.on('features', scrollToFeatures);
    const subAbout    = landingScrollEmitter.on('about',    scrollToAbout);
    return () => {
      subFeatures.remove();
      subAbout.remove();
    };
  }, [scrollToFeatures, scrollToAbout]);

  return (
    <SafeAreaView style={s.root}>
      <StatusBar barStyle="light-content" backgroundColor={colors.bgPrimary} />

      {/* ── Top Nav ── */}
      <View style={s.nav}>
        <Text style={s.navLogo}>LabFix</Text>
        <View style={s.navRight}>
          <Text style={s.navHint}>New here?</Text>
          <Link href="/login" asChild>
            <TouchableOpacity style={s.navBtn}>
              <Text style={s.navBtnText}>Sign Up</Text>
            </TouchableOpacity>
          </Link>
        </View>
      </View>

      <ScrollView
        ref={scrollRef}
        style={s.scroll}
        showsVerticalScrollIndicator={false}
        contentContainerStyle={s.scrollContent}
      >

        {/* ════════════════════════════════════════════
            HERO SECTION
        ════════════════════════════════════════════ */}
        <View style={s.hero}>

          {/* Eyebrow badge */}
          <View style={s.badge}>
            <Text style={s.badgeText}>✦  Equipment Issue Tracking</Text>
          </View>

          {/* Headline */}
          <Text style={s.heroTitle}>
            Streamline Your{'\n'}
            <Text style={s.heroAccent}>Lab Equipment</Text>
            {'\n'}Issues
          </Text>

          {/* Sub-copy */}
          <Text style={s.heroSub}>
            Report, track, and resolve computer lab equipment problems faster.
            LabFix connects students and staff with IT support through a simple,
            transparent ticketing system.
          </Text>

          {/* CTA buttons */}
          <View style={s.ctaRow}>
            <Link href="/register" asChild>
              <TouchableOpacity style={s.btnPrimary}>
                <Text style={s.btnPrimaryText}>Get Started</Text>
              </TouchableOpacity>
            </Link>
            <TouchableOpacity style={s.btnOutline} onPress={scrollToFeatures}>
              <Text style={s.btnOutlineText}>Learn More</Text>
            </TouchableOpacity>
          </View>

          {/* Live ticket mockup card */}
          <View style={s.mockCard}>
            {/* Card header */}
            <View style={s.mockHeader}>
              <Text style={s.mockTitle}>Live Ticket Queue</Text>
              <View style={s.liveRow}>
                <View style={s.liveDot} />
                <Text style={s.liveText}>Live</Text>
              </View>
            </View>

            {/* Ticket rows */}
            {MOCK_TICKETS.map((ticket, i) => (
              <View
                key={ticket.title}
                style={[
                  s.mockTicket,
                  i === MOCK_TICKETS.length - 1 && s.mockTicketLast,
                ]}
              >
                <Text style={s.mockTicketTitle}>{ticket.title}</Text>
                <View style={s.mockTicketMeta}>
                  <Text style={s.mockMetaText}>{ticket.lab}</Text>
                  <View
                    style={[
                      s.priorityPill,
                      { borderColor: PRIORITY_COLOR[ticket.priority] + '66' },
                    ]}
                  >
                    <Text
                      style={[
                        s.priorityText,
                        { color: PRIORITY_COLOR[ticket.priority] },
                      ]}
                    >
                      {ticket.priority}
                    </Text>
                  </View>
                  <Text style={s.mockMetaText}>{ticket.status}</Text>
                </View>
              </View>
            ))}
          </View>
        </View>

        {/* ════════════════════════════════════════════
            FEATURES SECTION
            onLayout captures Y so the tab bar can scroll here
        ════════════════════════════════════════════ */}
        <View
          style={s.featuresSection}
          onLayout={(e) => { featuresY.current = e.nativeEvent.layout.y; }}
        >
          <Text style={s.sectionTitle}>Everything You Need</Text>

          <View style={s.featureGrid}>
            {FEATURES.map((f) => (
              <View key={f.title} style={s.featureCard}>
                <Text style={s.featureIcon}>{f.icon}</Text>
                <Text style={s.featureTitle}>{f.title}</Text>
                <Text style={s.featureDesc}>{f.description}</Text>
              </View>
            ))}
          </View>
        </View>

        {/* ════════════════════════════════════════════
            ABOUT SECTION
        ════════════════════════════════════════════ */}
        <View
          style={s.aboutSection}
          onLayout={(e) => { aboutY.current = e.nativeEvent.layout.y; }}
        >
          <Text style={s.sectionTitle}>About LabFix</Text>
          <Text style={s.aboutText}>
            LabFix is an academic project built to streamline equipment
            maintenance reporting in computer laboratories. It replaces
            informal email chains and walk-up requests with a structured,
            trackable workflow — from first report to final resolution.
          </Text>
          <View style={s.ctaRow}>
            <Link href="/register" asChild>
              <TouchableOpacity style={s.btnPrimary}>
                <Text style={s.btnPrimaryText}>Create an Account</Text>
              </TouchableOpacity>
            </Link>
            <Link href="/contact" asChild>
              <TouchableOpacity style={s.btnOutline}>
                <Text style={s.btnOutlineText}>Contact Us</Text>
              </TouchableOpacity>
            </Link>
          </View>
        </View>

        {/* Footer */}
        <View style={s.footer}>
          <Text style={s.footerText}>
            © {new Date().getFullYear()} LabFix · Academic Project
          </Text>
        </View>

      </ScrollView>
    </SafeAreaView>
  );
}

// ─── Styles ───────────────────────────────────────────────────────────────────

const s = StyleSheet.create({
  root: {
    flex: 1,
    backgroundColor: colors.bgPrimary,
  },

  // ── Nav ─────────────────────────────────────────────────────────────────
  nav: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.sm + 4,
    borderBottomWidth: 1,
    borderBottomColor: colors.border,
    backgroundColor: colors.bgPrimary,
  },
  navLogo: {
    fontSize: font.xl,
    fontWeight: font.bold,
    color: colors.primary,
    letterSpacing: -0.5,
  },
  navRight: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
  },
  navHint: {
    fontSize: font.sm,
    color: colors.textMuted,
  },
  navBtn: {
    backgroundColor: colors.primary,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.xs + 2,
    borderRadius: radius.full,
  },
  navBtnText: {
    fontSize: font.sm,
    fontWeight: font.bold,
    color: '#fff',
  },

  // ── Scroll ──────────────────────────────────────────────────────────────
  scroll: { flex: 1 },
  scrollContent: { paddingBottom: spacing.xxl },

  // ── Hero ────────────────────────────────────────────────────────────────
  hero: {
    padding: spacing.lg,
    paddingTop: spacing.xl,
    alignItems: 'center',
    // Subtle radial glow behind hero (via background colour approximation)
    backgroundColor: 'rgba(45,212,191,0.03)',
  },
  badge: {
    backgroundColor: colors.primaryLight,
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radius.full,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.xs,
    marginBottom: spacing.lg,
  },
  badgeText: {
    fontSize: font.xs,
    fontWeight: font.semibold,
    color: colors.primary,
    letterSpacing: 0.3,
  },
  heroTitle: {
    fontSize: 28,
    fontWeight: font.bold,
    color: colors.textPrimary,
    textAlign: 'center',
    lineHeight: 36,
    marginBottom: spacing.md,
  },
  heroAccent: {
    color: colors.primary,
  },
  heroSub: {
    fontSize: font.base,
    color: colors.textMuted,
    textAlign: 'center',
    lineHeight: 22,
    marginBottom: spacing.xl,
    maxWidth: 320,
  },

  // ── Buttons ─────────────────────────────────────────────────────────────
  ctaRow: {
    flexDirection: 'row',
    gap: spacing.sm,
    marginBottom: spacing.xl,
    flexWrap: 'wrap',
    justifyContent: 'center',
  },
  btnPrimary: {
    backgroundColor: colors.primary,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.sm + 4,
    borderRadius: radius.full,
  },
  btnPrimaryText: {
    fontSize: font.base,
    fontWeight: font.bold,
    color: '#fff',
  },
  btnOutline: {
    borderWidth: 1.5,
    borderColor: colors.primary,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm + 4,
    borderRadius: radius.full,
  },
  btnOutlineText: {
    fontSize: font.base,
    fontWeight: font.semibold,
    color: colors.primary,
  },

  // ── Mock card ────────────────────────────────────────────────────────────
  mockCard: {
    width: '100%',
    backgroundColor: colors.bgCard,
    borderRadius: radius.xl,
    borderWidth: 1,
    borderColor: colors.borderStrong,
    overflow: 'hidden',
  },
  mockHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm + 2,
    backgroundColor: colors.primaryLight,
    borderBottomWidth: 1,
    borderBottomColor: colors.border,
  },
  mockTitle: {
    fontSize: font.sm,
    fontWeight: font.bold,
    color: colors.primary,
  },
  liveRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 5,
  },
  liveDot: {
    width: 7,
    height: 7,
    borderRadius: 4,
    backgroundColor: colors.success,
  },
  liveText: {
    fontSize: font.xs,
    color: colors.success,
    fontWeight: font.semibold,
  },
  mockTicket: {
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm + 2,
    borderBottomWidth: 1,
    borderBottomColor: colors.borderLight,
    borderLeftWidth: 3,
    borderLeftColor: colors.primary,
  },
  mockTicketLast: {
    borderBottomWidth: 0,
  },
  mockTicketTitle: {
    fontSize: font.sm,
    fontWeight: font.medium,
    color: colors.textPrimary,
    marginBottom: 4,
  },
  mockTicketMeta: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
  },
  mockMetaText: {
    fontSize: font.xs,
    color: colors.textMuted,
  },
  priorityPill: {
    borderWidth: 1,
    borderRadius: radius.full,
    paddingHorizontal: 7,
    paddingVertical: 1,
  },
  priorityText: {
    fontSize: font.xs,
    fontWeight: font.semibold,
  },

  // ── Features ────────────────────────────────────────────────────────────
  featuresSection: {
    padding: spacing.lg,
    backgroundColor: 'rgba(0,0,0,0.25)',
  },
  sectionTitle: {
    fontSize: font.xxl,
    fontWeight: font.bold,
    color: colors.primary,
    textAlign: 'center',
    marginBottom: spacing.lg,
  },
  featureGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
  },
  featureCard: {
    // Two columns: (100% - 1 gap) / 2. Using percentage with gap approx.
    width: '48.5%',
    backgroundColor: colors.bgCard,
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radius.xl,
    padding: spacing.md,
    alignItems: 'center',
  },
  featureIcon: {
    fontSize: 26,
    marginBottom: spacing.xs,
  },
  featureTitle: {
    fontSize: font.sm,
    fontWeight: font.semibold,
    color: colors.textPrimary,
    textAlign: 'center',
    marginBottom: spacing.xs,
  },
  featureDesc: {
    fontSize: font.xs,
    color: colors.textMuted,
    textAlign: 'center',
    lineHeight: 17,
  },

  // ── About ────────────────────────────────────────────────────────────────
  aboutSection: {
    padding: spacing.lg,
    alignItems: 'center',
  },
  aboutText: {
    fontSize: font.base,
    color: colors.textSecondary,
    textAlign: 'center',
    lineHeight: 23,
    marginBottom: spacing.xl,
    maxWidth: 320,
  },

  // ── Footer ───────────────────────────────────────────────────────────────
  footer: {
    padding: spacing.lg,
    borderTopWidth: 1,
    borderTopColor: colors.border,
    alignItems: 'center',
    marginTop: spacing.md,
  },
  footerText: {
    fontSize: font.xs,
    color: colors.textMuted,
  },
});