/**
 * app/(tabs)/knowledge-base/[slug].tsx  —  Knowledge Base Article Show
 *
 * Mirrors: resources/js/Pages/User/KnowledgeBaseShow.vue
 *
 * Fetches GET /api/knowledge-base/{slug} on mount (public, no auth needed).
 * Response: { article, related }
 *
 * Sections:
 *   1. Article header  — category badge, title, author, date, views
 *   2. Excerpt         — highlighted left-bordered block if present
 *   3. Article body    — full content (white-space preserved)
 *   4. Helpfulness     — 👍 / 👎 buttons, optimistic update
 *                        POST /api/knowledge-base/{slug}/helpful|not-helpful
 *   5. Related articles — tappable list → pushes new [slug] route
 *   6. Help CTA        — Submit a Ticket → /(tabs)/report
 *   Pull-to-refresh.
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
import { router, useLocalSearchParams } from 'expo-router';
import { colors, spacing, radius, font } from '@/constants/theme';
import { apiUrl } from '@/constants/api';

// ─── Types ────────────────────────────────────────────────────────────────────

type Article = {
  id:                     number;
  title:                  string;
  slug:                   string;
  content:                string;
  excerpt:                string | null;
  category:               string;
  views:                  number;
  helpful_count:          number;
  not_helpful_count:      number;
  helpfulness_percentage: number;
  published_at:           string;
  author?: { full_name: string };
};

type RelatedArticle = {
  id:       number;
  title:    string;
  slug:     string;
  category: string;
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

function categoryLabel(key: string): string {
  return key.charAt(0).toUpperCase() + key.slice(1);
}

function formatDate(iso: string): string {
  return new Date(iso).toLocaleDateString('en-US', {
    month: 'long', day: 'numeric', year: 'numeric',
  });
}

// ─── Category Badge ───────────────────────────────────────────────────────────

function CategoryBadge({ category }: { category: string }) {
  const c = CATEGORY_COLORS[category] ?? CATEGORY_COLORS.general;
  return (
    <View style={[badge.wrap, { backgroundColor: c.bg, borderColor: c.border }]}>
      <Text style={[badge.text, { color: c.text }]}>
        {CATEGORY_ICONS[category] ?? '📄'} {categoryLabel(category)}
      </Text>
    </View>
  );
}

const badge = StyleSheet.create({
  wrap: {
    borderWidth: 1,
    borderRadius: radius.full,
    paddingHorizontal: spacing.sm + 2,
    paddingVertical: 4,
    alignSelf: 'flex-start',
  },
  text: { fontSize: font.xs, fontWeight: font.semibold },
});

// ─── Component ────────────────────────────────────────────────────────────────

export default function KnowledgeBaseShowScreen() {
  const { slug } = useLocalSearchParams<{ slug: string }>();

  const [article,    setArticle]    = useState<Article | null>(null);
  const [related,    setRelated]    = useState<RelatedArticle[]>([]);
  const [loading,    setLoading]    = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error,      setError]      = useState<string | null>(null);

  // Optimistic vote state
  const [helpfulCount,    setHelpfulCount]    = useState(0);
  const [notHelpfulCount, setNotHelpfulCount] = useState(0);
  const [voted,           setVoted]           = useState<'helpful' | 'not-helpful' | null>(null);

  // ── Fetch ─────────────────────────────────────────────────────────────────
  const fetchArticle = useCallback(async () => {
    try {
      const res = await fetch(apiUrl(`knowledge-base/${slug}`), {
        headers: { 'Accept': 'application/json' },
      });
      if (res.status === 404) throw new Error('Article not found.');
      if (!res.ok) throw new Error(`Server error ${res.status}`);

      const data: { article: Article; related: RelatedArticle[] } = await res.json();
      setArticle(data.article);
      setRelated(data.related ?? []);
      setHelpfulCount(data.article.helpful_count);
      setNotHelpfulCount(data.article.not_helpful_count);
      setError(null);
    } catch (e: any) {
      setError(e.message ?? 'Could not load article.');
    }
  }, [slug]);

  useEffect(() => {
    setLoading(true);
    fetchArticle().finally(() => setLoading(false));
  }, [fetchArticle]);

  const onRefresh = useCallback(async () => {
    setRefreshing(true);
    await fetchArticle();
    setRefreshing(false);
  }, [fetchArticle]);

  // ── Helpfulness voting ────────────────────────────────────────────────────
  async function vote(type: 'helpful' | 'not-helpful') {
    if (voted) return; // one vote per session

    // Optimistic update
    if (type === 'helpful') {
      setHelpfulCount(n => n + 1);
    } else {
      setNotHelpfulCount(n => n + 1);
    }
    setVoted(type);

    try {
      await fetch(apiUrl(`knowledge-base/${slug}/${type}`), {
        method:  'POST',
        headers: { 'Accept': 'application/json' },
      });
    } catch {
      // Revert on failure
      if (type === 'helpful') {
        setHelpfulCount(n => n - 1);
      } else {
        setNotHelpfulCount(n => n - 1);
      }
      setVoted(null);
    }
  }

  const totalVotes = helpfulCount + notHelpfulCount;
  const helpfulPct = totalVotes > 0
    ? Math.round((helpfulCount / totalVotes) * 100)
    : 0;

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

  if (error || !article) {
    return (
      <SafeAreaView style={s.root}>
        <StatusBar barStyle="light-content" backgroundColor={colors.bgPrimary} />
        <View style={s.centered}>
          <Text style={s.errorIcon}>📄</Text>
          <Text style={s.errorText}>{error ?? 'Article not found.'}</Text>
          <TouchableOpacity
            style={s.retryBtn}
            onPress={() => router.back()}
          >
            <Text style={s.retryText}>← Go Back</Text>
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
        <TouchableOpacity
          onPress={() => router.push('/(tabs)/knowledge-base' as any)}
          activeOpacity={0.7}
        >
          <Text style={s.navBack}>← Knowledge Base</Text>
        </TouchableOpacity>
        <View style={{ width: 100 }} />
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
        {/* ── Article Header Card ── */}
        <View style={s.headerCard}>
          {/* Category badge + views */}
          <View style={s.headerTopRow}>
            <CategoryBadge category={article.category} />
            <Text style={s.viewsText}>
              👁️ {article.views.toLocaleString()} views
            </Text>
          </View>

          {/* Title */}
          <Text style={s.articleTitle}>{article.title}</Text>

          {/* Author + date */}
          <View style={s.metaRow}>
            <Text style={s.metaText}>
              ✍️ {article.author?.full_name ?? 'IT Support'}
            </Text>
            <Text style={s.metaDot}>·</Text>
            <Text style={s.metaText}>
              📅 {formatDate(article.published_at)}
            </Text>
          </View>
        </View>

        {/* ── Excerpt ── */}
        {!!article.excerpt && (
          <View style={s.excerptCard}>
            <View style={s.excerptAccent} />
            <Text style={s.excerptText}>{article.excerpt}</Text>
          </View>
        )}

        {/* ── Article Body ── */}
        <View style={s.card}>
          <Text style={s.contentText}>{article.content}</Text>
        </View>

        {/* ── Helpfulness Rating ── */}
        <View style={s.helpfulCard}>
          <Text style={s.helpfulQuestion}>Was this article helpful?</Text>

          <View style={s.helpfulBtns}>
            {/* Yes button */}
            <TouchableOpacity
              style={[
                s.yesBtn,
                voted === 'helpful' && s.yesBtnActive,
                voted && voted !== 'helpful' && s.btnDimmed,
              ]}
              onPress={() => vote('helpful')}
              disabled={!!voted}
              activeOpacity={0.75}
            >
              <Text style={[
                s.yesBtnText,
                voted === 'helpful' && { color: colors.primary },
              ]}>
                👍 Yes ({helpfulCount})
              </Text>
            </TouchableOpacity>

            {/* No button */}
            <TouchableOpacity
              style={[
                s.noBtn,
                voted === 'not-helpful' && s.noBtnActive,
                voted && voted !== 'not-helpful' && s.btnDimmed,
              ]}
              onPress={() => vote('not-helpful')}
              disabled={!!voted}
              activeOpacity={0.75}
            >
              <Text style={[
                s.noBtnText,
                voted === 'not-helpful' && { color: colors.danger },
              ]}>
                👎 No ({notHelpfulCount})
              </Text>
            </TouchableOpacity>
          </View>

          {/* Thank you message after voting */}
          {voted && (
            <Text style={s.votedText}>Thanks for your feedback! 🙏</Text>
          )}

          {/* Helpfulness bar */}
          {totalVotes > 0 && (
            <View style={s.helpfulBarWrap}>
              <View style={s.helpfulBarTrack}>
                <View style={[s.helpfulBarFill, { width: `${helpfulPct}%` }]} />
              </View>
              <Text style={s.helpfulPctText}>
                {helpfulPct}% of readers found this helpful
              </Text>
            </View>
          )}
        </View>

        {/* ── Related Articles ── */}
        {related.length > 0 && (
          <View style={s.card}>
            <Text style={s.cardTitle}>Related Articles</Text>
            {related.map((rel, i) => {
              const c = CATEGORY_COLORS[rel.category] ?? CATEGORY_COLORS.general;
              return (
                <TouchableOpacity
                  key={rel.id}
                  style={[
                    s.relatedItem,
                    i === related.length - 1 && { borderBottomWidth: 0 },
                  ]}
                  onPress={() =>
                    router.push(`/(tabs)/knowledge-base/${rel.slug}` as any)
                  }
                  activeOpacity={0.75}
                >
                  <Text style={[s.relatedIcon, { color: c.text }]}>
                    {CATEGORY_ICONS[rel.category] ?? '📄'}
                  </Text>
                  <Text style={s.relatedTitle} numberOfLines={2}>
                    {rel.title}
                  </Text>
                  <Text style={s.relatedArrow}>→</Text>
                </TouchableOpacity>
              );
            })}
          </View>
        )}

        {/* ── Help CTA ── */}
        <View style={s.helpBanner}>
          <Text style={s.helpTitle}>Still having issues?</Text>
          <Text style={s.helpText}>
            Submit a support ticket and our IT team will help you directly.
          </Text>
          <TouchableOpacity
            style={s.helpBtn}
            onPress={() => router.push('/(tabs)/report' as any)}
            activeOpacity={0.85}
          >
            <Text style={s.helpBtnText}>Submit a Ticket</Text>
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
  navBack: {
    fontSize: font.sm,
    fontWeight: font.semibold,
    color: colors.primary,
  },

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
  headerTopRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  viewsText: {
    fontSize: font.xs,
    color: colors.textMuted,
  },
  articleTitle: {
    fontSize: font.xl,
    fontWeight: font.bold,
    color: colors.textPrimary,
    lineHeight: 28,
  },
  metaRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    flexWrap: 'wrap',
  },
  metaText: { fontSize: font.xs, color: colors.textMuted },
  metaDot:  { fontSize: font.xs, color: colors.textMuted },

  // Excerpt
  excerptCard: {
    marginHorizontal: spacing.lg,
    marginBottom: spacing.md,
    flexDirection: 'row',
    backgroundColor: 'rgba(45,212,191,0.06)',
    borderWidth: 1,
    borderColor: 'rgba(45,212,191,0.2)',
    borderRadius: radius.lg,
    overflow: 'hidden',
  },
  excerptAccent: {
    width: 3,
    backgroundColor: colors.primary,
  },
  excerptText: {
    flex: 1,
    fontSize: font.sm,
    color: colors.textSecondary,
    lineHeight: 21,
    padding: spacing.md,
    fontStyle: 'italic',
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

  // Article content — white-space preserved via lineHeight
  contentText: {
    fontSize: font.base,
    color: colors.textSecondary,
    lineHeight: 26,
  },

  // Helpfulness
  helpfulCard: {
    marginHorizontal: spacing.lg,
    marginBottom: spacing.md,
    backgroundColor: colors.bgCard,
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radius.xl,
    padding: spacing.lg,
    gap: spacing.md,
  },
  helpfulQuestion: {
    fontSize: font.base,
    fontWeight: font.semibold,
    color: colors.textSecondary,
  },
  helpfulBtns: {
    flexDirection: 'row',
    gap: spacing.sm,
  },
  yesBtn: {
    flex: 1,
    backgroundColor: colors.bgCard,
    borderWidth: 1.5,
    borderColor: colors.border,
    borderRadius: radius.lg,
    paddingVertical: spacing.sm + 4,
    alignItems: 'center',
  },
  yesBtnActive: {
    borderColor: colors.primary,
    backgroundColor: colors.primaryLight,
  },
  yesBtnText: {
    fontSize: font.sm,
    fontWeight: font.semibold,
    color: colors.textSecondary,
  },
  noBtn: {
    flex: 1,
    backgroundColor: colors.bgCard,
    borderWidth: 1.5,
    borderColor: colors.border,
    borderRadius: radius.lg,
    paddingVertical: spacing.sm + 4,
    alignItems: 'center',
  },
  noBtnActive: {
    borderColor: colors.dangerBorder,
    backgroundColor: colors.dangerBg,
  },
  noBtnText: {
    fontSize: font.sm,
    fontWeight: font.semibold,
    color: colors.textSecondary,
  },
  btnDimmed: { opacity: 0.5 },
  votedText: {
    fontSize: font.sm,
    color: colors.primary,
    textAlign: 'center',
    fontWeight: font.semibold,
  },
  helpfulBarWrap: { gap: spacing.xs },
  helpfulBarTrack: {
    height: 5,
    backgroundColor: 'rgba(255,255,255,0.08)',
    borderRadius: 3,
    overflow: 'hidden',
  },
  helpfulBarFill: {
    height: '100%',
    backgroundColor: colors.primary,
    borderRadius: 3,
  },
  helpfulPctText: {
    fontSize: font.xs,
    color: colors.textMuted,
    textAlign: 'center',
  },

  // Related articles
  relatedItem: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingVertical: spacing.sm + 2,
    borderBottomWidth: 1,
    borderBottomColor: 'rgba(45,212,191,0.07)',
    gap: spacing.sm,
  },
  relatedIcon:  { fontSize: 16, width: 22, textAlign: 'center' },
  relatedTitle: {
    flex: 1,
    fontSize: font.sm,
    color: colors.textSecondary,
    lineHeight: 19,
  },
  relatedArrow: {
    fontSize: font.sm,
    color: colors.primary,
    fontWeight: font.bold,
  },

  // Help banner
  helpBanner: {
    marginHorizontal: spacing.lg,
    backgroundColor: colors.primaryLight,
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radius.xl,
    padding: spacing.lg,
    alignItems: 'center',
    gap: spacing.sm,
  },
  helpTitle: {
    fontSize: font.lg,
    fontWeight: font.bold,
    color: colors.primary,
  },
  helpText: {
    fontSize: font.sm,
    color: colors.textMuted,
    textAlign: 'center',
    lineHeight: 19,
  },
  helpBtn: {
    width: '100%',
    backgroundColor: colors.primary,
    borderRadius: radius.lg,
    paddingVertical: spacing.sm + 4,
    alignItems: 'center',
    marginTop: spacing.xs,
  },
  helpBtnText: {
    fontSize: font.base,
    fontWeight: font.bold,
    color: '#fff',
  },

  // Error / states
  errorIcon: { fontSize: 36, opacity: 0.5 },
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