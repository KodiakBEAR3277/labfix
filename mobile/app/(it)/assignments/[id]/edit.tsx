/**
 * app/(it)/assignments/[id]/edit.tsx  —  Update Assignment
 *
 * Mirrors: resources/js/Pages/IT/Assignments/Edit.vue
 *
 * Fetches GET /api/it/assignments/{id} on mount to pre-fill.
 * Submits  PUT /api/it/assignments/{id} on save.
 *
 * Key difference from queue/[id]/edit.tsx:
 *   - NO reassignment field — IT staff update only their own ticket's
 *     status and priority here. Reassignment is admin-only.
 *   - Status options are limited: assigned · in-progress · resolved
 *     (cannot set to new/closed from this screen — mirrors web)
 *   - Quick actions: 🚀 Start Working · ✅ Mark Resolved · 🔴 Escalate Priority
 *   - Status guidelines info block at the bottom
 *
 * On success → navigate back to assignments/[id] show screen.
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
import { loadAuth } from '@/utils/auth';
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
  assigned_at:   string | null;
  reporter?: { first_name: string; last_name: string };
  equipment?: {
    equipment_code: string; type: string; status: string;
    lab?: { name: string };
  };
  transactions?: { action: string; created_at: string }[];
};

type FormState = {
  status:   string;
  priority: string;
};

type FieldErrors = {
  status?:   string;
  priority?: string;
  general?:  string;
};

// ─── Constants ────────────────────────────────────────────────────────────────

// Assignment update: only these 3 statuses allowed (mirrors web validator)
const STATUS_OPTIONS = [
  { value: 'assigned',    label: 'Assigned',    desc: "Work hasn't started yet"           },
  { value: 'in-progress', label: 'In Progress', desc: "Actively working on this issue"    },
  { value: 'resolved',    label: 'Resolved',    desc: "Issue is fixed — reporter notified" },
];

const PRIORITY_OPTIONS = [
  { value: 'low',    label: 'Low',    emoji: '🟢', color: colors.success },
  { value: 'medium', label: 'Medium', emoji: '🟡', color: colors.warning },
  { value: 'high',   label: 'High',   emoji: '🔴', color: colors.danger  },
];

// ─── Helpers ─────────────────────────────────────────────────────────────────

function statusStyle(s: string): { bg: string; text: string; border: string } {
  switch (s) {
    case 'assigned':    return { bg: 'rgba(168,85,247,0.14)', text: '#c084fc',           border: 'rgba(168,85,247,0.3)' };
    case 'in-progress': return { bg: colors.warningBg,        text: colors.warningLight, border: colors.warningBorder };
    case 'resolved':    return { bg: colors.successBg,        text: colors.successLight, border: colors.successBorder };
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

export default function ITAssignmentEditScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();

  const [token,        setToken]        = useState<string | null>(null);
  const [ticket,       setTicket]       = useState<Ticket | null>(null);
  const [loading,      setLoading]      = useState(true);
  const [submitting,   setSubmitting]   = useState(false);
  const [quickLoading, setQuickLoading] = useState<string | null>(null);
  const [errors,       setErrors]       = useState<FieldErrors>({});

  const [form, setForm] = useState<FormState>({
    status:   '',
    priority: '',
  });

  // ── Auth ──────────────────────────────────────────────────────────────────
  useEffect(() => {
    loadAuth().then((auth) => {
      if (!auth) { router.replace('/login'); return; }
      setToken(auth.token);
    });
  }, []);

  // ── Fetch ticket to pre-fill ──────────────────────────────────────────────
  const fetchTicket = useCallback(async (tok: string) => {
    try {
      const res = await fetch(apiUrl(`it/assignments/${id}`), {
        headers: { 'Accept': 'application/json', 'Authorization': `Bearer ${tok}` },
      });
      if (res.status === 401) { router.replace('/login'); return; }
      if (res.status === 404) {
        setErrors({ general: 'This ticket is not assigned to you.' });
        return;
      }
      if (!res.ok) throw new Error(`Server error ${res.status}`);
      const data: Ticket = await res.json();
      setTicket(data);
      setForm({ status: data.status, priority: data.priority });
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
      const res = await fetch(apiUrl(`it/assignments/${ticket.id}`), {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'Accept':        'application/json',
          'Authorization': `Bearer ${token}`,
        },
        body: JSON.stringify({
          status:   form.status,
          priority: form.priority,
        }),
      });

      const data = await res.json();

      if (res.ok) {
        router.replace(`/(it)/assignments/${ticket.id}` as any);
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

  // ── Quick action ──────────────────────────────────────────────────────────
  async function quickUpdate(
    overrides: Partial<FormState>,
    key: string
  ) {
    if (!token || !ticket) return;
    setQuickLoading(key);

    try {
      const payload = {
        status:   overrides.status   ?? form.status,
        priority: overrides.priority ?? form.priority,
      };

      const res = await fetch(apiUrl(`it/assignments/${ticket.id}`), {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'Accept':        'application/json',
          'Authorization': `Bearer ${token}`,
        },
        body: JSON.stringify(payload),
      });

      if (!res.ok) throw new Error('Failed to update.');
      // Navigate back to show after quick action
      router.replace(`/(it)/assignments/${ticket.id}` as any);
    } catch {
      Alert.alert('Error', 'Could not update ticket. Please try again.');
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

  // Derived values
  const assignedTransaction = ticket.transactions?.find(tx => tx.action === 'assigned') ?? null;
  const pc = priorityColor(ticket.priority);

  // ─── Main render ──────────────────────────────────────────────────────────
  return (
    <SafeAreaView style={s.root} edges={['top']}>
      <StatusBar barStyle="light-content" backgroundColor={colors.bgPrimary} />

      {/* ── Top Nav ── */}
      <View style={s.nav}>
        <TouchableOpacity onPress={() => router.back()} activeOpacity={0.7}>
          <Text style={s.navBack}>← Assignment</Text>
        </TouchableOpacity>
        <Text style={s.navTitle}>Update Assignment</Text>
        <View style={{ width: 80 }} />
      </View>

      <KeyboardAvoidingView
        style={{ flex: 1 }}
        behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
      >
        <ScrollView
          showsVerticalScrollIndicator={false}
          contentContainerStyle={s.scroll}
          keyboardShouldPersistTaps="handled"
        >
          {/* ── Page header ── */}
          <View style={s.pageHeader}>
            <Text style={s.pageTitle}>Update Assignment</Text>
            <Text style={s.pageSub}>Update your progress on this ticket</Text>
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
            <InfoRow
              label="Time on Task"
              value={assignedTransaction
                ? diffForHumans(assignedTransaction.created_at)
                : 'Just assigned'}
            />
          </SectionCard>

          {/* ── Update form ── */}
          <SectionCard title="Update Progress">

            {/* Status selector */}
            <View style={s.fieldGroup}>
              <Text style={s.fieldLabel}>Status *</Text>
              <View style={s.statusList}>
                {STATUS_OPTIONS.map((opt) => {
                  const selected = form.status === opt.value;
                  const sc       = statusStyle(opt.value);
                  return (
                    <TouchableOpacity
                      key={opt.value}
                      style={[
                        s.statusOption,
                        selected && {
                          backgroundColor: sc.bg,
                          borderColor: sc.border,
                        },
                      ]}
                      onPress={() => {
                        setForm(f => ({ ...f, status: opt.value }));
                        if (errors.status) setErrors(e => ({ ...e, status: undefined }));
                      }}
                      activeOpacity={0.75}
                    >
                      {/* Selection indicator */}
                      <View style={[
                        s.radioOuter,
                        selected && { borderColor: sc.text },
                      ]}>
                        {selected && (
                          <View style={[s.radioInner, { backgroundColor: sc.text }]} />
                        )}
                      </View>

                      <View style={{ flex: 1 }}>
                        <Text style={[s.statusOptLabel, selected && { color: sc.text }]}>
                          {opt.label}
                        </Text>
                        <Text style={s.statusOptDesc}>{opt.desc}</Text>
                      </View>

                      {selected && (
                        <View style={[s.statusCheckBadge, { backgroundColor: sc.bg, borderColor: sc.border }]}>
                          <Text style={[s.statusCheckText, { color: sc.text }]}>✓</Text>
                        </View>
                      )}
                    </TouchableOpacity>
                  );
                })}
              </View>
              <Text style={s.helpText}>
                Update the current status of your work on this ticket
              </Text>
              <FieldError message={errors.status} />
            </View>

            {/* Priority selector */}
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
                          borderColor:     opt.color + '66',
                        },
                      ]}
                      onPress={() => {
                        setForm(f => ({ ...f, priority: opt.value }));
                        if (errors.priority) setErrors(e => ({ ...e, priority: undefined }));
                      }}
                      activeOpacity={0.75}
                    >
                      <Text style={{ fontSize: 16 }}>{opt.emoji}</Text>
                      <Text style={[
                        s.priorityOptText,
                        selected && { color: opt.color, fontWeight: font.bold },
                      ]}>
                        {opt.label}
                      </Text>
                    </TouchableOpacity>
                  );
                })}
              </View>
              <Text style={s.helpText}>
                Adjust priority if needed based on your assessment
              </Text>
              <FieldError message={errors.priority} />
            </View>

            {/* Notification hint */}
            <View style={s.notifHint}>
              <Text style={s.notifHintText}>
                💡 The reporter will be automatically notified when you update this ticket.
              </Text>
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
                <Text style={s.submitBtnText}>Save Changes &amp; Notify User</Text>
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
            <Text style={s.quickSub}>
              Apply common status changes with one tap
            </Text>
            <View style={s.quickGrid}>

              {ticket.status !== 'in-progress' && (
                <TouchableOpacity
                  style={[s.quickBtn, quickLoading === 'start' && { opacity: 0.6 }]}
                  onPress={() => quickUpdate({ status: 'in-progress' }, 'start')}
                  disabled={!!quickLoading}
                  activeOpacity={0.8}
                >
                  {quickLoading === 'start'
                    ? <ActivityIndicator size="small" color={colors.textSecondary} />
                    : <Text style={s.quickBtnText}>🚀 Start Working</Text>}
                </TouchableOpacity>
              )}

              {ticket.status !== 'resolved' && (
                <TouchableOpacity
                  style={[s.quickBtn, quickLoading === 'resolve' && { opacity: 0.6 }]}
                  onPress={() => quickUpdate({ status: 'resolved' }, 'resolve')}
                  disabled={!!quickLoading}
                  activeOpacity={0.8}
                >
                  {quickLoading === 'resolve'
                    ? <ActivityIndicator size="small" color={colors.textSecondary} />
                    : <Text style={s.quickBtnText}>✅ Mark Resolved</Text>}
                </TouchableOpacity>
              )}

              {ticket.priority !== 'high' && ticket.status !== 'resolved' && (
                <TouchableOpacity
                  style={[s.quickBtn, s.quickBtnDanger, quickLoading === 'escalate' && { opacity: 0.6 }]}
                  onPress={() => quickUpdate({ priority: 'high' }, 'escalate')}
                  disabled={!!quickLoading}
                  activeOpacity={0.8}
                >
                  {quickLoading === 'escalate'
                    ? <ActivityIndicator size="small" color="#f87171" />
                    : <Text style={[s.quickBtnText, { color: '#f87171' }]}>
                        🔴 Escalate Priority
                      </Text>}
                </TouchableOpacity>
              )}

            </View>
          </SectionCard>

          {/* ── Status guidelines ── */}
          <View style={s.guidelinesCard}>
            <Text style={s.guidelinesTitle}>📋 Status Guidelines</Text>
            {STATUS_OPTIONS.map((opt) => {
              const sc = statusStyle(opt.value);
              return (
                <View key={opt.value} style={s.guidelineRow}>
                  <View style={[s.guidelineDot, { backgroundColor: sc.text }]} />
                  <View style={{ flex: 1 }}>
                    <Text style={[s.guidelineLabel, { color: sc.text }]}>
                      {opt.label}
                    </Text>
                    <Text style={s.guidelineDesc}>{opt.desc}</Text>
                  </View>
                </View>
              );
            })}
          </View>

          {/* ── Equipment info (sidebar equivalent) ── */}
          <SectionCard title="Equipment Status">
            <InfoRow label="Equipment" value={ticket.equipment?.equipment_code} />
            <InfoRow
              label="Type"
              value={ticket.equipment?.type
                ? ticket.equipment.type.charAt(0).toUpperCase() + ticket.equipment.type.slice(1)
                : undefined}
            />
            <InfoRow label="Lab" value={ticket.equipment?.lab?.name} />
          </SectionCard>

          {/* ── Description preview ── */}
          <SectionCard title="Problem Description">
            <Text style={s.descPreview} numberOfLines={4}>
              {ticket.description}
            </Text>
            <TouchableOpacity
              style={s.viewFullBtn}
              onPress={() => router.push(`/(it)/assignments/${ticket.id}` as any)}
              activeOpacity={0.75}
            >
              <Text style={s.viewFullBtnText}>View Full Ticket Details →</Text>
            </TouchableOpacity>
          </SectionCard>

          {/* ── Current status summary bar ── */}
          <View style={s.currentBar}>
            <View style={s.currentBarItem}>
              <Text style={s.currentBarLabel}>Current Status</Text>
              <View style={[s.currentBarPill, {
                backgroundColor: statusStyle(ticket.status).bg,
                borderColor:     statusStyle(ticket.status).border,
              }]}>
                <Text style={[s.currentBarPillText, { color: statusStyle(ticket.status).text }]}>
                  {statusLabel(ticket.status)}
                </Text>
              </View>
            </View>
            <View style={s.currentBarItem}>
              <Text style={s.currentBarLabel}>Priority</Text>
              <Text style={[s.currentBarValue, { color: pc }]}>
                {ticket.priority.charAt(0).toUpperCase() + ticket.priority.slice(1)}
              </Text>
            </View>
            <View style={s.currentBarItem}>
              <Text style={s.currentBarLabel}>Assigned</Text>
              <Text style={s.currentBarValue}>
                {assignedTransaction
                  ? diffForHumans(assignedTransaction.created_at)
                  : 'Just now'}
              </Text>
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
  navBack:  { fontSize: font.sm, fontWeight: font.semibold, color: colors.primary, width: 80 },
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
  cardTitle: { fontSize: font.base, fontWeight: font.bold, color: colors.primary, marginBottom: spacing.md },

  // Info rows
  infoRow: {
    flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-start',
    paddingVertical: spacing.xs + 2, borderBottomWidth: 1,
    borderBottomColor: 'rgba(45,212,191,0.06)', gap: spacing.sm,
  },
  infoLabel: { fontSize: font.sm, color: colors.textMuted, flex: 1 },
  infoValue: { fontSize: font.sm, fontWeight: font.medium, color: colors.textPrimary, flex: 2, textAlign: 'right' },

  // Form
  fieldGroup:  { marginBottom: spacing.lg },
  fieldLabel:  { fontSize: font.sm, fontWeight: font.semibold, color: colors.textSecondary, marginBottom: spacing.sm },
  fieldError:  { color: '#f87171', fontSize: font.xs, marginTop: spacing.xs - 2 },
  helpText:    { fontSize: font.xs, color: colors.textMuted, marginTop: spacing.xs },

  // Status option list
  statusList: { gap: spacing.sm },
  statusOption: {
    flexDirection: 'row', alignItems: 'center', gap: spacing.md,
    backgroundColor: 'rgba(255,255,255,0.04)', borderWidth: 1.5,
    borderColor: colors.border, borderRadius: radius.lg, padding: spacing.md,
  },
  radioOuter: {
    width: 18, height: 18, borderRadius: 9, borderWidth: 2,
    borderColor: colors.border, alignItems: 'center', justifyContent: 'center', flexShrink: 0,
  },
  radioInner: { width: 8, height: 8, borderRadius: 4 },
  statusOptLabel: { fontSize: font.sm, fontWeight: font.semibold, color: colors.textSecondary, marginBottom: 2 },
  statusOptDesc:  { fontSize: font.xs, color: colors.textMuted },
  statusCheckBadge: {
    borderWidth: 1, borderRadius: radius.full,
    paddingHorizontal: 8, paddingVertical: 2,
  },
  statusCheckText: { fontSize: font.xs - 1, fontWeight: font.bold },

  // Priority
  priorityRow: { flexDirection: 'row', gap: spacing.sm },
  priorityOption: {
    flex: 1, flexDirection: 'row', alignItems: 'center', justifyContent: 'center',
    gap: spacing.xs, backgroundColor: 'rgba(255,255,255,0.04)',
    borderWidth: 1.5, borderColor: colors.border, borderRadius: radius.lg,
    paddingVertical: spacing.sm + 4,
  },
  priorityOptText: { fontSize: font.sm, color: colors.textMuted, fontWeight: font.medium },

  // Notification hint
  notifHint: {
    backgroundColor: 'rgba(45,212,191,0.08)', borderWidth: 1,
    borderColor: 'rgba(45,212,191,0.2)', borderRadius: radius.lg,
    padding: spacing.md, marginBottom: spacing.md,
  },
  notifHintText: { fontSize: font.sm, color: '#a7f3d0', lineHeight: 19 },

  // Submit / cancel
  submitBtn: {
    backgroundColor: colors.primary, borderRadius: radius.lg,
    paddingVertical: spacing.md, alignItems: 'center',
    shadowColor: colors.primary, shadowOffset: { width: 0, height: 3 },
    shadowOpacity: 0.3, shadowRadius: 6, elevation: 4,
  },
  submitBtnText: { fontSize: font.base, fontWeight: font.bold, color: '#fff' },
  cancelBtn: {
    backgroundColor: 'rgba(255,255,255,0.04)', borderWidth: 1, borderColor: colors.border,
    borderRadius: radius.lg, paddingVertical: spacing.md, alignItems: 'center', marginTop: spacing.sm,
  },
  cancelBtnText: { fontSize: font.base, fontWeight: font.semibold, color: colors.textMuted },

  // Quick actions
  quickSub:  { fontSize: font.xs, color: colors.textMuted, marginBottom: spacing.md },
  quickGrid: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm },
  quickBtn: {
    flex: 1, minWidth: '47%',
    backgroundColor: 'rgba(255,255,255,0.05)', borderWidth: 1, borderColor: colors.border,
    borderRadius: radius.lg, paddingVertical: spacing.sm + 4, alignItems: 'center',
  },
  quickBtnDanger: { backgroundColor: 'rgba(239,68,68,0.07)', borderColor: 'rgba(239,68,68,0.25)' },
  quickBtnText: { fontSize: font.sm, fontWeight: font.semibold, color: colors.textSecondary },

  // Status guidelines
  guidelinesCard: {
    backgroundColor: 'rgba(59,130,246,0.07)', borderWidth: 1, borderColor: 'rgba(59,130,246,0.2)',
    borderRadius: radius.xl, padding: spacing.lg, marginBottom: spacing.md, gap: spacing.md,
  },
  guidelinesTitle:{ fontSize: font.sm, fontWeight: font.bold, color: '#60a5fa', marginBottom: spacing.xs },
  guidelineRow:   { flexDirection: 'row', alignItems: 'flex-start', gap: spacing.sm },
  guidelineDot:   { width: 8, height: 8, borderRadius: 4, marginTop: 5, flexShrink: 0 },
  guidelineLabel: { fontSize: font.sm, fontWeight: font.semibold, marginBottom: 2 },
  guidelineDesc:  { fontSize: font.xs, color: '#93c5fd', lineHeight: 17 },

  // Description preview
  descPreview: {
    fontSize: font.base, color: colors.textSecondary, lineHeight: 23, marginBottom: spacing.md,
  },
  viewFullBtn: {
    backgroundColor: 'rgba(255,255,255,0.04)', borderWidth: 1, borderColor: colors.border,
    borderRadius: radius.lg, paddingVertical: spacing.sm + 2, alignItems: 'center',
  },
  viewFullBtnText: { fontSize: font.sm, fontWeight: font.semibold, color: colors.primary },

  // Current status bar
  currentBar: {
    flexDirection: 'row', backgroundColor: colors.bgCard, borderWidth: 1,
    borderColor: colors.border, borderRadius: radius.xl, padding: spacing.md,
    marginBottom: spacing.md,
  },
  currentBarItem:      { flex: 1, alignItems: 'center', gap: spacing.xs },
  currentBarLabel:     { fontSize: font.xs - 1, color: colors.textMuted, textAlign: 'center' },
  currentBarValue:     { fontSize: font.sm, fontWeight: font.semibold, color: colors.textPrimary, textAlign: 'center' },
  currentBarPill:      { borderWidth: 1, borderRadius: radius.full, paddingHorizontal: 8, paddingVertical: 3 },
  currentBarPillText:  { fontSize: font.xs - 2, fontWeight: font.bold },

  // Error / states
  errorText: { fontSize: font.base, color: colors.textSecondary, textAlign: 'center' },
  retryBtn:  { backgroundColor: colors.primary, paddingHorizontal: spacing.lg, paddingVertical: spacing.sm + 2, borderRadius: radius.full },
  retryText: { fontSize: font.base, fontWeight: font.bold, color: '#fff' },
});