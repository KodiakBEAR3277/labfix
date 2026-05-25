/**
 * app/(it)/assignments/[id].tsx  —  Assignment Detail
 *
 * Mirrors: resources/js/Pages/IT/Assignments/Show.vue
 *
 * Fetches GET /api/it/assignments/{id} on mount.
 * The ticket must be assigned to the authenticated user —
 * the API enforces this with a 404 if not.
 *
 * Sections (matches web Show.vue + HTML mockup):
 *   1. Ticket header card  — title, ticket # + "Assigned to Me", priority,
 *                            status, 6-cell meta grid
 *   2. Quick Actions       — Update Status · Start Working · Mark as Resolved
 *   3. My Progress         — status, priority, time on task, resolution time
 *   4. Problem Description
 *   5. Equipment Information
 *   6. Reporter Contact    — name, role, email, phone (tappable)
 *   7. Attachments         — if any
 *   8. Activity Timeline
 *   9. Next Steps hint     — contextual card based on current status
 *   Pull-to-refresh.
 */

import React, { useEffect, useState, useCallback } from 'react';
import {
  View, Text, ScrollView, TouchableOpacity,
  StyleSheet, StatusBar, ActivityIndicator,
  RefreshControl, Alert, Linking,
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
  user?: { first_name: string; last_name: string };
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
  assigned_at:   string | null;
  resolved_at:   string | null;
  attachments:   string[] | null;
  reporter?: {
    first_name: string; last_name: string;
    email: string | null; phone: string | null; role: string;
  };
  equipment?: {
    equipment_code: string; type: string; status: string;
    lab?: { name: string; location: string | null };
  };
  transactions?: Transaction[];
};

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

function equipmentStatusStyle(s: string): { bg: string; text: string; border: string } {
  switch (s) {
    case 'operational': return { bg: colors.successBg, text: colors.successLight, border: colors.successBorder };
    case 'has-issue':   return { bg: colors.infoBg,    text: colors.infoLight,    border: colors.infoBorder    };
    default:            return { bg: colors.warningBg, text: colors.warningLight, border: colors.warningBorder };
  }
}

function txIcon(action: string): string {
  const map: Record<string, string> = {
    created: '🎫', status_changed: '🔄', assigned: '👤',
    priority_changed: '⚠️', updated: '✏️', deleted: '🗑️', restored: '♻️',
  };
  return map[action] ?? '📌';
}

function txTitle(action: string): string {
  const map: Record<string, string> = {
    created: 'Ticket Created', status_changed: 'Status Updated',
    assigned: 'Assigned', priority_changed: 'Priority Changed',
    updated: 'Ticket Updated', deleted: 'Ticket Cancelled', restored: 'Ticket Restored',
  };
  return map[action] ?? 'Activity';
}

function formatDate(iso: string): string {
  return new Date(iso).toLocaleDateString('en-US', {
    month: 'short', day: 'numeric', year: 'numeric',
    hour: 'numeric', minute: '2-digit',
  });
}

function diffForHumans(iso: string): string {
  const sec = Math.floor((Date.now() - new Date(iso).getTime()) / 1000);
  if (sec < 60)    return `${sec}s ago`;
  if (sec < 3600)  return `${Math.floor(sec / 60)}m ago`;
  if (sec < 86400) return `${Math.floor(sec / 3600)}h ago`;
  return `${Math.floor(sec / 86400)}d ago`;
}

function getInitials(first: string, last: string): string {
  return `${first[0] ?? ''}${last[0] ?? ''}`.toUpperCase();
}

// ─── Sub-components ───────────────────────────────────────────────────────────

