/**
 * app/(public)/contact.tsx  —  Contact Screen
 *
 * Mirrors: resources/js/Pages/Contact.vue
 *
 * Fetches live contact info from the public API endpoint:
 *   GET /api/contact-info
 *   → { system_name, support_email, support_phone }
 *
 * Sections:
 *   1. Header     — page title + sub-copy
 *   2. Cards      — Email Support, Phone Support, Visit Us
 *   3. Social     — four social icon buttons (placeholder hrefs)
 *   4. Quick Help — CTA to Knowledge Base (navigates to login for now
 *                   since KB requires auth; adjust once auth is wired up)
 *
 * Email and phone links use Linking.openURL() — same as <a href="mailto:">
 * and <a href="tel:"> in the web version.
 *
 * The top nav mirrors the web version's layout: LabFix logo on the left,
 * hamburger (≡) on the right since this is inside the public tab zone
 * (the tab bar already provides navigation).
 */

import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  ScrollView,
  TouchableOpacity,
  StyleSheet,
  StatusBar,
  Linking,
  ActivityIndicator,
} from 'react-native';
import { Link } from 'expo-router';
import { colors, spacing, radius, font } from '@/constants/theme';
import { apiUrl } from '@/constants/api';

// ─── Types ────────────────────────────────────────────────────────────────────

type ContactInfo = {
  system_name:   string;
  support_email: string;
  support_phone: string;
};

const FALLBACK: ContactInfo = {
  system_name:   'LabFix',
  support_email: 'support@labfix.edu',
  support_phone: '',
};

// ─── Static data ──────────────────────────────────────────────────────────────

const SOCIAL_LINKS = [
  { icon: '📘', label: 'Facebook',  href: '#' },
  { icon: '🐦', label: 'Twitter',   href: '#' },
  { icon: '💼', label: 'LinkedIn',  href: '#' },
  { icon: '📸', label: 'Instagram', href: '#' },
] as const;

// ─── Component ────────────────────────────────────────────────────────────────

