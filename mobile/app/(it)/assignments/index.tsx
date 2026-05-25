/**
 * app/(it)/assignments/index.tsx  —  My Assignments
 *
 * Mirrors: resources/js/Pages/IT/Assignments/Index.vue
 *
 * Fetches GET /api/it/assignments?status=&page=
 * Returns: { tickets (paginated), stats }
 *
 * Features:
 *   - 4-stat row: Total Assigned · In Progress · High Priority · Completed Today
 *   - Filter tabs: All · In Progress · High Priority
 *   - Ticket cards — richer than the queue cards, showing description excerpt
 *   - Per-card actions: View Details · Update Status
 *   - Pull-to-refresh
 *   - Pagination
 *   - Empty state with link to queue
 */

import React, {
  useEffect, useState, useCallback, useRef,
} from 'react';
import {
  View, Text, FlatList, TouchableOpacity,
  StyleSheet, StatusBar, ActivityIndicator,
  RefreshControl,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { router } from 'expo-router';
import { colors, spacing, radius, font } from '@/constants/theme';
import { loadAuth } from '@/utils/auth';
import { apiUrl } from '@/constants/api';

// ─── Types ────────────────────────────────────────────────────────────────────

type Ticket = {
  id:            number;
  ticket_number: string;
  title:         string;
  description:   string;
  status:        string;
  priority:      string;
  category:      string;
  assigned_to:   number | null;
  created_at:    string;
  reporter?: { first_name: string; last_name: string };
  equipment?: {
    equipment_code: string;
    lab?: { name: string };
  };
};

type Stats = {
  total_assigned:  number;
  in_progress:     number;
  high_priority:   number;
  completed_today: number;
};

type AssignmentsResponse = {
  tickets: {
    data:          Ticket[];
    current_page:  number;
    last_page:     number;
    total:         number;
    from:          number | null;
    to:            number | null;
    prev_page_url: string | null;
    next_page_url: string | null;
  };
  stats: Stats;
};

type StatusFilter = '' | 'in-progress' | 'active';

// ─── Helpers ─────────────────────────────────────────────────────────────────

function statusStyle(s: string): { bg: string; text: string; border: string } {
  switch (s) {
    case 'new':         return { bg: colors.infoBg,           text: colors.infoLight,    border: colors.infoBorder    };
    case 'assigned':    return { bg: 'rgba(168,85,247,0.14)', text: '#c084fc',           border: 'rgba(168,85,247,0.3)' };
    case 'in-progress': return { bg: colors.warningBg,        text: colors.warningLight, border: colors.warningBorder };
    case 'resolved':
    case 'closed':      return { bg: colors.successBg,        text: colors.successLight, border: colors.successBorder };
    default:            return { bg: colors.borderLight,      text: colors.textMuted,    border: colors.border        };
  }
}

function statusLabel(s: string): string {
  return s.replace('-', ' ').replace(/\b\w/g, c => c.toUpperCase());
}

function priorityColor(p: string): string {
  return p === 'high' ? colors.danger : p === 'medium' ? colors.warning : colors.success;
}

function priorityEmoji(p: string): string {
  return p === 'high' ? '🔴' : p === 'medium' ? '🟡' : '🟢';
}

function diffForHumans(iso: string): string {
  const sec = Math.floor((Date.now() - new Date(iso).getTime()) / 1000);
  if (sec < 60)    return `${sec}s ago`;
  if (sec < 3600)  return `${Math.floor(sec / 60)}m ago`;
  if (sec < 86400) return `${Math.floor(sec / 3600)}h ago`;
  return `${Math.floor(sec / 86400)}d ago`;
}

// ─── Assignment Card ──────────────────────────────────────────────────────────

function AssignmentCard({ ticket }: { ticket: Ticket }) {
  const sc  = statusStyle(ticket.status);
  const pc  = priorityColor(ticket.priority);

  return (
    <TouchableOpacity
      style={[card.wrap, { borderLeftColor: pc }]}
      onPress={() => router.push(`/(it)/assignments/${ticket.id}` as any)}
      activeOpacity={0.75}
    >
      {/* Top: ticket number + priority badge */}
      <View style={card.top}>
        <Text style={card.ticketNum}>{ticket.ticket_number}</Text>
        <View style={[card.priorityBadge, { borderColor: pc + '66' }]}>
          <Text style={[card.priorityText, { color: pc }]}>
            {priorityEmoji(ticket.priority)}{' '}
            {ticket.priority.charAt(0).toUpperCase() + ticket.priority.slice(1)}
          </Text>
        </View>
      </View>

      {/* Title */}
      <Text style={card.title} numberOfLines={2}>{ticket.title}</Text>

      {/* Meta row */}
      <View style={card.metaRow}>
        {ticket.reporter && (
          <Text style={card.meta}>
            👤 {ticket.reporter.first_name} {ticket.reporter.last_name}
          </Text>
        )}
        {ticket.equipment?.lab?.name && (
          <Text style={card.meta}>📍 {ticket.equipment.lab.name}</Text>
        )}
        {ticket.equipment?.equipment_code && (
          <Text style={card.meta}>{ticket.equipment.equipment_code}</Text>
        )}
      </View>
      <View style={card.metaRow}>
        <Text style={card.meta}>🕒 {diffForHumans(ticket.created_at)}</Text>
        <Text style={card.meta}>
          🏷️ {ticket.category.charAt(0).toUpperCase() + ticket.category.slice(1)}
        </Text>
      </View>

      {/* Description excerpt */}
      {!!ticket.description && (
        <Text style={card.excerpt} numberOfLines={2}>
          {ticket.description}
        </Text>
      )}

      {/* Footer: status + action buttons */}
      <View style={card.footer}>
        <View style={[card.statusPill, { backgroundColor: sc.bg, borderColor: sc.border }]}>
          <Text style={[card.statusText, { color: sc.text }]}>
            {statusLabel(ticket.status)}
          </Text>
        </View>
        <View style={card.actions}>
          <TouchableOpacity
            style={card.viewBtn}
            onPress={() => router.push(`/(it)/assignments/${ticket.id}` as any)}
            activeOpacity={0.8}
          >
            <Text style={card.viewBtnText}>View Details</Text>
          </TouchableOpacity>
          <TouchableOpacity
            style={card.updateBtn}
            onPress={() => router.push(`/(it)/assignments/${ticket.id}/edit` as any)}
            activeOpacity={0.8}
          >
            <Text style={card.updateBtnText}>Update Status</Text>
          </TouchableOpacity>
        </View>
      </View>
    </TouchableOpacity>
  );
}

const card = StyleSheet.create({
  wrap: {
    backgroundColor: colors.bgCard,
    borderRadius: radius.xl,
    borderLeftWidth: 3,
    padding: spacing.md,
    marginBottom: spacing.sm,
    gap: spacing.xs + 1,
  },
  top: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  ticketNum: { fontSize: font.xs, fontWeight: font.semibold, color: colors.primary },
  priorityBadge: {
    borderWidth: 1, borderRadius: radius.full,
    paddingHorizontal: 8, paddingVertical: 2,
  },
  priorityText: { fontSize: font.xs - 1, fontWeight: font.semibold },
  title: {
    fontSize: font.base, fontWeight: font.medium,
    color: colors.textPrimary, lineHeight: 21,
  },
  metaRow:  { flexDirection: 'row', gap: spacing.md, flexWrap: 'wrap' },
  meta:     { fontSize: font.xs, color: colors.textMuted },
  excerpt: {
    fontSize: font.sm, color: colors.textMuted,
    lineHeight: 19, marginTop: spacing.xs,
  },
  footer: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingTop: spacing.xs + 2,
    borderTopWidth: 1,
    borderTopColor: 'rgba(45,212,191,0.08)',
    marginTop: spacing.xs,
  },
  statusPill: {
    borderWidth: 1, borderRadius: radius.full,
    paddingHorizontal: 8, paddingVertical: 3,
  },
  statusText: { fontSize: font.xs - 1, fontWeight: font.semibold },
  actions:    { flexDirection: 'row', gap: spacing.xs + 2 },
  viewBtn: {
    backgroundColor: colors.primary, borderRadius: radius.md,
    paddingHorizontal: spacing.md, paddingVertical: spacing.xs + 2,
  },
  viewBtnText: { fontSize: font.xs, fontWeight: font.bold, color: '#fff' },
  updateBtn: {
    backgroundColor: colors.primaryLight, borderWidth: 1,
    borderColor: colors.border, borderRadius: radius.md,
    paddingHorizontal: spacing.md, paddingVertical: spacing.xs + 2,
  },
  updateBtnText: { fontSize: font.xs, fontWeight: font.semibold, color: colors.primary },
});

