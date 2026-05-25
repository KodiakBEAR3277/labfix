/**
 * app/(tabs)/knowledge-base.tsx  —  Knowledge Base Index
 *
 * Mirrors: resources/js/Pages/User/KnowledgeBase.vue
 *
 * Features:
 *   - Hero search bar (debounced 350ms)
 *   - Category chips (Hardware, Software, Network, Display, Peripherals, General)
 *     with article counts — tap to filter, tap again to clear
 *   - Article list with title, meta, excerpt, tappable → show screen
 *   - Most Popular sidebar section (shown below articles on mobile)
 *   - Pull-to-refresh
 *   - Pagination (prev / next)
 *   - "Still need help?" CTA → Report Issue
 *
 * API (add these to routes/api.php):
 *
 *   // GET /api/knowledge-base
 *   Route::get('/knowledge-base', function (Request $request) {
 *       $query = \App\Models\Article::published()->with('author');
 *       if ($request->filled('search')) {
 *           $s = $request->search;
 *           $query->where(fn($q) => $q
 *               ->where('title',   'like', "%{$s}%")
 *               ->orWhere('content', 'like', "%{$s}%")
 *               ->orWhere('excerpt', 'like', "%{$s}%"));
 *       }
 *       if ($request->filled('category') && $request->category !== 'all') {
 *           $query->where('category', $request->category);
 *       }
 *       $articles        = $query->latest('published_at')->paginate(10);
 *       $popularArticles = \App\Models\Article::published()
 *           ->orderBy('views','desc')->take(5)
 *           ->get(['id','title','slug','category']);
 *       $categories = [];
 *       foreach (['hardware','software','network','display','peripherals','general'] as $cat) {
 *           $categories[$cat] = \App\Models\Article::published()
 *               ->where('category', $cat)->count();
 *       }
 *       return response()->json(compact('articles','popularArticles','categories'));
 *   });
 *
 *   // GET /api/knowledge-base/{slug}
 *   Route::get('/knowledge-base/{slug}', function ($slug) {
 *       $article = \App\Models\Article::published()
 *           ->where('slug', $slug)->with('author')->firstOrFail();
 *       $article->incrementViews();
 *       $related = \App\Models\Article::published()
 *           ->where('category', $article->category)
 *           ->where('id', '!=', $article->id)
 *           ->orderBy('views','desc')->take(3)
 *           ->get(['id','title','slug','category']);
 *       return response()->json(compact('article','related'));
 *   });
 *
 *   // POST /api/knowledge-base/{slug}/helpful
 *   Route::post('/knowledge-base/{slug}/helpful', function ($slug) {
 *       \App\Models\Article::published()->where('slug',$slug)
 *           ->firstOrFail()->markHelpful();
 *       return response()->json(['message' => 'Thank you for your feedback!']);
 *   });
 *
 *   // POST /api/knowledge-base/{slug}/not-helpful
 *   Route::post('/knowledge-base/{slug}/not-helpful', function ($slug) {
 *       \App\Models\Article::published()->where('slug',$slug)
 *           ->firstOrFail()->markNotHelpful();
 *       return response()->json(['message' => 'Thank you for your feedback!']);
 *   });
 *
 * These four routes can be public (no auth) since the web KB is also public.
 * Place them in the public section of api.php.
 */

