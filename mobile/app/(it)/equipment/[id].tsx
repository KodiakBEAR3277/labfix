/**
 * app/(it)/equipment/[id].tsx  —  Equipment History (IT zone)
 *
 * Past tickets for one device, with links into the queue detail.
 * Reached by tapping the equipment block on a ticket the IT user is viewing.
 *
 * API: GET /api/equipment/{id}/history
 *   → { equipment, lab, reports[], counts: { open, total } }
 *
 * The (it) layout already restricts this route to it-support / admin.
 */

import React, { useCallback, useEffect, useState } from 'react';
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
import { router, useLocalSearchParams } from 'expo-router';
import { colors, spacing, radius, font } from '@/constants/theme';
import { loadAuth } from '@/utils/auth';
import { apiUrl } from '@/constants/api';
import Icon from '@/components/ui/Icon';

// ─── Types ────────────────────────────────────────────────────────────────────

type Equipment = {
  id:             number;
  equipment_code: string;
  type:           string;
  status:         string;
  brand:          string | null;
  model:          string | null;
  serial_number:  string | null;
  notes:          string | null;
};

type Lab = {
  id:       number;
  name:     string;
  code?:    string;
  location?: string | null;
};

type HistoryReport = {
  id:             number;
  ticket_number:  string;
  title:          string;
  status:         string;
  priority:       string;
  created_at:     string;
  updated_at:     string;
};

type HistoryResponse = {
  equipment: Equipment;
  lab:       Lab;
  reports:   HistoryReport[];
  counts:    { open: number; total: number };
};

// ─── Helpers ─────────────────────────────────────────────────────────────────

function statusStyle(s: string): { bg: string; text: string; border: string } {
  switch (s) {
    case 'new':         return { bg: colors.infoBg,           text: colors.infoLight,    border: colors.infoBorder    };
    case 'assigned':    return { bg: 'rgba(168,85,247,0.14)', text: '#7e22ce',           border: 'rgba(168,85,247,0.3)' };
    case 'in-progress': return { bg: colors.warningBg,        text: colors.warningLight, border: colors.warningBorder };
    case 'resolved':
    case 'closed':      return { bg: colors.successBg,        text: colors.successLight, border: colors.successBorder };
    default:            return { bg: colors.borderLight,      text: colors.textMuted,    border: colors.border        };
  }
}

function equipmentStatusStyle(s: string): { bg: string; text: string; border: string } {
  switch (s) {
    case 'operational': return { bg: colors.successBg, text: colors.successLight, border: colors.successBorder };
    case 'has-issue':   return { bg: colors.infoBg,    text: colors.infoLight,    border: colors.infoBorder    };
    case 'retired':     return { bg: colors.borderLight, text: colors.textMuted,  border: colors.border        };
    default:            return { bg: colors.warningBg, text: colors.warningLight, border: colors.warningBorder };
  }
}

function statusLabel(s: string): string {
  return s.replace('-', ' ').replace(/\b\w/g, c => c.toUpperCase());
}

function formatDate(iso: string): string {
  return new Date(iso).toLocaleDateString('en-US', {
    month: 'short', day: 'numeric', year: 'numeric',
  });
}

function StatusPill({ status }: { status: string }) {
  const st = statusStyle(status);
  return (
    <View style={[s.pill, { backgroundColor: st.bg, borderColor: st.border }]}>
      <Text style={[s.pillText, { color: st.text }]}>{statusLabel(status)}</Text>
    </View>
  );
}

// ─── Main Component ───────────────────────────────────────────────────────────

