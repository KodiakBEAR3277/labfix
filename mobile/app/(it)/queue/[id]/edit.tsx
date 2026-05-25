/**
 * app/(it)/queue/[id]/edit.tsx  —  IT Ticket Edit
 *
 * Mirrors: resources/js/Pages/IT/Tickets/Edit.vue
 *
 * Fetches GET /api/it/queue/{id} on mount to pre-fill the form.
 * Submits  PUT /api/it/queue/{id} on save.
 *
 * Rules (same as web):
 *   - IT staff can update: status, priority, assigned_to (self only)
 *   - Admin can update: status, priority, assigned_to (anyone — fetched
 *     from the existing /api/it/queue response or a separate staff list;
 *     for now we keep it simple: admins see a "Myself" option plus
 *     "Unassigned" just like IT, since mobile doesn't need the full
 *     admin reassign flow — that lives in the admin zone)
 *   - Quick-action buttons: Mark In Progress · Mark Resolved · Assign to Me
 *
 * On success → navigate back to queue/[id] show screen.
 */

import React, { useEffect, useState, useCallback } from 'react';
import {
  View, Text, ScrollView, TouchableOpacity,
  StyleSheet, StatusBar, ActivityIndicator,
  Alert, KeyboardAvoidingView, Platform,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { router, useLocalSearchParams } from 'expo-router';
import { colors, spacing, radius, font } from '@/constants/theme';
import { loadAuth, AuthUser } from '@/utils/auth';
import { apiUrl } from '@/constants/api';

// ─── Types ────────────────────────────────────────────────────────────────────

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
  reporter?: { first_name: string; last_name: string };
  assigned_to_user?: { id: number; first_name: string; last_name: string; role: string };
  equipment?: {
    equipment_code: string; type: string; status: string;
    lab?: { name: string };
  };
};

type FormState = {
  status:      string;
  priority:    string;
  assigned_to: number | null;
};

type FieldErrors = {
  status?:      string;
  priority?:    string;
  assigned_to?: string;
  general?:     string;
};

// ─── Constants ────────────────────────────────────────────────────────────────

const STATUS_OPTIONS = [
  { value: 'new',         label: 'New'         },
  { value: 'assigned',    label: 'Assigned'     },
  { value: 'in-progress', label: 'In Progress'  },
  { value: 'resolved',    label: 'Resolved'     },
  { value: 'closed',      label: 'Closed'       },
];

const PRIORITY_OPTIONS = [
  { value: 'low',    label: 'Low',    color: colors.success },
  { value: 'medium', label: 'Medium', color: colors.warning },
  { value: 'high',   label: 'High',   color: colors.danger  },
];

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

function diffForHumans(iso: string): string {
  const sec = Math.floor((Date.now() - new Date(iso).getTime()) / 1000);
  if (sec < 60)    return `${sec}s ago`;
  if (sec < 3600)  return `${Math.floor(sec / 60)}m ago`;
  if (sec < 86400) return `${Math.floor(sec / 3600)}h ago`;
  return `${Math.floor(sec / 86400)}d ago`;
}

// ─── Sub-components ───────────────────────────────────────────────────────────

function FieldError({ message }: { message?: string }) {
  if (!message) return null;
  return <Text style={s.fieldError}>{message}</Text>;
}

function InfoRow({ label, value }: { label: string; value?: string }) {
  return (
    <View style={s.infoRow}>
      <Text style={s.infoLabel}>{label}</Text>
      <Text style={s.infoValue}>{value ?? '—'}</Text>
    </View>
  );
}

function SectionCard({ title, children }: { title: string; children: React.ReactNode }) {
  return (
    <View style={s.card}>
      <Text style={s.cardTitle}>{title}</Text>
      {children}
    </View>
  );
}

// ─── Main Component ───────────────────────────────────────────────────────────