import React, {
  useEffect,
  useState,
  useCallback,
  useRef,
} from 'react';
import {
  View,
  Text,
  ScrollView,
  FlatList,
  TouchableOpacity,
  TextInput,
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

type Article = {
  id:           number;
  title:        string;
  slug:         string;
  category:     string;
  excerpt:      string | null;
  views:        number;
  helpful_count:     number;
  not_helpful_count: number;
  published_at: string;
  author?: { full_name: string };
};

type Categories = Record<string, number>;

type KBResponse = {
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
  popularArticles: Article[];
  categories:      Categories;
};

type CategoryKey = 'hardware' | 'software' | 'network' | 'display' | 'peripherals' | 'general';

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
  hardware:    { bg: 'rgba(239,68,68,0.1)',    text: '#f87171', border: 'rgba(239,68,68,0.3)'    },
  software:    { bg: 'rgba(245,158,11,0.1)',   text: '#fbbf24', border: 'rgba(245,158,11,0.3)'   },
  network:     { bg: 'rgba(59,130,246,0.1)',   text: '#60a5fa', border: 'rgba(59,130,246,0.3)'   },
  display:     { bg: 'rgba(168,85,247,0.1)',   text: '#c084fc', border: 'rgba(168,85,247,0.3)'   },
  peripherals: { bg: 'rgba(16,185,129,0.1)',   text: '#34d399', border: 'rgba(16,185,129,0.3)'   },
  general:     { bg: 'rgba(45,212,191,0.1)',   text: '#2dd4bf', border: 'rgba(45,212,191,0.3)'   },
};

// ─── Helpers ─────────────────────────────────────────────────────────────────

function categoryLabel(key: string): string {
  return key.charAt(0).toUpperCase() + key.slice(1);
}

function formatDate(iso: string): string {
  return new Date(iso).toLocaleDateString('en-US', {
    month: 'short', day: 'numeric', year: 'numeric',
  });
}

function CategoryPill({ category }: { category: string }) {
  const c = CATEGORY_COLORS[category] ?? CATEGORY_COLORS.general;
  return (
    <View style={[pill.wrap, { backgroundColor: c.bg, borderColor: c.border }]}>
      <Text style={[pill.text, { color: c.text }]}>
        {CATEGORY_ICONS[category] ?? '📄'} {categoryLabel(category)}
      </Text>
    </View>
  );
}
const pill = StyleSheet.create({
  wrap: {
    borderWidth: 1,
    borderRadius: radius.full,
    paddingHorizontal: spacing.sm + 2,
    paddingVertical: 3,
    alignSelf: 'flex-start',
  },
  text: {
    fontSize: font.xs - 1,
    fontWeight: font.semibold,
  },
});

// ─── Article Card ─────────────────────────────────────────────────────────────

function ArticleCard({ article }: { article: Article }) {
  return (
    <TouchableOpacity
      style={s.articleCard}
      onPress={() => router.push(`/(tabs)/knowledge-base/${article.slug}` as any)}
      activeOpacity={0.75}
    >
      <View style={s.articleCardInner}>
        <View style={s.articleTop}>
          <CategoryPill category={article.category} />
          <Text style={s.articleViews}>👁️ {article.views.toLocaleString()}</Text>
        </View>

        <Text style={s.articleTitle} numberOfLines={2}>
          {article.title}
        </Text>

        <View style={s.articleMeta}>
          <Text style={s.articleMetaText}>
            ✍️ {article.author?.full_name ?? 'IT Support'}
          </Text>
          <Text style={s.articleMetaText}>
            📅 {formatDate(article.published_at)}
          </Text>
        </View>

        {!!article.excerpt && (
          <Text style={s.articleExcerpt} numberOfLines={2}>
            {article.excerpt}
          </Text>
        )}
      </View>

      <Text style={s.articleArrow}>→</Text>
    </TouchableOpacity>
  );
}

// ─── Main Component ───────────────────────────────────────────────────────────