export default function ContactScreen() {
  const [info, setInfo]       = useState<ContactInfo>(FALLBACK);
  const [loading, setLoading] = useState(true);
  const [error, setError]     = useState(false);

  useEffect(() => {
    let cancelled = false;

    fetch(apiUrl('contact-info'))
      .then((res) => {
        if (!res.ok) throw new Error('Non-OK response');
        return res.json() as Promise<ContactInfo>;
      })
      .then((data) => {
        if (!cancelled) setInfo(data);
      })
      .catch(() => {
        if (!cancelled) setError(true);
      })
      .finally(() => {
        if (!cancelled) setLoading(false);
      });

    return () => { cancelled = true; };
  }, []);

  function openEmail() {
    if (info.support_email) {
      Linking.openURL(`mailto:${info.support_email}`);
    }
  }

  function openPhone() {
    if (info.support_phone) {
      Linking.openURL(`tel:${info.support_phone}`);
    }
  }

  return (
    <View style={s.root}>
      <StatusBar barStyle="light-content" backgroundColor={colors.bgPrimary} />

      {/* ── Top Nav ── */}
      <View style={s.nav}>
        <Text style={s.navLogo}>LabFix</Text>
        {/* Hamburger placeholder — tab bar handles navigation in this zone */}
        <View style={s.hamburger}>
          <View style={s.hLine} />
          <View style={s.hLine} />
          <View style={s.hLine} />
        </View>
      </View>

      <ScrollView
        showsVerticalScrollIndicator={false}
        contentContainerStyle={s.scrollContent}
      >

        {/* ── Page Header ── */}
        <View style={s.pageHeader}>
          <Text style={s.pageTitle}>Contact Us</Text>
          <Text style={s.pageSub}>
            Get in touch with the{' '}
            {loading ? 'LabFix' : info.system_name} support team
          </Text>
        </View>

        {/* ── Contact Cards ── */}
        <View style={s.cards}>

          {/* Email */}
          <View style={s.card}>
            <Text style={s.cardIcon}>📧</Text>
            <View style={s.cardBody}>
              <Text style={s.cardTitle}>Email Support</Text>
              <Text style={s.cardText}>
                Send us an email and we'll get back to you within 24 hours
                during business days.
              </Text>
              {loading ? (
                <ActivityIndicator
                  size="small"
                  color={colors.primary}
                  style={{ alignSelf: 'flex-start', marginTop: spacing.sm }}
                />
              ) : (
                <TouchableOpacity
                  style={s.contactLink}
                  onPress={openEmail}
                  activeOpacity={0.7}
                >
                  <Text style={s.contactLinkText}>{info.support_email}</Text>
                </TouchableOpacity>
              )}
            </View>
          </View>

          {/* Phone */}
          <View style={s.card}>
            <Text style={s.cardIcon}>📞</Text>
            <View style={s.cardBody}>
              <Text style={s.cardTitle}>Phone Support</Text>
              <Text style={s.cardText}>
                Call us during office hours, Monday to Friday, 8 AM to 5 PM.
              </Text>
              {loading ? (
                <ActivityIndicator
                  size="small"
                  color={colors.primary}
                  style={{ alignSelf: 'flex-start', marginTop: spacing.sm }}
                />
              ) : info.support_phone ? (
                <TouchableOpacity
                  style={s.contactLink}
                  onPress={openPhone}
                  activeOpacity={0.7}
                >
                  <Text style={s.contactLinkText}>{info.support_phone}</Text>
                </TouchableOpacity>
              ) : (
                <View style={[s.contactLink, { opacity: 0.45 }]}>
                  <Text style={s.contactLinkText}>Not available</Text>
                </View>
              )}
            </View>
          </View>

          {/* Visit */}
          <View style={s.card}>
            <Text style={s.cardIcon}>📍</Text>
            <View style={s.cardBody}>
              <Text style={s.cardTitle}>Visit Us</Text>
              <Text style={s.cardText}>
                Find us at the IT Support office on campus during office hours.
              </Text>
              <View style={s.contactLink}>
                <Text style={s.contactLinkText}>IT Department Office</Text>
              </View>
            </View>
          </View>

        </View>

        {/* ── Social Links ── */}
        <View style={s.socialSection}>
          <Text style={s.socialTitle}>Follow Us</Text>
          <View style={s.socialRow}>
            {SOCIAL_LINKS.map((social) => (
              <TouchableOpacity
                key={social.label}
                style={s.socialBtn}
                activeOpacity={0.7}
                onPress={() => {
                  // Replace '#' with real URLs when available
                  if (social.href !== '#') Linking.openURL(social.href);
                }}
              >
                <Text style={s.socialIcon}>{social.icon}</Text>
              </TouchableOpacity>
            ))}
          </View>
        </View>

        {/* ── Quick Help CTA ── */}
        <View style={s.quickHelp}>
          <Text style={s.quickHelpTitle}>Need Quick Help?</Text>
          <Text style={s.quickHelpText}>
            Before reaching out, check our Knowledge Base — many common lab
            equipment issues are already documented with step-by-step fixes.
          </Text>
          {/*
            Points to /login for now since KB requires authentication.
            Update this href to /(tabs)/knowledge-base once auth is wired up.
          */}
          <Link href="/login" asChild>
            <TouchableOpacity style={s.helpBtn} activeOpacity={0.8}>
              <Text style={s.helpBtnText}>Browse Knowledge Base</Text>
            </TouchableOpacity>
          </Link>
        </View>

        {/* Footer */}
        <View style={s.footer}>
          <Text style={s.footerText}>
            © {new Date().getFullYear()} LabFix · Academic Project
          </Text>
        </View>

      </ScrollView>
    </View>
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
  hamburger: {
    gap: 5,
    padding: spacing.xs,
  },
  hLine: {
    width: 22,
    height: 2,
    backgroundColor: colors.textSecondary,
    borderRadius: 2,
  },

  // ── Scroll ───────────────────────────────────────────────────────────────
  scrollContent: {
    paddingBottom: spacing.xxl,
  },

  // ── Page header ──────────────────────────────────────────────────────────
  pageHeader: {
    paddingHorizontal: spacing.lg,
    paddingTop: spacing.xl,
    paddingBottom: spacing.lg,
    alignItems: 'center',
  },
  pageTitle: {
    fontSize: font.xl3,
    fontWeight: font.bold,
    color: colors.textPrimary,
    marginBottom: spacing.xs,
  },
  pageSub: {
    fontSize: font.base,
    color: colors.textMuted,
    textAlign: 'center',
  },

  // ── Contact cards ─────────────────────────────────────────────────────────
  cards: {
    paddingHorizontal: spacing.lg,
    gap: spacing.sm + 4,
  },
  card: {
    backgroundColor: colors.bgCard,
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radius.xl,
    padding: spacing.lg,
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: spacing.md,
  },
  cardIcon: {
    fontSize: 28,
    marginTop: 2,
  },
  cardBody: {
    flex: 1,
    gap: spacing.xs,
  },
  cardTitle: {
    fontSize: font.lg,
    fontWeight: font.semibold,
    color: colors.primary,
    marginBottom: 2,
  },
  cardText: {
    fontSize: font.sm,
    color: colors.textSecondary,
    lineHeight: 20,
  },
  contactLink: {
    alignSelf: 'flex-start',
    marginTop: spacing.sm,
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radius.md,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.xs + 1,
    backgroundColor: colors.primaryLight,
  },
  contactLinkText: {
    fontSize: font.sm,
    color: colors.primary,
    fontWeight: font.semibold,
  },

  // ── Social ────────────────────────────────────────────────────────────────
  socialSection: {
    paddingHorizontal: spacing.lg,
    paddingTop: spacing.xl,
    alignItems: 'center',
  },
  socialTitle: {
    fontSize: font.xxl,
    fontWeight: font.bold,
    color: colors.textPrimary,
    marginBottom: spacing.lg,
  },
  socialRow: {
    flexDirection: 'row',
    gap: spacing.lg,
  },
  socialBtn: {
    width: 58,
    height: 58,
    borderRadius: radius.full,
    backgroundColor: colors.primaryLight,
    borderWidth: 1,
    borderColor: colors.border,
    alignItems: 'center',
    justifyContent: 'center',
  },
  socialIcon: {
    fontSize: 24,
  },

  // ── Quick Help ────────────────────────────────────────────────────────────
  quickHelp: {
    marginHorizontal: spacing.lg,
    marginTop: spacing.xl,
    backgroundColor: colors.primaryLight,
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radius.xl,
    padding: spacing.lg,
    alignItems: 'center',
  },
  quickHelpTitle: {
    fontSize: font.xl,
    fontWeight: font.bold,
    color: colors.primary,
    marginBottom: spacing.sm,
  },
  quickHelpText: {
    fontSize: font.sm,
    color: colors.textSecondary,
    textAlign: 'center',
    lineHeight: 20,
    marginBottom: spacing.lg,
  },
  helpBtn: {
    width: '100%',
    backgroundColor: colors.primary,
    borderRadius: radius.lg,
    paddingVertical: spacing.sm + 4,
    alignItems: 'center',
  },
  helpBtnText: {
    fontSize: font.base,
    fontWeight: font.bold,
    color: '#fff',
  },

  // ── Footer ────────────────────────────────────────────────────────────────
  footer: {
    padding: spacing.lg,
    borderTopWidth: 1,
    borderTopColor: colors.border,
    alignItems: 'center',
    marginTop: spacing.xl,
  },
  footerText: {
    fontSize: font.xs,
    color: colors.textMuted,
  },
});