export default function ITQueueEditScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();

  const [authUser,    setAuthUser]    = useState<AuthUser | null>(null);
  const [token,       setToken]       = useState<string | null>(null);
  const [ticket,      setTicket]      = useState<Ticket | null>(null);
  const [loading,     setLoading]     = useState(true);
  const [submitting,  setSubmitting]  = useState(false);
  const [quickLoading,setQuickLoading]= useState<string | null>(null); // tracks which quick action
  const [errors,      setErrors]      = useState<FieldErrors>({});

  const [form, setForm] = useState<FormState>({
    status:      '',
    priority:    '',
    assigned_to: null,
  });

  // ── Auth ──────────────────────────────────────────────────────────────────
  useEffect(() => {
    loadAuth().then((auth) => {
      if (!auth) { router.replace('/login'); return; }
      setAuthUser(auth.user);
      setToken(auth.token);
    });
  }, []);

  // ── Fetch ticket to pre-fill ──────────────────────────────────────────────
  const fetchTicket = useCallback(async (tok: string) => {
    try {
      const res = await fetch(apiUrl(`it/queue/${id}`), {
        headers: { 'Accept': 'application/json', 'Authorization': `Bearer ${tok}` },
      });
      if (res.status === 401) { router.replace('/login'); return; }
      if (!res.ok) throw new Error(`Server error ${res.status}`);
      const data: Ticket = await res.json();
      setTicket(data);
      setForm({
        status:      data.status,
        priority:    data.priority,
        assigned_to: data.assigned_to,
      });
    } catch (e: any) {
      setErrors({ general: e.message ?? 'Could not load ticket.' });
    }
  }, [id]);

  useEffect(() => {
    if (!token) return;
    setLoading(true);
    fetchTicket(token).finally(() => setLoading(false));
  }, [token, fetchTicket]);

  // ── Submit main form ──────────────────────────────────────────────────────
  async function handleSubmit() {
    if (!token || !ticket) return;
    setErrors({});
    setSubmitting(true);

    try {
      const res = await fetch(apiUrl(`it/queue/${ticket.id}`), {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'Accept':        'application/json',
          'Authorization': `Bearer ${token}`,
        },
        body: JSON.stringify({
          status:      form.status,
          priority:    form.priority,
          assigned_to: form.assigned_to,
        }),
      });

      const data = await res.json();

      if (res.ok) {
        router.replace(`/(it)/queue/${ticket.id}` as any);
      } else if (res.status === 422) {
        const e: FieldErrors = {};
        const raw = data?.errors ?? {};
        for (const key of Object.keys(raw) as (keyof FieldErrors)[]) {
          e[key] = Array.isArray(raw[key]) ? raw[key][0] : raw[key];
        }
        setErrors(e);
      } else {
        setErrors({ general: data?.message ?? 'Something went wrong.' });
      }
    } catch {
      setErrors({ general: 'Could not reach the server. Check your connection.' });
    } finally {
      setSubmitting(false);
    }
  }

  // ── Quick action (one-tap status change) ──────────────────────────────────
  async function quickUpdate(overrides: Partial<FormState>, label: string) {
    if (!token || !ticket) return;
    setQuickLoading(label);

    try {
      const payload = {
        status:      overrides.status      ?? form.status,
        priority:    overrides.priority    ?? form.priority,
        assigned_to: overrides.assigned_to !== undefined
          ? overrides.assigned_to
          : form.assigned_to,
      };

      const res = await fetch(apiUrl(`it/queue/${ticket.id}`), {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'Accept':        'application/json',
          'Authorization': `Bearer ${token}`,
        },
        body: JSON.stringify(payload),
      });

      if (!res.ok) throw new Error('Failed to update ticket.');
      router.replace(`/(it)/queue/${ticket.id}` as any);
    } catch {
      Alert.alert('Error', 'Could not update ticket. Please try again.');
    } finally {
      setQuickLoading(null);
    }
  }

  // ── Assign to self ────────────────────────────────────────────────────────
  async function handleAssignSelf() {
    if (!token || !ticket || !authUser) return;
    setQuickLoading('assign-self');
    try {
      const res = await fetch(apiUrl(`it/queue/${ticket.id}/assign-self`), {
        method:  'POST',
        headers: { 'Accept': 'application/json', 'Authorization': `Bearer ${token}` },
      });
      if (!res.ok) throw new Error('Failed to assign ticket.');
      router.replace(`/(it)/queue/${ticket.id}` as any);
    } catch {
      Alert.alert('Error', 'Could not assign ticket. Please try again.');
    } finally {
      setQuickLoading(null);
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

  if (!ticket) {
    return (
      <SafeAreaView style={s.root}>
        <StatusBar barStyle="light-content" backgroundColor={colors.bgPrimary} />
        <View style={s.centered}>
          <Text style={s.errorText}>{errors.general ?? 'Ticket not found.'}</Text>
          <TouchableOpacity style={s.retryBtn} onPress={() => router.back()}>
            <Text style={s.retryText}>← Go Back</Text>
          </TouchableOpacity>
        </View>
      </SafeAreaView>
    );
  }

  const isMyTicket   = ticket.assigned_to === authUser?.id;
  const isUnassigned = !ticket.assigned_to;

  // ─── Main render ──────────────────────────────────────────────────────────
  return (
    <SafeAreaView style={s.root} edges={['top']}>
      <StatusBar barStyle="light-content" backgroundColor={colors.bgPrimary} />

      {/* ── Top Nav ── */}
      <View style={s.nav}>
        <TouchableOpacity
          onPress={() => router.back()}
          activeOpacity={0.7}
        >
          <Text style={s.navBack}>← Ticket</Text>
        </TouchableOpacity>
        <Text style={s.navTitle}>Edit Ticket</Text>
        <View style={{ width: 60 }} />
      </View>

      <KeyboardAvoidingView
        style={{ flex: 1 }}
        behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
        keyboardVerticalOffset={0}
      >
        <ScrollView
          showsVerticalScrollIndicator={false}
          contentContainerStyle={s.scroll}
          keyboardShouldPersistTaps="handled"
        >
          {/* ── Page header ── */}
          <View style={s.pageHeader}>
            <Text style={s.pageTitle}>Edit Ticket</Text>
            <Text style={s.pageSub}>Update status, priority, and assignment</Text>
          </View>

          {/* ── General error ── */}
          {errors.general && (
            <View style={s.errorBanner}>
              <Text style={s.errorBannerText}>{errors.general}</Text>
            </View>
          )}

          {/* ── Ticket summary (read-only) ── */}
          <SectionCard title="Ticket Summary">
            <InfoRow label="Ticket #"  value={ticket.ticket_number} />
            <InfoRow label="Title"     value={ticket.title} />
            <InfoRow
              label="Reporter"
              value={ticket.reporter
                ? `${ticket.reporter.first_name} ${ticket.reporter.last_name}`
                : '—'}
            />
            <InfoRow
              label="Location"
              value={[ticket.equipment?.lab?.name, ticket.equipment?.equipment_code]
                .filter(Boolean).join(', ') || '—'}
            />
            <InfoRow
              label="Category"
              value={ticket.category.charAt(0).toUpperCase() + ticket.category.slice(1)}
            />
          </SectionCard>

          {/* ── Edit form ── */}
          <SectionCard title="Update Ticket Details">

            {/* Status */}
            <View style={s.fieldGroup}>
              <Text style={s.fieldLabel}>Status *</Text>
              <View style={s.optionGrid}>
                {STATUS_OPTIONS.map((opt) => {
                  const selected = form.status === opt.value;
                  const sc       = statusStyle(opt.value);
                  return (
                    <TouchableOpacity
                      key={opt.value}
                      style={[
                        s.statusOption,
                        selected && { backgroundColor: sc.bg, borderColor: sc.border },
                      ]}
                      onPress={() => setForm(f => ({ ...f, status: opt.value }))}
                      activeOpacity={0.75}
                    >
                      <Text style={[s.statusOptionText, selected && { color: sc.text }]}>
                        {opt.label}
                      </Text>
                      {selected && <View style={[s.selectedDot, { backgroundColor: sc.text }]} />}
                    </TouchableOpacity>
                  );
                })}
              </View>
              <Text style={s.helpText}>Update the current status of this ticket</Text>
              <FieldError message={errors.status} />
            </View>

            {/* Priority */}
            <View style={s.fieldGroup}>
              <Text style={s.fieldLabel}>Priority *</Text>
              <View style={s.priorityRow}>
                {PRIORITY_OPTIONS.map((opt) => {
                  const selected = form.priority === opt.value;
                  return (
                    <TouchableOpacity
                      key={opt.value}
                      style={[
                        s.priorityOption,
                        selected && {
                          backgroundColor: opt.color + '22',
                          borderColor: opt.color + '55',
                        },
                      ]}
                      onPress={() => setForm(f => ({ ...f, priority: opt.value }))}
                      activeOpacity={0.75}
                    >
                      <Text style={{ fontSize: 14 }}>
                        {opt.value === 'high' ? '🔴' : opt.value === 'medium' ? '🟡' : '🟢'}
                      </Text>
                      <Text style={[
                        s.priorityOptionText,
                        selected && { color: opt.color, fontWeight: font.bold },
                      ]}>
                        {opt.label}
                      </Text>
                    </TouchableOpacity>
                  );
                })}
              </View>
              <Text style={s.helpText}>Adjust priority based on urgency and impact</Text>
              <FieldError message={errors.priority} />
            </View>

            {/* Assign To */}
            <View style={s.fieldGroup}>
              <Text style={s.fieldLabel}>Assign To</Text>
              <View style={s.assignRow}>
                {/* Unassigned option */}
                <TouchableOpacity
                  style={[
                    s.assignOption,
                    form.assigned_to === null && s.assignOptionSelected,
                  ]}
                  onPress={() => setForm(f => ({ ...f, assigned_to: null }))}
                  activeOpacity={0.75}
                >
                  <Text style={s.assignOptionText}>— Unassigned</Text>
                </TouchableOpacity>

                {/* Myself option */}
                {authUser && (
                  <TouchableOpacity
                    style={[
                      s.assignOption,
                      form.assigned_to === authUser.id && s.assignOptionSelected,
                    ]}
                    onPress={() => setForm(f => ({ ...f, assigned_to: authUser.id }))}
                    activeOpacity={0.75}
                  >
                    <Text style={s.assignOptionText}>
                      Myself ({authUser.name.split(' ')[0]})
                    </Text>
                    {form.assigned_to === authUser.id && (
                      <Text style={{ fontSize: 12, color: colors.primary }}> ✓</Text>
                    )}
                  </TouchableOpacity>
                )}
              </View>
              <Text style={s.helpText}>
                {authUser?.role === 'admin'
                  ? 'Assign to any IT technician via the admin panel for full control'
                  : 'IT Support can assign tickets to themselves only'}
              </Text>
              <FieldError message={errors.assigned_to} />
            </View>

            {/* Submit */}
            <TouchableOpacity
              style={[s.submitBtn, submitting && { opacity: 0.6 }]}
              onPress={handleSubmit}
              disabled={submitting}
              activeOpacity={0.85}
            >
              {submitting ? (
                <ActivityIndicator color="#fff" size="small" />
              ) : (
                <Text style={s.submitBtnText}>Update Ticket &amp; Notify User</Text>
              )}
            </TouchableOpacity>

            <TouchableOpacity
              style={s.cancelBtn}
              onPress={() => router.back()}
              disabled={submitting}
              activeOpacity={0.75}
            >
              <Text style={s.cancelBtnText}>Cancel</Text>
            </TouchableOpacity>
          </SectionCard>

          {/* ── Quick Actions ── */}
          <SectionCard title="Quick Actions">
            <Text style={s.quickSub}>Apply common status changes with one tap</Text>
            <View style={s.quickGrid}>
              {ticket.status !== 'in-progress' && (
                <TouchableOpacity
                  style={[s.quickBtn, quickLoading === 'in-progress' && { opacity: 0.6 }]}
                  onPress={() => quickUpdate({ status: 'in-progress' }, 'in-progress')}
                  disabled={!!quickLoading}
                  activeOpacity={0.8}
                >
                  {quickLoading === 'in-progress'
                    ? <ActivityIndicator size="small" color={colors.textSecondary} />
                    : <Text style={s.quickBtnText}>Mark In Progress</Text>}
                </TouchableOpacity>
              )}

              {ticket.status !== 'resolved' && (
                <TouchableOpacity
                  style={[s.quickBtn, quickLoading === 'resolved' && { opacity: 0.6 }]}
                  onPress={() => quickUpdate({ status: 'resolved' }, 'resolved')}
                  disabled={!!quickLoading}
                  activeOpacity={0.8}
                >
                  {quickLoading === 'resolved'
                    ? <ActivityIndicator size="small" color={colors.textSecondary} />
                    : <Text style={s.quickBtnText}>Mark Resolved</Text>}
                </TouchableOpacity>
              )}

              {(isUnassigned || ticket.assigned_to !== authUser?.id) && (
                <TouchableOpacity
                  style={[s.quickBtn, s.quickBtnTeal, quickLoading === 'assign-self' && { opacity: 0.6 }]}
                  onPress={handleAssignSelf}
                  disabled={!!quickLoading}
                  activeOpacity={0.8}
                >
                  {quickLoading === 'assign-self'
                    ? <ActivityIndicator size="small" color={colors.primary} />
                    : <Text style={[s.quickBtnText, { color: colors.primary }]}>Assign to Me</Text>}
                </TouchableOpacity>
              )}
            </View>
          </SectionCard>

          {/* ── Problem description preview ── */}
          <SectionCard title="Problem Description">
            <Text style={s.descPreview} numberOfLines={4}>
              {ticket.description}
            </Text>
            <TouchableOpacity
              style={s.viewFullBtn}
              onPress={() => router.push(`/(it)/queue/${ticket.id}` as any)}
              activeOpacity={0.75}
            >
              <Text style={s.viewFullBtnText}>View Full Ticket Details →</Text>
            </TouchableOpacity>
          </SectionCard>

          {/* ── Equipment status ── */}
          <SectionCard title="Equipment Status">
            <InfoRow label="Equipment"
              value={ticket.equipment?.equipment_code} />
            <InfoRow label="Type"
              value={ticket.equipment?.type
                ? ticket.equipment.type.charAt(0).toUpperCase() + ticket.equipment.type.slice(1)
                : undefined} />
            <InfoRow label="Lab"
              value={ticket.equipment?.lab?.name} />
            <InfoRow label="Current Status">
              {/* inline to render coloured pill */}
            </InfoRow>
          </SectionCard>

          {/* ── Current status info bar ── */}
          <View style={s.currentStatusBar}>
            <View style={s.currentStatusItem}>
              <Text style={s.currentStatusLabel}>Current Status</Text>
              <View style={[s.currentStatusPill, {
                backgroundColor: statusStyle(ticket.status).bg,
                borderColor: statusStyle(ticket.status).border,
              }]}>
                <Text style={[s.currentStatusPillText, { color: statusStyle(ticket.status).text }]}>
                  {statusLabel(ticket.status)}
                </Text>
              </View>
            </View>
            <View style={s.currentStatusItem}>
              <Text style={s.currentStatusLabel}>Assigned To</Text>
              <Text style={s.currentStatusValue}>
                {ticket.assigned_to
                  ? isMyTicket
                    ? 'You'
                    : ticket.assigned_to_user
                      ? `${ticket.assigned_to_user.first_name} ${ticket.assigned_to_user.last_name}`
                      : 'Someone'
                  : 'Unassigned'}
              </Text>
            </View>
            <View style={s.currentStatusItem}>
              <Text style={s.currentStatusLabel}>Created</Text>
              <Text style={s.currentStatusValue}>{diffForHumans(ticket.created_at)}</Text>
            </View>
          </View>

          <View style={{ height: spacing.xxl }} />
        </ScrollView>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
}

// ─── Styles ───────────────────────────────────────────────────────────────────

const s = StyleSheet.create({
  root:    { flex: 1, backgroundColor: colors.bgPrimary },
  centered:{ flex: 1, alignItems: 'center', justifyContent: 'center', gap: spacing.md, padding: spacing.lg },
  scroll:  { paddingHorizontal: spacing.lg, paddingBottom: spacing.lg },

  // Nav
  nav: {
    flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center',
    paddingHorizontal: spacing.lg, paddingVertical: spacing.sm + 4,
    borderBottomWidth: 1, borderBottomColor: colors.border,
  },
  navBack:  { fontSize: font.sm, fontWeight: font.semibold, color: colors.primary, width: 60 },
  navTitle: { fontSize: font.base, fontWeight: font.bold, color: colors.textPrimary },

  // Page header
  pageHeader: { paddingTop: spacing.lg, marginBottom: spacing.md },
  pageTitle:  { fontSize: font.xl, fontWeight: font.bold, color: colors.textPrimary },
  pageSub:    { fontSize: font.sm, color: colors.textMuted, marginTop: 2 },

  // Error banner
  errorBanner: {
    backgroundColor: colors.dangerBg, borderWidth: 1, borderColor: colors.dangerBorder,
    borderRadius: radius.md, padding: spacing.sm + 2, marginBottom: spacing.md,
  },
  errorBannerText: { color: '#fca5a5', fontSize: font.sm, lineHeight: 19 },

  // Cards
  card: {
    backgroundColor: colors.bgCard, borderWidth: 1, borderColor: colors.border,
    borderRadius: radius.xl, padding: spacing.lg, marginBottom: spacing.md,
  },
  cardTitle: {
    fontSize: font.base, fontWeight: font.bold, color: colors.primary, marginBottom: spacing.md,
  },

  // Info rows
  infoRow: {
    flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-start',
    paddingVertical: spacing.xs + 2,
    borderBottomWidth: 1, borderBottomColor: 'rgba(45,212,191,0.06)', gap: spacing.sm,
  },
  infoLabel: { fontSize: font.sm, color: colors.textMuted, flex: 1 },
  infoValue: { fontSize: font.sm, fontWeight: font.medium, color: colors.textPrimary, flex: 2, textAlign: 'right' },

  // Form fields
  fieldGroup:  { marginBottom: spacing.lg },
  fieldLabel:  { fontSize: font.sm, fontWeight: font.semibold, color: colors.textSecondary, marginBottom: spacing.sm },
  fieldError:  { color: '#f87171', fontSize: font.xs, marginTop: spacing.xs - 2 },
  helpText:    { fontSize: font.xs, color: colors.textMuted, marginTop: spacing.xs },

  // Status option grid (2 per row)
  optionGrid: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.xs + 2 },
  statusOption: {
    flexDirection: 'row', alignItems: 'center', gap: spacing.xs,
    backgroundColor: 'rgba(255,255,255,0.05)', borderWidth: 1.5,
    borderColor: colors.border, borderRadius: radius.lg,
    paddingHorizontal: spacing.md, paddingVertical: spacing.xs + 4,
  },
  statusOptionText: { fontSize: font.sm, color: colors.textMuted, fontWeight: font.medium },
  selectedDot: { width: 6, height: 6, borderRadius: 3 },

  // Priority row (3 equal columns)
  priorityRow: { flexDirection: 'row', gap: spacing.sm },
  priorityOption: {
    flex: 1, flexDirection: 'row', alignItems: 'center', justifyContent: 'center',
    gap: spacing.xs, backgroundColor: 'rgba(255,255,255,0.05)',
    borderWidth: 1.5, borderColor: colors.border, borderRadius: radius.lg,
    paddingVertical: spacing.sm + 2,
  },
  priorityOptionText: { fontSize: font.sm, color: colors.textMuted, fontWeight: font.medium },

  // Assign options
  assignRow: { gap: spacing.sm },
  assignOption: {
    flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between',
    backgroundColor: 'rgba(255,255,255,0.05)', borderWidth: 1.5,
    borderColor: colors.border, borderRadius: radius.lg,
    paddingHorizontal: spacing.md, paddingVertical: spacing.sm + 4,
  },
  assignOptionSelected: { borderColor: colors.primary, backgroundColor: colors.primaryLight },
  assignOptionText: { fontSize: font.sm, color: colors.textSecondary, fontWeight: font.medium },

  // Submit / cancel
  submitBtn: {
    backgroundColor: colors.primary, borderRadius: radius.lg,
    paddingVertical: spacing.md, alignItems: 'center', marginTop: spacing.sm,
    shadowColor: colors.primary, shadowOffset: { width: 0, height: 3 },
    shadowOpacity: 0.3, shadowRadius: 6, elevation: 4,
  },
  submitBtnText: { fontSize: font.base, fontWeight: font.bold, color: '#fff' },
  cancelBtn: {
    backgroundColor: 'rgba(255,255,255,0.04)', borderWidth: 1, borderColor: colors.border,
    borderRadius: radius.lg, paddingVertical: spacing.md, alignItems: 'center',
    marginTop: spacing.sm,
  },
  cancelBtnText: { fontSize: font.base, fontWeight: font.semibold, color: colors.textMuted },

  // Quick actions
  quickSub:  { fontSize: font.xs, color: colors.textMuted, marginBottom: spacing.md },
  quickGrid: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm },
  quickBtn: {
    backgroundColor: 'rgba(255,255,255,0.06)', borderWidth: 1, borderColor: colors.border,
    borderRadius: radius.lg, paddingHorizontal: spacing.md, paddingVertical: spacing.sm + 4,
    alignItems: 'center', minWidth: '47%', flex: 1,
  },
  quickBtnTeal: { backgroundColor: colors.primaryLight, borderColor: colors.border },
  quickBtnText: { fontSize: font.sm, fontWeight: font.semibold, color: colors.textSecondary },

  // Description preview
  descPreview: {
    fontSize: font.base, color: colors.textSecondary, lineHeight: 23, marginBottom: spacing.md,
  },
  viewFullBtn: {
    backgroundColor: 'rgba(255,255,255,0.05)', borderWidth: 1, borderColor: colors.border,
    borderRadius: radius.lg, paddingVertical: spacing.sm + 2, alignItems: 'center',
  },
  viewFullBtnText: { fontSize: font.sm, fontWeight: font.semibold, color: colors.primary },

  // Current status bar (bottom summary)
  currentStatusBar: {
    flexDirection: 'row', backgroundColor: colors.bgCard,
    borderWidth: 1, borderColor: colors.border, borderRadius: radius.xl,
    padding: spacing.md, marginBottom: spacing.md, gap: spacing.sm,
  },
  currentStatusItem: { flex: 1, alignItems: 'center', gap: spacing.xs },
  currentStatusLabel:{ fontSize: font.xs - 1, color: colors.textMuted, textAlign: 'center' },
  currentStatusValue:{ fontSize: font.sm, fontWeight: font.semibold, color: colors.textPrimary, textAlign: 'center' },
  currentStatusPill: { borderWidth: 1, borderRadius: radius.full, paddingHorizontal: 8, paddingVertical: 3 },
  currentStatusPillText: { fontSize: font.xs - 2, fontWeight: font.bold },

  // Error / retry
  errorText: { fontSize: font.base, color: colors.textSecondary, textAlign: 'center' },
  retryBtn:  { backgroundColor: colors.primary, paddingHorizontal: spacing.lg, paddingVertical: spacing.sm + 2, borderRadius: radius.full },
  retryText: { fontSize: font.base, fontWeight: font.bold, color: '#fff' },
});