/**
 * app/(tabs)/my-reports/[id].tsx  —  Ticket Detail (Show)
 *
 * Mirrors: resources/js/Pages/User/Reports/Show.vue
 *
 * Fetches GET /api/tickets/{id} on mount.
 * Response includes: reporter, assignedTo, equipment.lab, transactions.user
 *
 * Sections (top to bottom, single scroll):
 *   1. Ticket header card  — title, ticket number, status pill, meta grid
 *   2. Problem Description
 *   3. Equipment Information
 *   4. Manage Report card  — Edit + Cancel (unassigned) OR locked notice (assigned)
 *   5. Assigned Technician — avatar + name + role, or "Awaiting" state
 *   6. Quick Information   — status, priority, age
 *   7. Activity Timeline   — transactions list
 *
 * Cancel calls DELETE /api/tickets/{id} (not yet in api.php — shown as
 * a placeholder Alert that navigates back; add the endpoint later).
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
  Alert,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { router, useLocalSearchParams } from 'expo-router';
import { colors, spacing, radius, font } from '@/constants/theme';
import { loadAuth } from '@/utils/auth';
import { apiUrl } from '@/constants/api';

// ─── Types ────────────────────────────────────────────────────────────────────

type Transaction = {
  id:          number;
  action:      string;
  old_value:   string | null;
  new_value:   string | null;
  description: string;
  created_at:  string;
  user?: { full_name: string };
};

type Ticket = {
  id:            number;
  ticket_number: string;
  title:         string;
  description:   string;
  category:      string;
  status:        string;
  priority:      string;
  assigned_to:   number | null;
  created_at:    string;
  updated_at:    string;
  reporter?: { full_name: string };
  assigned_to_user?: { full_name: string; initials: string; role: string };
  equipment?: {
    equipment_code: string;
    type:           string;
    status:         string;
    notes:          string | null;
    lab?: {
      name:              string;
      location:          string | null;
      capacity:          number;
      operational_count: number;
    };
  };
  transactions?: Transaction[];
};

// ─── Helpers ─────────────────────────────────────────────────────────────────

function statusLabel(s: string): string {
  return s.replace('-', ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

function statusStyle(status: string) {
  switch (status) {
    case 'new':         return { bg: colors.infoBg,           text: colors.infoLight,    border: colors.infoBorder    };
    case 'assigned':    return { bg: 'rgba(168,85,247,0.18)', text: '#c084fc',           border: 'rgba(168,85,247,0.3)' };
    case 'in-progress': return { bg: colors.warningBg,        text: colors.warningLight, border: colors.warningBorder };
    case 'resolved':
    case 'closed':      return { bg: colors.successBg,        text: colors.successLight, border: colors.successBorder };
    default:            return { bg: colors.borderLight,      text: colors.textMuted,    border: colors.border        };
  }
}

function priorityColor(p: string): string {
  return p === 'high' ? colors.danger : p === 'medium' ? colors.warning : colors.success;
}

function formatDate(iso: string): string {
  return new Date(iso).toLocaleDateString('en-US', {
    month: 'short', day: 'numeric', year: 'numeric',
    hour: 'numeric', minute: '2-digit',
  });
}

function diffForHumans(iso: string): string {
  const seconds = Math.floor((Date.now() - new Date(iso).getTime()) / 1000);
  if (seconds < 60)    return `${seconds}s ago`;
  if (seconds < 3600)  return `${Math.floor(seconds / 60)}m ago`;
  if (seconds < 86400) return `${Math.floor(seconds / 3600)}h ago`;
  return `${Math.floor(seconds / 86400)}d ago`;
}

function txIcon(action: string): string {
  const map: Record<string, string> = {
    created:          '🎫',
    status_changed:   '🔄',
    assigned:         '👤',
    priority_changed: '⚠️',
    updated:          '✏️',
    deleted:          '🗑️',
    restored:         '♻️',
  };
  return map[action] ?? '📌';
}

function txTitle(action: string): string {
  const map: Record<string, string> = {
    created:          'Ticket Created',
    status_changed:   'Status Updated',
    assigned:         'Assigned',
    priority_changed: 'Priority Changed',
    updated:          'Ticket Updated',
    deleted:          'Ticket Cancelled',
    restored:         'Ticket Restored',
  };
  return map[action] ?? 'Activity';
}

function getInitials(name: string): string {
  return name.split(' ').map((n) => n[0]).slice(0, 2).join('').toUpperCase();
}

// ─── Sub-components ───────────────────────────────────────────────────────────

function InfoRow({ label, value }: { label: string; value?: string | null }) {
  return (
    <View style={ir.row}>
      <Text style={ir.label}>{label}</Text>
      <Text style={ir.value}>{value ?? '—'}</Text>
    </View>
  );
}
const ir = StyleSheet.create({
  row: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'flex-start',
    paddingVertical: spacing.xs + 2,
    borderBottomWidth: 1,
    borderBottomColor: 'rgba(45,212,191,0.06)',
    gap: spacing.sm,
  },
  label: { fontSize: font.sm, color: colors.textMuted, flex: 1 },
  value: { fontSize: font.sm, fontWeight: font.medium, color: colors.textPrimary, flex: 1, textAlign: 'right' },
});

// ─── Main component ───────────────────────────────────────────────────────────

export default function TicketShowScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();

  const [token,      setToken]      = useState<string | null>(null);
  const [ticket,     setTicket]     = useState<Ticket | null>(null);
  const [loading,    setLoading]    = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error,      setError]      = useState<string | null>(null);
  const [cancelling, setCancelling] = useState(false);

  useEffect(() => {
    loadAuth().then((auth) => {
      if (!auth) { router.replace('/login'); return; }
      setToken(auth.token);
    });
  }, []);

  const fetchTicket = useCallback(async (tok: string) => {
    try {
      const res = await fetch(apiUrl(`tickets/${id}`), {
        headers: {
          'Accept':        'application/json',
          'Authorization': `Bearer ${tok}`,
        },
      });
      if (res.status === 401) { router.replace('/login'); return; }
      if (res.status === 403) { router.back(); return; }
      if (!res.ok) throw new Error(`Server error ${res.status}`);
      const data: Ticket = await res.json();
      setTicket(data);
      setError(null);
    } catch (e: any) {
      setError(e.message ?? 'Could not load ticket.');
    }
  }, [id]);

  useEffect(() => {
    if (!token) return;
    setLoading(true);
    fetchTicket(token).finally(() => setLoading(false));
  }, [token, fetchTicket]);

  const onRefresh = useCallback(async () => {
    if (!token) return;
    setRefreshing(true);
    await fetchTicket(token);
    setRefreshing(false);
  }, [token, fetchTicket]);

  // ── Cancel ticket ─────────────────────────────────────────────────────────
  function handleCancel() {
    Alert.alert(
      'Cancel Ticket',
      'Are you sure you want to cancel this ticket? Admins can restore it later if needed.',
      [
        { text: 'Keep Ticket', style: 'cancel' },
        {
          text: 'Cancel Ticket',
          style: 'destructive',
          onPress: async () => {
            if (!token || !ticket) return;
            setCancelling(true);
            try {
              const res = await fetch(apiUrl(`tickets/${ticket.id}`), {
                method: 'DELETE',
                headers: {
                  'Accept':        'application/json',
                  'Authorization': `Bearer ${token}`,
                },
              });
              if (res.ok) {
                router.replace('/(tabs)/my-reports' as any);
              } else {
                const data = await res.json();
                Alert.alert('Error', data?.message ?? 'Could not cancel ticket.');
              }
            } catch {
              Alert.alert('Error', 'Could not reach the server.');
            } finally {
              setCancelling(false);
            }
          },
        },
      ]
    );
  }

  // ── Loading / error states ────────────────────────────────────────────────

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

  if (error || !ticket) {
    return (
      <SafeAreaView style={s.root}>
        <StatusBar barStyle="light-content" backgroundColor={colors.bgPrimary} />
        <View style={s.centered}>
          <Text style={s.errorText}>{error ?? 'Ticket not found.'}</Text>
          <TouchableOpacity style={s.retryBtn} onPress={() => router.back()}>
            <Text style={s.retryText}>← Go Back</Text>
          </TouchableOpacity>
        </View>
      </SafeAreaView>
    );
  }

  const sc     = statusStyle(ticket.status);
  const locked = !!ticket.assigned_to;
  const isOpen = ['new', 'assigned', 'in-progress'].includes(ticket.status);

  return (
    <SafeAreaView style={s.root} edges={['top']}>
      <StatusBar barStyle="light-content" backgroundColor={colors.bgPrimary} />

      {/* ── Top Nav ── */}
      <View style={s.nav}>
        <TouchableOpacity onPress={() => router.back()}>
          <Text style={s.navBack}>← My Reports</Text>
        </TouchableOpacity>
        <Text style={s.navTicketNum}>{ticket.ticket_number}</Text>
        <View style={{ width: 80 }} />
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
        {/* ── Ticket Header Card ── */}
        <View style={s.headerCard}>
          {/* Title + status */}
          <View style={s.headerTop}>
            <View style={{ flex: 1, paddingRight: spacing.sm }}>
              <Text style={s.ticketTitle}>{ticket.title}</Text>
              <Text style={s.ticketNum}>Ticket {ticket.ticket_number}</Text>
            </View>
            <View style={[s.statusPill, { backgroundColor: sc.bg, borderColor: sc.border }]}>
              <Text style={[s.statusPillText, { color: sc.text }]}>
                {statusLabel(ticket.status)}
              </Text>
            </View>
          </View>

          {/* Meta grid */}
          <View style={s.metaGrid}>
            <View style={s.metaItem}>
              <Text style={s.metaLabel}>Reporter</Text>
              <Text style={s.metaValue}>{ticket.reporter?.full_name ?? '—'}</Text>
            </View>
            <View style={s.metaItem}>
              <Text style={s.metaLabel}>Lab</Text>
              <Text style={s.metaValue}>{ticket.equipment?.lab?.name ?? '—'}</Text>
            </View>
            <View style={s.metaItem}>
              <Text style={s.metaLabel}>Equipment</Text>
              <Text style={s.metaValue}>{ticket.equipment?.equipment_code ?? '—'}</Text>
            </View>
            <View style={s.metaItem}>
              <Text style={s.metaLabel}>Category</Text>
              <Text style={s.metaValue}>
                {ticket.category.charAt(0).toUpperCase() + ticket.category.slice(1)}
              </Text>
            </View>
            <View style={s.metaItem}>
              <Text style={s.metaLabel}>Priority</Text>
              <Text style={[s.metaValue, { color: priorityColor(ticket.priority) }]}>
                {ticket.priority.charAt(0).toUpperCase() + ticket.priority.slice(1)}
              </Text>
            </View>
            <View style={s.metaItem}>
              <Text style={s.metaLabel}>Submitted</Text>
              <Text style={s.metaValue}>{formatDate(ticket.created_at)}</Text>
            </View>
          </View>
        </View>

        {/* ── Problem Description ── */}
        <View style={s.card}>
          <Text style={s.cardTitle}>Problem Description</Text>
          <Text style={s.descriptionText}>{ticket.description}</Text>
        </View>

        {/* ── Equipment Information ── */}
        <View style={s.card}>
          <Text style={s.cardTitle}>Equipment Information</Text>
          <InfoRow label="Equipment Code" value={ticket.equipment?.equipment_code} />
          <InfoRow
            label="Equipment Type"
            value={ticket.equipment?.type
              ? ticket.equipment.type.charAt(0).toUpperCase() + ticket.equipment.type.slice(1)
              : undefined}
          />
          <InfoRow
            label="Current Status"
            value={ticket.equipment?.status ? statusLabel(ticket.equipment.status) : undefined}
          />
          <InfoRow label="Lab Location"   value={ticket.equipment?.lab?.name}     />
          {ticket.equipment?.lab?.location && (
            <InfoRow label="Building/Floor" value={ticket.equipment.lab.location} />
          )}
        </View>

        {/* ── Manage Report ── */}
        {locked ? (
          <View style={[s.card, s.assignedNotice]}>
            <Text style={s.assignedNoticeTitle}>✓ Ticket Assigned</Text>
            <Text style={s.assignedNoticeText}>
              This ticket has been assigned and can no longer be edited.
            </Text>
          </View>
        ) : (
          <View style={s.card}>
            <Text style={s.cardTitle}>Manage Report</Text>
            <View style={s.manageActions}>
              {isOpen && (
                <TouchableOpacity
                  style={s.editBtn}
                  onPress={() => router.push(`/(tabs)/my-reports/${ticket.id}/edit` as any)}
                  activeOpacity={0.8}
                >
                  <Text style={s.editBtnText}>Edit Report</Text>
                </TouchableOpacity>
              )}
              {isOpen && (
                <TouchableOpacity
                  style={s.cancelBtn}
                  onPress={handleCancel}
                  disabled={cancelling}
                  activeOpacity={0.8}
                >
                  {cancelling ? (
                    <ActivityIndicator size="small" color="#f87171" />
                  ) : (
                    <Text style={s.cancelBtnText}>Cancel Ticket</Text>
                  )}
                </TouchableOpacity>
              )}
            </View>
            <Text style={s.manageNote}>Available until ticket is assigned to a technician</Text>
          </View>
        )}

        {/* ── Assigned Technician / Awaiting ── */}
        {ticket.assigned_to ? (
          <View style={s.card}>
            <Text style={s.cardTitle}>Assigned Technician</Text>
            <View style={s.techRow}>
              <View style={s.techAvatar}>
                <Text style={s.techAvatarText}>
                  {ticket.assigned_to_user
                    ? getInitials(ticket.assigned_to_user.full_name)
                    : '?'}
                </Text>
              </View>
              <View style={{ flex: 1 }}>
                <Text style={s.techName}>{ticket.assigned_to_user?.full_name ?? '—'}</Text>
                <Text style={s.techRole}>
                  {ticket.assigned_to_user?.role?.replace('-', ' ') ?? 'IT Support'}
                </Text>
              </View>
              <View style={[s.statusPill, statusStyle('assigned') as any]}>
                <Text style={[s.statusPillText, { color: '#c084fc' }]}>Active</Text>
              </View>
            </View>
          </View>
        ) : (
          <View style={s.card}>
            <Text style={s.cardTitle}>Assignment Status</Text>
            <View style={s.awaitingWrap}>
              <Text style={s.awaitingIcon}>⏳</Text>
              <Text style={s.awaitingText}>Awaiting assignment to a technician</Text>
            </View>
          </View>
        )}

        {/* ── Quick Information ── */}
        <View style={s.card}>
          <Text style={s.cardTitle}>Quick Information</Text>
          <InfoRow label="Status"   value={statusLabel(ticket.status)}                                              />
          <InfoRow label="Priority" value={ticket.priority.charAt(0).toUpperCase() + ticket.priority.slice(1)}     />
          <InfoRow label="Age"      value={diffForHumans(ticket.created_at)}                                        />
          {ticket.equipment?.lab && (
            <InfoRow
              label="Lab Operational"
              value={`${ticket.equipment.lab.operational_count ?? '?'}/${ticket.equipment.lab.capacity ?? '?'}`}
            />
          )}
        </View>

        {/* ── Activity Timeline ── */}
        <View style={s.card}>
          <Text style={s.cardTitle}>Activity Timeline</Text>

          {ticket.transactions && ticket.transactions.length > 0 ? (
            <View style={s.timeline}>
              {ticket.transactions.map((tx) => (
                <View key={tx.id} style={s.tlItem}>
                  <View style={s.tlDotWrap}>
                    <View style={s.tlDot} />
                    <View style={s.tlLine} />
                  </View>
                  <View style={s.tlContent}>
                    <View style={s.tlHeader}>
                      <Text style={s.tlTitle}>
                        {txIcon(tx.action)} {txTitle(tx.action)}
                      </Text>
                      <Text style={s.tlTime}>{formatDate(tx.created_at)}</Text>
                    </View>
                    <Text style={s.tlText}>{tx.description}</Text>
                    {(tx.old_value || tx.new_value) && (
                      <View style={s.tlChange}>
                        {tx.old_value && (
                          <Text style={s.tlOld}>{tx.old_value}</Text>
                        )}
                        {tx.old_value && tx.new_value && (
                          <Text style={s.tlArrow}> → </Text>
                        )}
                        {tx.new_value && (
                          <Text style={s.tlNew}>{tx.new_value}</Text>
                        )}
                      </View>
                    )}
                    <Text style={s.tlBy}>by {tx.user?.full_name ?? 'System'}</Text>
                  </View>
                </View>
              ))}
            </View>
          ) : (
            /* Fallback when no transactions yet */
            <View style={s.timeline}>
              <View style={s.tlItem}>
                <View style={s.tlDotWrap}>
                  <View style={s.tlDot} />
                </View>
                <View style={s.tlContent}>
                  <View style={s.tlHeader}>
                    <Text style={s.tlTitle}>🎫 Ticket Created</Text>
                    <Text style={s.tlTime}>{formatDate(ticket.created_at)}</Text>
                  </View>
                  <Text style={s.tlText}>
                    Report submitted by {ticket.reporter?.full_name ?? 'User'}
                  </Text>
                </View>
              </View>
            </View>
          )}
        </View>

        <View style={{ height: spacing.xl }} />
      </ScrollView>
    </SafeAreaView>
  );
}