export default function EquipmentHistoryScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();

  const [token,      setToken]      = useState<string | null>(null);
  const [data,       setData]       = useState<HistoryResponse | null>(null);
  const [loading,    setLoading]    = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error,      setError]      = useState<string | null>(null);

  useEffect(() => {
    loadAuth().then((auth) => {
      if (!auth) { router.replace('/login'); return; }
      setToken(auth.token);
    });
  }, []);

  const fetchHistory = useCallback(async (tok: string) => {
    try {
      const res = await fetch(apiUrl(`equipment/${id}/history`), {
        headers: { 'Accept': 'application/json', 'Authorization': `Bearer ${tok}` },
      });
      if (res.status === 401) { router.replace('/login'); return; }
      if (res.status === 404) throw new Error('Equipment not found.');
      if (!res.ok) throw new Error(`Server error ${res.status}`);
      setData(await res.json());
      setError(null);
    } catch (e: any) {
      setError(e.message ?? 'Could not load equipment history.');
    }
  }, [id]);

  useEffect(() => {
    if (!token) return;
    setLoading(true);
    fetchHistory(token).finally(() => setLoading(false));
  }, [token, fetchHistory]);

  const onRefresh = useCallback(async () => {
    if (!token) return;
    setRefreshing(true);
    await fetchHistory(token);
    setRefreshing(false);
  }, [token, fetchHistory]);

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
          <Icon name="alert" size={36} color={colors.warning} />
          <Text style={s.errorText}>{error ?? 'Equipment not found.'}</Text>
          <TouchableOpacity style={s.retryBtn} onPress={() => router.back()} activeOpacity={0.85}>
            <Text style={s.retryText}>Go Back</Text>
          </TouchableOpacity>
        </View>
      </SafeAreaView>
    );
  }

  const { equipment, lab, reports, counts } = data;
  const es = equipmentStatusStyle(equipment.status);
  const model = [equipment.brand, equipment.model].filter(Boolean).join(' ');

  return (
    <SafeAreaView style={s.root} edges={['top']}>
      <StatusBar barStyle="light-content" backgroundColor={colors.bgPrimary} />

      {/* ── Top Nav ── */}
      <View style={s.nav}>
        <TouchableOpacity style={s.inlineRow} onPress={() => router.back()} activeOpacity={0.7}>
          <Icon name="arrow-left" size={13} color={colors.primary} />
          <Text style={s.navBack}>Back</Text>
        </TouchableOpacity>
        <Text style={s.navTitle}>Equipment History</Text>
        <View style={{ width: 60 }} />
      </View>

      <ScrollView
        showsVerticalScrollIndicator={false}
        contentContainerStyle={s.scroll}
        refreshControl={
          <RefreshControl refreshing={refreshing} onRefresh={onRefresh}
            tintColor={colors.primary} colors={[colors.primary]} />
        }
      >
        {/* ── Equipment header ── */}
        <View style={s.card}>
          <View style={s.headerTop}>
            <View style={{ flex: 1 }}>
              <Text style={s.equipmentCode}>{equipment.equipment_code}</Text>
              <Text style={s.equipmentMeta}>
                {equipment.type.charAt(0).toUpperCase() + equipment.type.slice(1)}
                {model ? ` · ${model}` : ''}
              </Text>
            </View>
            <View style={[s.pill, { backgroundColor: es.bg, borderColor: es.border }]}>
              <Text style={[s.pillText, { color: es.text }]}>{statusLabel(equipment.status)}</Text>
            </View>
          </View>

          <View style={s.metaGrid}>
            <View style={s.metaItem}>
              <Text style={s.metaLabel}>Lab</Text>
              <Text style={s.metaValue}>{lab?.name ?? '—'}</Text>
            </View>
            {lab?.location && (
              <View style={s.metaItem}>
                <Text style={s.metaLabel}>Location</Text>
                <Text style={s.metaValue}>{lab.location}</Text>
              </View>
            )}
            {equipment.serial_number && (
              <View style={s.metaItem}>
                <Text style={s.metaLabel}>Serial Number</Text>
                <Text style={s.metaValue}>{equipment.serial_number}</Text>
              </View>
            )}
            <View style={s.metaItem}>
              <Text style={s.metaLabel}>Open Tickets</Text>
              <Text style={s.metaValue}>{counts.open}</Text>
            </View>
            <View style={s.metaItem}>
              <Text style={s.metaLabel}>Total Tickets</Text>
              <Text style={s.metaValue}>{counts.total}</Text>
            </View>
          </View>

          {equipment.notes ? (
            <Text style={s.notes}>{equipment.notes}</Text>
          ) : null}
        </View>

        {/* ── Ticket history ── */}
        <Text style={s.sectionTitle}>Ticket History</Text>

        {reports.length === 0 ? (
          <View style={s.card}>
            <View style={s.empty}>
              <Icon name="ticket" size={28} color={colors.textMuted} />
              <Text style={s.emptyText}>No tickets recorded for this equipment yet.</Text>
            </View>
          </View>
        ) : (
          <View style={s.card}>
            {reports.map((r, i) => (
              <TouchableOpacity
                key={r.id}
                style={[s.ticketRow, i > 0 && s.ticketRowBorder]}
                onPress={() => router.push(`/(it)/queue/${r.id}` as any)}
                activeOpacity={0.75}
              >
                <View style={{ flex: 1, minWidth: 0 }}>
                  <View style={s.ticketTop}>
                    <Text style={s.ticketNum}>{r.ticket_number}</Text>
                    <Text style={s.ticketDate}>{formatDate(r.created_at)}</Text>
                  </View>
                  <Text style={s.ticketTitle} numberOfLines={2}>{r.title}</Text>
                </View>
                <View style={s.ticketRight}>
                  <StatusPill status={r.status} />
                  <Icon name="chevron-right" size={16} color={colors.textMuted} />
                </View>
              </TouchableOpacity>
            ))}
          </View>
        )}

        <View style={{ height: spacing.xxl }} />
      </ScrollView>
    </SafeAreaView>
  );
}

