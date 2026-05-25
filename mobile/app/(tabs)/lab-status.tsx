/**
 * app/(tabs)/lab-status.tsx  —  Lab Status
 *
 * Mirrors: resources/js/Pages/User/LabStatus.vue
 *
 * Fetches from GET /api/labs (protected, already in api.php).
 * Returns: array of labs, each with an `equipment` array.
 *
 * Layout:
 *   1. Lab selector tab bar (horizontal scroll) — each tab shows lab name
 *      and an Operational / Limited / No Equipment badge
 *   2. Selected lab detail card:
 *        - Header: lab name, location, overall status badge
 *        - 4-col stats row: Total / Operational / Has Issues / Maintenance
 *        - Equipment grid: icon + code, colour-coded by status
 *        - Legend
 *   3. Pull-to-refresh
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
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { router } from 'expo-router';
import { colors, spacing, radius, font } from '@/constants/theme';
import { loadAuth } from '@/utils/auth';
import { apiUrl } from '@/constants/api';

// ─── Types ────────────────────────────────────────────────────────────────────

type Equipment = {
  id:             number;
  equipment_code: string;
  type:           string;
  status:         'operational' | 'has-issue' | 'maintenance' | 'retired';
  notes:          string | null;
};

type Lab = {
  id:        number;
  name:      string;
  code:      string;
  location:  string | null;
  capacity:  number;
  equipment: Equipment[];
};

// ─── Helpers ─────────────────────────────────────────────────────────────────

function operationalCount(lab: Lab)  { return lab.equipment.filter(e => e.status === 'operational').length; }
function issueCount(lab: Lab)        { return lab.equipment.filter(e => e.status === 'has-issue').length; }
function maintenanceCount(lab: Lab)  { return lab.equipment.filter(e => e.status === 'maintenance').length; }

function labStatusInfo(lab: Lab): { label: string; color: string; bg: string; border: string } {
  const total = lab.equipment.length;
  if (total === 0) return { label: 'No Equipment', color: colors.textMuted,    bg: 'rgba(156,163,175,0.1)', border: 'rgba(156,163,175,0.3)' };
  const pct = operationalCount(lab) / total;
  if (pct >= 0.5) return { label: 'Operational', color: colors.successLight, bg: colors.successBg,         border: colors.successBorder };
  return              { label: 'Limited',      color: colors.warningLight, bg: colors.warningBg,         border: colors.warningBorder };
}

function equipmentStyle(status: Equipment['status']): { bg: string; border: string; text: string } {
  switch (status) {
    case 'operational': return { bg: 'rgba(16,185,129,0.12)', border: 'rgba(16,185,129,0.45)', text: '#34d399' };
    case 'has-issue':   return { bg: 'rgba(239,68,68,0.12)',  border: 'rgba(239,68,68,0.45)',  text: '#f87171' };
    case 'maintenance': return { bg: 'rgba(245,158,11,0.12)', border: 'rgba(245,158,11,0.45)', text: '#fbbf24' };
    default:            return { bg: 'rgba(107,114,128,0.1)', border: 'rgba(107,114,128,0.3)', text: colors.textMuted };
  }
}

function equipmentIcon(type: string): string {
  switch (type) {
    case 'computer':  return '💻';
    case 'printer':   return '🖨️';
    case 'projector': return '📽️';
    default:          return '🖥️';
  }
}

function statusLabel(status: Equipment['status']): string {
  switch (status) {
    case 'operational': return 'Operational';
    case 'has-issue':   return 'Has Issue';
    case 'maintenance': return 'Maintenance';
    case 'retired':     return 'Retired';
    default:            return status;
  }
}

// ─── Equipment Item ───────────────────────────────────────────────────────────

function EquipmentItem({ eq }: { eq: Equipment }) {
  const style = equipmentStyle(eq.status);
  return (
    <View style={[
      eq_s.wrap,
      { backgroundColor: style.bg, borderColor: style.border },
    ]}>
      <Text style={eq_s.icon}>{equipmentIcon(eq.type)}</Text>
      <Text style={[eq_s.code, { color: style.text }]} numberOfLines={1}>
        {eq.equipment_code}
      </Text>
    </View>
  );
}

const eq_s = StyleSheet.create({
  wrap: {
    width: '14.5%',
    aspectRatio: 1,
    borderWidth: 1.5,
    borderRadius: radius.md,
    alignItems: 'center',
    justifyContent: 'center',
    padding: 2,
  },
  icon: { fontSize: 14, lineHeight: 18 },
  code: { fontSize: 7, fontWeight: '700', marginTop: 2, textAlign: 'center' },
});

// ─── Main Component ───────────────────────────────────────────────────────────

export default function LabStatusScreen() {
  const [token,       setToken]       = useState<string | null>(null);
  const [labs,        setLabs]        = useState<Lab[]>([]);
  const [loading,     setLoading]     = useState(true);
  const [refreshing,  setRefreshing]  = useState(false);
  const [error,       setError]       = useState<string | null>(null);
  const [selectedId,  setSelectedId]  = useState<number | null>(null);

  const selectedLab = labs.find(l => l.id === selectedId) ?? null;

  // ── Auth ──────────────────────────────────────────────────────────────────
  useEffect(() => {
    loadAuth().then((auth) => {
      if (!auth) { router.replace('/login'); return; }
      setToken(auth.token);
    });
  }, []);

  // ── Fetch ─────────────────────────────────────────────────────────────────
  const fetchLabs = useCallback(async (tok: string) => {
    try {
      const res = await fetch(apiUrl('labs'), {
        headers: {
          'Accept':        'application/json',
          'Authorization': `Bearer ${tok}`,
        },
      });
      if (res.status === 401) { router.replace('/login'); return; }
      if (!res.ok) throw new Error(`Server error ${res.status}`);
      const data: Lab[] = await res.json();
      setLabs(data);
      // Auto-select first lab
      if (data.length > 0 && selectedId === null) {
        setSelectedId(data[0].id);
      }
      setError(null);
    } catch (e: any) {
      setError(e.message ?? 'Could not load lab data.');
    }
  }, [selectedId]);

  useEffect(() => {
    if (!token) return;
    setLoading(true);
    fetchLabs(token).finally(() => setLoading(false));
  }, [token]); // eslint-disable-line react-hooks/exhaustive-deps

  const onRefresh = useCallback(async () => {
    if (!token) return;
    setRefreshing(true);
    await fetchLabs(token);
    setRefreshing(false);
  }, [token, fetchLabs]);

  // ─── Loading ──────────────────────────────────────────────────────────────
  if (loading) {
    return (
      <SafeAreaView style={s.root}>
        <StatusBar barStyle="light-content" backgroundColor={colors.bgPrimary} />
        <View style={s.centered}>
          <ActivityIndicator size="large" color={colors.primary} />
          <Text style={s.loadingText}>Loading lab data…</Text>
        </View>
      </SafeAreaView>
    );
  }

  if (error) {
    return (
      <SafeAreaView style={s.root}>
        <StatusBar barStyle="light-content" backgroundColor={colors.bgPrimary} />
        <View style={s.centered}>
          <Text style={s.errorIcon}>⚠️</Text>
          <Text style={s.errorText}>{error}</Text>
          <TouchableOpacity style={s.retryBtn} onPress={() => token && fetchLabs(token)}>
            <Text style={s.retryText}>Try Again</Text>
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
        <Text style={s.navLogo}>LabFix</Text>
        <Text style={s.navTitle}>Lab Status</Text>
        <View style={{ width: 60 }} />
      </View>

      <ScrollView
        showsVerticalScrollIndicator={false}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={onRefresh}
            tintColor={colors.primary}
            colors={[colors.primary]}
          />
        }
      >
        {/* ── Page header ── */}
        <View style={s.pageHeader}>
          <Text style={s.pageTitle}>Lab Status</Text>
          <Text style={s.pageSub}>Real-time equipment availability across all labs</Text>
        </View>

        {labs.length === 0 ? (
          <View style={s.centered}>
            <Text style={s.errorIcon}>🏫</Text>
            <Text style={s.errorText}>No labs available.</Text>
          </View>
        ) : (
          <>
            {/* ── Lab selector tabs (horizontal scroll) ── */}
            <ScrollView
              horizontal
              showsHorizontalScrollIndicator={false}
              contentContainerStyle={s.tabScroll}
            >
              {labs.map((lab) => {
                const info     = labStatusInfo(lab);
                const selected = selectedId === lab.id;
                return (
                  <TouchableOpacity
                    key={lab.id}
                    style={[s.tab, selected && s.tabActive]}
                    onPress={() => setSelectedId(lab.id)}
                    activeOpacity={0.75}
                  >
                    <Text style={[s.tabLabel, selected && s.tabLabelActive]}>
                      {lab.name}
                    </Text>
                    <View style={[
                      s.tabBadge,
                      { backgroundColor: info.bg, borderColor: info.border },
                    ]}>
                      <Text style={[s.tabBadgeText, { color: info.color }]}>
                        {info.label}
                      </Text>
                    </View>
                  </TouchableOpacity>
                );
              })}
            </ScrollView>

            {/* ── Selected lab detail ── */}
            {selectedLab && (() => {
              const info = labStatusInfo(selectedLab);
              const opCount   = operationalCount(selectedLab);
              const issCount  = issueCount(selectedLab);
              const maintCount = maintenanceCount(selectedLab);
              const activeEquipment = selectedLab.equipment.filter(e => e.status !== 'retired');

              return (
                <View style={s.labCard}>

                  {/* Lab header */}
                  <View style={s.labHeader}>
                    <View style={s.labHeaderLeft}>
                      <View style={s.labIconWrap}>
                        <Text style={s.labIconText}>💻</Text>
                      </View>
                      <View style={{ flex: 1 }}>
                        <Text style={s.labName}>{selectedLab.name}</Text>
                        <Text style={s.labLocation}>
                          {selectedLab.location ?? 'Location not specified'}
                        </Text>
                      </View>
                    </View>
                    <View style={[
                      s.statusBadge,
                      { backgroundColor: info.bg, borderColor: info.border },
                    ]}>
                      <Text style={[s.statusBadgeText, { color: info.color }]}>
                        {info.label}
                      </Text>
                    </View>
                  </View>

                  {/* 4-col stats */}
                  <View style={s.statsRow}>
                    <View style={s.statItem}>
                      <Text style={s.statLabel}>Total</Text>
                      <Text style={s.statValue}>{selectedLab.equipment.length}</Text>
                    </View>
                    <View style={s.statDivider} />
                    <View style={s.statItem}>
                      <Text style={s.statLabel}>Operational</Text>
                      <Text style={[s.statValue, { color: colors.successLight }]}>
                        {opCount}
                      </Text>
                    </View>
                    <View style={s.statDivider} />
                    <View style={s.statItem}>
                      <Text style={s.statLabel}>Has Issues</Text>
                      <Text style={[s.statValue, { color: colors.danger }]}>
                        {issCount}
                      </Text>
                    </View>
                    <View style={s.statDivider} />
                    <View style={s.statItem}>
                      <Text style={s.statLabel}>Maintenance</Text>
                      <Text style={[s.statValue, { color: colors.warning }]}>
                        {maintCount}
                      </Text>
                    </View>
                  </View>

                  {/* Progress bar */}
                  <View style={s.progressWrap}>
                    <View style={s.progressTrack}>
                      <View style={[
                        s.progressFill,
                        {
                          width: selectedLab.equipment.length > 0
                            ? `${(opCount / selectedLab.equipment.length) * 100}%`
                            : '0%',
                        },
                      ]} />
                    </View>
                    <Text style={s.progressLabel}>
                      {selectedLab.equipment.length > 0
                        ? `${Math.round((opCount / selectedLab.equipment.length) * 100)}% operational`
                        : 'No equipment'}
                    </Text>
                  </View>

                  {/* Equipment section */}
                  <View style={s.equipSection}>
                    <Text style={s.equipSectionTitle}>
                      Equipment Layout
                      <Text style={s.equipCount}> ({activeEquipment.length} items)</Text>
                    </Text>

                    {activeEquipment.length === 0 ? (
                      <View style={s.noEquip}>
                        <Text style={s.noEquipText}>No equipment added yet</Text>
                      </View>
                    ) : (
                      <View style={s.equipGrid}>
                        {activeEquipment.map((eq) => (
                          <EquipmentItem key={eq.id} eq={eq} />
                        ))}
                      </View>
                    )}

                    {/* Legend */}
                    <View style={s.legend}>
                      {[
                        { status: 'operational' as const, label: 'Operational' },
                        { status: 'has-issue'   as const, label: 'Has Issue'   },
                        { status: 'maintenance' as const, label: 'Maintenance' },
                      ].map(({ status, label }) => {
                        const st = equipmentStyle(status);
                        return (
                          <View key={status} style={s.legendItem}>
                            <View style={[s.legendDot, { backgroundColor: st.border }]} />
                            <Text style={s.legendText}>{label}</Text>
                          </View>
                        );
                      })}
                    </View>
                  </View>

                  {/* Notes for problem equipment */}
                  {selectedLab.equipment.some(e => e.notes && e.status !== 'operational') && (
                    <View style={s.notesSection}>
                      <Text style={s.notesSectionTitle}>📋 Equipment Notes</Text>
                      {selectedLab.equipment
                        .filter(e => e.notes && e.status !== 'operational')
                        .map((eq) => {
                          const st = equipmentStyle(eq.status);
                          return (
                            <View key={eq.id} style={s.noteItem}>
                              <Text style={[s.noteCode, { color: st.text }]}>
                                {equipmentIcon(eq.type)} {eq.equipment_code}
                              </Text>
                              <Text style={s.noteText}>{eq.notes}</Text>
                            </View>
                          );
                        })}
                    </View>
                  )}

                </View>
              );
            })()}

            {/* ── All labs overview summary ── */}
            <View style={s.overviewCard}>
              <Text style={s.overviewTitle}>All Labs Overview</Text>
              {labs.map((lab) => {
                const info = labStatusInfo(lab);
                const op   = operationalCount(lab);
                const pct  = lab.equipment.length > 0
                  ? Math.round((op / lab.equipment.length) * 100)
                  : 0;
                return (
                  <TouchableOpacity
                    key={lab.id}
                    style={[
                      s.overviewRow,
                      selectedId === lab.id && s.overviewRowActive,
                    ]}
                    onPress={() => setSelectedId(lab.id)}
                    activeOpacity={0.75}
                  >
                    <View style={{ flex: 1 }}>
                      <View style={s.overviewRowTop}>
                        <Text style={s.overviewLabName}>{lab.name}</Text>
                        <View style={[
                          s.overviewBadge,
                          { backgroundColor: info.bg, borderColor: info.border },
                        ]}>
                          <Text style={[s.overviewBadgeText, { color: info.color }]}>
                            {info.label}
                          </Text>
                        </View>
                      </View>
                      <View style={s.overviewMiniBar}>
                        <View style={s.overviewBarTrack}>
                          <View style={[s.overviewBarFill, { width: `${pct}%` }]} />
                        </View>
                        <Text style={s.overviewBarLabel}>
                          {op}/{lab.equipment.length} operational
                        </Text>
                      </View>
                    </View>
                    <Text style={s.overviewArrow}>→</Text>
                  </TouchableOpacity>
                );
              })}
            </View>

          </>
        )}

        <View style={{ height: spacing.xxl }} />
      </ScrollView>
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
  navLogo: {
    fontSize: font.xl,
    fontWeight: font.bold,
    color: colors.primary,
    letterSpacing: -0.5,
    width: 60,
  },
  navTitle: {
    fontSize: font.base,
    fontWeight: font.bold,
    color: colors.textPrimary,
  },

  // ── Page header ───────────────────────────────────────────────────────────
  pageHeader: {
    paddingHorizontal: spacing.lg,
    paddingTop: spacing.lg,
    paddingBottom: spacing.md,
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

  // ── Lab selector tabs ─────────────────────────────────────────────────────
  tabScroll: {
    paddingHorizontal: spacing.lg,
    paddingBottom: spacing.md,
    gap: spacing.sm,
  },
  tab: {
    backgroundColor: colors.bgCard,
    borderWidth: 1.5,
    borderColor: colors.border,
    borderRadius: radius.xl,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm,
    alignItems: 'center',
    gap: spacing.xs,
    marginRight: spacing.xs,
  },
  tabActive: {
    borderColor: colors.primary,
    backgroundColor: colors.primaryLight,
  },
  tabLabel: {
    fontSize: font.sm,
    fontWeight: font.semibold,
    color: colors.textMuted,
  },
  tabLabelActive: {
    color: colors.primary,
  },
  tabBadge: {
    borderWidth: 1,
    borderRadius: radius.full,
    paddingHorizontal: spacing.sm,
    paddingVertical: 2,
  },
  tabBadgeText: {
    fontSize: font.xs - 2,
    fontWeight: font.bold,
  },

  // ── Lab card ──────────────────────────────────────────────────────────────
  labCard: {
    marginHorizontal: spacing.lg,
    marginBottom: spacing.md,
    backgroundColor: colors.bgCard,
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radius.xl,
    overflow: 'hidden',
  },

  // Lab header
  labHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    padding: spacing.lg,
    borderBottomWidth: 1,
    borderBottomColor: colors.borderLight,
    gap: spacing.sm,
  },
  labHeaderLeft: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    flex: 1,
  },
  labIconWrap: {
    width: 40,
    height: 40,
    backgroundColor: colors.primaryLight,
    borderRadius: radius.lg,
    alignItems: 'center',
    justifyContent: 'center',
  },
  labIconText: { fontSize: 18 },
  labName: {
    fontSize: font.base,
    fontWeight: font.bold,
    color: colors.textPrimary,
    marginBottom: 2,
  },
  labLocation: {
    fontSize: font.xs,
    color: colors.textMuted,
  },
  statusBadge: {
    borderWidth: 1,
    borderRadius: radius.full,
    paddingHorizontal: spacing.sm + 2,
    paddingVertical: 4,
  },
  statusBadgeText: {
    fontSize: font.xs - 1,
    fontWeight: font.bold,
  },

  // Stats row
  statsRow: {
    flexDirection: 'row',
    padding: spacing.md,
    borderBottomWidth: 1,
    borderBottomColor: colors.borderLight,
  },
  statItem: {
    flex: 1,
    alignItems: 'center',
    gap: 2,
  },
  statDivider: {
    width: 1,
    backgroundColor: colors.borderLight,
    marginVertical: spacing.xs,
  },
  statLabel: {
    fontSize: font.xs - 1,
    color: colors.textMuted,
    textAlign: 'center',
  },
  statValue: {
    fontSize: font.xl,
    fontWeight: font.bold,
    color: colors.textPrimary,
  },

  // Progress bar
  progressWrap: {
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.sm,
    gap: spacing.xs,
    borderBottomWidth: 1,
    borderBottomColor: colors.borderLight,
  },
  progressTrack: {
    height: 6,
    backgroundColor: 'rgba(255,255,255,0.08)',
    borderRadius: 3,
    overflow: 'hidden',
  },
  progressFill: {
    height: '100%',
    backgroundColor: colors.primary,
    borderRadius: 3,
  },
  progressLabel: {
    fontSize: font.xs - 1,
    color: colors.textMuted,
    textAlign: 'right',
  },

  // Equipment section
  equipSection: {
    padding: spacing.lg,
  },
  equipSectionTitle: {
    fontSize: font.sm,
    fontWeight: font.bold,
    color: colors.primary,
    marginBottom: spacing.md,
  },
  equipCount: {
    color: colors.textMuted,
    fontWeight: font.normal,
  },
  equipGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.xs + 1,
    marginBottom: spacing.md,
  },
  noEquip: {
    alignItems: 'center',
    paddingVertical: spacing.lg,
  },
  noEquipText: {
    fontSize: font.sm,
    color: colors.textMuted,
  },

  // Legend
  legend: {
    flexDirection: 'row',
    gap: spacing.lg,
    paddingTop: spacing.sm,
    borderTopWidth: 1,
    borderTopColor: colors.borderLight,
  },
  legendItem: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.xs,
  },
  legendDot: {
    width: 10,
    height: 10,
    borderRadius: 3,
  },
  legendText: {
    fontSize: font.xs,
    color: colors.textMuted,
  },

  // Notes section
  notesSection: {
    marginHorizontal: spacing.lg,
    marginBottom: spacing.lg,
    backgroundColor: 'rgba(245,158,11,0.06)',
    borderWidth: 1,
    borderColor: 'rgba(245,158,11,0.2)',
    borderRadius: radius.lg,
    padding: spacing.md,
    gap: spacing.sm,
  },
  notesSectionTitle: {
    fontSize: font.sm,
    fontWeight: font.bold,
    color: colors.warningLight,
    marginBottom: spacing.xs,
  },
  noteItem: {
    gap: 2,
  },
  noteCode: {
    fontSize: font.sm,
    fontWeight: font.semibold,
  },
  noteText: {
    fontSize: font.xs,
    color: colors.textMuted,
    lineHeight: 17,
  },

  // All labs overview
  overviewCard: {
    marginHorizontal: spacing.lg,
    marginBottom: spacing.md,
    backgroundColor: colors.bgCard,
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radius.xl,
    padding: spacing.lg,
  },
  overviewTitle: {
    fontSize: font.base,
    fontWeight: font.bold,
    color: colors.primary,
    marginBottom: spacing.md,
  },
  overviewRow: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingVertical: spacing.sm + 2,
    borderBottomWidth: 1,
    borderBottomColor: 'rgba(45,212,191,0.07)',
    gap: spacing.sm,
  },
  overviewRowActive: {
    backgroundColor: colors.primaryLight,
    marginHorizontal: -spacing.lg,
    paddingHorizontal: spacing.lg,
    borderRadius: 0,
  },
  overviewRowTop: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: spacing.xs,
  },
  overviewLabName: {
    fontSize: font.sm,
    fontWeight: font.semibold,
    color: colors.textPrimary,
    flex: 1,
  },
  overviewBadge: {
    borderWidth: 1,
    borderRadius: radius.full,
    paddingHorizontal: spacing.sm,
    paddingVertical: 2,
  },
  overviewBadgeText: {
    fontSize: font.xs - 2,
    fontWeight: font.bold,
  },
  overviewMiniBar: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
  },
  overviewBarTrack: {
    flex: 1,
    height: 4,
    backgroundColor: 'rgba(255,255,255,0.08)',
    borderRadius: 2,
    overflow: 'hidden',
  },
  overviewBarFill: {
    height: '100%',
    backgroundColor: colors.primary,
    borderRadius: 2,
  },
  overviewBarLabel: {
    fontSize: font.xs - 1,
    color: colors.textMuted,
    width: 90,
    textAlign: 'right',
  },
  overviewArrow: {
    fontSize: font.sm,
    color: colors.primary,
    fontWeight: font.bold,
  },

  // ── States ────────────────────────────────────────────────────────────────
  loadingText: { fontSize: font.sm, color: colors.textMuted },
  errorIcon:   { fontSize: 36 },
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