// ─── Styles ───────────────────────────────────────────────────────────────────

const s = StyleSheet.create({
  root: { flex: 1, backgroundColor: colors.bgPrimary },
  centered: { flex: 1, alignItems: 'center', justifyContent: 'center', padding: spacing.lg, gap: spacing.md },
  scroll:   { paddingBottom: spacing.lg },

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
  navBack: {
    fontSize: font.sm,
    fontWeight: font.semibold,
    color: colors.primary,
    width: 80,
  },
  navTicketNum: {
    fontSize: font.sm,
    fontWeight: font.semibold,
    color: colors.textMuted,
  },

  // Header card
  headerCard: {
    margin: spacing.lg,
    marginBottom: spacing.md,
    backgroundColor: colors.bgCard,
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radius.xl,
    padding: spacing.lg,
  },
  headerTop: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    marginBottom: spacing.md,
  },
  ticketTitle: {
    fontSize: font.lg,
    fontWeight: font.bold,
    color: colors.textPrimary,
    lineHeight: 24,
    marginBottom: spacing.xs,
  },
  ticketNum: {
    fontSize: font.xs,
    color: colors.textMuted,
  },
  statusPill: {
    borderWidth: 1,
    borderRadius: radius.full,
    paddingHorizontal: spacing.sm,
    paddingVertical: 3,
    alignSelf: 'flex-start',
  },
  statusPillText: {
    fontSize: font.xs - 1,
    fontWeight: font.semibold,
  },

  // Meta grid — 2 columns
  metaGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    borderTopWidth: 1,
    borderTopColor: colors.borderLight,
    paddingTop: spacing.md,
    gap: spacing.sm,
  },
  metaItem: {
    width: '47%',
    gap: 2,
  },
  metaLabel: {
    fontSize: font.xs - 1,
    color: colors.textMuted,
  },
  metaValue: {
    fontSize: font.sm,
    fontWeight: font.medium,
    color: colors.textPrimary,
  },

  // Cards
  card: {
    marginHorizontal: spacing.lg,
    marginBottom: spacing.md,
    backgroundColor: colors.bgCard,
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radius.xl,
    padding: spacing.lg,
  },
  cardTitle: {
    fontSize: font.base,
    fontWeight: font.bold,
    color: colors.primary,
    marginBottom: spacing.md,
  },

  // Description
  descriptionText: {
    fontSize: font.base,
    color: colors.textSecondary,
    lineHeight: 23,
  },

  // Manage actions
  manageActions: {
    gap: spacing.sm,
    marginBottom: spacing.sm,
  },
  editBtn: {
    backgroundColor: colors.primary,
    borderRadius: radius.lg,
    paddingVertical: spacing.sm + 4,
    alignItems: 'center',
  },
  editBtnText: {
    fontSize: font.base,
    fontWeight: font.bold,
    color: '#fff',
  },
  cancelBtn: {
    backgroundColor: colors.dangerBg,
    borderWidth: 1,
    borderColor: colors.dangerBorder,
    borderRadius: radius.lg,
    paddingVertical: spacing.sm + 4,
    alignItems: 'center',
  },
  cancelBtnText: {
    fontSize: font.base,
    fontWeight: font.semibold,
    color: '#f87171',
  },
  manageNote: {
    fontSize: font.xs,
    color: colors.textMuted,
    textAlign: 'center',
    marginTop: spacing.xs,
  },

  // Assigned notice
  assignedNotice: {
    backgroundColor: 'rgba(45,212,191,0.08)',
    borderColor: 'rgba(45,212,191,0.3)',
  },
  assignedNoticeTitle: {
    fontSize: font.base,
    fontWeight: font.bold,
    color: colors.primary,
    marginBottom: spacing.xs,
  },
  assignedNoticeText: {
    fontSize: font.sm,
    color: colors.textSecondary,
    lineHeight: 20,
  },

  // Technician
  techRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  techAvatar: {
    width: 44,
    height: 44,
    borderRadius: radius.full,
    backgroundColor: colors.primary,
    alignItems: 'center',
    justifyContent: 'center',
  },
  techAvatarText: {
    fontSize: font.base,
    fontWeight: font.bold,
    color: '#fff',
  },
  techName: {
    fontSize: font.base,
    fontWeight: font.semibold,
    color: colors.textPrimary,
    marginBottom: 2,
  },
  techRole: {
    fontSize: font.sm,
    color: colors.textMuted,
    textTransform: 'capitalize',
  },

  // Awaiting
  awaitingWrap: {
    alignItems: 'center',
    paddingVertical: spacing.lg,
    gap: spacing.sm,
  },
  awaitingIcon: { fontSize: 32 },
  awaitingText: {
    fontSize: font.sm,
    color: colors.textMuted,
    textAlign: 'center',
  },

  // Timeline
  timeline: { gap: 0 },
  tlItem: {
    flexDirection: 'row',
    gap: spacing.sm,
    paddingBottom: spacing.md,
  },
  tlDotWrap: {
    alignItems: 'center',
    width: 12,
    paddingTop: 3,
  },
  tlDot: {
    width: 11,
    height: 11,
    borderRadius: 6,
    backgroundColor: colors.primary,
    borderWidth: 2,
    borderColor: colors.bgCard,
  },
  tlLine: {
    flex: 1,
    width: 1,
    backgroundColor: 'rgba(45,212,191,0.2)',
    marginTop: 3,
  },
  tlContent: {
    flex: 1,
    backgroundColor: 'rgba(45,212,191,0.05)',
    borderWidth: 1,
    borderColor: 'rgba(45,212,191,0.1)',
    borderLeftWidth: 2,
    borderLeftColor: colors.primary,
    borderRadius: radius.md,
    padding: spacing.sm + 2,
    gap: spacing.xs,
  },
  tlHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'flex-start',
    flexWrap: 'wrap',
    gap: spacing.xs,
  },
  tlTitle: {
    fontSize: font.sm,
    fontWeight: font.semibold,
    color: colors.textPrimary,
    flex: 1,
  },
  tlTime: {
    fontSize: font.xs - 1,
    color: colors.textMuted,
  },
  tlText: {
    fontSize: font.xs,
    color: colors.textMuted,
    lineHeight: 17,
  },
  tlChange: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: 'rgba(45,212,191,0.07)',
    borderRadius: radius.sm,
    paddingHorizontal: spacing.xs + 2,
    paddingVertical: 2,
    alignSelf: 'flex-start',
  },
  tlOld:   { fontSize: font.xs, color: '#f87171' },
  tlArrow: { fontSize: font.xs, color: colors.textMuted },
  tlNew:   { fontSize: font.xs, color: '#34d399' },
  tlBy: {
    fontSize: font.xs - 1,
    color: colors.textMuted,
    fontStyle: 'italic',
  },

  // Error
  errorText: { fontSize: font.base, color: colors.textSecondary, textAlign: 'center' },
  retryBtn: {
    backgroundColor: colors.primary,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.sm + 2,
    borderRadius: radius.full,
  },
  retryText: { fontSize: font.base, fontWeight: font.bold, color: '#fff' },
});