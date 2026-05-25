/**
 * app/(it)/knowledge-base/create.tsx  —  Create Knowledge Base Article
 *
 * Mirrors: resources/js/Pages/IT/KnowledgeBase/Create.vue
 *
 * API: POST /api/it/articles
 * Body: { title, category, excerpt, content, status }
 * Success: { message, article } → navigates back to KB index
 *
 * Fields:
 *   - Title *
 *   - Category * (hardware | software | network | display | peripherals | general)
 *   - Excerpt (optional, max 500 chars)
 *   - Content * (min 50 chars)
 *   - Status * (draft | published)
 */
 
import React, { useEffect, useState } from 'react';
import {
  View,
  Text,
  TextInput,
  TouchableOpacity,
  ScrollView,
  StyleSheet,
  StatusBar,
  ActivityIndicator,
  Alert,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { router } from 'expo-router';
import { colors, spacing, radius, font } from '@/constants/theme';
import { loadAuth } from '@/utils/auth';
import { apiUrl } from '@/constants/api';
 
// ─── Types ────────────────────────────────────────────────────────────────────
 
type Category = 'hardware' | 'software' | 'network' | 'display' | 'peripherals' | 'general';
type Status   = 'draft' | 'published';
 
type FieldErrors = {
  title?:    string;
  category?: string;
  content?:  string;
  excerpt?:  string;
  status?:   string;
};
 
// ─── Constants ────────────────────────────────────────────────────────────────
 
const CATEGORIES: { value: Category; label: string; icon: string }[] = [
  { value: 'hardware',    label: 'Hardware',    icon: '🔧' },
  { value: 'software',    label: 'Software',    icon: '💾' },
  { value: 'network',     label: 'Network',     icon: '🌐' },
  { value: 'display',     label: 'Display',     icon: '🖥️' },
  { value: 'peripherals', label: 'Peripherals', icon: '🖱️' },
  { value: 'general',     label: 'General',     icon: '📋' },
];
 
// ─── Small helpers ────────────────────────────────────────────────────────────
 
function FieldError({ message }: { message?: string }) {
  if (!message) return null;
  return <Text style={fe.text}>{message}</Text>;
}
const fe = StyleSheet.create({
  text: { color: '#f87171', fontSize: font.xs, marginTop: spacing.xs - 2 },
});
 
// ─── Component ────────────────────────────────────────────────────────────────
 
export default function ITKBCreateScreen() {
  const [token,    setToken]    = useState<string | null>(null);
  const [loading,  setLoading]  = useState(false);
  const [errors,   setErrors]   = useState<FieldErrors>({});
 
  // Form fields
  const [title,    setTitle]    = useState('');
  const [category, setCategory] = useState<Category | ''>('');
  const [excerpt,  setExcerpt]  = useState('');
  const [content,  setContent]  = useState('');
  const [status,   setStatus]   = useState<Status>('draft');
 
  // ── Auth ──────────────────────────────────────────────────────────────────
  useEffect(() => {
    loadAuth().then((auth) => {
      if (!auth) { router.replace('/login'); return; }
      setToken(auth.token);
    });
  }, []);
 
  // ── Validation ────────────────────────────────────────────────────────────
  function validate(): FieldErrors {
    const e: FieldErrors = {};
    if (!title.trim())         e.title    = 'Title is required.';
    if (!category)             e.category = 'Please select a category.';
    if (!content.trim())       e.content  = 'Content is required.';
    else if (content.trim().length < 50)
      e.content = 'Content must be at least 50 characters.';
    if (excerpt.length > 500)  e.excerpt  = 'Excerpt must be 500 characters or fewer.';
    return e;
  }
 
  // ── Submit ────────────────────────────────────────────────────────────────
  async function handleSubmit() {
    const clientErrors = validate();
    if (Object.keys(clientErrors).length > 0) {
      setErrors(clientErrors);
      return;
    }
 
    if (!token) return;
    setLoading(true);
    setErrors({});
 
    try {
      const res = await fetch(apiUrl('it/articles'), {
        method: 'POST',
        headers: {
          'Content-Type':  'application/json',
          'Accept':        'application/json',
          'Authorization': `Bearer ${token}`,
        },
        body: JSON.stringify({
          title:    title.trim(),
          category,
          excerpt:  excerpt.trim() || undefined,
          content:  content.trim(),
          status,
        }),
      });
 
      const data = await res.json();
 
      if (res.ok) {
        Alert.alert(
          'Article Created!',
          status === 'published'
            ? 'Your article has been published and is now visible to users.'
            : 'Your article has been saved as a draft.',
          [{ text: 'OK', onPress: () => router.replace('/(it)/knowledge-base' as any) }]
        );
      } else {
        // 422 validation errors from Laravel
        const serverErrors: FieldErrors = {};
        const raw = data?.errors ?? {};
        for (const key of Object.keys(raw) as (keyof FieldErrors)[]) {
          serverErrors[key] = Array.isArray(raw[key]) ? raw[key][0] : raw[key];
        }
        if (Object.keys(serverErrors).length === 0) {
          Alert.alert('Error', data?.message ?? 'Failed to create article.');
        }
        setErrors(serverErrors);
      }
    } catch {
      Alert.alert('Error', 'Could not reach the server. Check your connection.');
    } finally {
      setLoading(false);
    }
  }
 
  // ─── Render ───────────────────────────────────────────────────────────────
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
        <Text style={s.navTitle}>Create Article</Text>
        <View style={{ width: 110 }} />
      </View>
 
      <ScrollView
        showsVerticalScrollIndicator={false}
        contentContainerStyle={s.scroll}
        keyboardShouldPersistTaps="handled"
      >
        <View style={s.pageHeader}>
          <Text style={s.pageTitle}>New Article</Text>
          <Text style={s.pageSub}>Write a new troubleshooting guide or knowledge base article</Text>
        </View>
 
        <View style={s.card}>
 
          {/* ── Title ── */}
          <View style={s.fieldGroup}>
            <Text style={s.label}>Title *</Text>
            <TextInput
              style={[s.input, !!errors.title && s.inputError]}
              value={title}
              onChangeText={(v) => { setTitle(v); setErrors(e => ({ ...e, title: undefined })); }}
              placeholder="e.g., Computer won't turn on — Troubleshooting steps"
              placeholderTextColor={colors.textDisabled}
              returnKeyType="next"
              editable={!loading}
            />
            <Text style={s.helpText}>Choose a clear, descriptive title that users can easily search for</Text>
            <FieldError message={errors.title} />
          </View>
 
          {/* ── Category ── */}
          <View style={s.fieldGroup}>
            <Text style={s.label}>Category *</Text>
            <View style={s.categoryGrid}>
              {CATEGORIES.map((cat) => {
                const active = category === cat.value;
                return (
                  <TouchableOpacity
                    key={cat.value}
                    style={[s.categoryPill, active && s.categoryPillActive]}
                    onPress={() => {
                      setCategory(cat.value);
                      setErrors(e => ({ ...e, category: undefined }));
                    }}
                    activeOpacity={0.75}
                  >
                    <Text style={s.categoryIcon}>{cat.icon}</Text>
                    <Text style={[s.categoryLabel, active && s.categoryLabelActive]}>
                      {cat.label}
                    </Text>
                  </TouchableOpacity>
                );
              })}
            </View>
            <FieldError message={errors.category} />
          </View>
 
          {/* ── Excerpt ── */}
          <View style={s.fieldGroup}>
            <Text style={s.label}>Excerpt <Text style={s.optional}>(Optional)</Text></Text>
            <TextInput
              style={[s.input, s.textarea, !!errors.excerpt && s.inputError]}
              value={excerpt}
              onChangeText={(v) => { setExcerpt(v); setErrors(e => ({ ...e, excerpt: undefined })); }}
              placeholder="Brief summary of the article (max 500 characters)"
              placeholderTextColor={colors.textDisabled}
              multiline
              numberOfLines={3}
              textAlignVertical="top"
              editable={!loading}
            />
            <View style={s.charCountRow}>
              <Text style={s.helpText}>If left empty, auto-generated from content</Text>
              <Text style={[
                s.charCount,
                excerpt.length > 450 && { color: colors.warning },
                excerpt.length > 500 && { color: colors.danger },
              ]}>
                {excerpt.length}/500
              </Text>
            </View>
            <FieldError message={errors.excerpt} />
          </View>
 
          {/* ── Content ── */}
          <View style={s.fieldGroup}>
            <Text style={s.label}>Content *</Text>
            <TextInput
              style={[s.input, s.contentArea, !!errors.content && s.inputError]}
              value={content}
              onChangeText={(v) => { setContent(v); setErrors(e => ({ ...e, content: undefined })); }}
              placeholder="Write your article content here… Include step-by-step instructions, troubleshooting tips, and solutions."
              placeholderTextColor={colors.textDisabled}
              multiline
              numberOfLines={12}
              textAlignVertical="top"
              editable={!loading}
            />
            <View style={s.charCountRow}>
              <Text style={s.helpText}>Minimum 50 characters. Use clear language.</Text>
              <Text style={[
                s.charCount,
                content.trim().length > 0 && content.trim().length < 50 && { color: colors.danger },
                content.trim().length >= 50 && { color: colors.success },
              ]}>
                {content.trim().length} chars
              </Text>
            </View>
            <FieldError message={errors.content} />
          </View>
 
          {/* ── Status ── */}
          <View style={s.fieldGroup}>
            <Text style={s.label}>Status *</Text>
            <View style={s.statusRow}>
              <TouchableOpacity
                style={[s.statusPill, status === 'draft' && s.statusPillActive]}
                onPress={() => setStatus('draft')}
                activeOpacity={0.75}
              >
                <Text style={[s.statusPillText, status === 'draft' && s.statusPillTextActive]}>
                  📝 Save as Draft
                </Text>
              </TouchableOpacity>
              <TouchableOpacity
                style={[s.statusPill, status === 'published' && s.statusPillPublished]}
                onPress={() => setStatus('published')}
                activeOpacity={0.75}
              >
                <Text style={[s.statusPillText, status === 'published' && s.statusPillTextPublished]}>
                  🌐 Publish Now
                </Text>
              </TouchableOpacity>
            </View>
            <Text style={s.helpText}>
              {status === 'draft'
                ? 'Drafts are only visible to IT staff.'
                : 'Published articles are visible to all users.'}
            </Text>
          </View>
 
        </View>
 
        {/* ── Action buttons ── */}
        <View style={s.actions}>
          <TouchableOpacity
            style={[s.submitBtn, loading && s.submitBtnDisabled]}
            onPress={handleSubmit}
            disabled={loading}
            activeOpacity={0.85}
          >
            {loading
              ? <ActivityIndicator color="#fff" />
              : <Text style={s.submitBtnText}>
                  {status === 'published' ? 'Publish Article' : 'Save as Draft'}
                </Text>}
          </TouchableOpacity>
 
          <TouchableOpacity
            style={s.cancelBtn}
            onPress={() => router.push('/(it)/knowledge-base' as any)}
            disabled={loading}
            activeOpacity={0.8}
          >
            <Text style={s.cancelBtnText}>Cancel</Text>
          </TouchableOpacity>
        </View>
 
        <View style={{ height: spacing.xxl }} />
      </ScrollView>
    </SafeAreaView>
  );
}
 