function InfoRow({ label, value, children }: {
  label: string; value?: string; children?: React.ReactNode;
}) {
  return (
    <View style={ir.row}>
      <Text style={ir.label}>{label}</Text>
      {children ?? <Text style={ir.value}>{value ?? '—'}</Text>}
    </View>
  );
}
const ir = StyleSheet.create({
  row:   { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', paddingVertical: spacing.xs + 2, borderBottomWidth: 1, borderBottomColor: 'rgba(45,212,191,0.06)', gap: spacing.sm },
  label: { fontSize: font.sm, color: colors.textMuted, flex: 1 },
  value: { fontSize: font.sm, fontWeight: font.medium, color: colors.textPrimary, flex: 2, textAlign: 'right' },
});

function SectionCard({ title, children }: { title: string; children: React.ReactNode }) {
  return (
    <View style={s.card}>
      <Text style={s.cardTitle}>{title}</Text>
      {children}
    </View>
  );
}

function StatusPill({ status }: { status: string }) {
  const st = statusStyle(status);
  return (
    <View style={{ borderWidth: 1, borderRadius: radius.full, paddingHorizontal: 9, paddingVertical: 3, backgroundColor: st.bg, borderColor: st.border }}>
      <Text style={{ fontSize: font.xs - 1, fontWeight: font.semibold, color: st.text }}>
        {statusLabel(status)}
      </Text>
    </View>
  );
}

// ─── Main Component ───────────────────────────────────────────────────────────

export default function ITAssignmentShowScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();

  const [token,      setToken]      = useState<string | null>(null);
  const [ticket,     setTicket]     = useState<Ticket | null>(null);
  const [loading,    setLoading]    = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error,      setError]      = useState<string | null>(null);

  // Quick action loading states
  const [startingWork, setStartingWork] = useState(false);
  const [resolving,    setResolving]    = useState(false);

  // ── Auth ──────────────────────────────────────────────────────────────────
  useEffect(() => {
    loadAuth().then((auth) => {
      if (!auth) { router.replace('/login'); return; }
      setToken(auth.token);
    });
  }, []);

  // ── Fetch ─────────────────────────────────────────────────────────────────
  const fetchTicket = useCallback(async (tok: string) => {
    try {
      const res = await fetch(apiUrl(`it/assignments/${id}`), {
        headers: { 'Accept': 'application/json', 'Authorization': `Bearer ${tok}` },
      });
      if (res.status === 401) { router.replace('/login'); return; }
      if (res.status === 404) {
        setError('This ticket is not assigned to you or does not exist.');
        return;
      }
      if (!res.ok) throw new Error(`Server error ${res.status}`);
      setTicket(await res.json());
      setError(null);
    } catch (e: any) {
      setError(e.message ?? 'Could not load assignment.');
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

  // ── Quick update helper ───────────────────────────────────────────────────
  async function quickUpdate(
    status: string,
    setLoading: (v: boolean) => void
  ) {
    if (!token || !ticket) return;
    setLoading(true);
    try {
      const res = await fetch(apiUrl(`it/assignments/${ticket.id}`), {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'Accept':        'application/json',
          'Authorization': `Bearer ${token}`,
        },
        body: JSON.stringify({
          status,
          priority: ticket.priority,
        }),
      });
      if (!res.ok) throw new Error('Failed to update ticket.');
      setTicket(await res.json());
    } catch {
      Alert.alert('Error', 'Could not update ticket. Please try again.');
    } finally {
      setLoading(false);
    }
  }

  // ─── Derived values from transactions ────────────────────────────────────
  const assignedTransaction = ticket?.transactions?.find(tx => tx.action === 'assigned') ?? null;
  const resolvedTransaction = ticket?.transactions?.find(
    tx => tx.action === 'status_changed' && tx.new_value === 'resolved'
  ) ?? null;

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

  if (error || !ticket) {
    return (
      <SafeAreaView style={s.root}>
        <StatusBar barStyle="light-content" backgroundColor={colors.bgPrimary} />
        <View style={s.centered}>
          <Text style={s.errorIcon}>⚠️</Text>
          <Text style={s.errorText}>{error ?? 'Ticket not found.'}</Text>
          <TouchableOpacity style={s.retryBtn} onPress={() => router.back()}>
            <Text style={s.retryText}>← Go Back</Text>
          </TouchableOpacity>
        </View>
      </SafeAreaView>
    );
  }

  const pc        = priorityColor(ticket.priority);
  const isResolved = ticket.status === 'resolved' || ticket.status === 'closed';
  const isWorking  = ticket.status === 'in-progress';

  // ─── Main render ──────────────────────────────────────────────────────────
  return (
    <SafeAreaView style={s.root} edges={['top']}>
      <StatusBar barStyle="light-content" backgroundColor={colors.bgPrimary} />

      {/* ── Top Nav ── */}
      <View style={s.nav}>
        <TouchableOpacity onPress={() => router.back()} activeOpacity={0.7}>
          <Text style={s.navBack}>← My Assignments</Text>
        </TouchableOpacity>
        <TouchableOpacity
          style={s.updateBtn}
          onPress={() => router.push(`/(it)/assignments/${ticket.id}/edit` as any)}
          activeOpacity={0.8}
        >
          <Text style={s.updateBtnText}>Update Status</Text>
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
        {/* ── Ticket Header Card ── */}
        <View style={s.headerCard}>
          {/* Title + priority */}
          <View style={s.headerTop}>
            <View style={{ flex: 1, paddingRight: spacing.sm }}>
              <Text style={s.ticketTitle}>{ticket.title}</Text>
              <Text style={s.ticketSubNum}>
                Ticket {ticket.ticket_number} · Assigned to Me
              </Text>
            </View>
            <View style={[s.priorityBadge, { backgroundColor: pc + '22', borderColor: pc + '55' }]}>
              <Text style={[s.priorityBadgeText, { color: pc }]}>
                {ticket.priority.charAt(0).toUpperCase() + ticket.priority.slice(1)} Priority
              </Text>
            </View>
          </View>

          {/* Meta grid */}
          <View style={s.metaGrid}>
            {[
              { label: 'Reporter',  value: ticket.reporter ? `${ticket.reporter.first_name} ${ticket.reporter.last_name}` : '—' },
              { label: 'Location',  value: [ticket.equipment?.lab?.name, ticket.equipment?.equipment_code].filter(Boolean).join(', ') || '—' },
              { label: 'Category',  value: ticket.category.charAt(0).toUpperCase() + ticket.category.slice(1) },
              { label: 'Assigned',  value: assignedTransaction ? diffForHumans(assignedTransaction.created_at) : 'Just now' },
              { label: 'Submitted', value: formatDate(ticket.created_at) },
              { label: 'Last Updated', value: diffForHumans(ticket.updated_at) },
            ].map((m) => (
              <View key={m.label} style={s.metaItem}>
                <Text style={s.metaLabel}>{m.label}</Text>
                <Text style={s.metaValue} numberOfLines={1}>{m.value}</Text>
              </View>
            ))}
          </View>

          {/* Status row */}
          <View style={s.statusRow}>
            <Text style={s.statusRowLabel}>Status</Text>
            <StatusPill status={ticket.status} />
          </View>
        </View>

        {/* ── Quick Actions ── */}
        <SectionCard title="Quick Actions">
          <View style={s.actionBtns}>
            <TouchableOpacity
              style={s.primaryBtn}
              onPress={() => router.push(`/(it)/assignments/${ticket.id}/edit` as any)}
              activeOpacity={0.85}
            >
              <Text style={s.primaryBtnText}>Update Status</Text>
            </TouchableOpacity>

            {!isWorking && !isResolved && (
              <TouchableOpacity
                style={[s.outlineBtn, startingWork && { opacity: 0.6 }]}
                onPress={() => quickUpdate('in-progress', setStartingWork)}
                disabled={startingWork}
                activeOpacity={0.8}
              >
                {startingWork
                  ? <ActivityIndicator size="small" color={colors.primary} />
                  : <Text style={s.outlineBtnText}>🚀 Start Working</Text>}
              </TouchableOpacity>
            )}

            {!isResolved && (
              <TouchableOpacity
                style={[s.outlineBtn, resolving && { opacity: 0.6 }]}
                onPress={() => quickUpdate('resolved', setResolving)}
                disabled={resolving}
                activeOpacity={0.8}
              >
                {resolving
                  ? <ActivityIndicator size="small" color={colors.primary} />
                  : <Text style={s.outlineBtnText}>✅ Mark as Resolved</Text>}
              </TouchableOpacity>
            )}
          </View>
        </SectionCard>

        {/* ── My Progress ── */}
        <SectionCard title="My Progress">
          <InfoRow label="Current Status">
            <StatusPill status={ticket.status} />
          </InfoRow>
          <InfoRow
            label="Priority"
            value={ticket.priority.charAt(0).toUpperCase() + ticket.priority.slice(1)}
          />
          <InfoRow
            label="Time on Task"
            value={assignedTransaction
              ? diffForHumans(assignedTransaction.created_at)
              : 'Just assigned'}
          />
          {resolvedTransaction && (
            <InfoRow
              label="Resolution Time"
              value={diffForHumans(ticket.created_at)}
            />
          )}
        </SectionCard>

        {/* ── Problem Description ── */}
        <SectionCard title="Problem Description">
          <Text style={s.descText}>{ticket.description}</Text>
        </SectionCard>

        {/* ── Equipment Information ── */}
        <SectionCard title="Equipment Information">
          <InfoRow label="Equipment Code" value={ticket.equipment?.equipment_code} />
          <InfoRow
            label="Equipment Type"
            value={ticket.equipment?.type
              ? ticket.equipment.type.charAt(0).toUpperCase() + ticket.equipment.type.slice(1)
              : undefined}
          />
          <InfoRow label="Current Status">
            {ticket.equipment?.status ? (
              (() => {
                const es = equipmentStatusStyle(ticket.equipment.status);
                return (
                  <View style={{ borderWidth: 1, borderRadius: radius.full, paddingHorizontal: 8, paddingVertical: 3, backgroundColor: es.bg, borderColor: es.border }}>
                    <Text style={{ fontSize: font.xs - 1, fontWeight: font.semibold, color: es.text }}>
                      {statusLabel(ticket.equipment.status)}
                    </Text>
                  </View>
                );
              })()
            ) : <Text style={ir.value}>—</Text>}
          </InfoRow>
          <InfoRow label="Lab Location" value={ticket.equipment?.lab?.name} />
          {ticket.equipment?.lab?.location && (
            <InfoRow label="Building/Floor" value={ticket.equipment.lab.location} />
          )}
        </SectionCard>

        {/* ── Reporter Contact ── */}
        <SectionCard title="Reporter Contact">
          <View style={s.reporterCard}>
            <View style={s.reporterAvatar}>
              <Text style={s.reporterAvatarText}>
                {getInitials(
                  ticket.reporter?.first_name ?? '?',
                  ticket.reporter?.last_name ?? ''
                )}
              </Text>
            </View>
            <View style={{ flex: 1 }}>
              <Text style={s.reporterName}>
                {ticket.reporter?.first_name} {ticket.reporter?.last_name}
              </Text>
              <Text style={s.reporterRole}>
                {statusLabel(ticket.reporter?.role ?? '')}
              </Text>
              {ticket.reporter?.email && (
                <TouchableOpacity
                  onPress={() => Linking.openURL(`mailto:${ticket.reporter!.email}`)}
                  activeOpacity={0.7}
                >
                  <Text style={s.reporterContact}>📧 {ticket.reporter.email}</Text>
                </TouchableOpacity>
              )}
              {ticket.reporter?.phone && (
                <TouchableOpacity
                  onPress={() => Linking.openURL(`tel:${ticket.reporter!.phone}`)}
                  activeOpacity={0.7}
                >
                  <Text style={s.reporterContact}>📞 {ticket.reporter.phone}</Text>
                </TouchableOpacity>
              )}
            </View>
          </View>
        </SectionCard>

        {/* ── Attachments ── */}
        {ticket.attachments && ticket.attachments.length > 0 && (
          <SectionCard title="Attachments">
            <View style={s.attachGrid}>
              {ticket.attachments.map((att) => (
                <View key={att} style={s.attachItem}>
                  <Text style={s.attachIcon}>
                    {att.endsWith('.pdf') ? '📄' : '🖼️'}
                  </Text>
                  <Text style={s.attachName} numberOfLines={1}>
                    {att.split('/').pop()}
                  </Text>
                </View>
              ))}
            </View>
          </SectionCard>
        )}

        {/* ── Activity Timeline ── */}
        <SectionCard title="Activity Timeline">
          {ticket.transactions && ticket.transactions.length > 0 ? (
            <View style={s.timeline}>
              {ticket.transactions.map((tx, i) => (
                <View key={tx.id} style={s.tlItem}>
                  <View style={s.tlDotWrap}>
                    <View style={s.tlDot} />
                    {i < (ticket.transactions?.length ?? 0) - 1 && (
                      <View style={s.tlLine} />
                    )}
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
                        {tx.old_value && <Text style={s.tlOld}>{tx.old_value}</Text>}
                        {tx.old_value && tx.new_value && (
                          <Text style={s.tlArrow}> → </Text>
                        )}
                        {tx.new_value && <Text style={s.tlNew}>{tx.new_value}</Text>}
                      </View>
                    )}
                    <Text style={s.tlBy}>
                      by {tx.user
                        ? `${tx.user.first_name} ${tx.user.last_name}`
                        : 'System'}
                    </Text>
                  </View>
                </View>
              ))}
            </View>
          ) : (
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
                    Report submitted by {ticket.reporter?.first_name} {ticket.reporter?.last_name}
                  </Text>
                </View>
              </View>
            </View>
          )}
        </SectionCard>

        {/* ── Next Steps hint ── */}
        {isResolved ? (
          <View style={[s.hintCard, s.hintCardGreen]}>
            <Text style={[s.hintTitle, { color: '#10b981' }]}>✅ Great Job!</Text>
            <Text style={s.hintText}>
              You've successfully resolved this ticket. The reporter has been notified.
            </Text>
          </View>
        ) : (
          <View style={[s.hintCard, s.hintCardTeal]}>
            <Text style={[s.hintTitle, { color: colors.primary }]}>💡 Next Steps</Text>
            {ticket.status === 'assigned' ? (
              <Text style={s.hintText}>
                Start working on this ticket to let the reporter know you're actively resolving their issue.
              </Text>
            ) : ticket.status === 'in-progress' ? (
              <Text style={s.hintText}>
                Once you've resolved the issue, mark this ticket as resolved to notify the reporter.
              </Text>
            ) : (
              <Text style={s.hintText}>
                Update the status to reflect your current progress on this ticket.
              </Text>
            )}
          </View>
        )}

        <View style={{ height: spacing.xxl }} />
      </ScrollView>
    </SafeAreaView>
  );
}

