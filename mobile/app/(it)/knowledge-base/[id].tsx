/**
 * app/(it)/knowledge-base/[id]/show.tsx  —  IT KB Article Preview
 *
 * Mirrors: resources/js/Pages/IT/KnowledgeBase/Show.vue
 *
 * Fetches GET /api/it/articles/{id} on mount.
 * Shows full article content with IT-only stats sidebar:
 *   - Status, category, views, helpful/not-helpful votes, helpfulness %, dates
 *   - Edit button → [id]/edit
 *   - Publish Now button (if draft) → PUT /api/it/articles/{id} with status=published
 *   - Delete button → DELETE /api/it/articles/{id} → back to index
 *
 * Pull-to-refresh supported.
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

type Article = {
  id:                     number;
  title:                  string;
  slug:                   string;
  content:                string;
  excerpt:                string | null;
  category:               string;
  status:                 'draft' | 'published';
  views:                  number;
  helpful_count:          number;
  not_helpful_count:      number;
  helpfulness_percentage: number;
  published_at:           string | null;
  created_at:             string;
  updated_at:             string;
  author?: { full_name: string };
};

// ─── Constants ────────────────────────────────────────────────────────────────

const CATEGORY_ICONS: Record<string, string> = {
  hardware:    '🔧',
  software:    '💾',
  network:     '🌐',
  display:     '🖥️',
  peripherals: '🖱️',
  general:     '📋',
};

const CATEGORY_COLORS: Record<string, { bg: string; text: string; border: string }> = {
  hardware:    { bg: 'rgba(239,68,68,0.1)',  text: '#f87171', border: 'rgba(239,68,68,0.3)'  },
  software:    { bg: 'rgba(245,158,11,0.1)', text: '#fbbf24', border: 'rgba(245,158,11,0.3)' },
  network:     { bg: 'rgba(59,130,246,0.1)', text: '#60a5fa', border: 'rgba(59,130,246,0.3)' },
  display:     { bg: 'rgba(168,85,247,0.1)', text: '#c084fc', border: 'rgba(168,85,247,0.3)' },
  peripherals: { bg: 'rgba(16,185,129,0.1)', text: '#34d399', border: 'rgba(16,185,129,0.3)' },
  general:     { bg: 'rgba(45,212,191,0.1)', text: '#2dd4bf', border: 'rgba(45,212,191,0.3)' },
};

// ─── Helpers ─────────────────────────────────────────────────────────────────

function formatDate(iso: string): string {
  return new Date(iso).toLocaleDateString('en-US', {
    month: 'short', day: 'numeric', year: 'numeric',
  });
}

function diffForHumans(iso: string): string {
  const sec = Math.floor((Date.now() - new Date(iso).getTime()) / 1000);
  if (sec < 60)    return `${sec}s ago`;
  if (sec < 3600)  return `${Math.floor(sec / 60)}m ago`;
  if (sec < 86400) return `${Math.floor(sec / 3600)}h ago`;
  return `${Math.floor(sec / 86400)}d ago`;
}

function formatViews(n: number): string {
  return n >= 1000 ? `${(n / 1000).toFixed(1)}K` : String(n);
}

// ─── Info Row ─────────────────────────────────────────────────────────────────

function InfoRow({ label, children }: { label: string; children: React.ReactNode }) {
  return (
    <View style={ir.row}>
      <Text style={ir.label}>{label}</Text>
      <View style={ir.value}>{children}</View>
    </View>
  );
}
const ir = StyleSheet.create({
  row: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingVertical: spacing.xs + 2,
    borderBottomWidth: 1,
    borderBottomColor: 'rgba(45,212,191,0.06)',
  },
  label: { fontSize: font.sm, color: colors.textMuted, flex: 1 },
  value: { flex: 1, alignItems: 'flex-end' },
});

// ─── Component ────────────────────────────────────────────────────────────────

export default function ITKBShowScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();

  const [token,      setToken]      = useState<string | null>(null);
  const [article,    setArticle]    = useState<Article | null>(null);
  const [loading,    setLoading]    = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error,      setError]      = useState<string | null>(null);
  const [publishing, setPublishing] = useState(false);
  const [deleting,   setDeleting]   = useState(false);

  // ── Auth ──────────────────────────────────────────────────────────────────
  useEffect(() => {
    loadAuth().then((auth) => {
      if (!auth) { router.replace('/login'); return; }
      setToken(auth.token);
    });
  }, []);

  // ── Fetch ─────────────────────────────────────────────────────────────────
  const fetchArticle = useCallback(async (tok: string) => {
    try {
      const res = await fetch(apiUrl(`it/articles/${id}`), {
        headers: {
          'Accept':        'application/json',
          'Authorization': `Bearer ${tok}`,
        },
      });
      if (res.status === 401) { router.replace('/login'); return; }
      if (res.status === 404) throw new Error('Article not found.');
      if (!res.ok) throw new Error(`Server error ${res.status}`);

      const data: Article = await res.json();
      setArticle(data);
      setError(null);
    } catch (e: any) {
      setError(e.message ?? 'Could not load article.');
    }
  }, [id]);

  useEffect(() => {
    if (!token) return;
    setLoading(true);
    fetchArticle(token).finally(() => setLoading(false));
  }, [token, fetchArticle]);

  const onRefresh = useCallback(async () => {
    if (!token) return;
    setRefreshing(true);
    await fetchArticle(token);
    setRefreshing(false);
  }, [token, fetchArticle]);

  // ── Publish Now ───────────────────────────────────────────────────────────
  async function handlePublish() {
    if (!token || !article) return;

    Alert.alert(
      'Publish Article',
      'This article will be visible to all users. Are you sure?',
      [
        { text: 'Cancel', style: 'cancel' },
        {
          text: 'Publish',
          onPress: async () => {
            setPublishing(true);
            try {
              const res = await fetch(apiUrl(`it/articles/${id}`), {
                method: 'PUT',
                headers: {
                  'Content-Type':  'application/json',
                  'Accept':        'application/json',
                  'Authorization': `Bearer ${token}`,
                },
                body: JSON.stringify({
                  title:    article.title,
                  content:  article.content,
                  category: article.category,
                  excerpt:  article.excerpt ?? undefined,
                  status:   'published',
                }),
              });

              if (res.ok) {
                setArticle(prev => prev ? { ...prev, status: 'published' } : prev);
                Alert.alert('Published!', 'Your article is now live for all users.');
              } else {
                Alert.alert('Error', 'Failed to publish article. Please try again.');
              }
            } catch {
              Alert.alert('Error', 'Could not reach the server.');
            } finally {
              setPublishing(false);
            }
          },
        },
      ]
    );
  }

  // ── Delete ────────────────────────────────────────────────────────────────
  async function handleDelete() {
    if (!token || !article) return;

    Alert.alert(
      'Delete Article',
      `Are you sure you want to delete "${article.title}"? This cannot be undone.`,
      [
        { text: 'Cancel', style: 'cancel' },
        {
          text: 'Delete',
          style: 'destructive',
          onPress: async () => {
            setDeleting(true);
            try {
              const res = await fetch(apiUrl(`it/articles/${id}`), {
                method: 'DELETE',
                headers: {
                  'Accept':        'application/json',
                  'Authorization': `Bearer ${token}`,
                },
              });

              if (res.ok) {
                router.replace('/(it)/knowledge-base' as any);
              } else {
                Alert.alert('Error', 'Failed to delete article. Please try again.');
              }
            } catch {
              Alert.alert('Error', 'Could not reach the server.');
            } finally {
              setDeleting(false);
            }
          },
        },
      ]
    );
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

  if (error || !article) {
    return (
      <SafeAreaView style={s.root}>
        <StatusBar barStyle="light-content" backgroundColor={colors.bgPrimary} />
        <View style={s.centered}>
          <Text style={s.errorIcon}>📄</Text>
          <Text style={s.errorText}>{error ?? 'Article not found.'}</Text>
          <TouchableOpacity style={s.retryBtn} onPress={() => router.back()}>
            <Text style={s.retryText}>← Go Back</Text>
          </TouchableOpacity>
        </View>
      </SafeAreaView>
    );
  }

  const isPublished = article.status === 'published';
  const catColor    = CATEGORY_COLORS[article.category] ?? CATEGORY_COLORS.general;

  // ─── Main render ──────────────────────────────────────────────────────────
  return (
    <SafeAreaView style={s.root} edges={['top']}>
      <StatusBar barStyle="light-content" backgroundColor={colors.bgPrimary} />

      {/* ── Top Nav ── */}
      <View style={s.nav}>
        <TouchableOpacity
          onPress={() => router.push('/(it)/knowledge-base' as any)}
          activeOpacity={0.7}
        >
          <Text style={s.navBack}>← Knowledge Base</Text>
        </TouchableOpacity>
        <Text style={s.navTitle}>Article Preview</Text>
        <TouchableOpacity
          style={s.editNavBtn}
          onPress={() => router.push(`/(it)/knowledge-base/${id}/edit` as any)}
          activeOpacity={0.85}
        >
          <Text style={s.editNavBtnText}>Edit</Text>
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
        {/* ── Article Header ── */}
        <View style={s.headerCard}>
          {/* Category + status row */}
          <View style={s.badgeRow}>
            <View style={[s.categoryBadge, { backgroundColor: catColor.bg, borderColor: catColor.border }]}>
              <Text style={[s.categoryBadgeText, { color: catColor.text }]}>
                {CATEGORY_ICONS[article.category] ?? '📄'}{' '}
                {article.category.charAt(0).toUpperCase() + article.category.slice(1)}
              </Text>
            </View>
            <View style={[
              s.statusBadge,
              isPublished
                ? { backgroundColor: colors.successBg,  borderColor: colors.successBorder }
                : { backgroundColor: colors.warningBg,   borderColor: colors.warningBorder },
            ]}>
              <Text style={[
                s.statusBadgeText,
                { color: isPublished ? colors.successLight : colors.warningLight },
              ]}>
                {isPublished ? 'Published' : 'Draft'}
              </Text>
            </View>
          </View>

          {/* Title */}
          <Text style={s.articleTitle}>{article.title}</Text>

          {/* Meta */}
          <View style={s.metaRow}>
            <Text style={s.metaText}>
              👁️ {formatViews(article.views)} views
            </Text>
            <Text style={s.metaDot}>·</Text>
            <Text style={s.metaText}>
              👍 {article.helpfulness_percentage}% helpful
            </Text>
            <Text style={s.metaDot}>·</Text>
            <Text style={s.metaText}>
              ✏️ {article.author?.full_name ?? 'IT Support'}
            </Text>
          </View>
          <View style={s.metaRow}>
            <Text style={s.metaText}>
              📅 Updated {diffForHumans(article.updated_at)}
            </Text>
          </View>
        </View>

        {/* ── Excerpt ── */}
        {!!article.excerpt && (
          <View style={s.excerptCard}>
            <View style={s.excerptAccent} />
            <View style={s.excerptBody}>
              <Text style={s.excerptLabel}>Excerpt</Text>
              <Text style={s.excerptText}>{article.excerpt}</Text>
            </View>
          </View>
        )}

        {/* ── Article Content ── */}
        <View style={s.card}>
          <Text style={s.contentText}>{article.content}</Text>
        </View>

        {/* ── Article Statistics ── */}
        <View style={s.card}>
          <Text style={s.cardTitle}>Article Statistics</Text>

          <InfoRow label="Status">
            <View style={[
              s.inlineStatusBadge,
              isPublished
                ? { backgroundColor: colors.successBg,  borderColor: colors.successBorder }
                : { backgroundColor: colors.warningBg,   borderColor: colors.warningBorder },
            ]}>
              <Text style={[
                s.inlineStatusText,
                { color: isPublished ? colors.successLight : colors.warningLight },
              ]}>
                {isPublished ? 'Published' : 'Draft'}
              </Text>
            </View>
          </InfoRow>

          <InfoRow label="Category">
            <Text style={[s.statValue, { color: catColor.text }]}>
              {CATEGORY_ICONS[article.category]}{' '}
              {article.category.charAt(0).toUpperCase() + article.category.slice(1)}
            </Text>
          </InfoRow>

          <InfoRow label="Total Views">
            <Text style={s.statValue}>{article.views.toLocaleString()}</Text>
          </InfoRow>

          <InfoRow label="Helpful Votes">
            <Text style={[s.statValue, { color: colors.successLight }]}>
              👍 {article.helpful_count}
            </Text>
          </InfoRow>

          <InfoRow label="Not Helpful">
            <Text style={[s.statValue, { color: '#f87171' }]}>
              👎 {article.not_helpful_count}
            </Text>
          </InfoRow>

          {/* Helpfulness bar */}
          {(article.helpful_count + article.not_helpful_count) > 0 && (
            <InfoRow label="Helpfulness">
              <View style={s.helpBarWrap}>
                <View style={s.helpBarTrack}>
                  <View style={[
                    s.helpBarFill,
                    { width: `${article.helpfulness_percentage}%` },
                  ]} />
                </View>
                <Text style={s.helpPct}>{article.helpfulness_percentage}%</Text>
              </View>
            </InfoRow>
          )}

          <InfoRow label="Created">
            <Text style={s.statValue}>{formatDate(article.created_at)}</Text>
          </InfoRow>

          <View style={{ borderBottomWidth: 0 }}>
            <InfoRow label="Last Updated">
              <Text style={s.statValue}>{diffForHumans(article.updated_at)}</Text>
            </InfoRow>
          </View>
        </View>

        {/* ── Actions ── */}
        <View style={s.actionsCard}>
          <Text style={s.cardTitle}>Actions</Text>

          {/* Edit */}
          <TouchableOpacity
            style={s.editBtn}
            onPress={() => router.push(`/(it)/knowledge-base/${id}/edit` as any)}
            activeOpacity={0.85}
          >
            <Text style={s.editBtnText}>✏️ Edit Article</Text>
          </TouchableOpacity>

          {/* Publish Now — only if draft */}
          {!isPublished && (
            <TouchableOpacity
              style={[s.publishBtn, publishing && s.btnDisabled]}
              onPress={handlePublish}
              disabled={publishing}
              activeOpacity={0.85}
            >
              {publishing
                ? <ActivityIndicator size="small" color={colors.successLight} />
                : <Text style={s.publishBtnText}>🌐 Publish Now</Text>}
            </TouchableOpacity>
          )}

          {/* Delete */}
          <TouchableOpacity
            style={[s.deleteBtn, deleting && s.btnDisabled]}
            onPress={handleDelete}
            disabled={deleting}
            activeOpacity={0.85}
          >
            {deleting
              ? <ActivityIndicator size="small" color="#f87171" />
              : <Text style={s.deleteBtnText}>🗑️ Delete Article</Text>}
          </TouchableOpacity>
        </View>

        <View style={{ height: spacing.xxl }} />
      </ScrollView>
    </SafeAreaView>
  );
}

