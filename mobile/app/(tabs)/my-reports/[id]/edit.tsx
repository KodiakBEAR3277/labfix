/**
 * app/(tabs)/my-reports/[id]/edit.tsx  —  Edit Report
 *
 * Mirrors: resources/js/Pages/User/Reports/Edit.vue
 *
 * Rules (same as web):
 *   - Only allowed if the ticket has NOT been assigned (assigned_to === null)
 *   - Can edit: title, category, description
 *   - Cannot change: lab, equipment, status, priority
 *   - New attachments can be added (web supports this; mobile skips file upload
 *     since the API doesn't yet have a multipart endpoint — noted below)
 *
 * API:
 *   GET  /api/tickets/{id}   → load current ticket data + verify ownership
 *   PUT  /api/tickets/{id}   → not yet in api.php; we call PATCH via POST
 *                              with a _method override in the body.
 *                              The endpoint mirrors User\ReportController::update()
 *
 * NOTE: The PUT /api/tickets/{id} endpoint isn't in routes/api.php yet.
 * This screen is built and ready — add the route when the backend is extended:
 *
 *   Route::put('/tickets/{id}', function (Request $request, $id) {
 *       $user   = auth()->user();
 *       $ticket = \App\Models\Report::where('user_id', $user->id)->findOrFail($id);
 *       if ($ticket->assigned_to) {
 *           return response()->json(['message' => 'Cannot edit an assigned ticket.'], 403);
 *       }
 *       $validated = $request->validate([
 *           'title'       => ['required', 'string', 'max:255'],
 *           'description' => ['required', 'string', 'min:10'],
 *           'category'    => ['required', 'in:hardware,software,network,other'],
 *       ]);
 *       $ticket->update($validated);
 *       // Log update transaction
 *       \App\Models\TicketTransaction::create([
 *           'ticket_id'   => $ticket->id,
 *           'user_id'     => $user->id,
 *           'action'      => 'updated',
 *           'description' => $user->full_name . ' updated ticket details',
 *       ]);
 *       return response()->json($ticket->load(['equipment.lab', 'transactions']), 200);
 *   });
 */