// ─── Styles ───────────────────────────────────────────────────────────────────

const s = StyleSheet.create({
  root:    { flex: 1, backgroundColor: colors.bgPrimary },
  scroll:  { paddingBottom: spacing.lg },
  centered:{ flex: 1, alignItems: 'center', justifyContent: 'center', gap: spacing.md, padding: spacing.lg },

  // Nav
  nav: {
    flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center',
    paddingHorizontal: spacing.lg, paddingVertical: spacing.sm + 4,
    borderBottomWidth: 1, borderBottomColor: colors.border,
  },
  navBack:       { fontSize: font.sm, fontWeight: font.semibold, color: colors.primary },
  updateBtn:     { backgroundColor: colors.primary, borderRadius: radius.md, paddingHorizontal: spacing.md, paddingVertical: spacing.xs + 2 },
  updateBtnText: { fontSize: font.sm, fontWeight: font.bold, color: '#fff' },

  // Header card
  headerCard: {
    marginHorizontal: spacing.lg, marginTop: spacing.lg, marginBottom: spacing.md,
    backgroundColor: colors.bgCard, borderWidth: 1, borderColor: colors.border,
    borderRadius: radius.xl, padding: spacing.lg,
  },
  headerTop: { flexDirection: 'row', alignItems: 'flex-start', marginBottom: spacing.md },
  ticketTitle:    { fontSize: font.lg, fontWeight: font.bold, color: colors.textPrimary, lineHeight: 24, marginBottom: spacing.xs },
  ticketSubNum:   { fontSize: font.xs, color: colors.textMuted },
  priorityBadge:  { borderWidth: 1, borderRadius: radius.full, paddingHorizontal: spacing.sm + 2, paddingVertical: 4, alignSelf: 'flex-start' },
  priorityBadgeText: { fontSize: font.xs - 1, fontWeight: font.bold },

  metaGrid: {
    flexDirection: 'row', flexWrap: 'wrap',
    borderTopWidth: 1, borderTopColor: colors.borderLight,
    paddingTop: spacing.md, gap: spacing.sm, marginBottom: spacing.md,
  },
  metaItem:  { width: '47%', gap: 2 },
  metaLabel: { fontSize: font.xs - 1, color: colors.textMuted },
  metaValue: { fontSize: font.sm, fontWeight: font.medium, color: colors.textPrimary },

  statusRow: {
    flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center',
    borderTopWidth: 1, borderTopColor: colors.borderLight, paddingTop: spacing.sm,
  },
  statusRowLabel: { fontSize: font.sm, color: colors.textMuted },

  // Cards
  card: {
    marginHorizontal: spacing.lg, marginBottom: spacing.md,
    backgroundColor: colors.bgCard, borderWidth: 1, borderColor: colors.border,
    borderRadius: radius.xl, padding: spacing.lg,
  },
  cardTitle: { fontSize: font.base, fontWeight: font.bold, color: colors.primary, marginBottom: spacing.md },

  // Action buttons
  actionBtns: { gap: spacing.sm },
  primaryBtn: {
    backgroundColor: colors.primary, borderRadius: radius.lg,
    paddingVertical: spacing.md, alignItems: 'center',
    shadowColor: colors.primary, shadowOffset: { width: 0, height: 3 },
    shadowOpacity: 0.3, shadowRadius: 6, elevation: 4,
  },
  primaryBtnText: { fontSize: font.base, fontWeight: font.bold, color: '#fff' },
  outlineBtn: {
    borderWidth: 1.5, borderColor: colors.primary, borderRadius: radius.lg,
    paddingVertical: spacing.md, alignItems: 'center',
  },
  outlineBtnText: { fontSize: font.base, fontWeight: font.semibold, color: colors.primary },

  // Description
  descText: { fontSize: font.base, color: colors.textSecondary, lineHeight: 24 },

  // Reporter
  reporterCard: { flexDirection: 'row', alignItems: 'flex-start', gap: spacing.md },
  reporterAvatar: {
    width: 46, height: 46, borderRadius: radius.full,
    backgroundColor: colors.primary, alignItems: 'center', justifyContent: 'center', flexShrink: 0,
  },
  reporterAvatarText: { fontSize: font.base, fontWeight: font.bold, color: '#fff' },
  reporterName:    { fontSize: font.base, fontWeight: font.semibold, color: colors.textPrimary, marginBottom: 2 },
  reporterRole:    { fontSize: font.sm, color: colors.textMuted, textTransform: 'capitalize' },
  reporterContact: { fontSize: font.xs, color: colors.primary, marginTop: spacing.xs },

  // Attachments
  attachGrid: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm },
  attachItem: {
    width: '30%', backgroundColor: 'rgba(255,255,255,0.04)',
    borderWidth: 1, borderColor: colors.border, borderRadius: radius.lg,
    padding: spacing.sm, alignItems: 'center', gap: spacing.xs,
  },
  attachIcon: { fontSize: 22 },
  attachName: { fontSize: font.xs - 1, color: colors.textMuted, textAlign: 'center' },

  // Timeline
  timeline: { gap: 0 },
  tlItem:   { flexDirection: 'row', gap: spacing.sm, paddingBottom: spacing.md },
  tlDotWrap:{ alignItems: 'center', width: 12, paddingTop: 3 },
  tlDot:    { width: 11, height: 11, borderRadius: 6, backgroundColor: colors.primary, borderWidth: 2, borderColor: colors.bgCard },
  tlLine:   { flex: 1, width: 1, backgroundColor: 'rgba(45,212,191,0.2)', marginTop: 3 },
  tlContent:{
    flex: 1, backgroundColor: 'rgba(45,212,191,0.05)',
    borderWidth: 1, borderColor: 'rgba(45,212,191,0.1)',
    borderLeftWidth: 2, borderLeftColor: colors.primary,
    borderRadius: radius.md, padding: spacing.sm + 2, gap: spacing.xs,
  },
  tlHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-start', flexWrap: 'wrap', gap: spacing.xs },
  tlTitle:  { fontSize: font.sm, fontWeight: font.semibold, color: colors.textPrimary, flex: 1 },
  tlTime:   { fontSize: font.xs - 1, color: colors.textMuted },
  tlText:   { fontSize: font.xs, color: colors.textMuted, lineHeight: 17 },
  tlChange: { flexDirection: 'row', alignItems: 'center', backgroundColor: 'rgba(45,212,191,0.07)', borderRadius: radius.sm, paddingHorizontal: spacing.xs + 2, paddingVertical: 2, alignSelf: 'flex-start' },
  tlOld:    { fontSize: font.xs, color: '#f87171' },
  tlArrow:  { fontSize: font.xs, color: colors.textMuted },
  tlNew:    { fontSize: font.xs, color: '#34d399' },
  tlBy:     { fontSize: font.xs - 1, color: colors.textMuted, fontStyle: 'italic' },

  // Next steps hint card
  hintCard: { marginHorizontal: spacing.lg, marginBottom: spacing.md, borderRadius: radius.xl, padding: spacing.lg, gap: spacing.xs },
  hintCardTeal:  { backgroundColor: 'rgba(45,212,191,0.08)', borderWidth: 1, borderColor: 'rgba(45,212,191,0.2)' },
  hintCardGreen: { backgroundColor: 'rgba(16,185,129,0.08)', borderWidth: 1, borderColor: 'rgba(16,185,129,0.2)' },
  hintTitle: { fontSize: font.base, fontWeight: font.bold, marginBottom: 2 },
  hintText:  { fontSize: font.sm, color: colors.textMuted, lineHeight: 20 },

  // Error / states
  errorIcon: { fontSize: 36 },
  errorText: { fontSize: font.base, color: colors.textSecondary, textAlign: 'center' },
  retryBtn:  { backgroundColor: colors.primary, paddingHorizontal: spacing.lg, paddingVertical: spacing.sm + 2, borderRadius: radius.full },
  retryText: { fontSize: font.base, fontWeight: font.bold, color: '#fff' },
});