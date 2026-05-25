/**
 * app/(tabs)/my-reports.tsx  —  My Reports (Ticket Index)
 *
 * Mirrors: resources/js/Pages/User/Reports/Index.vue
 *
 * Fetches from GET /api/tickets (protected, returns own tickets for students/staff).
 * Supports:
 *   - Search by ticket number or title (debounced, 350 ms)
 *   - Status filter tabs: All · Active · Resolved · Closed
 *   - Pull-to-refresh
 *   - Pagination (prev / next)
 *
 * Each ticket card is tappable → navigates to /(tabs)/my-reports/[id]
 * Unassigned tickets show Edit + Cancel actions inline.
 * Assigned tickets show a 🔒 locked indicator.
 */

import React, {
  useEffect,
  useState,
  useCallback,
  useRef,
} from 'react';
import {
  View,
  Text,
  ScrollView,
  FlatList,
  TouchableOpacity,
  TextInput,
  StyleSheet,
  StatusBar,
  ActivityIndicator,
  RefreshControl,
  Alert,
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
  status:        'new' | 'assigned' | 'in-progress' | 'resolved' | 'closed';
  priority:      'low' | 'medium' | 'high';
  assigned_to:   number | null;
  created_at:    string;
  equipment?: {
    equipment_code: string;
    lab?: { name: string };
  };
};

type PaginatedResponse = {
  data:          Ticket[];
  current_page:  number;
  last_page:     number;
  total:         number;
  from:          number | null;
  to:            number | null;
  prev_page_url: string | null;
  next_page_url: string | null;
};

type StatusFilter = 'all' | 'active' | 'resolved' | 'closed';

const FILTER_OPTIONS: { key: StatusFilter; label: string }[] = [
  { key: 'all',      label: 'All'      },
  { key: 'active',   label: 'Active'   },
  { key: 'resolved', label: 'Resolved' },
  { key: 'closed',   label: 'Closed'   },
];

// ─── Helpers ─────────────────────────────────────────────────────────────────

