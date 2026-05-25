/**
 * app/(it)/queue/[id].tsx  —  IT Ticket Detail (View)
 *
 * Mirrors: resources/js/Pages/IT/Tickets/Show.vue
 *
 * Fetches GET /api/it/queue/{id} on mount.
 * Response: full ticket with reporter, assignedTo, equipment.lab, transactions.user
 *
 * Sections:
 *   1. Ticket header card  — title, ticket #, priority badge, status, meta grid
 *   2. Problem Description
 *   3. Equipment Information
 *   4. Activity Timeline   — full transaction log
 *   5. Sidebar (stacked on mobile):
 *        - Quick Information (status, priority, response time, age)
 *        - Reporter Information
 *        - Assigned Technician / Assignment Status
 *        - Quick Actions (Edit · Mark Resolved · Assign to Me)
 *   Pull-to-refresh.
 */

import React, { useEffect, useState, useCallback } from 'react';
import {
  View, Text, ScrollView, TouchableOpacity,
  StyleSheet, StatusBar, ActivityIndicator,
  RefreshControl, Alert,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { router, useLocalSearchParams } from 'expo-router';
import { colors, spacing, radius, font } from '@/constants/theme';
import { loadAuth, AuthUser } from '@/utils/auth';
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
  assigned_to_user?: {
    first_name: string; last_name: string;
    email: string | null; role: string;
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
      {children ? children : <Text style={ir.value}>{value ?? '—'}</Text>}
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
    <View style={sc.card}>
      <Text style={sc.title}>{title}</Text>
      {children}
    </View>
  );
}
const sc = StyleSheet.create({
  card:  { backgroundColor: colors.bgCard, borderWidth: 1, borderColor: colors.border, borderRadius: radius.xl, padding: spacing.lg, marginBottom: spacing.md },
  title: { fontSize: font.base, fontWeight: font.bold, color: colors.primary, marginBottom: spacing.md },
});

function StatusPill({ status }: { status: string }) {
  const st = statusStyle(status);
  return (
    <View style={[{ borderWidth: 1, borderRadius: radius.full, paddingHorizontal: 9, paddingVertical: 3, backgroundColor: st.bg, borderColor: st.border }]}>
      <Text style={{ fontSize: font.xs - 1, fontWeight: font.semibold, color: st.text }}>
        {statusLabel(status)}
      </Text>
    </View>
  );
}

// ─── Main Component ───────────────────────────────────────────────────────────