// ─── Filter Tab ───────────────────────────────────────────────────────────────

function FilterTab({
  label, active, onPress,
}: { label: string; active: boolean; onPress: () => void }) {
  return (
    <TouchableOpacity
      style={[ft.tab, active && ft.tabActive]}
      onPress={onPress}
      activeOpacity={0.75}
    >
      <Text style={[ft.text, active && ft.textActive]}>{label}</Text>
    </TouchableOpacity>
  );
}

const ft = StyleSheet.create({
  tab: {
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.xs + 4,
    borderRadius: radius.full,
    backgroundColor: colors.bgCard,
    borderWidth: 1,
    borderColor: colors.border,
    marginRight: spacing.xs,
  },
  tabActive: {
    backgroundColor: colors.primaryLight,
    borderColor: colors.primary,
  },
  text:       { fontSize: font.sm, color: colors.textMuted, fontWeight: font.medium },
  textActive: { color: colors.primary, fontWeight: font.semibold },
});

// ─── Main Component ───────────────────────────────────────────────────────────

export default function ITAssignmentsScreen() {
  const [token,       setToken]       = useState<string | null>(null);
  const [tickets,     setTickets]     = useState<Ticket[]>([]);
  const [stats,       setStats]       = useState<Stats | null>(null);
  const [pagination,  setPagination]  = useState<Omit<AssignmentsResponse['tickets'], 'data'> | null>(null);
  const [loading,     setLoading]     = useState(true);
  const [refreshing,  setRefreshing]  = useState(false);
  const [pageLoading, setPageLoading] = useState(false);
  const [error,       setError]       = useState<string | null>(null);

  const [activeFilter, setActiveFilter] = useState<StatusFilter>('');
  const [page,         setPage]         = useState(1);

  // ── Auth ──────────────────────────────────────────────────────────────────
  useEffect(() => {
    loadAuth().then((auth) => {
      if (!auth) { router.replace('/login'); return; }
      setToken(auth.token);
    });
  }, []);

  // ── Fetch ─────────────────────────────────────────────────────────────────
  const fetchAssignments = useCallback(async (
    tok: string,
    opts: { status?: StatusFilter; page?: number } = {}
  ) => {
    const params = new URLSearchParams();
    if (opts.status)              params.set('status', opts.status);
    if (opts.page && opts.page > 1) params.set('page', String(opts.page));

    try {
      const res = await fetch(`${apiUrl('it/assignments')}?${params}`, {
        headers: { 'Accept': 'application/json', 'Authorization': `Bearer ${tok}` },
      });
      if (res.status === 401) { router.replace('/login'); return; }
      if (!res.ok) throw new Error(`Server error ${res.status}`);
      const json: AssignmentsResponse = await res.json();
      setTickets(json.tickets.data);
      setStats(json.stats);
      const { data: _, ...meta } = json.tickets;
      setPagination(meta);
      setError(null);
    } catch (e: any) {
      setError(e.message ?? 'Could not load assignments.');
    }
  }, []);

  // Initial load
  useEffect(() => {
    if (!token) return;
    setLoading(true);
    fetchAssignments(token).finally(() => setLoading(false));
  }, [token]); // eslint-disable-line

  // Filter change
  function handleFilterChange(filter: StatusFilter) {
    if (!token) return;
    setActiveFilter(filter);
    setPage(1);
    setLoading(true);
    fetchAssignments(token, { status: filter, page: 1 }).finally(() => setLoading(false));
  }

  // Pagination
  function handlePage(p: number) {
    if (!token) return;
    setPage(p);
    setPageLoading(true);
    fetchAssignments(token, { status: activeFilter, page: p }).finally(() => setPageLoading(false));
  }

  // Pull-to-refresh
  const onRefresh = useCallback(async () => {
    if (!token) return;
    setRefreshing(true);
    await fetchAssignments(token, { status: activeFilter, page: 1 });
    setPage(1);
    setRefreshing(false);
  }, [token, activeFilter, fetchAssignments]);

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

  // ─── Main render ──────────────────────────────────────────────────────────
  return (
    <SafeAreaView style={s.root} edges={['top']}>
      <StatusBar barStyle="light-content" backgroundColor={colors.bgPrimary} />

      {/* ── Top Nav ── */}
      <View style={s.nav}>
        <Text style={s.navLogo}>LabFix</Text>
        <Text style={s.navTitle}>My Assignments</Text>
        <View style={{ width: 60 }} />
      </View>

      <FlatList
        data={tickets}
        keyExtractor={(t) => String(t.id)}
        showsVerticalScrollIndicator={false}
        contentContainerStyle={s.listContent}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={onRefresh}
            tintColor={colors.primary}
            colors={[colors.primary]}
          />
        }
        ListHeaderComponent={
          <>
            {/* Page header */}
            <View style={s.pageHeader}>
              <Text style={s.pageTitle}>My Assignments</Text>
              <Text style={s.pageSub}>Tickets currently assigned to you</Text>
            </View>

            {/* Stats row */}
            {stats && (
              <View style={s.statsRow}>
                {[
                  { label: 'Total Assigned',   value: stats.total_assigned,  color: colors.primary },
                  { label: 'In Progress',       value: stats.in_progress,     color: colors.warningLight },
                  { label: 'High Priority',     value: stats.high_priority,   color: colors.danger },
                  { label: 'Done Today',        value: stats.completed_today, color: colors.successLight },
                ].map((st) => (
                  <View key={st.label} style={s.statCard}>
                    <Text style={s.statLabel}>{st.label}</Text>
                    <Text style={[s.statValue, { color: st.color }]}>{st.value}</Text>
                  </View>
                ))}
              </View>
            )}

            {/* Filter tabs */}
            <View style={s.filterRow}>
              <FilterTab
                label={`All (${stats?.total_assigned ?? 0})`}
                active={activeFilter === ''}
                onPress={() => handleFilterChange('')}
              />
              <FilterTab
                label={`In Progress (${stats?.in_progress ?? 0})`}
                active={activeFilter === 'in-progress'}
                onPress={() => handleFilterChange('in-progress')}
              />
              <FilterTab
                label={`High Priority (${stats?.high_priority ?? 0})`}
                active={activeFilter === 'active'}
                onPress={() => handleFilterChange('active')}
              />
            </View>

            {/* Error */}
            {error && (
              <View style={s.errorBanner}>
                <Text style={s.errorBannerText}>{error}</Text>
              </View>
            )}
          </>
        }
        ListEmptyComponent={
          <View style={s.emptyState}>
            <Text style={s.emptyIcon}>📋</Text>
            <Text style={s.emptyTitle}>No assignments yet</Text>
            <Text style={s.emptySub}>
              You don't have any tickets assigned to you at the moment
            </Text>
            <TouchableOpacity
              style={s.emptyBtn}
              onPress={() => router.push('/(it)/queue' as any)}
              activeOpacity={0.85}
            >
              <Text style={s.emptyBtnText}>View Ticket Queue</Text>
            </TouchableOpacity>
          </View>
        }
        ListFooterComponent={
          <>
            {/* Pagination */}
            {pagination && pagination.last_page > 1 && (
              <View style={s.pagination}>
                <Text style={s.pageInfo}>
                  {pagination.from ?? 0}–{pagination.to ?? 0} of {pagination.total} tickets
                </Text>
                <View style={s.pageControls}>
                  <TouchableOpacity
                    style={[s.pageBtn, !pagination.prev_page_url && s.pageBtnDisabled]}
                    onPress={() => handlePage(page - 1)}
                    disabled={!pagination.prev_page_url || pageLoading}
                  >
                    <Text style={s.pageBtnText}>← Prev</Text>
                  </TouchableOpacity>
                  <View style={s.pageCurrent}>
                    <Text style={s.pageCurrentText}>{page}</Text>
                  </View>
                  <TouchableOpacity
                    style={[s.pageBtn, !pagination.next_page_url && s.pageBtnDisabled]}
                    onPress={() => handlePage(page + 1)}
                    disabled={!pagination.next_page_url || pageLoading}
                  >
                    <Text style={s.pageBtnText}>Next →</Text>
                  </TouchableOpacity>
                </View>
                {pageLoading && (
                  <ActivityIndicator size="small" color={colors.primary} />
                )}
              </View>
            )}

            {/* Quick link to queue */}
            {tickets.length > 0 && (
              <TouchableOpacity
                style={s.queueLink}
                onPress={() => router.push('/(it)/queue' as any)}
                activeOpacity={0.75}
              >
                <Text style={s.queueLinkText}>View Full Ticket Queue →</Text>
              </TouchableOpacity>
            )}

            <View style={{ height: spacing.xxl }} />
          </>
        }
        renderItem={({ item }) => <AssignmentCard ticket={item} />}
      />
    </SafeAreaView>
  );
}