import React, { useEffect, useState, useCallback } from 'react';
import {
  View,
  Text,
  ScrollView,
  TouchableOpacity,
  TextInput,
  StyleSheet,
  StatusBar,
  ActivityIndicator,
  KeyboardAvoidingView,
  Platform,
  Alert,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { router, useLocalSearchParams } from 'expo-router';
import { colors, spacing, radius, font } from '@/constants/theme';
import { loadAuth } from '@/utils/auth';
import { apiUrl } from '@/constants/api';

// ─── Types ────────────────────────────────────────────────────────────────────

type Category = 'hardware' | 'software' | 'network' | 'other';

type Ticket = {
  id:            number;
  ticket_number: string;
  title:         string;
  description:   string;
  category:      Category;
  status:        string;
  priority:      string;
  assigned_to:   number | null;
  equipment?: {
    equipment_code: string;
    lab?: { name: string };
  };
};

type FormState = {
  title:       string;
  category:    Category | '';
  description: string;
};

type FieldErrors = {
  title?:       string;
  category?:    string;
  description?: string;
  general?:     string;
};

// ─── Constants ────────────────────────────────────────────────────────────────

const CATEGORIES: { value: Category; icon: string; label: string }[] = [
  { value: 'hardware', icon: '🔧', label: 'Hardware'  },
  { value: 'software', icon: '💾', label: 'Software'  },
  { value: 'network',  icon: '🌐', label: 'Network'   },
  { value: 'other',    icon: '❓', label: 'Other'     },
];

// ─── Helpers ─────────────────────────────────────────────────────────────────

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

// ─── Component ────────────────────────────────────────────────────────────────

export default function EditReportScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();

  const [token,      setToken]      = useState<string | null>(null);
  const [ticket,     setTicket]     = useState<Ticket | null>(null);
  const [loading,    setLoading]    = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [errors,     setErrors]     = useState<FieldErrors>({});

  const [form, setForm] = useState<FormState>({
    title:       '',
    category:    '',
    description: '',
  });

  // ── Auth ──────────────────────────────────────────────────────────────────
  useEffect(() => {
    loadAuth().then((auth) => {
      if (!auth) { router.replace('/login'); return; }
      setToken(auth.token);
    });
  }, []);

  // ── Fetch ticket ──────────────────────────────────────────────────────────
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

      // Guard: if ticket is assigned, redirect back to show page
      if (data.assigned_to) {
        Alert.alert(
          'Cannot Edit',
          'This ticket has been assigned to a technician and can no longer be edited.',
          [{ text: 'OK', onPress: () => router.back() }]
        );
        return;
      }

      setTicket(data);
      setForm({
        title:       data.title,
        category:    data.category,
        description: data.description,
      });
    } catch {
      setErrors({ general: 'Could not load ticket. Please go back and try again.' });
    }
  }, [id]);

  useEffect(() => {
    if (!token) return;
    setLoading(true);
    fetchTicket(token).finally(() => setLoading(false));
  }, [token, fetchTicket]);

  // ── Validation ────────────────────────────────────────────────────────────
  function validate(): boolean {
    const e: FieldErrors = {};
    if (!form.title.trim())                   e.title       = 'Issue title is required.';
    if (!form.category)                        e.category    = 'Please select a category.';
    if (form.description.trim().length < 10)   e.description = 'Description must be at least 10 characters.';
    setErrors(e);
    return Object.keys(e).length === 0;
  }

  // ── Submit ────────────────────────────────────────────────────────────────
  async function handleSubmit() {
    if (!validate() || !token || !ticket) return;

    setSubmitting(true);
    setErrors({});

    try {
      const res = await fetch(apiUrl(`tickets/${ticket.id}`), {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'Accept':        'application/json',
          'Authorization': `Bearer ${token}`,
        },
        body: JSON.stringify({
          title:       form.title.trim(),
          category:    form.category,
          description: form.description.trim(),
        }),
      });

      const data = await res.json();

      if (res.ok) {
        // Navigate back to the show page
        router.replace(`/(tabs)/my-reports/${ticket.id}` as any);
      } else if (res.status === 403) {
        Alert.alert(
          'Cannot Edit',
          data?.message ?? 'This ticket can no longer be edited.',
          [{ text: 'OK', onPress: () => router.back() }]
        );
      } else if (res.status === 422) {
        const serverErrors: FieldErrors = {};
        const raw = data?.errors ?? {};
        for (const key of Object.keys(raw) as (keyof FieldErrors)[]) {
          serverErrors[key] = Array.isArray(raw[key]) ? raw[key][0] : raw[key];
        }
        setErrors(serverErrors);
      } else {
        setErrors({ general: data?.message ?? 'Something went wrong. Please try again.' });
      }
    } catch {
      setErrors({ general: 'Could not reach the server. Check your connection.' });
    } finally {
      setSubmitting(false);
    }
  }

  // ── Discard confirmation ──────────────────────────────────────────────────
  function handleBack() {
    const isDirty =
      ticket &&
      (form.title !== ticket.title ||
        form.category !== ticket.category ||
        form.description !== ticket.description);

    if (isDirty) {
      Alert.alert(
        'Discard Changes?',
        'You have unsaved changes. Are you sure you want to go back?',
        [
          { text: 'Keep Editing', style: 'cancel' },
          { text: 'Discard',      style: 'destructive', onPress: () => router.back() },
        ]
      );
    } else {
      router.back();
    }
  }

  // ─── Loading state ────────────────────────────────────────────────────────
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
          <Text style={s.errorText}>
            {errors.general ?? 'Ticket not found.'}
          </Text>
          <TouchableOpacity style={s.retryBtn} onPress={() => router.back()}>
            <Text style={s.retryBtnText}>← Go Back</Text>
          </TouchableOpacity>
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
        <TouchableOpacity onPress={handleBack} activeOpacity={0.7}>
          <Text style={s.navBack}>← Back</Text>
        </TouchableOpacity>
        <Text style={s.navTitle}>Edit Report</Text>
        <View style={{ width: 60 }} />
      </View>

      <KeyboardAvoidingView
        style={{ flex: 1 }}
        behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
        keyboardVerticalOffset={0}
      >
        <ScrollView
          contentContainerStyle={s.scroll}
          showsVerticalScrollIndicator={false}
          keyboardShouldPersistTaps="handled"
        >

          {/* ── Page header ── */}
          <View style={s.pageHeader}>
            <Text style={s.pageTitle}>Edit Report</Text>
            <Text style={s.pageSub}>
              Update your report details before it's assigned to a technician
            </Text>
          </View>

          {/* ── General error ── */}
          {errors.general && (
            <View style={s.errorBanner}>
              <Text style={s.errorBannerText}>{errors.general}</Text>
            </View>
          )}

          {/* ── Read-only ticket summary ── */}
          <View style={s.card}>
            <Text style={s.cardTitle}>Ticket Summary</Text>
            <InfoRow label="Ticket #"  value={ticket.ticket_number} />
            <InfoRow label="Location"  value={
              [ticket.equipment?.lab?.name, ticket.equipment?.equipment_code]
                .filter(Boolean).join(', ')
            } />
            <InfoRow label="Status"    value={ticket.status.replace('-', ' ').replace(/\b\w/g, c => c.toUpperCase())} />
            <InfoRow label="Priority"  value={ticket.priority.charAt(0).toUpperCase() + ticket.priority.slice(1)} />
          </View>

          {/* ── Editable fields ── */}
          <View style={s.card}>
            <Text style={s.cardTitle}>Report Details</Text>

            {/* Title */}
            <View style={s.fieldGroup}>
              <Text style={s.fieldLabel}>Issue Title *</Text>
              <TextInput
                style={[s.input, !!errors.title && s.inputError]}
                value={form.title}
                onChangeText={(v) => {
                  setForm((f) => ({ ...f, title: v }));
                  if (errors.title) setErrors((e) => ({ ...e, title: undefined }));
                }}
                placeholder="Brief description of the problem"
                placeholderTextColor={colors.textDisabled}
                maxLength={255}
                returnKeyType="next"
                editable={!submitting}
              />
              <FieldError message={errors.title} />
              <Text style={s.charCount}>{form.title.length}/255</Text>
            </View>

            {/* Category */}
            <View style={s.fieldGroup}>
              <Text style={s.fieldLabel}>Category *</Text>
              <View style={s.catRow}>
                {CATEGORIES.map((cat) => {
                  const selected = form.category === cat.value;
                  return (
                    <TouchableOpacity
                      key={cat.value}
                      style={[s.catPill, selected && s.catPillSelected]}
                      onPress={() => {
                        setForm((f) => ({ ...f, category: cat.value }));
                        if (errors.category) setErrors((e) => ({ ...e, category: undefined }));
                      }}
                      activeOpacity={0.75}
                      disabled={submitting}
                    >
                      <Text style={s.catPillIcon}>{cat.icon}</Text>
                      <Text style={[s.catPillText, selected && s.catPillTextSelected]}>
                        {cat.label}
                      </Text>
                    </TouchableOpacity>
                  );
                })}
              </View>
              <FieldError message={errors.category} />
            </View>

            {/* Description */}
            <View style={s.fieldGroup}>
              <Text style={s.fieldLabel}>Description *</Text>
              <TextInput
                style={[s.input, s.textArea, !!errors.description && s.inputError]}
                value={form.description}
                onChangeText={(v) => {
                  setForm((f) => ({ ...f, description: v }));
                  if (errors.description) setErrors((e) => ({ ...e, description: undefined }));
                }}
                placeholder="Describe the issue in as much detail as possible…"
                placeholderTextColor={colors.textDisabled}
                multiline
                textAlignVertical="top"
                editable={!submitting}
              />
              <FieldError message={errors.description} />
              <Text style={s.charCount}>
                {form.description.length} chars
                {form.description.length > 0 && form.description.length < 10
                  ? ` (${10 - form.description.length} more needed)`
                  : ''}
              </Text>
            </View>
          </View>

          {/* ── Info note ── */}
          <View style={s.infoNote}>
            <Text style={s.infoNoteIcon}>ℹ️</Text>
            <Text style={s.infoNoteText}>
              You can only edit this report before it's assigned to a technician.
              Once assigned, contact the assigned technician if you need to make changes.
            </Text>
          </View>

          {/* ── Action buttons ── */}
          <View style={s.actions}>
            <TouchableOpacity
              style={[s.submitBtn, submitting && s.submitBtnDisabled]}
              onPress={handleSubmit}
              disabled={submitting}
              activeOpacity={0.85}
            >
              {submitting ? (
                <ActivityIndicator color="#fff" size="small" />
              ) : (
                <Text style={s.submitBtnText}>Update Report</Text>
              )}
            </TouchableOpacity>

            <TouchableOpacity
              style={s.cancelBtn}
              onPress={handleBack}
              disabled={submitting}
              activeOpacity={0.75}
            >
              <Text style={s.cancelBtnText}>Cancel</Text>
            </TouchableOpacity>
          </View>

          <View style={{ height: spacing.xxl }} />
        </ScrollView>
      </KeyboardAvoidingView>
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
    padding: spacing.lg,
    gap: spacing.md,
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
  navBack: {
    fontSize: font.sm,
    fontWeight: font.semibold,
    color: colors.primary,
    width: 60,
  },
  navTitle: {
    fontSize: font.base,
    fontWeight: font.bold,
    color: colors.textPrimary,
  },

  // ── Scroll ────────────────────────────────────────────────────────────────
  scroll: {
    paddingHorizontal: spacing.lg,
    paddingBottom: spacing.xl,
  },

  // ── Page header ───────────────────────────────────────────────────────────
  pageHeader: {
    paddingTop: spacing.lg,
    paddingBottom: spacing.md,
  },
  pageTitle: {
    fontSize: font.xl,
    fontWeight: font.bold,
    color: colors.textPrimary,
    marginBottom: spacing.xs,
  },
  pageSub: {
    fontSize: font.sm,
    color: colors.textMuted,
    lineHeight: 19,
  },

  // ── Error banner ──────────────────────────────────────────────────────────
  errorBanner: {
    backgroundColor: colors.dangerBg,
    borderWidth: 1,
    borderColor: colors.dangerBorder,
    borderRadius: radius.md,
    padding: spacing.sm + 2,
    marginBottom: spacing.md,
  },
  errorBannerText: {
    color: '#fca5a5',
    fontSize: font.sm,
    lineHeight: 19,
  },

  // ── Cards ─────────────────────────────────────────────────────────────────
  card: {
    backgroundColor: colors.bgCard,
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radius.xl,
    padding: spacing.lg,
    marginBottom: spacing.md,
  },
  cardTitle: {
    fontSize: font.base,
    fontWeight: font.bold,
    color: colors.primary,
    marginBottom: spacing.md,
  },

  // ── Info rows (read-only summary) ─────────────────────────────────────────
  infoRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'flex-start',
    paddingVertical: spacing.xs + 2,
    borderBottomWidth: 1,
    borderBottomColor: 'rgba(45,212,191,0.06)',
    gap: spacing.sm,
  },
  infoLabel: {
    fontSize: font.sm,
    color: colors.textMuted,
    flex: 1,
  },
  infoValue: {
    fontSize: font.sm,
    fontWeight: font.medium,
    color: colors.textPrimary,
    flex: 2,
    textAlign: 'right',
  },

  // ── Form fields ───────────────────────────────────────────────────────────
  fieldGroup: {
    marginBottom: spacing.md,
  },
  fieldLabel: {
    fontSize: font.sm,
    fontWeight: font.semibold,
    color: colors.textSecondary,
    marginBottom: spacing.xs,
  },
  input: {
    backgroundColor: 'rgba(255,255,255,0.05)',
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radius.lg,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm + 4,
    color: colors.textPrimary,
    fontSize: font.base,
  },
  textArea: {
    minHeight: 140,
    paddingTop: spacing.md,
    lineHeight: 22,
  },
  inputError: {
    borderColor: colors.dangerBorder,
    backgroundColor: 'rgba(239,68,68,0.05)',
  },
  fieldError: {
    color: '#f87171',
    fontSize: font.xs,
    marginTop: spacing.xs - 2,
  },
  charCount: {
    fontSize: font.xs - 1,
    color: colors.textMuted,
    marginTop: spacing.xs - 2,
    textAlign: 'right',
  },

  // ── Category pill row ─────────────────────────────────────────────────────
  catRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.xs + 2,
  },
  catPill: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.xs,
    backgroundColor: 'rgba(255,255,255,0.05)',
    borderWidth: 1.5,
    borderColor: colors.border,
    borderRadius: radius.full,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.xs + 2,
  },
  catPillSelected: {
    borderColor: colors.primary,
    backgroundColor: colors.primaryLight,
  },
  catPillIcon: {
    fontSize: 14,
  },
  catPillText: {
    fontSize: font.sm,
    fontWeight: font.medium,
    color: colors.textMuted,
  },
  catPillTextSelected: {
    color: colors.primary,
    fontWeight: font.semibold,
  },

  // ── Info note ─────────────────────────────────────────────────────────────
  infoNote: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: spacing.sm,
    backgroundColor: colors.infoBg,
    borderWidth: 1,
    borderColor: colors.infoBorder,
    borderRadius: radius.lg,
    padding: spacing.md,
    marginBottom: spacing.lg,
  },
  infoNoteIcon: {
    fontSize: 15,
    marginTop: 1,
  },
  infoNoteText: {
    flex: 1,
    fontSize: font.sm,
    color: '#93c5fd',
    lineHeight: 19,
  },

  // ── Action buttons ────────────────────────────────────────────────────────
  actions: {
    gap: spacing.sm,
  },
  submitBtn: {
    backgroundColor: colors.primary,
    borderRadius: radius.lg,
    paddingVertical: spacing.md,
    alignItems: 'center',
    shadowColor: colors.primary,
    shadowOffset: { width: 0, height: 3 },
    shadowOpacity: 0.3,
    shadowRadius: 6,
    elevation: 4,
  },
  submitBtnDisabled: {
    opacity: 0.55,
  },
  submitBtnText: {
    fontSize: font.base,
    fontWeight: font.bold,
    color: '#fff',
  },
  cancelBtn: {
    backgroundColor: colors.bgCard,
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radius.lg,
    paddingVertical: spacing.md,
    alignItems: 'center',
  },
  cancelBtnText: {
    fontSize: font.base,
    fontWeight: font.semibold,
    color: colors.textSecondary,
  },

  // ── Error / retry ─────────────────────────────────────────────────────────
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
  retryBtnText: {
    fontSize: font.base,
    fontWeight: font.bold,
    color: '#fff',
  },
});