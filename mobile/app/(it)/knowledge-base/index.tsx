/**
 * app/(it)/knowledge-base/index.tsx  —  Knowledge Base Management
 *
 * Mirrors: resources/js/Pages/IT/KnowledgeBase/Index.vue
 *
 * Fetches GET /api/it/articles?search=&status=&category=&page=
 * Returns: { articles (paginated), stats }
 *
 * Features:
 *   - 4-stat row: Total · Published · Drafts · Total Views
 *   - Search bar (debounced 350ms)
 *   - Filter tabs: All · Published · Drafts · Hardware · Software · Network
 *   - Article cards with: title, status badge, category, views, helpfulness %,
 *     author, updated time, Edit · View · Delete actions
 *   - Delete confirmation alert → DELETE /api/it/articles/{id}
 *   - Floating "+ Create Article" button → knowledge-base/create
 *   - Pull-to-refresh
 *   - Pagination
 */

import React, {
  useEffect, useState, useCallback, useRef,
} from 'react';
import {
  View, Text, FlatList, TouchableOpacity,
  TextInput, StyleSheet, StatusBar, ActivityIndicator,
  RefreshControl, Alert, ScrollView,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { router } from 'expo-router';
import { colors, spacing, radius, font } from '@/constants/theme';
import { loadAuth } from '@/utils/auth';
import { apiUrl } from '@/constants/api';

// ─── Types ────────────────────────────────────────────────────────────────────

type Article = {
  id:                 number;
  title:              string;
  slug:               string;
  category:           string;
  status:             'draft' | 'published';
  views:              number;
  helpful_count:      number;
  not_helpful_count:  number;
  helpfulness_percentage: number;
  updated_at:         string;
  published_at:       string | null;
  author?: { full_name: string };
};

type Stats = {
  total:       number;
  published:   number;
  drafts:      number;
  total_views: number;
};

type ArticlesResponse = {
  articles: {
    data:          Article[];
    current_page:  number;
    last_page:     number;
    total:         number;
    from:          number | null;
    to:            number | null;
    prev_page_url: string | null;
    next_page_url: string | null;
  };
  stats: Stats;
};

type StatusFilter   = '' | 'published' | 'draft';
type CategoryFilter = 'all' | 'hardware' | 'software' | 'network' | 'display' | 'peripherals' | 'general';

// ─── Helpers ─────────────────────────────────────────────────────────────────

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

const CATEGORY_ICONS: Record<string, string> = {
  hardware: '🔧', software: '💾', network: '🌐',
  display: '🖥️', peripherals: '🖱️', general: '📋',
};

// ─── Article Card ─────────────────────────────────────────────────────────────

function ArticleCard({
  article,
  onDelete,
  deleting,
}: {
  article:  Article;
  onDelete: () => void;
  deleting: boolean;
}) {
  const isPublished = article.status === 'published';

  return (
    <View style={[card.wrap, !isPublished && card.wrapDraft]}>
      {/* Top: title + status badge */}
      <View style={card.top}>
        <Text style={card.title} numberOfLines={2}>{article.title}</Text>
        <View style={[
          card.statusBadge,
          isPublished
            ? { backgroundColor: colors.successBg, borderColor: colors.successBorder }
            : { backgroundColor: colors.warningBg, borderColor: colors.warningBorder },
        ]}>
          <Text style={[
            card.statusText,
            { color: isPublished ? colors.successLight : colors.warningLight },
          ]}>
            {isPublished ? 'Published' : 'Draft'}
          </Text>
        </View>
      </View>

      {/* Meta row */}
      <View style={card.metaRow}>
        <Text style={card.meta}>
          {CATEGORY_ICONS[article.category] ?? '📄'}{' '}
          {article.category.charAt(0).toUpperCase() + article.category.slice(1)}
        </Text>
        <Text style={card.meta}>👁️ {formatViews(article.views)} views</Text>
        <Text style={card.meta}>👍 {article.helpfulness_percentage}% helpful</Text>
      </View>
      <View style={card.metaRow}>
        <Text style={card.meta}>
          ✏️ {article.author?.full_name ?? 'Unknown'}
        </Text>
        <Text style={card.meta}>📅 {diffForHumans(article.updated_at)}</Text>
      </View>

      {/* Action buttons */}
      <View style={card.actions}>
        <TouchableOpacity
          style={card.editBtn}
          onPress={() => router.push(`/(it)/knowledge-base/${article.id}/edit` as any)}
          activeOpacity={0.8}
        >
          <Text style={card.editBtnText}>Edit</Text>
        </TouchableOpacity>

        <TouchableOpacity
          style={card.viewBtn}
          onPress={() => router.push(`/(it)/knowledge-base/${article.id}` as any)}
          activeOpacity={0.8}
        >
          <Text style={card.viewBtnText}>
            {isPublished ? 'View' : 'Preview'}
          </Text>
        </TouchableOpacity>

        <TouchableOpacity
          style={[card.deleteBtn, deleting && { opacity: 0.5 }]}
          onPress={onDelete}
          disabled={deleting}
          activeOpacity={0.8}
        >
          {deleting
            ? <ActivityIndicator size="small" color="#f87171" />
            : <Text style={card.deleteBtnText}>Delete</Text>}
        </TouchableOpacity>
      </View>
    </View>
  );
}

const card = StyleSheet.create({
  wrap: {
    backgroundColor: colors.bgCard,
    borderWidth: 1,
    borderColor: colors.border,
    borderLeftWidth: 3,
    borderLeftColor: colors.primary,
    borderRadius: radius.xl,
    padding: spacing.md,
    marginBottom: spacing.sm,
    gap: spacing.xs + 1,
  },
  wrapDraft: {
    borderLeftColor: colors.warning,
  },
  top: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: spacing.sm,
  },
  title: {
    flex: 1,
    fontSize: font.base,
    fontWeight: font.semibold,
    color: colors.textPrimary,
    lineHeight: 21,
  },
  statusBadge: {
    borderWidth: 1,
    borderRadius: radius.full,
    paddingHorizontal: 8,
    paddingVertical: 3,
    flexShrink: 0,
    alignSelf: 'flex-start',
  },
  statusText: { fontSize: font.xs - 1, fontWeight: font.bold },
  metaRow:    { flexDirection: 'row', gap: spacing.md, flexWrap: 'wrap' },
  meta:       { fontSize: font.xs, color: colors.textMuted },
  actions:    { flexDirection: 'row', gap: spacing.xs + 2, marginTop: spacing.xs },
  editBtn: {
    backgroundColor: colors.primary,
    borderRadius: radius.md,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.xs + 2,
  },
  editBtnText: { fontSize: font.xs, fontWeight: font.bold, color: '#fff' },
  viewBtn: {
    backgroundColor: 'rgba(255,255,255,0.06)',
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radius.md,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.xs + 2,
  },
  viewBtnText: { fontSize: font.xs, fontWeight: font.semibold, color: colors.textSecondary },
  deleteBtn: {
    backgroundColor: colors.dangerBg,
    borderWidth: 1,
    borderColor: colors.dangerBorder,
    borderRadius: radius.md,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.xs + 2,
    minWidth: 60,
    alignItems: 'center',
  },
  deleteBtnText: { fontSize: font.xs, fontWeight: font.semibold, color: '#f87171' },
});