// ─── Styles ───────────────────────────────────────────────────────────────────

const s = StyleSheet.create({
  root:    { flex: 1, backgroundColor: colors.bgPrimary },
  scroll:  { paddingBottom: spacing.lg },
  centered:{
    flex: 1, alignItems: 'center', justifyContent: 'center',
    padding: spacing.lg, gap: spacing.md,
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
  navBack:  { fontSize: font.sm, fontWeight: font.semibold, color: colors.primary, width: 110 },
  navTitle: { fontSize: font.base, fontWeight: font.bold, color: colors.textPrimary },
  editNavBtn: {
    backgroundColor: colors.primary,
    borderRadius: radius.full,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.xs + 2,
  },
  editNavBtnText: { fontSize: font.sm, fontWeight: font.bold, color: '#fff' },

  // Header card
  headerCard: {
    marginHorizontal: spacing.lg,
    marginTop: spacing.lg,
    marginBottom: spacing.md,
    backgroundColor: colors.bgCard,
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radius.xl,
    padding: spacing.lg,
    gap: spacing.sm,
  },
  badgeRow: {
    flexDirection: 'row',
    gap: spacing.sm,
    flexWrap: 'wrap',
  },
  categoryBadge: {
    borderWidth: 1,
    borderRadius: radius.full,
    paddingHorizontal: spacing.sm + 2,
    paddingVertical: 4,
  },
  categoryBadgeText: { fontSize: font.xs, fontWeight: font.semibold },
  statusBadge: {
    borderWidth: 1,
    borderRadius: radius.full,
    paddingHorizontal: spacing.sm + 2,
    paddingVertical: 4,
  },
  statusBadgeText: { fontSize: font.xs, fontWeight: font.bold },
  articleTitle: {
    fontSize: font.xl,
    fontWeight: font.bold,
    color: colors.textPrimary,
    lineHeight: 28,
  },
  metaRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.xs + 2,
    flexWrap: 'wrap',
  },
  metaText: { fontSize: font.xs, color: colors.textMuted },
  metaDot:  { fontSize: font.xs, color: colors.textMuted },

  // Excerpt
  excerptCard: {
    marginHorizontal: spacing.lg,
    marginBottom: spacing.md,
    flexDirection: 'row',
    backgroundColor: 'rgba(45,212,191,0.05)',
    borderWidth: 1,
    borderColor: 'rgba(45,212,191,0.2)',
    borderRadius: radius.lg,
    overflow: 'hidden',
  },
  excerptAccent: { width: 3, backgroundColor: colors.primary },
  excerptBody:   { flex: 1, padding: spacing.md, gap: spacing.xs },
  excerptLabel:  { fontSize: font.xs, fontWeight: font.bold, color: colors.primary },
  excerptText:   { fontSize: font.sm, color: colors.textSecondary, lineHeight: 20, fontStyle: 'italic' },

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
  contentText: {
    fontSize: font.base,
    color: colors.textSecondary,
    lineHeight: 26,
  },

  // Stats
  inlineStatusBadge: {
    borderWidth: 1,
    borderRadius: radius.full,
    paddingHorizontal: spacing.sm,
    paddingVertical: 2,
  },
  inlineStatusText: { fontSize: font.xs - 1, fontWeight: font.bold },
  statValue: {
    fontSize: font.sm,
    fontWeight: font.medium,
    color: colors.textPrimary,
    textAlign: 'right',
  },
  helpBarWrap: { alignItems: 'flex-end', gap: 4, minWidth: 100 },
  helpBarTrack: {
    width: 90,
    height: 5,
    backgroundColor: 'rgba(255,255,255,0.08)',
    borderRadius: 3,
    overflow: 'hidden',
  },
  helpBarFill: {
    height: '100%',
    backgroundColor: colors.primary,
    borderRadius: 3,
  },
  helpPct: { fontSize: font.xs, color: colors.primary, fontWeight: font.semibold },

  // Actions card
  actionsCard: {
    marginHorizontal: spacing.lg,
    marginBottom: spacing.md,
    backgroundColor: colors.bgCard,
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radius.xl,
    padding: spacing.lg,
    gap: spacing.sm,
  },
  editBtn: {
    backgroundColor: colors.primary,
    borderRadius: radius.lg,
    paddingVertical: spacing.sm + 4,
    alignItems: 'center',
  },
  editBtnText: { fontSize: font.base, fontWeight: font.bold, color: '#fff' },
  publishBtn: {
    backgroundColor: colors.successBg,
    borderWidth: 1,
    borderColor: colors.successBorder,
    borderRadius: radius.lg,
    paddingVertical: spacing.sm + 4,
    alignItems: 'center',
  },
  publishBtnText: { fontSize: font.base, fontWeight: font.semibold, color: colors.successLight },
  deleteBtn: {
    backgroundColor: colors.dangerBg,
    borderWidth: 1,
    borderColor: colors.dangerBorder,
    borderRadius: radius.lg,
    paddingVertical: spacing.sm + 4,
    alignItems: 'center',
  },
  deleteBtnText: { fontSize: font.base, fontWeight: font.semibold, color: '#f87171' },
  btnDisabled: { opacity: 0.55 },

  // Error
  errorIcon: { fontSize: 36, opacity: 0.5 },
  errorText: { fontSize: font.base, color: colors.textSecondary, textAlign: 'center' },
  retryBtn: {
    backgroundColor: colors.primary,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.sm + 2,
    borderRadius: radius.full,
  },
  retryText: { fontSize: font.base, fontWeight: font.bold, color: '#fff' },
});