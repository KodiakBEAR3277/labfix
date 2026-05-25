/**
 * app/(it)/queue/index.tsx  —  IT Ticket Queue
 *
 * Mirrors: resources/js/Pages/IT/Tickets/Index.vue
 *
 * Fetches GET /api/it/queue?search=&status=&priority=&lab=&page=
 * Returns: { tickets (paginated), stats, labs }
 *
 * Features:
 *   - Search bar (debounced 350ms)
 *   - Filter pills: Status · Priority · Lab
 *   - Per-row actions: View · Assign to Me (unassigned) · Update (mine)
 *   - Info banner explaining IT view
 *   - Pagination
 *   - Pull-to-refresh
 */

import React, {
  useEffect, useState, useCallback, useRef,
} from 'react';
import {
  View, Text, ScrollView, FlatList, TouchableOpacity,
  TextInput, StyleSheet, StatusBar, ActivityIndicator,
  RefreshControl, Alert,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { router } from 'expo-router';
import { colors, spacing, radius, font } from '@/constants/theme';
import { loadAuth, AuthUser } from '@/utils/auth';
import { apiUrl } from '@/constants/api';

// ─── Types ────────────────────────────────────────────────────────────────────

type Ticket = {
  id:          number;
  ticket_number: string;
  title:       string;
  status:      string;
  priority:    string;
  assigned_to: number | null;
  created_at:  string;
  reporter?:   { id: number; first_name: string; last_name: string };
  assigned_to_user?: { id: number; first_name: string; last_name: string };
  equipment?:  { equipment_code: string; lab?: { name: string } };
};

type Stats = {
  open:          number;
  assigned:      number;
  in_progress:   number;
  high_priority: number;
  unassigned:    number;
};

type QueueResponse = {
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
  labs:  string[];
};

type StatusFilter   = 'all' | 'new' | 'assigned' | 'in-progress' | 'resolved';
type PriorityFilter = 'all' | 'high' | 'medium' | 'low';

// ─── Helpers ─────────────────────────────────────────────────────────────────

function statusStyle(status: string): { bg: string; text: string; border: string } {
  switch (status) {
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

// ─── Ticket Card ─────────────────────────────────────────────────────────────

function TicketCard({
  ticket,
  authUserId,
  onView,
  onAssignSelf,
  assigningId,
}: {
  ticket:       Ticket;
  authUserId:   number;
  onView:       () => void;
  onAssignSelf: () => void;
  assigningId:  number | null;
}) {
  const sc      = statusStyle(ticket.status);
  const pc      = priorityColor(ticket.priority);
  const isMyTicket    = ticket.assigned_to === authUserId;
  const isUnassigned  = !ticket.assigned_to;
  const assigningThis = assigningId === ticket.id;

  return (
    <View style={[card.wrap, { borderLeftColor: pc }]}>
      {/* Top row */}
      <View style={card.top}>
        <Text style={card.ticketNum}>{ticket.ticket_number}</Text>
        <View style={[card.statusPill, { backgroundColor: sc.bg, borderColor: sc.border }]}>
          <Text style={[card.statusText, { color: sc.text }]}>
            {statusLabel(ticket.status)}
          </Text>
        </View>
      </View>

      {/* Title */}
      <Text style={card.title} numberOfLines={2}>{ticket.title}</Text>

      {/* Meta */}
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
        <View style={[card.priorityPill, { borderColor: pc + '66' }]}>
          <Text style={[card.priorityText, { color: pc }]}>
            {priorityEmoji(ticket.priority)} {ticket.priority.charAt(0).toUpperCase() + ticket.priority.slice(1)}
          </Text>
        </View>
        <Text style={[card.meta, { marginLeft: 'auto' }]}>
          {diffForHumans(ticket.created_at)}
        </Text>
      </View>

      {/* Assignment label */}
      {isMyTicket ? (
        <Text style={card.assignedToYou}>👤 Assigned to You</Text>
      ) : ticket.assigned_to && ticket.assigned_to_user ? (
        <Text style={card.assignedToOther}>
          👤 {ticket.assigned_to_user.first_name} {ticket.assigned_to_user.last_name}
        </Text>
      ) : (
        <Text style={card.unassigned}>⏳ Unassigned</Text>
      )}

      {/* Action buttons */}
      <View style={card.actions}>
        <TouchableOpacity style={card.viewBtn} onPress={onView} activeOpacity={0.8}>
          <Text style={card.viewBtnText}>View</Text>
        </TouchableOpacity>

        {isMyTicket && (
          <TouchableOpacity
            style={card.updateBtn}
            onPress={() => router.push(`/(it)/queue/${ticket.id}/edit` as any)}
            activeOpacity={0.8}
          >
            <Text style={card.updateBtnText}>Update</Text>
          </TouchableOpacity>
        )}

        {isUnassigned && (
          <TouchableOpacity
            style={[card.assignBtn, assigningThis && { opacity: 0.6 }]}
            onPress={onAssignSelf}
            disabled={assigningThis}
            activeOpacity={0.8}
          >
            {assigningThis ? (
              <ActivityIndicator size="small" color={colors.primary} />
            ) : (
              <Text style={card.assignBtnText}>Assign to Me</Text>
            )}
          </TouchableOpacity>
        )}
      </View>
    </View>
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
  ticketNum:    { fontSize: font.xs, fontWeight: font.semibold, color: colors.primary },
  statusPill:   { borderWidth: 1, borderRadius: radius.full, paddingHorizontal: 8, paddingVertical: 2 },
  statusText:   { fontSize: font.xs - 1, fontWeight: font.semibold },
  title:        { fontSize: font.base, fontWeight: font.medium, color: colors.textPrimary, lineHeight: 21 },
  metaRow:      { flexDirection: 'row', alignItems: 'center', gap: spacing.xs, flexWrap: 'wrap' },
  meta:         { fontSize: font.xs, color: colors.textMuted },
  priorityPill: { borderWidth: 1, borderRadius: radius.full, paddingHorizontal: 7, paddingVertical: 2 },
  priorityText: { fontSize: font.xs - 1, fontWeight: font.semibold },
  assignedToYou:   { fontSize: font.xs, color: colors.primary, fontWeight: font.semibold },
  assignedToOther: { fontSize: font.xs, color: colors.textMuted },
  unassigned:      { fontSize: font.xs, color: colors.textMuted },
  actions: { flexDirection: 'row', gap: spacing.xs + 2, marginTop: spacing.xs },
  viewBtn: {
    backgroundColor: colors.primary,
    borderRadius: radius.md,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.xs + 2,
  },
  viewBtnText: { fontSize: font.xs, fontWeight: font.bold, color: '#fff' },
  updateBtn: {
    backgroundColor: colors.primaryLight,
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radius.md,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.xs + 2,
  },
  updateBtnText: { fontSize: font.xs, fontWeight: font.semibold, color: colors.primary },
  assignBtn: {
    backgroundColor: colors.primaryLight,
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radius.md,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.xs + 2,
    minWidth: 90,
    alignItems: 'center',
  },
  assignBtnText: { fontSize: font.xs, fontWeight: font.semibold, color: colors.primary },
});

// ─── Filter Pill ─────────────────────────────────────────────────────────────

function FilterPill({
  label, active, onPress,
}: { label: string; active: boolean; onPress: () => void }) {
  return (
    <TouchableOpacity
      style={[fp.pill, active && fp.pillActive]}
      onPress={onPress}
      activeOpacity={0.75}
    >
      <Text style={[fp.text, active && fp.textActive]}>{label}</Text>
    </TouchableOpacity>
  );
}
const fp = StyleSheet.create({
  pill:       { backgroundColor: colors.bgCard, borderWidth: 1, borderColor: colors.border, borderRadius: radius.full, paddingHorizontal: spacing.md, paddingVertical: spacing.xs + 2, marginRight: spacing.xs },
  pillActive: { backgroundColor: colors.primaryLight, borderColor: colors.primary },
  text:       { fontSize: font.xs, color: colors.textMuted, fontWeight: font.medium, whiteSpace: 'nowrap' },
  textActive: { color: colors.primary, fontWeight: font.semibold },
});

// ─── Main Component ───────────────────────────────────────────────────────────

export default function ITQueueScreen() {
  const [authUser,    setAuthUser]    = useState<AuthUser | null>(null);
  const [token,       setToken]       = useState<string | null>(null);
  const [tickets,     setTickets]     = useState<Ticket[]>([]);
  const [stats,       setStats]       = useState<Stats | null>(null);
  const [labs,        setLabs]        = useState<string[]>([]);
  const [pagination,  setPagination]  = useState<Omit<QueueResponse['tickets'], 'data'> | null>(null);
  const [loading,     setLoading]     = useState(true);
  const [refreshing,  setRefreshing]  = useState(false);
  const [pageLoading, setPageLoading] = useState(false);
  const [error,       setError]       = useState<string | null>(null);
  const [assigningId, setAssigningId] = useState<number | null>(null);

  // Filters
  const [search,   setSearch]   = useState('');
  const [status,   setStatus]   = useState<StatusFilter>('all');
  const [priority, setPriority] = useState<PriorityFilter>('all');
  const [lab,      setLab]      = useState('all');
  const [page,     setPage]     = useState(1);

  const searchTimer = useRef<ReturnType<typeof setTimeout> | null>(null);

  // ── Auth ──────────────────────────────────────────────────────────────────
  useEffect(() => {
    loadAuth().then((auth) => {
      if (!auth) { router.replace('/login'); return; }
      setAuthUser(auth.user);
      setToken(auth.token);
    });
  }, []);

  // ── Fetch ─────────────────────────────────────────────────────────────────
  const fetchQueue = useCallback(async (
    tok: string,
    opts: { search?: string; status?: string; priority?: string; lab?: string; page?: number } = {}
  ) => {
    const params = new URLSearchParams();
    if (opts.search)                          params.set('search',   opts.search);
    if (opts.status && opts.status !== 'all') params.set('status',   opts.status);
    if (opts.priority && opts.priority !== 'all') params.set('priority', opts.priority);
    if (opts.lab && opts.lab !== 'all')       params.set('lab',      opts.lab);
    if (opts.page && opts.page > 1)           params.set('page',     String(opts.page));

    try {
      const res = await fetch(`${apiUrl('it/queue')}?${params}`, {
        headers: { 'Accept': 'application/json', 'Authorization': `Bearer ${tok}` },
      });
      if (res.status === 401) { router.replace('/login'); return; }
      if (!res.ok) throw new Error(`Server error ${res.status}`);
      const json: QueueResponse = await res.json();
      setTickets(json.tickets.data);
      setStats(json.stats);
      setLabs(json.labs);
      const { data: _, ...meta } = json.tickets;
      setPagination(meta);
      setError(null);
    } catch (e: any) {
      setError(e.message ?? 'Could not load queue.');
    }
  }, []);

  // Initial load
  useEffect(() => {
    if (!token) return;
    setLoading(true);
    fetchQueue(token).finally(() => setLoading(false));
  }, [token]); // eslint-disable-line

  // Search debounce
  useEffect(() => {
    if (!token) return;
    if (searchTimer.current) clearTimeout(searchTimer.current);
    searchTimer.current = setTimeout(() => {
      setPage(1);
      setLoading(true);
      fetchQueue(token, { search, status, priority, lab, page: 1 }).finally(() => setLoading(false));
    }, 350);
    return () => { if (searchTimer.current) clearTimeout(searchTimer.current); };
  }, [search]); // eslint-disable-line

  function applyFilters(overrides: Partial<{ status: string; priority: string; lab: string }>) {
    if (!token) return;
    const next = { search, status, priority, lab, page: 1, ...overrides };
    setPage(1);
    setLoading(true);
    fetchQueue(token, next).finally(() => setLoading(false));
  }

  function handlePage(p: number) {
    if (!token) return;
    setPage(p);
    setPageLoading(true);
    fetchQueue(token, { search, status, priority, lab, page: p }).finally(() => setPageLoading(false));
  }

  const onRefresh = useCallback(async () => {
    if (!token) return;
    setRefreshing(true);
    await fetchQueue(token, { search, status, priority, lab, page: 1 });
    setPage(1);
    setRefreshing(false);
  }, [token, search, status, priority, lab, fetchQueue]);

  // ── Assign to self ────────────────────────────────────────────────────────
  async function handleAssignSelf(ticketId: number) {
    if (!token) return;
    setAssigningId(ticketId);
    try {
      const res = await fetch(apiUrl(`it/queue/${ticketId}/assign-self`), {
        method:  'POST',
        headers: { 'Accept': 'application/json', 'Authorization': `Bearer ${token}` },
      });
      if (!res.ok) throw new Error('Failed to assign ticket.');
      // Refresh list
      await fetchQueue(token, { search, status, priority, lab, page });
    } catch {
      Alert.alert('Error', 'Could not assign ticket. Please try again.');
    } finally {
      setAssigningId(null);
    }
  }

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
        <Text style={s.navTitle}>Ticket Queue</Text>
        <View style={{ width: 60 }} />
      </View>

      <FlatList
        data={tickets}
        keyExtractor={(t) => String(t.id)}
        showsVerticalScrollIndicator={false}
        refreshControl={
          <RefreshControl refreshing={refreshing} onRefresh={onRefresh}
            tintColor={colors.primary} colors={[colors.primary]} />
        }
        contentContainerStyle={s.listContent}
        ListHeaderComponent={
          <>
            {/* Page header */}
            <View style={s.pageHeader}>
              <Text style={s.pageTitle}>Ticket Queue</Text>
              <Text style={s.pageSub}>View and manage all open tickets</Text>
            </View>

            {/* Stats row */}
            {stats && (
              <View style={s.statsRow}>
                {[
                  { label: 'Open',        value: stats.open          },
                  { label: 'Assigned',    value: stats.assigned      },
                  { label: 'In Progress', value: stats.in_progress   },
                  { label: 'High Prio',   value: stats.high_priority, color: colors.danger },
                  { label: 'Unassigned',  value: stats.unassigned    },
                ].map((st) => (
                  <View key={st.label} style={s.statCard}>
                    <Text style={s.statLabel}>{st.label}</Text>
                    <Text style={[s.statValue, st.color ? { color: st.color } : {}]}>
                      {st.value}
                    </Text>
                  </View>
                ))}
              </View>
            )}

            {/* Info banner */}
            <View style={s.infoBanner}>
              <Text style={s.infoBannerIcon}>ℹ️</Text>
              <Text style={s.infoBannerText}>
                <Text style={{ fontWeight: font.bold, color: colors.infoLight }}>IT Support View: </Text>
                You can view all tickets and assign unassigned ones to yourself.{' '}
                <Text
                  style={{ color: colors.primary, fontWeight: font.semibold }}
                  onPress={() => router.push('/(it)/assignments' as any)}
                >
                  View My Assignments →
                </Text>
              </Text>
            </View>

            {/* Search */}
            <View style={s.searchBar}>
              <Text style={s.searchIcon}>🔍</Text>
              <TextInput
                style={s.searchInput}
                value={search}
                onChangeText={setSearch}
                placeholder="Search by ID, title, reporter, location…"
                placeholderTextColor={colors.textDisabled}
                returnKeyType="search"
                clearButtonMode="while-editing"
              />
            </View>

            {/* Status filter */}
            <ScrollView horizontal showsHorizontalScrollIndicator={false}
              contentContainerStyle={s.filterRow}>
              {(['all','new','assigned','in-progress','resolved'] as StatusFilter[]).map((opt) => (
                <FilterPill
                  key={opt}
                  label={opt === 'all' ? 'All Status' : statusLabel(opt)}
                  active={status === opt}
                  onPress={() => { setStatus(opt); applyFilters({ status: opt }); }}
                />
              ))}
            </ScrollView>

            {/* Priority + Lab filters */}
            <ScrollView horizontal showsHorizontalScrollIndicator={false}
              contentContainerStyle={s.filterRow}>
              {(['all','high','medium','low'] as PriorityFilter[]).map((opt) => (
                <FilterPill
                  key={opt}
                  label={opt === 'all' ? 'All Priority' : opt.charAt(0).toUpperCase() + opt.slice(1)}
                  active={priority === opt}
                  onPress={() => { setPriority(opt); applyFilters({ priority: opt }); }}
                />
              ))}
              <View style={s.filterDivider} />
              <FilterPill
                label="All Labs"
                active={lab === 'all'}
                onPress={() => { setLab('all'); applyFilters({ lab: 'all' }); }}
              />
              {labs.map((l) => (
                <FilterPill
                  key={l}
                  label={l}
                  active={lab === l}
                  onPress={() => { setLab(l); applyFilters({ lab: l }); }}
                />
              ))}
            </ScrollView>

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
            <Text style={s.emptyTitle}>No tickets found</Text>
            <Text style={s.emptySub}>
              {search || status !== 'all' || priority !== 'all' || lab !== 'all'
                ? 'Try adjusting your filters'
                : 'All clear! No tickets in the queue.'}
            </Text>
          </View>
        }
        ListFooterComponent={
          <>
            {/* Pagination */}
            {pagination && pagination.last_page > 1 && (
              <View style={s.pagination}>
                <Text style={s.pageInfo}>
                  {pagination.from ?? 0}–{pagination.to ?? 0} of {pagination.total}
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
                {pageLoading && <ActivityIndicator size="small" color={colors.primary} />}
              </View>
            )}

            {/* Bottom summary cards */}
            <View style={s.summaryRow}>
              <TouchableOpacity
                style={s.summaryCard}
                onPress={() => router.push('/(it)/assignments' as any)}
                activeOpacity={0.8}
              >
                <Text style={s.summaryLabel}>My Active Tickets</Text>
                <Text style={s.summaryValue}>{stats?.assigned ?? 0}</Text>
                <Text style={s.summaryLink}>View My Assignments →</Text>
              </TouchableOpacity>

              <TouchableOpacity
                style={[s.summaryCard, s.summaryCardDanger]}
                onPress={() => { setPriority('high'); applyFilters({ priority: 'high' }); }}
                activeOpacity={0.8}
              >
                <Text style={[s.summaryLabel, { color: colors.danger }]}>High Priority</Text>
                <Text style={[s.summaryValue, { color: colors.textPrimary }]}>
                  {stats?.high_priority ?? 0}
                </Text>
                <Text style={[s.summaryLink, { color: '#f87171' }]}>View High Priority →</Text>
              </TouchableOpacity>
            </View>

            <View style={{ height: spacing.xxl }} />
          </>
        }
        renderItem={({ item }) => (
          <TicketCard
            ticket={item}
            authUserId={authUser?.id ?? -1}
            onView={() => router.push(`/(it)/queue/${item.id}` as any)}
            onAssignSelf={() => handleAssignSelf(item.id)}
            assigningId={assigningId}
          />
        )}
      />
    </SafeAreaView>
  );
}

// ─── Styles ───────────────────────────────────────────────────────────────────

const s = StyleSheet.create({
  root: { flex: 1, backgroundColor: colors.bgPrimary },
  centered: { flex: 1, alignItems: 'center', justifyContent: 'center', gap: spacing.md },

  nav: {
    flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center',
    paddingHorizontal: spacing.lg, paddingVertical: spacing.sm + 4,
    borderBottomWidth: 1, borderBottomColor: colors.border,
  },
  navLogo:  { fontSize: font.xl, fontWeight: font.bold, color: colors.primary, letterSpacing: -0.5, width: 60 },
  navTitle: { fontSize: font.base, fontWeight: font.bold, color: colors.textPrimary },

  listContent: { paddingHorizontal: spacing.lg, paddingBottom: spacing.lg },

  pageHeader:  { paddingTop: spacing.lg, marginBottom: spacing.md },
  pageTitle:   { fontSize: font.xl, fontWeight: font.bold, color: colors.textPrimary },
  pageSub:     { fontSize: font.sm, color: colors.textMuted, marginTop: 2 },

  statsRow: { flexDirection: 'row', gap: spacing.xs, marginBottom: spacing.md, flexWrap: 'nowrap' },
  statCard: {
    flex: 1, backgroundColor: colors.bgCard, borderWidth: 1, borderColor: colors.border,
    borderLeftWidth: 3, borderLeftColor: colors.primary, borderRadius: radius.lg,
    padding: spacing.sm, alignItems: 'center',
  },
  statLabel: { fontSize: font.xs - 2, color: colors.textMuted, textAlign: 'center', marginBottom: 2 },
  statValue: { fontSize: font.lg, fontWeight: font.bold, color: colors.primary },

  infoBanner: {
    flexDirection: 'row', alignItems: 'flex-start', gap: spacing.sm,
    backgroundColor: colors.infoBg, borderWidth: 1, borderColor: colors.infoBorder,
    borderRadius: radius.lg, padding: spacing.md, marginBottom: spacing.md,
  },
  infoBannerIcon: { fontSize: 15, marginTop: 1 },
  infoBannerText: { flex: 1, fontSize: font.xs, color: '#93c5fd', lineHeight: 18 },

  searchBar: {
    flexDirection: 'row', alignItems: 'center', backgroundColor: colors.bgCard,
    borderWidth: 1, borderColor: colors.border, borderRadius: radius.lg,
    paddingHorizontal: spacing.md, paddingVertical: spacing.sm + 2,
    gap: spacing.sm, marginBottom: spacing.sm,
  },
  searchIcon:  { fontSize: 15 },
  searchInput: { flex: 1, fontSize: font.base, color: colors.textPrimary },

  filterRow:    { paddingBottom: spacing.sm, gap: 0 },
  filterDivider: { width: 1, backgroundColor: colors.border, marginHorizontal: spacing.xs, marginVertical: 4 },

  errorBanner: {
    backgroundColor: colors.dangerBg, borderWidth: 1, borderColor: colors.dangerBorder,
    borderRadius: radius.md, padding: spacing.sm + 2, marginBottom: spacing.md,
  },
  errorBannerText: { color: '#fca5a5', fontSize: font.sm },

  emptyState: { alignItems: 'center', paddingVertical: spacing.xxl, gap: spacing.sm },
  emptyIcon:  { fontSize: 36, opacity: 0.35 },
  emptyTitle: { fontSize: font.lg, fontWeight: font.semibold, color: colors.textSecondary },
  emptySub:   { fontSize: font.sm, color: colors.textMuted, textAlign: 'center' },

  pagination:      { alignItems: 'center', paddingVertical: spacing.lg, gap: spacing.sm },
  pageInfo:        { fontSize: font.sm, color: colors.textMuted },
  pageControls:    { flexDirection: 'row', alignItems: 'center', gap: spacing.sm },
  pageBtn:         { paddingHorizontal: spacing.md, paddingVertical: spacing.xs + 2, borderRadius: radius.md, backgroundColor: colors.bgCard, borderWidth: 1, borderColor: colors.border },
  pageBtnDisabled: { opacity: 0.4 },
  pageBtnText:     { fontSize: font.sm, color: colors.textSecondary },
  pageCurrent:     { width: 32, height: 32, borderRadius: radius.full, backgroundColor: colors.primary, alignItems: 'center', justifyContent: 'center' },
  pageCurrentText: { fontSize: font.sm, fontWeight: font.bold, color: '#fff' },

  summaryRow: { flexDirection: 'row', gap: spacing.sm, marginTop: spacing.md },
  summaryCard: {
    flex: 1, backgroundColor: 'rgba(45,212,191,0.07)', borderWidth: 1,
    borderColor: colors.border, borderRadius: radius.xl, padding: spacing.md,
  },
  summaryCardDanger: { backgroundColor: 'rgba(239,68,68,0.07)', borderColor: 'rgba(239,68,68,0.25)' },
  summaryLabel: { fontSize: font.xs, fontWeight: font.bold, color: colors.primary, marginBottom: spacing.xs },
  summaryValue: { fontSize: font.xl3, fontWeight: font.bold, color: colors.textPrimary, lineHeight: 32 },
  summaryLink:  { fontSize: font.xs, color: colors.primary, marginTop: spacing.xs },
});