// ─── Styles ───────────────────────────────────────────────────────────────────

const s = StyleSheet.create({
  root:      { flex: 1, backgroundColor: colors.bgPrimary },
  scroll:    { paddingHorizontal: spacing.lg, paddingTop: spacing.lg },
  inlineRow: { flexDirection: 'row', alignItems: 'center', gap: 4 },
  centered:  { flex: 1, alignItems: 'center', justifyContent: 'center', gap: spacing.md, padding: spacing.lg },

  nav: {
    flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center',
    paddingHorizontal: spacing.lg, paddingVertical: spacing.sm + 4,
    borderBottomWidth: 1, borderBottomColor: colors.border,
  },
  navBack:  { fontSize: font.sm, fontWeight: font.semibold, color: colors.primary, width: 60 },
  navTitle: { fontSize: font.base, fontWeight: font.bold, color: colors.textPrimary },

  card: {
    backgroundColor: colors.bgCard, borderWidth: 1, borderColor: colors.border,
    borderRadius: radius.xl, padding: spacing.lg, marginBottom: spacing.md,
  },
  headerTop:     { flexDirection: 'row', alignItems: 'flex-start', gap: spacing.sm, marginBottom: spacing.md },
  equipmentCode: { fontSize: font.xxl, fontWeight: font.bold, color: colors.textPrimary },
  equipmentMeta: { fontSize: font.sm, color: colors.textMuted, marginTop: 2 },

  metaGrid: {
    flexDirection: 'row', flexWrap: 'wrap',
    borderTopWidth: 1, borderTopColor: colors.borderLight,
    paddingTop: spacing.md, gap: spacing.sm,
  },
  metaItem:  { width: '47%', gap: 2 },
  metaLabel: { fontSize: font.xs - 1, color: colors.textMuted },
  metaValue: { fontSize: font.sm, fontWeight: font.medium, color: colors.textPrimary },

  notes: {
    marginTop: spacing.md, paddingTop: spacing.md,
    borderTopWidth: 1, borderTopColor: colors.borderLight,
    fontSize: font.xs, color: colors.textMuted, lineHeight: 18,
  },

  sectionTitle: {
    fontSize: font.sm, fontWeight: font.bold, color: colors.primary,
    textTransform: 'uppercase', letterSpacing: 0.6, marginBottom: spacing.sm,
  },

  pill:     { borderWidth: 1, borderRadius: radius.full, paddingHorizontal: 9, paddingVertical: 3, alignSelf: 'flex-start' },
  pillText: { fontSize: font.xs - 1, fontWeight: font.semibold },

  ticketRow:       { flexDirection: 'row', alignItems: 'center', gap: spacing.sm, paddingVertical: spacing.sm + 2 },
  ticketRowBorder: { borderTopWidth: 1, borderTopColor: colors.borderLight },
  ticketTop:       { flexDirection: 'row', alignItems: 'center', gap: spacing.sm, marginBottom: 2 },
  ticketNum:       { fontSize: font.xs, fontWeight: font.bold, color: colors.primary },
  ticketDate:      { fontSize: font.xs - 1, color: colors.textMuted },
  ticketTitle:     { fontSize: font.sm, fontWeight: font.medium, color: colors.textPrimary, lineHeight: 19 },
  ticketRight:     { flexDirection: 'row', alignItems: 'center', gap: spacing.xs },

  empty:     { alignItems: 'center', paddingVertical: spacing.md, gap: spacing.sm },
  emptyText: { fontSize: font.sm, color: colors.textMuted, textAlign: 'center' },

  errorText: { fontSize: font.base, color: colors.textSecondary, textAlign: 'center' },
  retryBtn:  { backgroundColor: colors.primary, paddingHorizontal: spacing.lg, paddingVertical: spacing.sm + 2, borderRadius: radius.full },
  retryText: { fontSize: font.base, fontWeight: font.bold, color: '#fff' },
});