// ─── Styles ───────────────────────────────────────────────────────────────────
 
const s = StyleSheet.create({
  root:   { flex: 1, backgroundColor: colors.bgPrimary },
  scroll: { paddingBottom: spacing.lg },
 
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
 
  // Page header
  pageHeader: {
    paddingHorizontal: spacing.lg,
    paddingTop: spacing.lg,
    marginBottom: spacing.md,
  },
  pageTitle: { fontSize: font.xl, fontWeight: font.bold, color: colors.textPrimary },
  pageSub:   { fontSize: font.sm, color: colors.textMuted, marginTop: 2, lineHeight: 18 },
 
  // Card
  card: {
    marginHorizontal: spacing.lg,
    backgroundColor: colors.bgCard,
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radius.xl,
    padding: spacing.lg,
    gap: spacing.xs,
  },
 
  // Fields
  fieldGroup: { marginBottom: spacing.md },
  label: {
    fontSize: font.sm,
    fontWeight: font.semibold,
    color: colors.textSecondary,
    marginBottom: spacing.xs,
  },
  optional: { fontWeight: font.normal, color: colors.textMuted },
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
  textarea:     { minHeight: 80,  paddingTop: spacing.sm + 4 },
  contentArea:  { minHeight: 200, paddingTop: spacing.sm + 4 },
  inputError:   { borderColor: colors.dangerBorder, backgroundColor: 'rgba(239,68,68,0.06)' },
  helpText:     { fontSize: font.xs, color: colors.textMuted, marginTop: spacing.xs },
  charCountRow: { flexDirection: 'row', justifyContent: 'space-between', marginTop: spacing.xs },
  charCount:    { fontSize: font.xs, color: colors.textMuted },
 
  // Category grid
  categoryGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.xs + 2,
    marginTop: spacing.xs,
  },
  categoryPill: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.xs,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.xs + 3,
    borderRadius: radius.full,
    backgroundColor: 'rgba(255,255,255,0.05)',
    borderWidth: 1,
    borderColor: colors.border,
  },
  categoryPillActive: {
    backgroundColor: colors.primaryLight,
    borderColor: colors.primary,
  },
  categoryIcon:  { fontSize: 14 },
  categoryLabel: { fontSize: font.sm, color: colors.textMuted, fontWeight: font.medium },
  categoryLabelActive: { color: colors.primary, fontWeight: font.semibold },
 
  // Status
  statusRow: { flexDirection: 'row', gap: spacing.sm, marginTop: spacing.xs },
  statusPill: {
    flex: 1,
    alignItems: 'center',
    paddingVertical: spacing.sm + 4,
    borderRadius: radius.lg,
    backgroundColor: 'rgba(255,255,255,0.05)',
    borderWidth: 1,
    borderColor: colors.border,
  },
  statusPillActive: {
    backgroundColor: colors.warningBg,
    borderColor: colors.warningBorder,
  },
  statusPillPublished: {
    backgroundColor: colors.successBg,
    borderColor: colors.successBorder,
  },
  statusPillText: { fontSize: font.sm, fontWeight: font.semibold, color: colors.textMuted },
  statusPillTextActive:    { color: colors.warningLight },
  statusPillTextPublished: { color: colors.successLight },
 
  // Action buttons
  actions: {
    marginHorizontal: spacing.lg,
    marginTop: spacing.lg,
    gap: spacing.sm,
  },
  submitBtn: {
    backgroundColor: colors.primary,
    borderRadius: radius.lg,
    paddingVertical: spacing.md,
    alignItems: 'center',
    shadowColor: colors.primary,
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.3,
    shadowRadius: 8,
    elevation: 5,
  },
  submitBtnDisabled: { opacity: 0.65 },
  submitBtnText: { fontSize: font.base, fontWeight: font.bold, color: '#fff' },
  cancelBtn: {
    borderWidth: 1.5,
    borderColor: colors.border,
    borderRadius: radius.lg,
    paddingVertical: spacing.md,
    alignItems: 'center',
  },
  cancelBtnText: { fontSize: font.base, fontWeight: font.semibold, color: colors.textMuted },
});