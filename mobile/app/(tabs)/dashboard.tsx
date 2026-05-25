/**
 * app/(tabs)/index.tsx  —  User Dashboard
 *
 * Mirrors: resources/js/Pages/User/Dashboard.vue
 *
 * Fetches from GET /api/dashboard (protected route).
 * API returns:
 *   {
 *     role: 'student' | 'staff',
 *     stats: { active, resolved_this_month, total },
 *     recent_tickets: Report[]   ← last 3, with equipment.lab + assignedTo
 *   }
 *
 * Sections (matching the web version and the mockup):
 *   1. Top nav        — LabFix logo + avatar (initials)
 *   2. Welcome banner — "Welcome back, [first name]!"
 *   3. Stats row      — Active · Resolved This Month · Total
 *   4. Quick actions  — Report Issue · My Reports · Knowledge Base · Lab Status
 *   5. Recent Reports — last 3 tickets, tappable cards
 */

import React, { useEffect, useState, useCallback } from 'react';
import {
  View,
  Text,
  ScrollView,
  TouchableOpacity,
  StyleSheet,
  StatusBar,
  ActivityIndicator,
  RefreshControl,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { router } from 'expo-router';
import { colors, spacing, radius, font } from '@/constants/theme';
import { loadAuth, clearAuth, AuthUser } from '@/utils/auth';
import { apiUrl } from '@/constants/api';

// ─── Types ────────────────────────────────────────────────────────────────────

type Stats = {
  active:               number;
  resolved_this_month:  number;
  total:                number;
};

type Ticket = {
  id:            number;
  ticket_number: string;
  title:         string;
  status:        'new' | 'assigned' | 'in-progress' | 'resolved' | 'closed';
  priority:      'low' | 'medium' | 'high';
  equipment?: {
    equipment_code: string;
    lab?: { name: string };
  };
};

type DashboardData = {
  role:           string;
  stats:          Stats;
  recent_tickets: Ticket[];
};

// ─── Helpers ─────────────────────────────────────────────────────────────────

function statusLabel(status: string): string {
  return status.replace('-', ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

function statusColors(status: string): { bg: string; text: string; border: string } {
  switch (status) {
    case 'new':         return { bg: colors.infoBg,     text: colors.infoLight,    border: colors.infoBorder    };
    case 'assigned':    return { bg: 'rgba(168,85,247,0.18)', text: '#c084fc', border: 'rgba(168,85,247,0.3)' };
    case 'in-progress': return { bg: colors.warningBg,  text: colors.warningLight, border: colors.warningBorder };
    case 'resolved':
    case 'closed':      return { bg: colors.successBg,  text: colors.successLight, border: colors.successBorder };
    default:            return { bg: colors.borderLight, text: colors.textMuted,    border: colors.border        };
  }
}

function priorityColors(priority: string): string {
  switch (priority) {
    case 'high':   return colors.danger;
    case 'medium': return colors.warning;
    default:       return colors.success;
  }
}

function getInitials(name: string): string {
  return name
    .split(' ')
    .map((n) => n[0])
    .slice(0, 2)
    .join('')
    .toUpperCase();
}

function getFirstName(name: string): string {
  return name.split(' ')[0];
}

// ─── Quick action data ────────────────────────────────────────────────────────

const QUICK_ACTIONS = [
  {
    icon:  '🎫',
    title: 'Report Issue',
    desc:  'Submit a new equipment issue',
    route: '/(tabs)/report',
  },
  {
    icon:  '📋',
    title: 'My Reports',
    desc:  'View all your submitted tickets',
    route: '/(tabs)/my-reports',
  },
  {
    icon:  '📚',
    title: 'Knowledge Base',
    desc:  'Browse self-help articles',
    route: '/(tabs)/knowledge-base',
  },
  {
    icon:  '🖥️',
    title: 'Lab Status',
    desc:  'Check which labs are operational',
    route: '/(tabs)/lab-status',
  },
] as const;

// ─── Component ────────────────────────────────────────────────────────────────

export default function UserDashboard() {
  const [user,       setUser]       = useState<AuthUser | null>(null);
  const [token,      setToken]      = useState<string | null>(null);
  const [data,       setData]       = useState<DashboardData | null>(null);
  const [loading,    setLoading]    = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error,      setError]      = useState<string | null>(null);

  // Load stored auth on mount
  useEffect(() => {
    loadAuth().then((auth) => {
      if (!auth) {
        router.replace('/login');
        return;
      }
      setUser(auth.user);
      setToken(auth.token);
    });
  }, []);

  // Fetch dashboard data once we have the token
  const fetchDashboard = useCallback(async (tok: string) => {
    try {
      const res = await fetch(apiUrl('dashboard'), {
        headers: {
          'Accept':        'application/json',
          'Authorization': `Bearer ${tok}`,
        },
      });

      if (res.status === 401) {
        // Token expired — log out
        await clearAuth();
        router.replace('/login');
        return;
      }

      if (!res.ok) throw new Error(`Server error: ${res.status}`);

      const json = await res.json();
      setData(json);
      setError(null);
    } catch (e: any) {
      setError(e.message ?? 'Could not load dashboard. Check your connection.');
    }
  }, []);

  useEffect(() => {
    if (!token) return;
    setLoading(true);
    fetchDashboard(token).finally(() => setLoading(false));
  }, [token, fetchDashboard]);

  const onRefresh = useCallback(async () => {
    if (!token) return;
    setRefreshing(true);
    await fetchDashboard(token);
    setRefreshing(false);
  }, [token, fetchDashboard]);

  // ── Render states ─────────────────────────────────────────────────────────

  if (loading) {
    return (
      <SafeAreaView style={s.root}>
        <StatusBar barStyle="light-content" backgroundColor={colors.bgPrimary} />
        <View style={s.centered}>
          <ActivityIndicator size="large" color={colors.primary} />
        </View>
      </SafeAreaView>
    );
  }

  if (error) {
    return (
      <SafeAreaView style={s.root}>
        <StatusBar barStyle="light-content" backgroundColor={colors.bgPrimary} />
        <View style={s.centered}>
          <Text style={s.errorIcon}>⚠️</Text>
          <Text style={s.errorText}>{error}</Text>
          <TouchableOpacity
            style={s.retryBtn}
            onPress={() => token && fetchDashboard(token)}
          >
            <Text style={s.retryBtnText}>Try Again</Text>
          </TouchableOpacity>
        </View>
      </SafeAreaView>
    );
  }

  const stats   = data?.stats;
  const tickets = data?.recent_tickets ?? [];

  return (
    <SafeAreaView style={s.root} edges={['top']}>
      <StatusBar barStyle="light-content" backgroundColor={colors.bgPrimary} />

      {/* ── Top Nav ── */}
      <View style={s.nav}>
        <Text style={s.navLogo}>LabFix</Text>
        <TouchableOpacity
          style={s.avatar}
          onPress={() => router.push('/profile' as any)}
          activeOpacity={0.8}
        >
          <Text style={s.avatarText}>
            {user ? getInitials(user.name) : 'U'}
          </Text>
        </TouchableOpacity>
      </View>

      <ScrollView
        showsVerticalScrollIndicator={false}
        contentContainerStyle={s.scroll}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={onRefresh}
            tintColor={colors.primary}
            colors={[colors.primary]}
          />
        }
      >
        {/* ── Welcome Banner ── */}
        <View style={s.welcomeBanner}>
          <Text style={s.welcomeTitle}>
            Welcome back,{' '}
            <Text style={s.welcomeAccent}>
              {user ? getFirstName(user.name) : ''}!
            </Text>
          </Text>
          <Text style={s.welcomeSub}>
            Here's an overview of your submitted reports
          </Text>
        </View>

        {/* ── Stats Row ── */}
        <View style={s.statsRow}>
          <View style={s.statCard}>
            <Text style={s.statLabel}>Active Tickets</Text>
            <Text style={s.statValue}>{stats?.active ?? '—'}</Text>
          </View>
          <View style={s.statCard}>
            <Text style={s.statLabel}>Resolved This Month</Text>
            <Text style={s.statValue}>{stats?.resolved_this_month ?? '—'}</Text>
          </View>
          <View style={s.statCard}>
            <Text style={s.statLabel}>Total Reports</Text>
            <Text style={s.statValue}>{stats?.total ?? '—'}</Text>
          </View>
        </View>

        {/* ── Quick Actions ── */}
        <View style={s.sectionHeader}>
          <Text style={s.sectionTitle}>Quick Actions</Text>
        </View>
        <View style={s.actionGrid}>
          {QUICK_ACTIONS.map((action) => (
            <TouchableOpacity
              key={action.title}
              style={s.actionCard}
              onPress={() => router.push(action.route as any)}
              activeOpacity={0.75}
            >
              <Text style={s.actionIcon}>{action.icon}</Text>
              <Text style={s.actionTitle}>{action.title}</Text>
              <Text style={s.actionDesc}>{action.desc}</Text>
            </TouchableOpacity>
          ))}
        </View>

        {/* ── Recent Reports ── */}
        <View style={s.card}>
          <View style={s.cardHeader}>
            <Text style={s.sectionTitle}>Recent Reports</Text>
            <TouchableOpacity onPress={() => router.push('/(tabs)/my-reports' as any)}>
              <Text style={s.viewAll}>View All →</Text>
            </TouchableOpacity>
          </View>

          {tickets.length === 0 ? (
            <View style={s.emptyState}>
              <Text style={s.emptyIcon}>📋</Text>
              <Text style={s.emptyText}>No reports yet.</Text>
              <TouchableOpacity onPress={() => router.push('/(tabs)/report' as any)}>
                <Text style={s.emptyLink}>Report your first issue →</Text>
              </TouchableOpacity>
            </View>
          ) : (
            tickets.map((ticket) => {
              const sc = statusColors(ticket.status);
              return (
                <TouchableOpacity
                  key={ticket.id}
                  style={s.ticketCard}
                  onPress={() => router.push(`/(tabs)/my-reports/${ticket.id}` as any)}
                  activeOpacity={0.75}
                >
                  {/* Left priority accent bar */}
                  <View
                    style={[
                      s.priorityBar,
                      { backgroundColor: priorityColors(ticket.priority) },
                    ]}
                  />

                  <View style={s.ticketBody}>
                    {/* Top row: ticket number + status pill */}
                    <View style={s.ticketTop}>
                      <Text style={s.ticketNum}>{ticket.ticket_number}</Text>
                      <View style={[s.statusPill, { backgroundColor: sc.bg, borderColor: sc.border }]}>
                        <Text style={[s.statusPillText, { color: sc.text }]}>
                          {statusLabel(ticket.status)}
                        </Text>
                      </View>
                    </View>

                    {/* Title */}
                    <Text style={s.ticketTitle} numberOfLines={2}>
                      {ticket.title}
                    </Text>

                    {/* Meta: lab · equipment · priority */}
                    <View style={s.ticketMeta}>
                      {ticket.equipment?.lab?.name && (
                        <Text style={s.metaText}>{ticket.equipment.lab.name}</Text>
                      )}
                      {ticket.equipment?.equipment_code && (
                        <Text style={s.metaText}>· {ticket.equipment.equipment_code}</Text>
                      )}
                      <View
                        style={[
                          s.priorityPill,
                          { borderColor: priorityColors(ticket.priority) + '66' },
                        ]}
                      >
                        <Text
                          style={[
                            s.priorityPillText,
                            { color: priorityColors(ticket.priority) },
                          ]}
                        >
                          {ticket.priority.charAt(0).toUpperCase() + ticket.priority.slice(1)}
                        </Text>
                      </View>
                    </View>
                  </View>
                </TouchableOpacity>
              );
            })
          )}
        </View>

        <View style={{ height: spacing.xl }} />
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
  centered: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    gap: spacing.md,
    padding: spacing.lg,
  },
  errorIcon: { fontSize: 36 },
  errorText: {
    fontSize: font.base,
    color: colors.textSecondary,
    textAlign: 'center',
    lineHeight: 22,
  },
  retryBtn: {
    marginTop: spacing.sm,
    backgroundColor: colors.primary,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.sm + 2,
    borderRadius: radius.full,
  },
  retryBtnText: {
    color: '#fff',
    fontWeight: font.bold,
    fontSize: font.base,
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
  avatar: {
    width: 34,
    height: 34,
    borderRadius: radius.full,
    backgroundColor: colors.primary,
    alignItems: 'center',
    justifyContent: 'center',
  },
  avatarText: {
    fontSize: font.sm,
    fontWeight: font.bold,
    color: '#fff',
  },

  // ── Scroll ──────────────────────────────────────────────────────────────
  scroll: {
    paddingBottom: spacing.lg,
  },

  // ── Welcome ──────────────────────────────────────────────────────────────
  welcomeBanner: {
    margin: spacing.lg,
    marginBottom: spacing.md,
    padding: spacing.lg,
    backgroundColor: 'rgba(45,212,191,0.07)',
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radius.xl,
  },
  welcomeTitle: {
    fontSize: font.xl,
    fontWeight: font.bold,
    color: colors.textPrimary,
    marginBottom: spacing.xs,
  },
  welcomeAccent: {
    color: colors.primary,
  },
  welcomeSub: {
    fontSize: font.sm,
    color: colors.textMuted,
  },

  // ── Stats ─────────────────────────────────────────────────────────────────
  statsRow: {
    flexDirection: 'row',
    paddingHorizontal: spacing.lg,
    gap: spacing.sm,
    marginBottom: spacing.lg,
  },
  statCard: {
    flex: 1,
    backgroundColor: colors.bgCard,
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radius.xl,
    padding: spacing.md,
    paddingLeft: spacing.md + 2,
    // Left accent bar via border
    borderLeftWidth: 3,
    borderLeftColor: colors.primary,
    overflow: 'hidden',
  },
  statLabel: {
    fontSize: font.xs,
    color: colors.textMuted,
    fontWeight: font.medium,
    marginBottom: spacing.xs,
    lineHeight: 15,
  },
  statValue: {
    fontSize: font.xl3,
    fontWeight: font.bold,
    color: colors.primary,
    lineHeight: 30,
  },

  // ── Quick actions ─────────────────────────────────────────────────────────
  sectionHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: spacing.lg,
    marginBottom: spacing.sm,
  },
  sectionTitle: {
    fontSize: font.lg,
    fontWeight: font.bold,
    color: colors.primary,
  },
  actionGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    paddingHorizontal: spacing.lg,
    gap: spacing.sm,
    marginBottom: spacing.lg,
  },
  actionCard: {
    width: '48%',
    backgroundColor: colors.bgCard,
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radius.xl,
    padding: spacing.md,
    alignItems: 'center',
    gap: spacing.xs,
  },
  actionIcon: {
    fontSize: 26,
    lineHeight: 32,
  },
  actionTitle: {
    fontSize: font.sm,
    fontWeight: font.semibold,
    color: colors.primary,
    textAlign: 'center',
  },
  actionDesc: {
    fontSize: font.xs,
    color: colors.textMuted,
    textAlign: 'center',
    lineHeight: 16,
  },

  // ── Recent reports card ───────────────────────────────────────────────────
  card: {
    marginHorizontal: spacing.lg,
    backgroundColor: colors.bgCard,
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radius.xl,
    padding: spacing.lg,
  },
  cardHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: spacing.md,
  },
  viewAll: {
    fontSize: font.sm,
    fontWeight: font.semibold,
    color: colors.primary,
  },

  // ── Empty state ───────────────────────────────────────────────────────────
  emptyState: {
    alignItems: 'center',
    paddingVertical: spacing.xl,
    gap: spacing.sm,
  },
  emptyIcon: {
    fontSize: 32,
    opacity: 0.4,
  },
  emptyText: {
    fontSize: font.base,
    color: colors.textMuted,
  },
  emptyLink: {
    fontSize: font.base,
    color: colors.primary,
    fontWeight: font.semibold,
  },

  // ── Ticket card ───────────────────────────────────────────────────────────
  ticketCard: {
    flexDirection: 'row',
    backgroundColor: colors.bgCardAlt,
    borderRadius: radius.lg,
    marginBottom: spacing.sm,
    overflow: 'hidden',
  },
  priorityBar: {
    width: 3,
    borderRadius: 0,
  },
  ticketBody: {
    flex: 1,
    padding: spacing.md,
    gap: spacing.xs,
  },
  ticketTop: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  ticketNum: {
    fontSize: font.xs,
    fontWeight: font.semibold,
    color: colors.primary,
  },
  statusPill: {
    borderWidth: 1,
    borderRadius: radius.full,
    paddingHorizontal: spacing.sm,
    paddingVertical: 2,
  },
  statusPillText: {
    fontSize: font.xs - 1,
    fontWeight: font.semibold,
  },
  ticketTitle: {
    fontSize: font.base,
    fontWeight: font.medium,
    color: colors.textPrimary,
    lineHeight: 20,
  },
  ticketMeta: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.xs,
    flexWrap: 'wrap',
  },
  metaText: {
    fontSize: font.xs,
    color: colors.textMuted,
  },
  priorityPill: {
    borderWidth: 1,
    borderRadius: radius.full,
    paddingHorizontal: spacing.xs + 2,
    paddingVertical: 1,
    marginLeft: 'auto',
  },
  priorityPillText: {
    fontSize: font.xs - 1,
    fontWeight: font.semibold,
  },
});