export default function ITQueueShowScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();

  const [authUser,    setAuthUser]    = useState<AuthUser | null>(null);
  const [token,       setToken]       = useState<string | null>(null);
  const [ticket,      setTicket]      = useState<Ticket | null>(null);
  const [loading,     setLoading]     = useState(true);
  const [refreshing,  setRefreshing]  = useState(false);
  const [error,       setError]       = useState<string | null>(null);
  const [resolving,   setResolving]   = useState(false);
  const [assigning,   setAssigning]   = useState(false);

  // ── Auth ──────────────────────────────────────────────────────────────────
  useEffect(() => {
    loadAuth().then((auth) => {
      if (!auth) { router.replace('/login'); return; }
      setAuthUser(auth.user);
      setToken(auth.token);
    });
  }, []);

  // ── Fetch ─────────────────────────────────────────────────────────────────
  const fetchTicket = useCallback(async (tok: string) => {
    try {
      const res = await fetch(apiUrl(`it/queue/${id}`), {
        headers: { 'Accept': 'application/json', 'Authorization': `Bearer ${tok}` },
      });
      if (res.status === 401) { router.replace('/login'); return; }
      if (!res.ok) throw new Error(`Server error ${res.status}`);
      setTicket(await res.json());
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

  // ── Mark Resolved ─────────────────────────────────────────────────────────
  async function handleMarkResolved() {
    if (!token || !ticket) return;
    setResolving(true);
    try {
      const res = await fetch(apiUrl(`it/queue/${ticket.id}`), {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'Authorization': `Bearer ${token}`,
        },
        body: JSON.stringify({
          status:      'resolved',
          priority:    ticket.priority,
          assigned_to: ticket.assigned_to,
        }),
      });
      if (!res.ok) throw new Error('Failed to update ticket.');
      setTicket(await res.json());
    } catch {
      Alert.alert('Error', 'Could not update ticket. Please try again.');
    } finally {
      setResolving(false);
    }
  }

  // ── Assign to Self ────────────────────────────────────────────────────────
  async function handleAssignSelf() {
    if (!token || !ticket) return;
    setAssigning(true);
    try {
      const res = await fetch(apiUrl(`it/queue/${ticket.id}/assign-self`), {
        method: 'POST',
        headers: { 'Accept': 'application/json', 'Authorization': `Bearer ${token}` },
      });
      if (!res.ok) throw new Error('Failed to assign ticket.');
      setTicket(await res.json());
    } catch {
      Alert.alert('Error', 'Could not assign ticket. Please try again.');
    } finally {
      setAssigning(false);
    }
  }

  // ─── Loading / error states ───────────────────────────────────────────────
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

  const isMyTicket   = ticket.assigned_to === authUser?.id;
  const isUnassigned = !ticket.assigned_to;
  const isResolved   = ticket.status === 'resolved' || ticket.status === 'closed';
  const sc_          = statusStyle(ticket.status);
  const pc           = priorityColor(ticket.priority);

  // ─── Main render ──────────────────────────────────────────────────────────
  return (
    <SafeAreaView style={s.root} edges={['top']}>
      <StatusBar barStyle="light-content" backgroundColor={colors.bgPrimary} />

      {/* ── Top Nav ── */}
      <View style={s.nav}>
        <TouchableOpacity onPress={() => router.back()} activeOpacity={0.7}>
          <Text style={s.navBack}>← Queue</Text>
        </TouchableOpacity>
        <Text style={s.navTicketNum}>{ticket.ticket_number}</Text>
        <TouchableOpacity
          style={s.editBtn}
          onPress={() => router.push(`/(it)/queue/${ticket.id}/edit` as any)}
          activeOpacity={0.8}
        >
          <Text style={s.editBtnText}>Edit</Text>
        </TouchableOpacity>
      </View>

      <ScrollView
        showsVerticalScrollIndicator={false}
        contentContainerStyle={s.scroll}
        refreshControl={
          <RefreshControl refreshing={refreshing} onRefresh={onRefresh}
            tintColor={colors.primary} colors={[colors.primary]} />
        }
      >
        {/* ── Ticket Header Card ── */}
        <View style={s.headerCard}>
          {/* Title + priority badge */}
          <View style={s.headerTop}>
            <View style={{ flex: 1, paddingRight: spacing.sm }}>
              <Text style={s.ticketTitle}>{ticket.title}</Text>
              <Text style={s.ticketSubNum}>Ticket {ticket.ticket_number}</Text>
            </View>
            <View style={[s.priorityBadge, { backgroundColor: pc + '22', borderColor: pc + '55' }]}>
              <Text style={[s.priorityBadgeText, { color: pc }]}>
                {ticket.priority.charAt(0).toUpperCase() + ticket.priority.slice(1)} Priority
              </Text>
            </View>
          </View>

          {/* Meta grid — 2 columns */}
          <View style={s.metaGrid}>
            {[
              { label: 'Reporter',    value: ticket.reporter ? `${ticket.reporter.first_name} ${ticket.reporter.last_name}` : '—' },
              { label: 'Location',   value: [ticket.equipment?.lab?.name, ticket.equipment?.equipment_code].filter(Boolean).join(', ') || '—' },
              { label: 'Category',   value: ticket.category.charAt(0).toUpperCase() + ticket.category.slice(1) },
              { label: 'Priority',   value: ticket.priority.charAt(0).toUpperCase() + ticket.priority.slice(1), color: pc },
              { label: 'Submitted',  value: formatDate(ticket.created_at) },
              { label: 'Last Updated', value: diffForHumans(ticket.updated_at) },
            ].map((m) => (
              <View key={m.label} style={s.metaItem}>
                <Text style={s.metaLabel}>{m.label}</Text>
                <Text style={[s.metaValue, m.color ? { color: m.color } : {}]} numberOfLines={1}>
                  {m.value}
                </Text>
              </View>
            ))}
          </View>

          {/* Status pill */}
          <View style={s.statusRow}>
            <Text style={s.statusLabelText}>Status</Text>
            <StatusPill status={ticket.status} />
          </View>
        </View>

        {/* ── Quick Actions ── */}
        <SectionCard title="Quick Actions">
          <View style={s.actionBtns}>
            <TouchableOpacity
              style={s.primaryBtn}
              onPress={() => router.push(`/(it)/queue/${ticket.id}/edit` as any)}
              activeOpacity={0.85}
            >
              <Text style={s.primaryBtnText}>Edit Ticket</Text>
            </TouchableOpacity>

            {!isResolved && (
              <TouchableOpacity
                style={[s.outlineBtn, resolving && { opacity: 0.6 }]}
                onPress={handleMarkResolved}
                disabled={resolving}
                activeOpacity={0.8}
              >
                {resolving
                  ? <ActivityIndicator size="small" color={colors.primary} />
                  : <Text style={s.outlineBtnText}>Mark as Resolved</Text>}
              </TouchableOpacity>
            )}

            {isUnassigned && (
              <TouchableOpacity
                style={[s.outlineBtn, assigning && { opacity: 0.6 }]}
                onPress={handleAssignSelf}
                disabled={assigning}
                activeOpacity={0.8}
              >
                {assigning
                  ? <ActivityIndicator size="small" color={colors.primary} />
                  : <Text style={s.outlineBtnText}>Assign to Me</Text>}
              </TouchableOpacity>
            )}
          </View>
        </SectionCard>

        {/* ── Problem Description ── */}
        <SectionCard title="Problem Description">
          <Text style={s.descText}>{ticket.description}</Text>
        </SectionCard>

        {/* ── Equipment Information ── */}
        <SectionCard title="Equipment Information">
          <InfoRow label="Equipment Code" value={ticket.equipment?.equipment_code} />
          <InfoRow label="Equipment Type"
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
          <InfoRow label="Lab Location"   value={ticket.equipment?.lab?.name} />
          {ticket.equipment?.lab?.location && (
            <InfoRow label="Building/Floor" value={ticket.equipment.lab.location} />
          )}
        </SectionCard>

        {/* ── Quick Info ── */}
        <SectionCard title="Quick Information">
          <InfoRow label="Status">
            <StatusPill status={ticket.status} />
          </InfoRow>
          <InfoRow
            label="Priority"
            value={ticket.priority.charAt(0).toUpperCase() + ticket.priority.slice(1)}
          />
          {ticket.assigned_at && (
            <InfoRow label="Response Time" value={diffForHumans(ticket.assigned_at)} />
          )}
          <InfoRow label="Age" value={diffForHumans(ticket.created_at)} />
        </SectionCard>

        {/* ── Reporter Information ── */}
        <SectionCard title="Reporter Information">
          <View style={s.personCard}>
            <View style={s.personAvatar}>
              <Text style={s.personAvatarText}>
                {getInitials(ticket.reporter?.first_name ?? '?', ticket.reporter?.last_name ?? '')}
              </Text>
            </View>
            <View style={{ flex: 1 }}>
              <Text style={s.personName}>
                {ticket.reporter?.first_name} {ticket.reporter?.last_name}
              </Text>
              <Text style={s.personRole}>
                {statusLabel(ticket.reporter?.role ?? '')}
              </Text>
              {ticket.reporter?.email && (
                <Text style={s.personContact}>📧 {ticket.reporter.email}</Text>
              )}
              {ticket.reporter?.phone && (
                <Text style={s.personContact}>📞 {ticket.reporter.phone}</Text>
              )}
            </View>
          </View>
        </SectionCard>

        {/* ── Assigned Technician / Assignment Status ── */}
        {ticket.assigned_to && ticket.assigned_to_user ? (
          <SectionCard title="Assigned Technician">
            <View style={s.personCard}>
              <View style={[s.personAvatar, { backgroundColor: '#d97706' }]}>
                <Text style={s.personAvatarText}>
                  {getInitials(ticket.assigned_to_user.first_name, ticket.assigned_to_user.last_name)}
                </Text>
              </View>
              <View style={{ flex: 1 }}>
                <Text style={s.personName}>
                  {ticket.assigned_to_user.first_name} {ticket.assigned_to_user.last_name}
                  {isMyTicket && (
                    <Text style={{ color: colors.primary, fontSize: font.xs }}> (You)</Text>
                  )}
                </Text>
                <Text style={s.personRole}>
                  {statusLabel(ticket.assigned_to_user.role ?? '')}
                </Text>
                {ticket.assigned_to_user.email && (
                  <Text style={s.personContact}>📧 {ticket.assigned_to_user.email}</Text>
                )}
              </View>
            </View>
          </SectionCard>
        ) : (
          <SectionCard title="Assignment Status">
            <View style={s.unassignedWrap}>
              <Text style={s.unassignedIcon}>⏳</Text>
              <Text style={s.unassignedText}>Not yet assigned</Text>
              <TouchableOpacity
                style={[s.primaryBtn, { marginTop: spacing.md }, assigning && { opacity: 0.6 }]}
                onPress={handleAssignSelf}
                disabled={assigning}
                activeOpacity={0.85}
              >
                {assigning
                  ? <ActivityIndicator size="small" color="#fff" />
                  : <Text style={s.primaryBtnText}>Assign to Me</Text>}
              </TouchableOpacity>
            </View>
          </SectionCard>
        )}

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
                    {i < (ticket.transactions?.length ?? 0) - 1 && <View style={s.tlLine} />}
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
                      by {tx.user ? `${tx.user.first_name} ${tx.user.last_name}` : 'System'}
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
  navBack:      { fontSize: font.sm, fontWeight: font.semibold, color: colors.primary, width: 60 },
  navTicketNum: { fontSize: font.sm, fontWeight: font.semibold, color: colors.textMuted },
  editBtn: {
    backgroundColor: colors.primary, borderRadius: radius.md,
    paddingHorizontal: spacing.md, paddingVertical: spacing.xs + 2,
  },
  editBtnText: { fontSize: font.sm, fontWeight: font.bold, color: '#fff' },

  // Header card
  headerCard: {
    marginHorizontal: spacing.lg, marginTop: spacing.lg, marginBottom: spacing.md,
    backgroundColor: colors.bgCard, borderWidth: 1, borderColor: colors.border,
    borderRadius: radius.xl, padding: spacing.lg,
  },
  headerTop: {
    flexDirection: 'row', alignItems: 'flex-start', marginBottom: spacing.md,
  },
  ticketTitle:    { fontSize: font.lg, fontWeight: font.bold, color: colors.textPrimary, lineHeight: 24, marginBottom: spacing.xs },
  ticketSubNum:   { fontSize: font.xs, color: colors.textMuted },
  priorityBadge: {
    borderWidth: 1, borderRadius: radius.full,
    paddingHorizontal: spacing.sm + 2, paddingVertical: 4, alignSelf: 'flex-start',
  },
  priorityBadgeText: { fontSize: font.xs - 1, fontWeight: font.bold },

  // Meta grid
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
  statusLabelText: { fontSize: font.sm, color: colors.textMuted },

  // Action buttons
  actionBtns: { gap: spacing.sm },
  primaryBtn: {
    backgroundColor: colors.primary, borderRadius: radius.lg,
    paddingVertical: spacing.sm + 4, alignItems: 'center',
    shadowColor: colors.primary, shadowOffset: { width: 0, height: 3 },
    shadowOpacity: 0.3, shadowRadius: 6, elevation: 4,
  },
  primaryBtnText: { fontSize: font.base, fontWeight: font.bold, color: '#fff' },
  outlineBtn: {
    backgroundColor: 'transparent', borderWidth: 1.5, borderColor: colors.primary,
    borderRadius: radius.lg, paddingVertical: spacing.sm + 4, alignItems: 'center',
  },
  outlineBtnText: { fontSize: font.base, fontWeight: font.semibold, color: colors.primary },

  // Description
  descText: { fontSize: font.base, color: colors.textSecondary, lineHeight: 24 },

  // Person card (reporter / technician)
  personCard: { flexDirection: 'row', alignItems: 'flex-start', gap: spacing.md },
  personAvatar: {
    width: 46, height: 46, borderRadius: radius.full,
    backgroundColor: colors.primary, alignItems: 'center', justifyContent: 'center', flexShrink: 0,
  },
  personAvatarText: { fontSize: font.base, fontWeight: font.bold, color: '#fff' },
  personName:    { fontSize: font.base, fontWeight: font.semibold, color: colors.textPrimary, marginBottom: 2 },
  personRole:    { fontSize: font.sm, color: colors.textMuted, textTransform: 'capitalize' },
  personContact: { fontSize: font.xs, color: colors.primary, marginTop: spacing.xs },

  // Unassigned state
  unassignedWrap: { alignItems: 'center', paddingVertical: spacing.md, gap: spacing.xs },
  unassignedIcon: { fontSize: 30 },
  unassignedText: { fontSize: font.sm, color: colors.textMuted },

  // Attachments
  attachGrid: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm },
  attachItem: {
    width: '30%', backgroundColor: colors.bgCard,
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

  // Error / states
  errorIcon: { fontSize: 36 },
  errorText: { fontSize: font.base, color: colors.textSecondary, textAlign: 'center' },
  retryBtn:  { backgroundColor: colors.primary, paddingHorizontal: spacing.lg, paddingVertical: spacing.sm + 2, borderRadius: radius.full },
  retryText: { fontSize: font.base, fontWeight: font.bold, color: '#fff' },
});