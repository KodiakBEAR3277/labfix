/**
 * app/(it)/dashboard.tsx  —  IT Support Dashboard
 *
 * Mirrors: resources/js/Pages/IT/Dashboard.vue
 *          + IT DashboardController.php (IT namespace)
 *
 * Fetches from GET /api/it/dashboard (it-support | admin only).
 * API returns:
 *   stats          — { open_tickets, my_assignments, high_priority,
 *                      resolved_today, avg_response_time, team_satisfaction }
 *   recentTickets  — last 10 tickets (with reporter + equipment.lab)
 *   priorityAlerts — up to 5 high-priority open tickets
 *   recentResolved — last 3 resolved: [{ ticket_number, resolved_at }]
 *   unassignedCount — number of unassigned open tickets
 *
 * Sections (match HTML mockup + web Dashboard.vue):
 *   1. Top nav          — LabFix logo + amber avatar, (Admin) tag if role=admin
 *   2. Welcome banner   — "Welcome back, {first_name}!"
 *   3. 6-stat grid      — 2 rows of 3
 *   4. Priority alerts  — high-priority open tickets, "Handle" button
 *   5. Recent Tickets   — last 5 from the 10 fetched, tappable → queue detail
 *   6. Quick Actions    — View Unassigned · My Assignments · Knowledge Base
 *   7. Recent Activity  — last 3 resolved tickets
 *   Pull-to-refresh throughout.
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
import { loadAuth, AuthUser } from '@/utils/auth';
import { apiUrl } from '@/constants/api';

// ─── Types ────────────────────────────────────────────────────────────────────

type Stats = {
  open_tickets:      number;
  my_assignments:    number;
  high_priority:     number;
  resolved_today:    number;
  avg_response_time: string;
  team_satisfaction: number;
};

type Ticket = {
  id:            number;
  ticket_number: string;
  title:         string;
  status:        string;
  priority:      string;
  created_at:    string;
  reporter?: { first_name: string; last_name: string };
  equipment?: { equipment_code: string; lab?: { name: string } };
};

type RecentResolved = {
  ticket_number: string;
  resolved_at:   string;
};

type DashboardData = {
  stats:           Stats;
  recentTickets:   Ticket[];
  priorityAlerts:  Ticket[];
  recentResolved:  RecentResolved[];
  unassignedCount: number;
};

// ─── Helpers ─────────────────────────────────────────────────────────────────

function statusColor(status: string): { bg: string; text: string; border: string } {
  switch (status) {
    case 'new':         return { bg: colors.infoBg,            text: colors.infoLight,    border: colors.infoBorder    };
    case 'assigned':    return { bg: 'rgba(168,85,247,0.14)',   text: '#c084fc',           border: 'rgba(168,85,247,0.3)' };
    case 'in-progress': return { bg: colors.warningBg,         text: colors.warningLight, border: colors.warningBorder };
    case 'resolved':
    case 'closed':      return { bg: colors.successBg,         text: colors.successLight, border: colors.successBorder };
    default:            return { bg: colors.borderLight,       text: colors.textMuted,    border: colors.border        };
  }
}

function statusLabel(s: string): string {
  return s.replace('-', ' ').replace(/\b\w/g, c => c.toUpperCase());
}

function priorityColor(p: string): string {
  return p === 'high' ? colors.danger : p === 'medium' ? colors.warning : colors.success;
}

function diffForHumans(iso: string): string {
  const seconds = Math.floor((Date.now() - new Date(iso).getTime()) / 1000);
  if (seconds < 60)    return `${seconds}s ago`;
  if (seconds < 3600)  return `${Math.floor(seconds / 60)}m ago`;
  if (seconds < 86400) return `${Math.floor(seconds / 3600)}h ago`;
  return `${Math.floor(seconds / 86400)}d ago`;
}

function getFirstName(name: string): string {
  return name.split(' ')[0];
}

function getInitials(name: string): string {
  return name.split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase();
}

// ─── Sub-components ───────────────────────────────────────────────────────────

// 6-cell stat grid card
function StatCard({
  label,
  value,
  icon,
  detail,
  detailColor,
}: {
  label:        string;
  value:        string | number;
  icon:         string;
  detail?:      string;
  detailColor?: string;
}) {
  return (
    <View style={sc.card}>
      <View style={sc.header}>
        <Text style={sc.label}>{label}</Text>
        <Text style={sc.icon}>{icon}</Text>
      </View>
      <Text style={sc.value}>{value}</Text>
      {!!detail && (
        <Text style={[sc.detail, detailColor ? { color: detailColor } : {}]}>
          {detail}
        </Text>
      )}
    </View>
  );
}
const sc = StyleSheet.create({
  card: {
    flex: 1,
    backgroundColor: colors.bgCard,
    borderWidth: 1,
    borderColor: colors.border,
    borderLeftWidth: 3,
    borderLeftColor: colors.primary,
    borderRadius: radius.xl,
    padding: spacing.md,
    minWidth: '30%',
  },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: spacing.xs,
  },
  label:  { fontSize: font.xs - 1, color: colors.textMuted, fontWeight: font.medium, flex: 1, lineHeight: 14 },
  icon:   { fontSize: 16 },
  value:  { fontSize: font.xl3, fontWeight: font.bold, color: colors.primary, lineHeight: 30 },
  detail: { fontSize: font.xs - 1, color: colors.textMuted, marginTop: 2 },
});

// Priority alert row
function AlertRow({ ticket, onPress }: { ticket: Ticket; onPress: () => void }) {
  return (
    <View style={ar.wrap}>
      <View style={{ flex: 1 }}>
        <Text style={ar.title} numberOfLines={1}>
          {ticket.ticket_number} — {ticket.title}
        </Text>
        <Text style={ar.meta}>
          {diffForHumans(ticket.created_at)} · {ticket.equipment?.lab?.name ?? 'Unknown Lab'}
        </Text>
      </View>
      <TouchableOpacity style={ar.btn} onPress={onPress} activeOpacity={0.8}>
        <Text style={ar.btnText}>Handle</Text>
      </TouchableOpacity>
    </View>
  );
}
const ar = StyleSheet.create({
  wrap: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: 'rgba(239,68,68,0.07)',
    borderWidth: 1,
    borderColor: 'rgba(239,68,68,0.22)',
    borderRadius: radius.lg,
    padding: spacing.md,
    marginBottom: spacing.xs + 2,
    gap: spacing.sm,
  },
  title: { fontSize: font.sm, fontWeight: font.semibold, color: colors.textPrimary, marginBottom: 2 },
  meta:  { fontSize: font.xs, color: colors.textMuted },
  btn: {
    backgroundColor: colors.danger,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.xs + 2,
    borderRadius: radius.md,
    flexShrink: 0,
  },
  btnText: { fontSize: font.xs, fontWeight: font.bold, color: '#fff' },
});

// Ticket list row (recent tickets)
function TicketRow({ ticket, onPress }: { ticket: Ticket; onPress: () => void }) {
  const sc_  = statusColor(ticket.status);
  const pc   = priorityColor(ticket.priority);
  return (
    <TouchableOpacity style={tr.wrap} onPress={onPress} activeOpacity={0.75}>
      <View style={[tr.priorityBar, { backgroundColor: pc }]} />
      <View style={{ flex: 1, paddingLeft: spacing.sm }}>
        <View style={tr.top}>
          <Text style={tr.ticketNum}>{ticket.ticket_number}</Text>
          <View style={[tr.statusPill, { backgroundColor: sc_.bg, borderColor: sc_.border }]}>
            <Text style={[tr.statusText, { color: sc_.text }]}>
              {statusLabel(ticket.status)}
            </Text>
          </View>
        </View>
        <Text style={tr.title} numberOfLines={1}>{ticket.title}</Text>
        <Text style={tr.meta}>
          {ticket.equipment?.lab?.name}
          {ticket.reporter ? ` · ${ticket.reporter.first_name} ${ticket.reporter.last_name}` : ''}
          {' · '}{diffForHumans(ticket.created_at)}
        </Text>
      </View>
    </TouchableOpacity>
  );
}
const tr = StyleSheet.create({
  wrap: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: 'rgba(30,30,40,0.9)',
    borderRadius: radius.lg,
    marginBottom: spacing.xs + 2,
    overflow: 'hidden',
  },
  priorityBar: { width: 3, alignSelf: 'stretch' },
  top: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 2, paddingTop: spacing.sm, paddingRight: spacing.sm },
  ticketNum:  { fontSize: font.xs, fontWeight: font.semibold, color: colors.primary },
  statusPill: { borderWidth: 1, borderRadius: radius.full, paddingHorizontal: 7, paddingVertical: 2 },
  statusText: { fontSize: font.xs - 2, fontWeight: font.semibold },
  title:      { fontSize: font.sm, fontWeight: font.medium, color: colors.textPrimary, paddingRight: spacing.sm, paddingBottom: spacing.xs, lineHeight: 19 },
  meta:       { fontSize: font.xs - 1, color: colors.textMuted, paddingBottom: spacing.sm, paddingRight: spacing.sm },
});

// Quick-action row item
function QuickAction({
  icon,
  title,
  sub,
  onPress,
  iconBg,
}: {
  icon: string; title: string; sub: string; onPress: () => void; iconBg?: string;
}) {
  return (
    <TouchableOpacity style={qa.wrap} onPress={onPress} activeOpacity={0.75}>
      <View style={[qa.iconBox, iconBg ? { backgroundColor: iconBg } : {}]}>
        <Text style={qa.icon}>{icon}</Text>
      </View>
      <View style={{ flex: 1 }}>
        <Text style={qa.title}>{title}</Text>
        <Text style={qa.sub}>{sub}</Text>
      </View>
      <Text style={qa.arrow}>→</Text>
    </TouchableOpacity>
  );
}
const qa = StyleSheet.create({
  wrap: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    paddingVertical: spacing.sm + 2,
    borderBottomWidth: 1,
    borderBottomColor: 'rgba(45,212,191,0.06)',
  },
  iconBox: {
    width: 34,
    height: 34,
    borderRadius: radius.lg,
    backgroundColor: colors.primaryLight,
    alignItems: 'center',
    justifyContent: 'center',
    flexShrink: 0,
  },
  icon:  { fontSize: 16 },
  title: { fontSize: font.sm, fontWeight: font.medium, color: colors.textPrimary },
  sub:   { fontSize: font.xs, color: colors.textMuted },
  arrow: { fontSize: font.sm, color: colors.primary, fontWeight: font.bold },
});

// ─── Main Component ───────────────────────────────────────────────────────────

export default function ITDashboardScreen() {
  const [user,       setUser]       = useState<AuthUser | null>(null);
  const [token,      setToken]      = useState<string | null>(null);
  const [data,       setData]       = useState<DashboardData | null>(null);
  const [loading,    setLoading]    = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error,      setError]      = useState<string | null>(null);

  // ── Auth ──────────────────────────────────────────────────────────────────
  useEffect(() => {
    loadAuth().then((auth) => {
      if (!auth) { router.replace('/login'); return; }
      setUser(auth.user);
      setToken(auth.token);
    });
  }, []);

  // ── Fetch ─────────────────────────────────────────────────────────────────
  const fetchDashboard = useCallback(async (tok: string) => {
    try {
      const res = await fetch(apiUrl('it/dashboard'), {
        headers: {
          'Accept':        'application/json',
          'Authorization': `Bearer ${tok}`,
        },
      });

      if (res.status === 401) { router.replace('/login'); return; }
      if (res.status === 403) { router.replace('/(tabs)/dashboard' as any); return; }
      if (!res.ok) throw new Error(`Server error ${res.status}`);

      setData(await res.json());
      setError(null);
    } catch (e: any) {
      setError(e.message ?? 'Could not load dashboard.');
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

  // ─── Loading ──────────────────────────────────────────────────────────────
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

  if (error || !data) {
    return (
      <SafeAreaView style={s.root}>
        <StatusBar barStyle="light-content" backgroundColor={colors.bgPrimary} />
        <View style={s.centered}>
          <Text style={s.errorIcon}>⚠️</Text>
          <Text style={s.errorText}>{error ?? 'Something went wrong.'}</Text>
          <TouchableOpacity style={s.retryBtn} onPress={() => token && fetchDashboard(token)}>
            <Text style={s.retryText}>Try Again</Text>
          </TouchableOpacity>
        </View>
      </SafeAreaView>
    );
  }

  const { stats, recentTickets, priorityAlerts, recentResolved, unassignedCount } = data;
  const isAdmin = user?.role === 'admin';

  // ─── Main render ──────────────────────────────────────────────────────────
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
            {user ? getInitials(user.name) : 'IT'}
          </Text>
        </TouchableOpacity>
      </View>

      <ScrollView
        showsVerticalScrollIndicator={false}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={onRefresh}
            tintColor={colors.primary}
            colors={[colors.primary]}
          />
        }
      >
        {/* ── Welcome banner ── */}
        <View style={s.welcomeBanner}>
          <Text style={s.welcomeTitle}>
            IT Support Dashboard
          </Text>
          <Text style={s.welcomeSub}>
            Welcome back,{' '}
            <Text style={s.welcomeAccent}>
              {user ? getFirstName(user.name) : 'IT'}!
            </Text>
            {' '}Here's your ticket overview
          </Text>
        </View>

        {/* ── 6 stat cards (2 rows × 3) ── */}
        <View style={s.statsGrid}>
          <View style={s.statsRow}>
            <StatCard
              label="Open Tickets"
              value={stats.open_tickets}
              icon="📋"
              detail={stats.open_tickets > 20 ? 'High volume' : 'Normal'}
            />
            <StatCard
              label="My Assignments"
              value={stats.my_assignments}
              icon="👤"
              detail="Active tasks"
            />
            <StatCard
              label="High Priority"
              value={stats.high_priority}
              icon="🔴"
              detail={stats.high_priority > 5 ? 'Needs attention' : 'Under control'}
              detailColor={stats.high_priority > 5 ? colors.danger : undefined}
            />
          </View>
          <View style={s.statsRow}>
            <StatCard
              label="Resolved Today"
              value={stats.resolved_today}
              icon="✅"
              detail="Great progress!"
              detailColor={colors.successLight}
            />
            <StatCard
              label="Avg Response"
              value={`${stats.avg_response_time}h`}
              icon="⏱️"
              detail="Last 7 days"
            />
            <StatCard
              label="Team Perf."
              value={`${stats.team_satisfaction}%`}
              icon="📊"
              detail="Resolution rate"
            />
          </View>
        </View>

        {/* ── Priority alerts ── */}
        {priorityAlerts.length > 0 && (
          <View style={s.section}>
            <View style={s.sectionHeader}>
              <View style={s.alertHeaderLeft}>
                <Text style={[s.sectionTitle, { color: colors.danger }]}>
                  ⚠️ High Priority Alerts
                </Text>
                <View style={s.alertBadge}>
                  <Text style={s.alertBadgeText}>{priorityAlerts.length}</Text>
                </View>
              </View>
            </View>
            {priorityAlerts.map((ticket) => (
              <AlertRow
                key={ticket.id}
                ticket={ticket}
                onPress={() => router.push(`/(it)/queue/${ticket.id}` as any)}
              />
            ))}
          </View>
        )}

        {/* ── Recent tickets ── */}
        <View style={s.card}>
          <View style={s.cardHeader}>
            <Text style={s.cardTitle}>Recent Tickets</Text>
            <TouchableOpacity onPress={() => router.push('/(it)/queue' as any)}>
              <Text style={s.viewAll}>View All →</Text>
            </TouchableOpacity>
          </View>

          {recentTickets.length === 0 ? (
            <View style={s.emptyState}>
              <Text style={s.emptyIcon}>📋</Text>
              <Text style={s.emptyText}>No recent tickets</Text>
            </View>
          ) : (
            recentTickets.slice(0, 5).map((ticket) => (
              <TicketRow
                key={ticket.id}
                ticket={ticket}
                onPress={() => router.push(`/(it)/queue/${ticket.id}` as any)}
              />
            ))
          )}
        </View>

        {/* ── Quick Actions + Recent Activity side by side ── */}
        <View style={s.card}>
          <Text style={s.cardTitle}>Quick Actions</Text>
          <QuickAction
            icon="🔥"
            title="View Unassigned"
            sub={`${unassignedCount} ticket${unassignedCount !== 1 ? 's' : ''} waiting`}
            iconBg="rgba(239,68,68,0.12)"
            onPress={() => router.push('/(it)/queue' as any)}
          />
          <QuickAction
            icon="👤"
            title="My Assignments"
            sub={`${stats.my_assignments} active`}
            onPress={() => router.push('/(it)/assignments' as any)}
          />
          <QuickAction
            icon="📚"
            title="Knowledge Base"
            sub="Manage articles"
            onPress={() => router.push('/(it)/knowledge-base' as any)}
          />
        </View>

        {/* ── Recent activity (resolved) ── */}
        <View style={s.card}>
          <Text style={s.cardTitle}>Recent Activity</Text>
          {recentResolved.length === 0 ? (
            <View style={s.emptyState}>
              <Text style={s.emptyText}>No recent activity</Text>
            </View>
          ) : (
            recentResolved.map((r, i) => (
              <View
                key={i}
                style={[
                  s.activityRow,
                  i === recentResolved.length - 1 && { borderBottomWidth: 0 },
                ]}
              >
                <View style={[qa.iconBox, { backgroundColor: colors.successBg }]}>
                  <Text style={{ fontSize: 15 }}>✅</Text>
                </View>
                <View style={{ flex: 1 }}>
                  <Text style={s.activityTitle}>
                    Ticket {r.ticket_number} resolved
                  </Text>
                  <Text style={s.activityTime}>{r.resolved_at}</Text>
                </View>
              </View>
            ))
          )}
        </View>

        <View style={{ height: spacing.xxl }} />
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

  // ── Nav ─────────────────────────────────────────────────────────────────
  nav: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.sm + 4,
    borderBottomWidth: 1,
    borderBottomColor: colors.border,
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
  adminTag: {
    backgroundColor: 'rgba(239,68,68,0.15)',
    borderWidth: 1,
    borderColor: 'rgba(239,68,68,0.3)',
    borderRadius: radius.full,
    paddingHorizontal: spacing.sm,
    paddingVertical: 3,
  },
  adminTagText: {
    fontSize: font.xs - 1,
    fontWeight: font.bold,
    color: '#f87171',
  },
  // Amber avatar — distinguishes IT from user (teal) and admin (red)
  avatar: {
    width: 34,
    height: 34,
    borderRadius: radius.full,
    backgroundColor: '#d97706',
    alignItems: 'center',
    justifyContent: 'center',
  },
  avatarText: {
    fontSize: font.xs,
    fontWeight: font.bold,
    color: '#fff',
  },

  // ── Welcome ───────────────────────────────────────────────────────────────
  welcomeBanner: {
    margin: spacing.lg,
    marginBottom: spacing.md,
    backgroundColor: 'rgba(45,212,191,0.07)',
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radius.xl,
    padding: spacing.lg,
  },
  welcomeTitle: {
    fontSize: font.lg,
    fontWeight: font.bold,
    color: colors.primary,
    marginBottom: spacing.xs,
  },
  welcomeSub: {
    fontSize: font.sm,
    color: colors.textMuted,
    lineHeight: 20,
  },
  welcomeAccent: {
    color: colors.textPrimary,
    fontWeight: font.semibold,
  },

  // ── Stats grid ────────────────────────────────────────────────────────────
  statsGrid: {
    paddingHorizontal: spacing.lg,
    gap: spacing.sm,
    marginBottom: spacing.md,
  },
  statsRow: {
    flexDirection: 'row',
    gap: spacing.sm,
  },

  // ── Alert section ─────────────────────────────────────────────────────────
  section: {
    paddingHorizontal: spacing.lg,
    marginBottom: spacing.md,
  },
  sectionHeader: {
    marginBottom: spacing.sm,
  },
  alertHeaderLeft: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
  },
  sectionTitle: {
    fontSize: font.sm,
    fontWeight: font.bold,
    color: colors.primary,
  },
  alertBadge: {
    backgroundColor: colors.danger,
    borderRadius: radius.full,
    width: 20,
    height: 20,
    alignItems: 'center',
    justifyContent: 'center',
  },
  alertBadgeText: {
    fontSize: font.xs - 2,
    fontWeight: font.bold,
    color: '#fff',
  },

  // ── Cards ─────────────────────────────────────────────────────────────────
  card: {
    marginHorizontal: spacing.lg,
    marginBottom: spacing.md,
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
  cardTitle: {
    fontSize: font.base,
    fontWeight: font.bold,
    color: colors.primary,
    marginBottom: spacing.md,
  },
  viewAll: {
    fontSize: font.sm,
    fontWeight: font.semibold,
    color: colors.primary,
  },

  // ── Activity rows ─────────────────────────────────────────────────────────
  activityRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    paddingVertical: spacing.sm + 2,
    borderBottomWidth: 1,
    borderBottomColor: 'rgba(45,212,191,0.06)',
  },
  activityTitle: {
    fontSize: font.sm,
    fontWeight: font.medium,
    color: colors.textPrimary,
  },
  activityTime: {
    fontSize: font.xs,
    color: colors.textMuted,
    marginTop: 2,
  },

  // ── Empty ─────────────────────────────────────────────────────────────────
  emptyState: {
    alignItems: 'center',
    paddingVertical: spacing.lg,
    gap: spacing.xs,
  },
  emptyIcon: { fontSize: 28, opacity: 0.35 },
  emptyText: { fontSize: font.sm, color: colors.textMuted },

  // ── Error ─────────────────────────────────────────────────────────────────
  errorIcon: { fontSize: 36 },
  errorText: {
    fontSize: font.base,
    color: colors.textSecondary,
    textAlign: 'center',
  },
  retryBtn: {
    backgroundColor: colors.primary,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.sm + 2,
    borderRadius: radius.full,
  },
  retryText: {
    fontSize: font.base,
    fontWeight: font.bold,
    color: '#fff',
  },
});