export default function KnowledgeBaseScreen() {
  const [token,        setToken]        = useState<string | null>(null);
  const [articles,     setArticles]     = useState<Article[]>([]);
  const [popular,      setPopular]      = useState<Article[]>([]);
  const [categories,   setCategories]   = useState<Categories>({});
  const [pagination,   setPagination]   = useState<Omit<KBResponse['articles'], 'data'> | null>(null);
  const [loading,      setLoading]      = useState(true);
  const [refreshing,   setRefreshing]   = useState(false);
  const [pageLoading,  setPageLoading]  = useState(false);
  const [error,        setError]        = useState<string | null>(null);

  const [search,       setSearch]       = useState('');
  const [activeCategory, setActiveCategory] = useState<string>('all');
  const [currentPage,  setCurrentPage]  = useState(1);

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
    opts: { search?: string; category?: string; page?: number } = {}
  ) => {
    const params = new URLSearchParams();
    if (opts.search)                                params.set('search',   opts.search);
    if (opts.category && opts.category !== 'all')   params.set('category', opts.category);
    if (opts.page && opts.page > 1)                 params.set('page',     String(opts.page));

    try {
      const res = await fetch(`${apiUrl('knowledge-base')}?${params.toString()}`, {
        headers: {
          'Accept':        'application/json',
          'Authorization': `Bearer ${tok}`,
        },
      });

      if (res.status === 401) { router.replace('/login'); return; }
      if (!res.ok) throw new Error(`Server error ${res.status}`);

      const json: KBResponse = await res.json();
      setArticles(json.articles.data);
      setPopular(json.popularArticles);
      setCategories(json.categories);
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
  }, [token]); // eslint-disable-line react-hooks/exhaustive-deps

  // Search debounce
  useEffect(() => {
    if (!token) return;
    if (searchTimer.current) clearTimeout(searchTimer.current);
    searchTimer.current = setTimeout(() => {
      setCurrentPage(1);
      setLoading(true);
      fetchArticles(token, { search, category: activeCategory, page: 1 })
        .finally(() => setLoading(false));
    }, 350);
    return () => { if (searchTimer.current) clearTimeout(searchTimer.current); };
  }, [search]); // eslint-disable-line react-hooks/exhaustive-deps

  // Category change
  function handleCategoryToggle(key: string) {
    const next = activeCategory === key ? 'all' : key;
    setActiveCategory(next);
    setCurrentPage(1);
    if (!token) return;
    setLoading(true);
    fetchArticles(token, { search, category: next, page: 1 })
      .finally(() => setLoading(false));
  }

  // Clear all filters
  function clearFilters() {
    setSearch('');
    setActiveCategory('all');
    setCurrentPage(1);
    if (!token) return;
    setLoading(true);
    fetchArticles(token, {}).finally(() => setLoading(false));
  }

  // Pagination
  function handlePage(page: number) {
    if (!token) return;
    setCurrentPage(page);
    setPageLoading(true);
    fetchArticles(token, { search, category: activeCategory, page })
      .finally(() => setPageLoading(false));
  }

  // Pull-to-refresh
  const onRefresh = useCallback(async () => {
    if (!token) return;
    setRefreshing(true);
    await fetchArticles(token, { search, category: activeCategory, page: 1 });
    setCurrentPage(1);
    setRefreshing(false);
  }, [token, search, activeCategory, fetchArticles]);

  // ─── Render ───────────────────────────────────────────────────────────────

  return (
    <SafeAreaView style={s.root} edges={['top']}>
      <StatusBar barStyle="light-content" backgroundColor={colors.bgPrimary} />

      {/* ── Top Nav ── */}
      <View style={s.nav}>
        <Text style={s.navLogo}>LabFix</Text>
        <Text style={s.navTitle}>Knowledge Base</Text>
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
        {/* ── Hero search ── */}
        <View style={s.hero}>
          <Text style={s.heroTitle}>Knowledge Base</Text>
          <Text style={s.heroSub}>
            Find answers to common lab equipment issues before submitting a ticket
          </Text>
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
            {(search !== '' || activeCategory !== 'all') && (
              <TouchableOpacity onPress={clearFilters} activeOpacity={0.7}>
                <Text style={s.clearBtn}>✕ Clear</Text>
              </TouchableOpacity>
            )}
          </View>
        </View>

        {/* ── Category chips ── */}
        <View style={s.sectionPad}>
          <Text style={s.sectionTitle}>Browse by Category</Text>
        </View>
        <ScrollView
          horizontal
          showsHorizontalScrollIndicator={false}
          contentContainerStyle={s.catScroll}
        >
          {Object.entries(categories).map(([key, count]) => {
            const active = activeCategory === key;
            const c      = CATEGORY_COLORS[key] ?? CATEGORY_COLORS.general;
            return (
              <TouchableOpacity
                key={key}
                style={[
                  s.catChip,
                  active && { backgroundColor: c.bg, borderColor: c.border },
                ]}
                onPress={() => handleCategoryToggle(key)}
                activeOpacity={0.75}
              >
                <Text style={s.catChipIcon}>{CATEGORY_ICONS[key] ?? '📄'}</Text>
                <View>
                  <Text style={[s.catChipLabel, active && { color: c.text }]}>
                    {categoryLabel(key)}
                  </Text>
                  <Text style={s.catChipCount}>
                    {count} article{count !== 1 ? 's' : ''}
                  </Text>
                </View>
              </TouchableOpacity>
            );
          })}
        </ScrollView>

        {/* ── Articles ── */}
        <View style={s.sectionPad}>
          <View style={s.sectionHeader}>
            <Text style={s.sectionTitle}>
              {activeCategory !== 'all'
                ? `${categoryLabel(activeCategory)} Articles`
                : 'All Articles'}
            </Text>
            {(search !== '' || activeCategory !== 'all') && (
              <TouchableOpacity onPress={clearFilters} activeOpacity={0.7}>
                <Text style={s.clearFiltersLink}>Clear filters ×</Text>
              </TouchableOpacity>
            )}
          </View>
        </View>

        {loading ? (
          <View style={s.centered}>
            <ActivityIndicator size="large" color={colors.primary} />
            <Text style={s.loadingText}>Loading articles…</Text>
          </View>
        ) : error ? (
          <View style={s.centered}>
            <Text style={s.errorText}>{error}</Text>
            <TouchableOpacity
              style={s.retryBtn}
              onPress={() => token && fetchArticles(token, { search, category: activeCategory })}
            >
              <Text style={s.retryText}>Try Again</Text>
            </TouchableOpacity>
          </View>
        ) : articles.length === 0 ? (
          <View style={s.emptyState}>
            <Text style={s.emptyIcon}>🔍</Text>
            <Text style={s.emptyTitle}>No articles found</Text>
            <Text style={s.emptySub}>Try a different search term or category</Text>
            {(search || activeCategory !== 'all') && (
              <TouchableOpacity style={s.retryBtn} onPress={clearFilters}>
                <Text style={s.retryText}>Clear Filters</Text>
              </TouchableOpacity>
            )}
          </View>
        ) : (
          <View style={s.articleList}>
            {articles.map((article) => (
              <ArticleCard key={article.id} article={article} />
            ))}
          </View>
        )}

        {/* ── Pagination ── */}
        {!loading && pagination && pagination.last_page > 1 && (
          <View style={s.pagination}>
            <Text style={s.pageInfo}>
              {pagination.from ?? 0}–{pagination.to ?? 0} of {pagination.total} articles
            </Text>
            <View style={s.pageControls}>
              <TouchableOpacity
                style={[s.pageBtn, !pagination.prev_page_url && s.pageBtnDisabled]}
                onPress={() => handlePage(currentPage - 1)}
                disabled={!pagination.prev_page_url || pageLoading}
              >
                <Text style={s.pageBtnText}>← Prev</Text>
              </TouchableOpacity>
              <View style={s.pageCurrent}>
                <Text style={s.pageCurrentText}>{currentPage}</Text>
              </View>
              <TouchableOpacity
                style={[s.pageBtn, !pagination.next_page_url && s.pageBtnDisabled]}
                onPress={() => handlePage(currentPage + 1)}
                disabled={!pagination.next_page_url || pageLoading}
              >
                <Text style={s.pageBtnText}>Next →</Text>
              </TouchableOpacity>
            </View>
            {pageLoading && (
              <ActivityIndicator
                size="small"
                color={colors.primary}
                style={{ marginTop: spacing.sm }}
              />
            )}
          </View>
        )}

        {/* ── Most Popular ── */}
        {popular.length > 0 && (
          <View style={[s.card, { marginHorizontal: spacing.lg, marginTop: spacing.md }]}>
            <Text style={s.cardTitle}>🔥 Most Popular</Text>
            {popular.map((article, i) => (
              <TouchableOpacity
                key={article.id}
                style={[s.popularItem, i === popular.length - 1 && { borderBottomWidth: 0 }]}
                onPress={() => router.push(`/(tabs)/knowledge-base/${article.slug}` as any)}
                activeOpacity={0.75}
              >
                <Text style={s.popularIcon}>
                  {CATEGORY_ICONS[article.category] ?? '📄'}
                </Text>
                <Text style={s.popularTitle} numberOfLines={2}>
                  {article.title}
                </Text>
                <Text style={s.popularArrow}>→</Text>
              </TouchableOpacity>
            ))}
          </View>
        )}

        {/* ── Help CTA ── */}
        <View style={s.helpBanner}>
          <Text style={s.helpTitle}>Still need help?</Text>
          <Text style={s.helpText}>
            Can't find what you're looking for? Submit a support ticket and our IT team will assist you.
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
  root: {
    flex: 1,
    backgroundColor: colors.bgPrimary,
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

  // ── Hero ─────────────────────────────────────────────────────────────────
  hero: {
    margin: spacing.lg,
    marginBottom: spacing.md,
    backgroundColor: 'rgba(45,212,191,0.07)',
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radius.xl,
    padding: spacing.lg,
    alignItems: 'center',
  },
  heroTitle: {
    fontSize: font.xl,
    fontWeight: font.bold,
    color: colors.textPrimary,
    marginBottom: spacing.xs,
    textAlign: 'center',
  },
  heroSub: {
    fontSize: font.sm,
    color: colors.textMuted,
    textAlign: 'center',
    lineHeight: 19,
    marginBottom: spacing.md,
  },
  searchBar: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: 'rgba(255,255,255,0.05)',
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radius.full,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm + 2,
    width: '100%',
    gap: spacing.sm,
  },
  searchIcon: { fontSize: 15 },
  searchInput: {
    flex: 1,
    fontSize: font.base,
    color: colors.textPrimary,
  },
  clearBtn: {
    fontSize: font.xs,
    color: colors.textMuted,
    fontWeight: font.semibold,
  },

  // ── Section ───────────────────────────────────────────────────────────────
  sectionPad: {
    paddingHorizontal: spacing.lg,
    marginBottom: spacing.sm,
  },
  sectionHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  sectionTitle: {
    fontSize: font.base,
    fontWeight: font.bold,
    color: colors.primary,
  },
  clearFiltersLink: {
    fontSize: font.sm,
    color: colors.textMuted,
    fontWeight: font.semibold,
  },

  // ── Category chips (horizontal scroll) ───────────────────────────────────
  catScroll: {
    paddingHorizontal: spacing.lg,
    paddingBottom: spacing.md,
    gap: spacing.sm,
  },
  catChip: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    backgroundColor: colors.bgCard,
    borderWidth: 1.5,
    borderColor: colors.border,
    borderRadius: radius.xl,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm,
    marginRight: spacing.xs,
  },
  catChipIcon: { fontSize: 18 },
  catChipLabel: {
    fontSize: font.sm,
    fontWeight: font.semibold,
    color: colors.textPrimary,
  },
  catChipCount: {
    fontSize: font.xs - 1,
    color: colors.textMuted,
    marginTop: 1,
  },

  // ── Article list ──────────────────────────────────────────────────────────
  articleList: {
    paddingHorizontal: spacing.lg,
    gap: spacing.sm,
  },
  articleCard: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: colors.bgCard,
    borderWidth: 1,
    borderColor: colors.border,
    borderLeftWidth: 3,
    borderLeftColor: colors.primary,
    borderRadius: radius.xl,
    padding: spacing.md,
    marginBottom: spacing.sm,
  },
  articleCardInner: {
    flex: 1,
    gap: spacing.xs + 1,
  },
  articleTop: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  articleViews: {
    fontSize: font.xs,
    color: colors.textMuted,
  },
  articleTitle: {
    fontSize: font.base,
    fontWeight: font.semibold,
    color: colors.textPrimary,
    lineHeight: 21,
  },
  articleMeta: {
    flexDirection: 'row',
    gap: spacing.md,
    flexWrap: 'wrap',
  },
  articleMetaText: {
    fontSize: font.xs,
    color: colors.textMuted,
  },
  articleExcerpt: {
    fontSize: font.xs,
    color: colors.textMuted,
    lineHeight: 17,
  },
  articleArrow: {
    fontSize: font.lg,
    color: colors.primary,
    marginLeft: spacing.sm,
    fontWeight: font.bold,
  },

  // ── Pagination ────────────────────────────────────────────────────────────
  pagination: {
    alignItems: 'center',
    paddingVertical: spacing.lg,
    gap: spacing.sm,
    paddingHorizontal: spacing.lg,
  },
  pageInfo: {
    fontSize: font.sm,
    color: colors.textMuted,
  },
  pageControls: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
  },
  pageBtn: {
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.xs + 2,
    borderRadius: radius.md,
    backgroundColor: colors.bgCard,
    borderWidth: 1,
    borderColor: colors.border,
  },
  pageBtnDisabled: { opacity: 0.4 },
  pageBtnText: {
    fontSize: font.sm,
    color: colors.textSecondary,
  },
  pageCurrent: {
    width: 32,
    height: 32,
    borderRadius: radius.full,
    backgroundColor: colors.primary,
    alignItems: 'center',
    justifyContent: 'center',
  },
  pageCurrentText: {
    fontSize: font.sm,
    fontWeight: font.bold,
    color: '#fff',
  },

  // ── Popular ───────────────────────────────────────────────────────────────
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
  popularItem: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingVertical: spacing.sm + 2,
    borderBottomWidth: 1,
    borderBottomColor: 'rgba(45,212,191,0.07)',
    gap: spacing.sm,
  },
  popularIcon: { fontSize: 16, width: 22, textAlign: 'center' },
  popularTitle: {
    flex: 1,
    fontSize: font.sm,
    color: colors.textSecondary,
    lineHeight: 19,
  },
  popularArrow: {
    fontSize: font.sm,
    color: colors.primary,
    fontWeight: font.bold,
  },

  // ── Help banner ───────────────────────────────────────────────────────────
  helpBanner: {
    marginHorizontal: spacing.lg,
    marginTop: spacing.sm,
    backgroundColor: colors.primaryLight,
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radius.xl,
    padding: spacing.lg,
    alignItems: 'center',
  },
  helpTitle: {
    fontSize: font.lg,
    fontWeight: font.bold,
    color: colors.primary,
    marginBottom: spacing.xs,
  },
  helpText: {
    fontSize: font.sm,
    color: colors.textMuted,
    textAlign: 'center',
    lineHeight: 19,
    marginBottom: spacing.md,
  },
  helpBtn: {
    width: '100%',
    backgroundColor: colors.primary,
    borderRadius: radius.lg,
    paddingVertical: spacing.sm + 4,
    alignItems: 'center',
  },
  helpBtnText: {
    fontSize: font.base,
    fontWeight: font.bold,
    color: '#fff',
  },

  // ── States ────────────────────────────────────────────────────────────────
  centered: {
    alignItems: 'center',
    paddingVertical: spacing.xxl,
    gap: spacing.md,
    paddingHorizontal: spacing.lg,
  },
  loadingText: {
    fontSize: font.sm,
    color: colors.textMuted,
  },
  emptyState: {
    alignItems: 'center',
    paddingVertical: spacing.xxl,
    paddingHorizontal: spacing.lg,
    gap: spacing.sm,
  },
  emptyIcon: { fontSize: 32, opacity: 0.4 },
  emptyTitle: {
    fontSize: font.lg,
    fontWeight: font.semibold,
    color: colors.textSecondary,
  },
  emptySub: {
    fontSize: font.sm,
    color: colors.textMuted,
    textAlign: 'center',
  },
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