// ─── Styles ───────────────────────────────────────────────────────────────────

const s = StyleSheet.create({
  root:    { flex: 1, backgroundColor: colors.bgPrimary },
  centered:{ flex: 1, alignItems: 'center', justifyContent: 'center', gap: spacing.md },

  // Nav
  nav: {
    flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center',
    paddingHorizontal: spacing.lg, paddingVertical: spacing.sm + 4,
    borderBottomWidth: 1, borderBottomColor: colors.border,
  },
  navLogo:  { fontSize: font.xl, fontWeight: font.bold, color: colors.primary, letterSpacing: -0.5, width: 60 },
  navTitle: { fontSize: font.base, fontWeight: font.bold, color: colors.textPrimary },

  listContent: { paddingHorizontal: spacing.lg, paddingBottom: spacing.lg },

  // Page header
  pageHeader: { paddingTop: spacing.lg, marginBottom: spacing.md },
  pageTitle:  { fontSize: font.xl, fontWeight: font.bold, color: colors.textPrimary },
  pageSub:    { fontSize: font.sm, color: colors.textMuted, marginTop: 2 },

  // Stats
  statsRow: { flexDirection: 'row', gap: spacing.xs, marginBottom: spacing.md },
  statCard: {
    flex: 1, backgroundColor: colors.bgCard,
    borderWidth: 1, borderColor: colors.border,
    borderLeftWidth: 3, borderLeftColor: colors.primary,
    borderRadius: radius.lg, padding: spacing.sm,
    alignItems: 'center',
  },
  statLabel: { fontSize: font.xs - 2, color: colors.textMuted, textAlign: 'center', marginBottom: 2, lineHeight: 13 },
  statValue: { fontSize: font.lg, fontWeight: font.bold },

  // Filter tabs
  filterRow: {
    flexDirection: 'row',
    marginBottom: spacing.md,
    flexWrap: 'nowrap',
  },

  // Error banner
  errorBanner: {
    backgroundColor: colors.dangerBg, borderWidth: 1, borderColor: colors.dangerBorder,
    borderRadius: radius.md, padding: spacing.sm + 2, marginBottom: spacing.md,
  },
  errorBannerText: { color: '#fca5a5', fontSize: font.sm },

  // Empty state
  emptyState: {
    alignItems: 'center',
    paddingVertical: spacing.xxl,
    gap: spacing.sm,
  },
  emptyIcon:  { fontSize: 36, opacity: 0.35 },
  emptyTitle: { fontSize: font.lg, fontWeight: font.semibold, color: colors.textSecondary },
  emptySub:   { fontSize: font.sm, color: colors.textMuted, textAlign: 'center', lineHeight: 20, maxWidth: 280 },
  emptyBtn: {
    marginTop: spacing.sm,
    backgroundColor: colors.primary,
    paddingHorizontal: spacing.xl,
    paddingVertical: spacing.sm + 4,
    borderRadius: radius.full,
  },
  emptyBtnText: { fontSize: font.base, fontWeight: font.bold, color: '#fff' },

  // Pagination
  pagination:      { alignItems: 'center', paddingVertical: spacing.lg, gap: spacing.sm },
  pageInfo:        { fontSize: font.sm, color: colors.textMuted },
  pageControls:    { flexDirection: 'row', alignItems: 'center', gap: spacing.sm },
  pageBtn:         { paddingHorizontal: spacing.md, paddingVertical: spacing.xs + 2, borderRadius: radius.md, backgroundColor: colors.bgCard, borderWidth: 1, borderColor: colors.border },
  pageBtnDisabled: { opacity: 0.4 },
  pageBtnText:     { fontSize: font.sm, color: colors.textSecondary },
  pageCurrent:     { width: 32, height: 32, borderRadius: radius.full, backgroundColor: colors.primary, alignItems: 'center', justifyContent: 'center' },
  pageCurrentText: { fontSize: font.sm, fontWeight: font.bold, color: '#fff' },

  // Queue link
  queueLink: {
    alignItems: 'center',
    paddingVertical: spacing.md,
    marginTop: spacing.xs,
  },
  queueLinkText: {
    fontSize: font.sm,
    fontWeight: font.semibold,
    color: colors.primary,
  },
});