function statusLabel(status: string): string {
  return status.replace('-', ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

function statusStyle(status: string): { bg: string; text: string; border: string } {
  switch (status) {
    case 'new':         return { bg: colors.infoBg,            text: colors.infoLight,    border: colors.infoBorder    };
    case 'assigned':    return { bg: 'rgba(168,85,247,0.18)',   text: '#c084fc',           border: 'rgba(168,85,247,0.3)' };
    case 'in-progress': return { bg: colors.warningBg,         text: colors.warningLight, border: colors.warningBorder };
    case 'resolved':
    case 'closed':      return { bg: colors.successBg,         text: colors.successLight, border: colors.successBorder };
    default:            return { bg: colors.borderLight,       text: colors.textMuted,    border: colors.border        };
  }
}

function priorityColor(priority: string): string {
  switch (priority) {
    case 'high':   return colors.danger;
    case 'medium': return colors.warning;
    default:       return colors.success;
  }
}

function priorityEmoji(priority: string): string {
  return priority === 'high' ? '🔴' : priority === 'medium' ? '🟡' : '🟢';
}

function formatDate(iso: string): string {
  return new Date(iso).toLocaleDateString('en-US', {
    month: 'short', day: 'numeric', year: 'numeric',
  });
}

// ─── Component ────────────────────────────────────────────────────────────────

export default function MyReportsScreen() {
  const [token,      setToken]      = useState<string | null>(null);
  const [tickets,    setTickets]    = useState<Ticket[]>([]);
  const [pagination, setPagination] = useState<Omit<PaginatedResponse, 'data'> | null>(null);
  const [loading,    setLoading]    = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [pageLoading,setPageLoading]= useState(false);
  const [error,      setError]      = useState<string | null>(null);

  // Filters
  const [search,     setSearch]     = useState('');
  const [statusFilter, setStatusFilter] = useState<StatusFilter>('all');
  const [currentPage,  setCurrentPage]  = useState(1);

  const searchTimer = useRef<ReturnType<typeof setTimeout> | null>(null);

  // Load token
  useEffect(() => {
    loadAuth().then((auth) => {
      if (!auth) { router.replace('/login'); return; }
      setToken(auth.token);
    });
  }, []);

  // ── Fetch ─────────────────────────────────────────────────────────────────

  const fetchTickets = useCallback(async (
    tok: string,
    opts: { search?: string; status?: StatusFilter; page?: number } = {}
  ) => {
    const params = new URLSearchParams();
    if (opts.search)                          params.set('search', opts.search);
    if (opts.status && opts.status !== 'all') params.set('status', opts.status);
    if (opts.page && opts.page > 1)           params.set('page', String(opts.page));

    const url = `${apiUrl('tickets')}?${params.toString()}`;

    try {
      const res = await fetch(url, {
        headers: {
          'Accept':        'application/json',
          'Authorization': `Bearer ${tok}`,
        },
      });

      if (res.status === 401) { router.replace('/login'); return; }
      if (!res.ok) throw new Error(`Server error ${res.status}`);

      const json: PaginatedResponse = await res.json();
      setTickets(json.data);
      const { data: _, ...meta } = json;
      setPagination(meta);
      setError(null);
    } catch (e: any) {
      setError(e.message ?? 'Could not load tickets.');
    }
  }, []);

  // Initial load
  useEffect(() => {
    if (!token) return;
    setLoading(true);
    fetchTickets(token, { search, status: statusFilter, page: currentPage })
      .finally(() => setLoading(false));
  }, [token]); // eslint-disable-line react-hooks/exhaustive-deps

  // Search with debounce
  useEffect(() => {
    if (!token) return;
    if (searchTimer.current) clearTimeout(searchTimer.current);
    searchTimer.current = setTimeout(() => {
      setCurrentPage(1);
      setLoading(true);
      fetchTickets(token, { search, status: statusFilter, page: 1 })
        .finally(() => setLoading(false));
    }, 350);
    return () => { if (searchTimer.current) clearTimeout(searchTimer.current); };
  }, [search]); // eslint-disable-line react-hooks/exhaustive-deps

  // Status filter change
  function handleStatusChange(s: StatusFilter) {
    setStatusFilter(s);
    setCurrentPage(1);
    if (!token) return;
    setLoading(true);
    fetchTickets(token, { search, status: s, page: 1 })
      .finally(() => setLoading(false));
  }

  // Pagination
  function handlePage(page: number) {
    if (!token) return;
    setCurrentPage(page);
    setPageLoading(true);
    fetchTickets(token, { search, status: statusFilter, page })
      .finally(() => setPageLoading(false));
  }

  // Pull-to-refresh
  const onRefresh = useCallback(async () => {
    if (!token) return;
    setRefreshing(true);
    await fetchTickets(token, { search, status: statusFilter, page: 1 });
    setCurrentPage(1);
    setRefreshing(false);
  }, [token, search, statusFilter, fetchTickets]);

  // ── Derived stats (from current full set) ─────────────────────────────────
  // We show them from pagination totals when available
  const total    = pagination?.total ?? tickets.length;
  const active   = tickets.filter(t => ['new','assigned','in-progress'].includes(t.status)).length;
  const resolved = tickets.filter(t => t.status === 'resolved').length;

  // ─── Render ───────────────────────────────────────────────────────────────

  return (
    <SafeAreaView style={s.root} edges={['top']}>
      <StatusBar barStyle="light-content" backgroundColor={colors.bgPrimary} />

      {/* ── Top Nav ── */}
      <View style={s.nav}>
        <Text style={s.navLogo}>LabFix</Text>
        <TouchableOpacity
          style={s.newBtn}
          onPress={() => router.push('/(tabs)/report' as any)}
          activeOpacity={0.8}
        >
          <Text style={s.newBtnText}>+ New</Text>
        </TouchableOpacity>
      </View>

      {/* ── Page Header ── */}
      <View style={s.pageHeader}>
        <View>
          <Text style={s.pageTitle}>My Reports</Text>
          <Text style={s.pageSub}>Track all your submitted tickets</Text>
        </View>
      </View>

      {/* ── Stats Row ── */}
      <View style={s.statsRow}>
        <View style={s.statCard}>
          <Text style={s.statLabel}>Total</Text>
          <Text style={s.statValue}>{total}</Text>
        </View>
        <View style={s.statCard}>
          <Text style={s.statLabel}>Active</Text>
          <Text style={s.statValue}>{active}</Text>
        </View>
        <View style={s.statCard}>
          <Text style={s.statLabel}>Resolved</Text>
          <Text style={s.statValue}>{resolved}</Text>
        </View>
      </View>

      {/* ── Search ── */}
      <View style={s.searchBar}>
        <Text style={s.searchIcon}>🔍</Text>
        <TextInput
          style={s.searchInput}
          value={search}
          onChangeText={setSearch}
          placeholder="Search by ticket # or title…"
          placeholderTextColor={colors.textDisabled}
          returnKeyType="search"
          clearButtonMode="while-editing"
        />
      </View>

      {/* ── Filter tabs ── */}
      <View style={s.filterRow}>
        {FILTER_OPTIONS.map((opt) => (
          <TouchableOpacity
            key={opt.key}
            style={[s.filterPill, statusFilter === opt.key && s.filterPillActive]}
            onPress={() => handleStatusChange(opt.key)}
            activeOpacity={0.75}
          >
            <Text style={[s.filterPillText, statusFilter === opt.key && s.filterPillTextActive]}>
              {opt.label}
            </Text>
          </TouchableOpacity>
        ))}
      </View>

      {/* ── Content ── */}
      {loading ? (
        <View style={s.centered}>
          <ActivityIndicator size="large" color={colors.primary} />
        </View>
      ) : error ? (
        <View style={s.centered}>
          <Text style={s.errorText}>{error}</Text>
          <TouchableOpacity
            style={s.retryBtn}
            onPress={() => token && fetchTickets(token, { search, status: statusFilter, page: currentPage })}
          >
            <Text style={s.retryText}>Try Again</Text>
          </TouchableOpacity>
        </View>
      ) : (
        <FlatList
          data={tickets}
          keyExtractor={(item) => String(item.id)}
          contentContainerStyle={s.listContent}
          showsVerticalScrollIndicator={false}
          refreshControl={
            <RefreshControl
              refreshing={refreshing}
              onRefresh={onRefresh}
              tintColor={colors.primary}
              colors={[colors.primary]}
            />
          }
          ListEmptyComponent={
            <View style={s.emptyState}>
              <Text style={s.emptyIcon}>📋</Text>
              <Text style={s.emptyTitle}>No reports found</Text>
              <Text style={s.emptySub}>
                {search || statusFilter !== 'all'
                  ? 'Try adjusting your search or filters'
                  : 'Report your first issue to get started'}
              </Text>
              {search || statusFilter !== 'all' ? (
                <TouchableOpacity
                  style={s.clearBtn}
                  onPress={() => { setSearch(''); handleStatusChange('all'); }}
                >
                  <Text style={s.clearBtnText}>Clear Filters</Text>
                </TouchableOpacity>
              ) : (
                <TouchableOpacity
                  style={s.clearBtn}
                  onPress={() => router.push('/(tabs)/report' as any)}
                >
                  <Text style={s.clearBtnText}>+ Report Issue</Text>
                </TouchableOpacity>
              )}
            </View>
          }
          ListFooterComponent={
            <>
              {/* Pagination controls */}
              {pagination && pagination.last_page > 1 && (
                <View style={s.pagination}>
                  <Text style={s.pageInfo}>
                    {pagination.from ?? 0}–{pagination.to ?? 0} of {pagination.total}
                  </Text>
                  <View style={s.pageControls}>
                    <TouchableOpacity
                      style={[s.pageBtn, !pagination.prev_page_url && s.pageBtnDisabled]}
                      onPress={() => handlePage(currentPage - 1)}
                      disabled={!pagination.prev_page_url || pageLoading}
                    >
                      <Text style={s.pageBtnText}>← Prev</Text>
                    </TouchableOpacity>
                    <View style={s.pageCurrent}>
                      <Text style={s.pageCurrentText}>{currentPage}</Text>
                    </View>
                    <TouchableOpacity
                      style={[s.pageBtn, !pagination.next_page_url && s.pageBtnDisabled]}
                      onPress={() => handlePage(currentPage + 1)}
                      disabled={!pagination.next_page_url || pageLoading}
                    >
                      <Text style={s.pageBtnText}>Next →</Text>
                    </TouchableOpacity>
                  </View>
                  {pageLoading && (
                    <ActivityIndicator
                      size="small"
                      color={colors.primary}
                      style={{ marginTop: spacing.sm }}
                    />
                  )}
                </View>
              )}

              {/* Info note */}
              <View style={s.infoNote}>
                <Text style={s.infoIcon}>ℹ️</Text>
                <Text style={s.infoText}>
                  You can edit or cancel tickets before they're assigned to a technician.
                  Once assigned, contact the technician to make changes.
                </Text>
              </View>

              <View style={{ height: spacing.xl }} />
            </>
          }
          renderItem={({ item }) => <TicketCard ticket={item} />}
        />
      )}
    </SafeAreaView>
  );
}

// ─── Ticket Card ──────────────────────────────────────────────────────────────

function TicketCard({ ticket }: { ticket: Ticket }) {
  const sc      = statusStyle(ticket.status);
  const locked  = !!ticket.assigned_to;
  const isOpen  = ['new', 'assigned', 'in-progress'].includes(ticket.status);

  return (
    <TouchableOpacity
      style={[s.ticketCard, { borderLeftColor: priorityColor(ticket.priority) }]}
      onPress={() => router.push(`/(tabs)/my-reports/${ticket.id}` as any)}
      activeOpacity={0.75}
    >
      {/* Top row */}
      <View style={s.ticketTop}>
        <Text style={s.ticketNum}>{ticket.ticket_number}</Text>
        <View style={[s.statusPill, { backgroundColor: sc.bg, borderColor: sc.border }]}>
          <Text style={[s.statusPillText, { color: sc.text }]}>
            {statusLabel(ticket.status)}
          </Text>
        </View>
      </View>

      {/* Title */}
      <Text style={s.ticketTitle} numberOfLines={2}>{ticket.title}</Text>

      {/* Meta row */}
      <View style={s.ticketMeta}>
        {ticket.equipment?.lab?.name && (
          <Text style={s.metaText}>{ticket.equipment.lab.name}</Text>
        )}
        {ticket.equipment?.equipment_code && (
          <Text style={s.metaText}>· {ticket.equipment.equipment_code}</Text>
        )}
        <View style={[s.priorityPill, { borderColor: priorityColor(ticket.priority) + '66' }]}>
          <Text style={[s.priorityText, { color: priorityColor(ticket.priority) }]}>
            {priorityEmoji(ticket.priority)}{' '}
            {ticket.priority.charAt(0).toUpperCase() + ticket.priority.slice(1)}
          </Text>
        </View>
        <Text style={[s.metaText, { marginLeft: 'auto' }]}>
          {formatDate(ticket.created_at)}
        </Text>
      </View>

      {/* Action buttons */}
      <View style={s.ticketActions}>
        <TouchableOpacity
          style={s.actionBtn}
          onPress={() => router.push(`/(tabs)/my-reports/${ticket.id}` as any)}
          activeOpacity={0.75}
        >
          <Text style={s.actionBtnText}>View</Text>
        </TouchableOpacity>

        {locked ? (
          <View style={[s.actionBtn, s.actionBtnLocked]}>
            <Text style={s.actionBtnLockedText}>🔒 Locked</Text>
          </View>
        ) : (
          <>
            {isOpen && (
              <TouchableOpacity
                style={[s.actionBtn, s.actionBtnEdit]}
                onPress={() => router.push(`/(tabs)/my-reports/${ticket.id}/edit` as any)}
                activeOpacity={0.75}
              >
                <Text style={s.actionBtnEditText}>Edit</Text>
              </TouchableOpacity>
            )}
            {isOpen && (
              <TouchableOpacity
                style={[s.actionBtn, s.actionBtnCancel]}
                onPress={() =>
                  Alert.alert(
                    'Cancel Ticket',
                    'Are you sure you want to cancel this ticket? Admins can restore it later if needed.',
                    [
                      { text: 'Keep', style: 'cancel' },
                      {
                        text: 'Cancel Ticket',
                        style: 'destructive',
                        onPress: () => {
                          // Navigate to detail which handles the delete
                          router.push(`/(tabs)/my-reports/${ticket.id}` as any);
                        },
                      },
                    ]
                  )
                }
                activeOpacity={0.75}
              >
                <Text style={s.actionBtnCancelText}>Cancel</Text>
              </TouchableOpacity>
            )}
          </>
        )}
      </View>
    </TouchableOpacity>
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
    padding: spacing.lg,
    gap: spacing.md,
  },

  // Nav
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
  newBtn: {
    backgroundColor: colors.primary,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.xs + 2,
    borderRadius: radius.full,
  },
  newBtnText: {
    fontSize: font.sm,
    fontWeight: font.bold,
    color: '#fff',
  },

  // Page header
  pageHeader: {
    paddingHorizontal: spacing.lg,
    paddingTop: spacing.md,
    paddingBottom: spacing.sm,
  },
  pageTitle: {
    fontSize: font.xl,
    fontWeight: font.bold,
    color: colors.textPrimary,
  },
  pageSub: {
    fontSize: font.sm,
    color: colors.textMuted,
    marginTop: 2,
  },

  // Stats
  statsRow: {
    flexDirection: 'row',
    paddingHorizontal: spacing.lg,
    gap: spacing.sm,
    marginBottom: spacing.md,
  },
  statCard: {
    flex: 1,
    backgroundColor: colors.bgCard,
    borderWidth: 1,
    borderColor: colors.border,
    borderLeftWidth: 3,
    borderLeftColor: colors.primary,
    borderRadius: radius.xl,
    padding: spacing.md,
  },
  statLabel: {
    fontSize: font.xs,
    color: colors.textMuted,
    marginBottom: 4,
  },
  statValue: {
    fontSize: font.xl,
    fontWeight: font.bold,
    color: colors.primary,
  },

  // Search
  searchBar: {
    flexDirection: 'row',
    alignItems: 'center',
    marginHorizontal: spacing.lg,
    marginBottom: spacing.sm,
    backgroundColor: colors.bgCard,
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radius.lg,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm + 2,
    gap: spacing.sm,
  },
  searchIcon: { fontSize: 16 },
  searchInput: {
    flex: 1,
    fontSize: font.base,
    color: colors.textPrimary,
  },

  // Filter pills
  filterRow: {
    flexDirection: 'row',
    paddingHorizontal: spacing.lg,
    gap: spacing.xs + 2,
    marginBottom: spacing.md,
  },
  filterPill: {
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.xs + 2,
    borderRadius: radius.full,
    backgroundColor: colors.bgCard,
    borderWidth: 1,
    borderColor: colors.border,
  },
  filterPillActive: {
    backgroundColor: colors.primaryLight,
    borderColor: colors.primary,
  },
  filterPillText: {
    fontSize: font.sm,
    color: colors.textMuted,
    fontWeight: font.medium,
  },
  filterPillTextActive: {
    color: colors.primary,
    fontWeight: font.semibold,
  },

  // List
  listContent: {
    paddingHorizontal: spacing.lg,
    paddingTop: spacing.xs,
  },

  // Ticket card
  ticketCard: {
    backgroundColor: colors.bgCard,
    borderRadius: radius.xl,
    borderLeftWidth: 3,
    padding: spacing.md,
    marginBottom: spacing.sm,
    gap: spacing.xs + 2,
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
  },
  priorityText: {
    fontSize: font.xs - 1,
    fontWeight: font.semibold,
  },

  // Action buttons row
  ticketActions: {
    flexDirection: 'row',
    gap: spacing.xs + 2,
    marginTop: spacing.xs,
  },
  actionBtn: {
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.xs + 2,
    borderRadius: radius.md,
    backgroundColor: colors.primary,
  },
  actionBtnText: {
    fontSize: font.xs,
    fontWeight: font.bold,
    color: '#fff',
  },
  actionBtnEdit: {
    backgroundColor: colors.infoBg,
    borderWidth: 1,
    borderColor: colors.infoBorder,
  },
  actionBtnEditText: {
    fontSize: font.xs,
    fontWeight: font.semibold,
    color: colors.infoLight,
  },
  actionBtnCancel: {
    backgroundColor: colors.dangerBg,
    borderWidth: 1,
    borderColor: colors.dangerBorder,
  },
  actionBtnCancelText: {
    fontSize: font.xs,
    fontWeight: font.semibold,
    color: '#f87171',
  },
  actionBtnLocked: {
    backgroundColor: 'rgba(156,163,175,0.12)',
    borderWidth: 1,
    borderColor: 'rgba(156,163,175,0.2)',
  },
  actionBtnLockedText: {
    fontSize: font.xs,
    color: colors.textMuted,
  },

  // Empty state
  emptyState: {
    alignItems: 'center',
    paddingVertical: spacing.xxl,
    gap: spacing.sm,
  },
  emptyIcon: { fontSize: 36, opacity: 0.4 },
  emptyTitle: {
    fontSize: font.lg,
    fontWeight: font.semibold,
    color: colors.textSecondary,
  },
  emptySub: {
    fontSize: font.sm,
    color: colors.textMuted,
    textAlign: 'center',
  },
  clearBtn: {
    marginTop: spacing.sm,
    backgroundColor: colors.primary,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.sm + 2,
    borderRadius: radius.full,
  },
  clearBtnText: {
    fontSize: font.base,
    fontWeight: font.bold,
    color: '#fff',
  },

  // Pagination
  pagination: {
    alignItems: 'center',
    paddingVertical: spacing.lg,
    gap: spacing.sm,
  },
  pageInfo: {
    fontSize: font.sm,
    color: colors.textMuted,
  },
  pageControls: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
  },
  pageBtn: {
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.xs + 2,
    borderRadius: radius.md,
    backgroundColor: colors.bgCard,
    borderWidth: 1,
    borderColor: colors.border,
  },
  pageBtnDisabled: { opacity: 0.4 },
  pageBtnText: {
    fontSize: font.sm,
    color: colors.textSecondary,
  },
  pageCurrent: {
    width: 32,
    height: 32,
    borderRadius: radius.full,
    backgroundColor: colors.primary,
    alignItems: 'center',
    justifyContent: 'center',
  },
  pageCurrentText: {
    fontSize: font.sm,
    fontWeight: font.bold,
    color: '#fff',
  },

  // Info note
  infoNote: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: spacing.sm,
    marginTop: spacing.md,
    backgroundColor: colors.infoBg,
    borderWidth: 1,
    borderColor: colors.infoBorder,
    borderRadius: radius.lg,
    padding: spacing.md,
  },
  infoIcon: { fontSize: 16, marginTop: 1 },
  infoText: {
    flex: 1,
    fontSize: font.sm,
    color: '#93c5fd',
    lineHeight: 19,
  },

  // Error
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