// ─── Filter Tab ───────────────────────────────────────────────────────────────

function FilterTab({ label, active, onPress }: {
  label: string; active: boolean; onPress: () => void;
}) {
  return (
    <TouchableOpacity
      style={[ft.tab, active && ft.tabActive]}
      onPress={onPress}
      activeOpacity={0.75}
    >
      <Text style={[ft.text, active && ft.textActive]}>{label}</Text>
    </TouchableOpacity>
  );
}

const ft = StyleSheet.create({
  tab: {
    paddingHorizontal: spacing.md, paddingVertical: spacing.xs + 3,
    borderRadius: radius.full, backgroundColor: colors.bgCard,
    borderWidth: 1, borderColor: colors.border, marginRight: spacing.xs,
  },
  tabActive:  { backgroundColor: colors.primaryLight, borderColor: colors.primary },
  text:       { fontSize: font.xs, color: colors.textMuted, fontWeight: font.medium },
  textActive: { color: colors.primary, fontWeight: font.semibold },
});

// ─── Main Component ───────────────────────────────────────────────────────────

export default function ITKBIndexScreen() {
  const [token,       setToken]       = useState<string | null>(null);
  const [articles,    setArticles]    = useState<Article[]>([]);
  const [stats,       setStats]       = useState<Stats | null>(null);
  const [pagination,  setPagination]  = useState<Omit<ArticlesResponse['articles'], 'data'> | null>(null);
  const [loading,     setLoading]     = useState(true);
  const [refreshing,  setRefreshing]  = useState(false);
  const [pageLoading, setPageLoading] = useState(false);
  const [error,       setError]       = useState<string | null>(null);
  const [deletingId,  setDeletingId]  = useState<number | null>(null);

  // Filters
  const [search,   setSearch]   = useState('');
  const [status,   setStatus]   = useState<StatusFilter>('');
  const [category, setCategory] = useState<CategoryFilter>('all');
  const [page,     setPage]     = useState(1);

  const searchTimer = useRef<ReturnType<typeof setTimeout> | null>(null);

  // ── Auth ──────────────────────────────────────────────────────────────────
  useEffect(() => {
    loadAuth().then((auth) => {
      if (!auth) { router.replace('/login'); return; }
      setToken(auth.token);
    });
  }, []);

  // ── Fetch ─────────────────────────────────────────────────────────────────
  const fetchArticles = useCallback(async (
    tok: string,
    opts: { search?: string; status?: StatusFilter; category?: CategoryFilter; page?: number } = {}
  ) => {
    const params = new URLSearchParams();
    if (opts.search)                               params.set('search',   opts.search);
    if (opts.status)                               params.set('status',   opts.status);
    if (opts.category && opts.category !== 'all')  params.set('category', opts.category);
    if (opts.page && opts.page > 1)                params.set('page',     String(opts.page));

    try {
      const res = await fetch(`${apiUrl('it/articles')}?${params}`, {
        headers: { 'Accept': 'application/json', 'Authorization': `Bearer ${tok}` },
      });
      if (res.status === 401) { router.replace('/login'); return; }
      if (!res.ok) throw new Error(`Server error ${res.status}`);
      const json: ArticlesResponse = await res.json();
      setArticles(json.articles.data);
      setStats(json.stats);
      const { data: _, ...meta } = json.articles;
      setPagination(meta);
      setError(null);
    } catch (e: any) {
      setError(e.message ?? 'Could not load articles.');
    }
  }, []);

  // Initial load
  useEffect(() => {
    if (!token) return;
    setLoading(true);
    fetchArticles(token).finally(() => setLoading(false));
  }, [token]); // eslint-disable-line

  // Search debounce
  useEffect(() => {
    if (!token) return;
    if (searchTimer.current) clearTimeout(searchTimer.current);
    searchTimer.current = setTimeout(() => {
      setPage(1);
      setLoading(true);
      fetchArticles(token, { search, status, category, page: 1 })
        .finally(() => setLoading(false));
    }, 350);
    return () => { if (searchTimer.current) clearTimeout(searchTimer.current); };
  }, [search]); // eslint-disable-line

  function applyFilters(overrides: Partial<{
    status: StatusFilter; category: CategoryFilter;
  }>) {
    if (!token) return;
    const next = { search, status, category, page: 1, ...overrides };
    setPage(1);
    setLoading(true);
    fetchArticles(token, next).finally(() => setLoading(false));
  }

  function handlePage(p: number) {
    if (!token) return;
    setPage(p);
    setPageLoading(true);
    fetchArticles(token, { search, status, category, page: p })
      .finally(() => setPageLoading(false));
  }

  const onRefresh = useCallback(async () => {
    if (!token) return;
    setRefreshing(true);
    await fetchArticles(token, { search, status, category, page: 1 });
    setPage(1);
    setRefreshing(false);
  }, [token, search, status, category, fetchArticles]);

  // ── Delete ────────────────────────────────────────────────────────────────
  function confirmDelete(article: Article) {
    Alert.alert(
      'Delete Article',
      `Are you sure you want to delete "${article.title}"? This cannot be undone.`,
      [
        { text: 'Cancel', style: 'cancel' },
        {
          text: 'Delete',
          style: 'destructive',
          onPress: () => handleDelete(article.id),
        },
      ]
    );
  }

  async function handleDelete(articleId: number) {
    if (!token) return;
    setDeletingId(articleId);
    try {
      const res = await fetch(apiUrl(`it/articles/${articleId}`), {
        method:  'DELETE',
        headers: { 'Accept': 'application/json', 'Authorization': `Bearer ${token}` },
      });
      if (!res.ok) throw new Error('Failed to delete article.');
      // Remove from list immediately
      setArticles(prev => prev.filter(a => a.id !== articleId));
      // Update stats
      setStats(prev => prev ? { ...prev, total: prev.total - 1 } : prev);
    } catch {
      Alert.alert('Error', 'Could not delete article. Please try again.');
    } finally {
      setDeletingId(null);
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

  // ─── Main render ──────────────────────────────────────────────────────────
  return (
    <SafeAreaView style={s.root} edges={['top']}>
      <StatusBar barStyle="light-content" backgroundColor={colors.bgPrimary} />

      {/* ── Top Nav ── */}
      <View style={s.nav}>
        <Text style={s.navLogo}>LabFix</Text>
        <Text style={s.navTitle}>KB Management</Text>
        <TouchableOpacity
          style={s.createBtn}
          onPress={() => router.push('/(it)/knowledge-base/create' as any)}
          activeOpacity={0.85}
        >
          <Text style={s.createBtnText}>+ Create</Text>
        </TouchableOpacity>
      </View>

      <FlatList
        data={articles}
        keyExtractor={(a) => String(a.id)}
        showsVerticalScrollIndicator={false}
        contentContainerStyle={s.listContent}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={onRefresh}
            tintColor={colors.primary}
            colors={[colors.primary]}
          />
        }
        ListHeaderComponent={
          <>
            {/* Page header */}
            <View style={s.pageHeader}>
              <Text style={s.pageTitle}>Knowledge Base Mgmt</Text>
              <Text style={s.pageSub}>Create and manage troubleshooting articles</Text>
            </View>

            {/* Stats row */}
            {stats && (
              <View style={s.statsRow}>
                {[
                  { label: 'Total',      value: stats.total,       color: colors.primary        },
                  { label: 'Published',  value: stats.published,   color: colors.successLight   },
                  { label: 'Drafts',     value: stats.drafts,      color: colors.warningLight   },
                  { label: 'Views',      value: formatViews(stats.total_views), color: colors.primary },
                ].map((st) => (
                  <View key={st.label} style={s.statCard}>
                    <Text style={s.statLabel}>{st.label}</Text>
                    <Text style={[s.statValue, { color: st.color }]}>{st.value}</Text>
                  </View>
                ))}
              </View>
            )}

            {/* Search */}
            <View style={s.searchBar}>
              <Text style={s.searchIcon}>🔍</Text>
              <TextInput
                style={s.searchInput}
                value={search}
                onChangeText={setSearch}
                placeholder="Search articles…"
                placeholderTextColor={colors.textDisabled}
                returnKeyType="search"
                clearButtonMode="while-editing"
              />
            </View>

            {/* Filter tabs row 1 — status + some categories */}
            <ScrollView
              horizontal
              showsHorizontalScrollIndicator={false}
              contentContainerStyle={s.filterRow}
            >
              <FilterTab
                label="All"
                active={!status && category === 'all'}
                onPress={() => {
                  setStatus(''); setCategory('all');
                  applyFilters({ status: '', category: 'all' });
                }}
              />
              <FilterTab
                label="Published"
                active={status === 'published'}
                onPress={() => {
                  setStatus('published'); setCategory('all');
                  applyFilters({ status: 'published', category: 'all' });
                }}
              />
              <FilterTab
                label="Drafts"
                active={status === 'draft'}
                onPress={() => {
                  setStatus('draft'); setCategory('all');
                  applyFilters({ status: 'draft', category: 'all' });
                }}
              />
              {(['hardware', 'software', 'network', 'display', 'peripherals', 'general'] as CategoryFilter[]).map((cat) => (
                <FilterTab
                  key={cat}
                  label={`${CATEGORY_ICONS[cat]} ${cat.charAt(0).toUpperCase() + cat.slice(1)}`}
                  active={category === cat}
                  onPress={() => {
                    setStatus(''); setCategory(cat);
                    applyFilters({ status: '', category: cat });
                  }}
                />
              ))}
            </ScrollView>

            {/* Error */}
            {error && (
              <View style={s.errorBanner}>
                <Text style={s.errorBannerText}>{error}</Text>
              </View>
            )}
          </>
        }
        ListEmptyComponent={
          <View style={s.emptyState}>
            <Text style={s.emptyIcon}>📚</Text>
            <Text style={s.emptyTitle}>No articles yet</Text>
            <Text style={s.emptySub}>
              {search || status || category !== 'all'
                ? 'Try adjusting your search or filters'
                : 'Create your first knowledge base article'}
            </Text>
            <TouchableOpacity
              style={s.emptyBtn}
              onPress={() => router.push('/(it)/knowledge-base/create' as any)}
              activeOpacity={0.85}
            >
              <Text style={s.emptyBtnText}>+ Create Article</Text>
            </TouchableOpacity>
          </View>
        }
        ListFooterComponent={
          <>
            {/* Pagination */}
            {pagination && pagination.last_page > 1 && (
              <View style={s.pagination}>
                <Text style={s.pageInfo}>
                  {pagination.from ?? 0}–{pagination.to ?? 0} of {pagination.total} articles
                </Text>
                <View style={s.pageControls}>
                  <TouchableOpacity
                    style={[s.pageBtn, !pagination.prev_page_url && s.pageBtnDisabled]}
                    onPress={() => handlePage(page - 1)}
                    disabled={!pagination.prev_page_url || pageLoading}
                  >
                    <Text style={s.pageBtnText}>← Prev</Text>
                  </TouchableOpacity>
                  <View style={s.pageCurrent}>
                    <Text style={s.pageCurrentText}>{page}</Text>
                  </View>
                  <TouchableOpacity
                    style={[s.pageBtn, !pagination.next_page_url && s.pageBtnDisabled]}
                    onPress={() => handlePage(page + 1)}
                    disabled={!pagination.next_page_url || pageLoading}
                  >
                    <Text style={s.pageBtnText}>Next →</Text>
                  </TouchableOpacity>
                </View>
                {pageLoading && (
                  <ActivityIndicator size="small" color={colors.primary} />
                )}
              </View>
            )}
            <View style={{ height: spacing.xxl }} />
          </>
        }
        renderItem={({ item }) => (
          <ArticleCard
            article={item}
            onDelete={() => confirmDelete(item)}
            deleting={deletingId === item.id}
          />
        )}
      />
    </SafeAreaView>
  );
}

// ─── Styles ───────────────────────────────────────────────────────────────────

const s = StyleSheet.create({
  root:    { flex: 1, backgroundColor: colors.bgPrimary },
  centered:{ flex: 1, alignItems: 'center', justifyContent: 'center', gap: spacing.md },

  // Nav
  nav: {
    flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center',
    paddingHorizontal: spacing.lg, paddingVertical: spacing.sm + 4,
    borderBottomWidth: 1, borderBottomColor: colors.border,
  },
  navLogo:  { fontSize: font.xl, fontWeight: font.bold, color: colors.primary, letterSpacing: -0.5, width: 60 },
  navTitle: { fontSize: font.base, fontWeight: font.bold, color: colors.textPrimary },
  createBtn: {
    backgroundColor: colors.primary, borderRadius: radius.full,
    paddingHorizontal: spacing.md, paddingVertical: spacing.xs + 2,
  },
  createBtnText: { fontSize: font.sm, fontWeight: font.bold, color: '#fff' },

  listContent: { paddingHorizontal: spacing.lg, paddingBottom: spacing.lg },

  // Page header
  pageHeader: { paddingTop: spacing.lg, marginBottom: spacing.md },
  pageTitle:  { fontSize: font.xl, fontWeight: font.bold, color: colors.textPrimary },
  pageSub:    { fontSize: font.sm, color: colors.textMuted, marginTop: 2 },

  // Stats
  statsRow: { flexDirection: 'row', gap: spacing.xs, marginBottom: spacing.md },
  statCard: {
    flex: 1, backgroundColor: colors.bgCard, borderWidth: 1, borderColor: colors.border,
    borderLeftWidth: 3, borderLeftColor: colors.primary, borderRadius: radius.lg,
    padding: spacing.sm, alignItems: 'center',
  },
  statLabel: { fontSize: font.xs - 2, color: colors.textMuted, textAlign: 'center', marginBottom: 2 },
  statValue: { fontSize: font.lg, fontWeight: font.bold },

  // Search
  searchBar: {
    flexDirection: 'row', alignItems: 'center', backgroundColor: colors.bgCard,
    borderWidth: 1, borderColor: colors.border, borderRadius: radius.lg,
    paddingHorizontal: spacing.md, paddingVertical: spacing.sm + 2,
    gap: spacing.sm, marginBottom: spacing.sm,
  },
  searchIcon:  { fontSize: 15 },
  searchInput: { flex: 1, fontSize: font.base, color: colors.textPrimary },

  // Filter row
  filterRow: { paddingBottom: spacing.md, gap: 0 },

  // Error
  errorBanner: {
    backgroundColor: colors.dangerBg, borderWidth: 1, borderColor: colors.dangerBorder,
    borderRadius: radius.md, padding: spacing.sm + 2, marginBottom: spacing.md,
  },
  errorBannerText: { color: '#fca5a5', fontSize: font.sm },

  // Empty state
  emptyState: { alignItems: 'center', paddingVertical: spacing.xxl, gap: spacing.sm },
  emptyIcon:  { fontSize: 36, opacity: 0.35 },
  emptyTitle: { fontSize: font.lg, fontWeight: font.semibold, color: colors.textSecondary },
  emptySub:   { fontSize: font.sm, color: colors.textMuted, textAlign: 'center' },
  emptyBtn: {
    marginTop: spacing.sm, backgroundColor: colors.primary,
    paddingHorizontal: spacing.xl, paddingVertical: spacing.sm + 4, borderRadius: radius.full,
  },
  emptyBtnText: { fontSize: font.base, fontWeight: font.bold, color: '#fff' },

  // Pagination
  pagination:      { alignItems: 'center', paddingVertical: spacing.lg, gap: spacing.sm },
  pageInfo:        { fontSize: font.sm, color: colors.textMuted },
  pageControls:    { flexDirection: 'row', alignItems: 'center', gap: spacing.sm },
  pageBtn:         { paddingHorizontal: spacing.md, paddingVertical: spacing.xs + 2, borderRadius: radius.md, backgroundColor: colors.bgCard, borderWidth: 1, borderColor: colors.border },
  pageBtnDisabled: { opacity: 0.4 },
  pageBtnText:     { fontSize: font.sm, color: colors.textSecondary },
  pageCurrent:     { width: 32, height: 32, borderRadius: radius.full, backgroundColor: colors.primary, alignItems: 'center', justifyContent: 'center' },
  pageCurrentText: { fontSize: font.sm, fontWeight: font.bold, color: '#fff' },
});