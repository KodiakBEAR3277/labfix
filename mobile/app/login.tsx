/**
 * app/login.tsx  —  Login Screen
 *
 * Mirrors: resources/js/Pages/Auth/Login.vue
 *
 * API:
 *   POST /api/login
 *   Body:    { email: string, password: string }
 *   Success: { token: string, user: { id, name, email, role } }
 *   Failure: 422 { errors: { email: string[] } }
 *            or 401 with a generic message
 *
 * On success:
 *   1. Token + user are saved to SecureStore via saveAuth()
 *   2. User is redirected to their role-appropriate dashboard
 *      (admin → /(admin)/dashboard, it-support → /(it)/dashboard,
 *       student/staff → /(tabs)/dashboard)
 *
 * On failure:
 *   - Inline error shown below the email field (mirrors web form.errors.email)
 *   - Password field is cleared
 *   - Submit button re-enabled
 *
 * UX details that match the web page:
 *   - Password is cleared on any error (same as Login.vue onError callback)
 *   - Email field retains its value on error
 *   - "Don't have an account?" → /register
 *   - Logo links back to / (Landing)
 */

import React, { useState, useRef } from 'react';
import {
  View,
  Text,
  TextInput,
  TouchableOpacity,
  StyleSheet,
  StatusBar,
  KeyboardAvoidingView,
  Platform,
  ScrollView,
  ActivityIndicator,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Link, router } from 'expo-router';
import { colors, spacing, radius, font } from '@/constants/theme';
import { apiUrl } from '@/constants/api';
import { saveAuth, dashboardRouteForRole, AuthUser } from '@/utils/auth';

// ─── Types ────────────────────────────────────────────────────────────────────

type ApiLoginResponse = {
  token: string;
  user:  AuthUser;
};

// ─── Component ────────────────────────────────────────────────────────────────

export default function LoginScreen() {
  const [email,       setEmail]       = useState('');
  const [password,    setPassword]    = useState('');
  const [emailError,  setEmailError]  = useState('');
  const [loading,     setLoading]     = useState(false);

  const passwordRef = useRef<TextInput>(null);

  function clearErrors() {
    setEmailError('');
  }

  async function handleSubmit() {
    // Basic client-side guard — server will also validate
    if (!email.trim() || !password) {
      setEmailError('Please enter your email and password.');
      return;
    }

    clearErrors();
    setLoading(true);

    try {
      const res = await fetch(apiUrl('login'), {
        method:  'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body:    JSON.stringify({ email: email.trim(), password }),
      });

      const data = await res.json();

      if (res.ok) {
        // ── Success path ──────────────────────────────────────────────────
        const { token, user } = data as ApiLoginResponse;
        await saveAuth(token, user);
        // Replace so the user can't swipe/back back to login
        router.replace(dashboardRouteForRole(user.role) as any);

      } else {
        // ── Error path ────────────────────────────────────────────────────
        // Laravel returns 422 with { errors: { email: ['...'] } }
        // or 401 with { message: '...' }
        const message: string =
          data?.errors?.email?.[0] ??
          data?.message ??
          'The provided credentials do not match our records.';

        setEmailError(message);
        // Clear password on any error — mirrors Login.vue onError callback
        setPassword('');
      }

    } catch {
      // Network error
      setEmailError('Could not reach the server. Check your connection and try again.');
      setPassword('');
    } finally {
      setLoading(false);
    }
  }

  return (
    <SafeAreaView style={s.root} edges={['top', 'bottom']}>
      <StatusBar barStyle="light-content" backgroundColor={colors.bgPrimary} />

      {/* ── Top Nav ── */}
      <View style={s.nav}>
        {/* Logo navigates back to landing */}
        <Link href="/" asChild>
          <TouchableOpacity>
            <Text style={s.navLogo}>LabFix</Text>
          </TouchableOpacity>
        </Link>
        <View style={s.navRight}>
        </View>
      </View>

      {/*
        KeyboardAvoidingView pushes the form up when the soft keyboard
        appears so the inputs are never hidden behind it.
      */}
      <KeyboardAvoidingView
        style={s.kav}
        behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
        keyboardVerticalOffset={0}
      >
        <ScrollView
          contentContainerStyle={s.scrollContent}
          showsVerticalScrollIndicator={false}
          keyboardShouldPersistTaps="handled"
        >

          {/* ── Brand area ── */}
          <View style={s.brand}>
            <View style={s.brandIconBox}>
              <Text style={s.brandIconText}>🔧</Text>
            </View>
            <Text style={s.brandSub}>
              Sign <Text style={s.brandSubAccent}>in</Text> to continue
            </Text>
          </View>

          {/* ── Auth card ── */}
          <View style={s.card}>

            {/* Card heading */}
            <Text style={s.cardTitle}>
              SIGN<Text style={s.cardTitleAccent}>IN</Text>
            </Text>
            <Text style={s.cardSub}>Sign in with your email address</Text>

            {/* Inline error banner — only shown when emailError is set */}
            {!!emailError && (
              <View style={s.errorBanner}>
                <Text style={s.errorBannerText}>{emailError}</Text>
              </View>
            )}

            {/* Email field */}
            <View style={s.fieldGroup}>
              <Text style={s.fieldLabel}>Email</Text>
              <TextInput
                style={[s.input, !!emailError && s.inputError]}
                value={email}
                onChangeText={(v) => { setEmail(v); clearErrors(); }}
                placeholder="yourname@gmail.com"
                placeholderTextColor={colors.textDisabled}
                keyboardType="email-address"
                autoCapitalize="none"
                autoCorrect={false}
                returnKeyType="next"
                onSubmitEditing={() => passwordRef.current?.focus()}
                editable={!loading}
              />
            </View>

            {/* Password field */}
            <View style={s.fieldGroup}>
              <Text style={s.fieldLabel}>Password</Text>
              <TextInput
                ref={passwordRef}
                style={s.input}
                value={password}
                onChangeText={setPassword}
                placeholder="Password"
                placeholderTextColor={colors.textDisabled}
                secureTextEntry
                returnKeyType="done"
                onSubmitEditing={handleSubmit}
                editable={!loading}
              />
            </View>

            {/* Submit button */}
            <TouchableOpacity
              style={[s.submitBtn, loading && s.submitBtnDisabled]}
              onPress={handleSubmit}
              activeOpacity={0.85}
              disabled={loading}
            >
              {loading ? (
                <ActivityIndicator color="#fff" />
              ) : (
                <Text style={s.submitBtnText}>Sign In</Text>
              )}
            </TouchableOpacity>

            {/* Divider */}
            <View style={s.divider}>
              <View style={s.dividerLine} />
              <Text style={s.dividerText}>Or continue with</Text>
              <View style={s.dividerLine} />
            </View>

            {/* Social buttons — placeholder, no OAuth implemented yet */}
            <View style={s.socialRow}>
              <TouchableOpacity style={s.socialBtn} activeOpacity={0.7}>
                <Text style={s.socialBtnText}>G  Google</Text>
              </TouchableOpacity>
              <TouchableOpacity style={s.socialBtn} activeOpacity={0.7}>
                <Text style={s.socialBtnText}>f  Facebook</Text>
              </TouchableOpacity>
            </View>

            {/* Footer link */}
            <View style={s.cardFooter}>
              <Text style={s.cardFooterText}>
                By signing in you agree with our{' '}
              </Text>
              <TouchableOpacity activeOpacity={0.7}>
                <Text style={s.cardFooterLink}>Terms and Conditions</Text>
              </TouchableOpacity>
            </View>

          </View>

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
  },
  navRight: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
  },
  navHint: {
    fontSize: font.xs,
    color: colors.textMuted,
  },
  navBtn: {
    backgroundColor: colors.primary,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.xs + 1,
    borderRadius: radius.full,
  },
  navBtnText: {
    fontSize: font.xs,
    fontWeight: font.bold,
    color: '#fff',
  },

  // ── Keyboard avoiding + scroll ───────────────────────────────────────────
  kav: {
    flex: 1,
  },
  scrollContent: {
    flexGrow: 1,
    paddingBottom: spacing.xxl,
    // Subtle dark gradient effect via background
    backgroundColor: 'rgba(20,20,30,0.6)',
  },

  // ── Brand area ───────────────────────────────────────────────────────────
  brand: {
    alignItems: 'center',
    paddingTop: spacing.xl,
    paddingBottom: spacing.md,
  },
  brandIconBox: {
    width: 56,
    height: 56,
    backgroundColor: colors.primaryLight,
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radius.xl,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: spacing.sm,
  },
  brandIconText: {
    fontSize: 26,
  },
  brandSub: {
    fontSize: font.sm,
    color: colors.textMuted,
  },
  brandSubAccent: {
    color: colors.primary,
    fontWeight: font.bold,
  },

  // ── Card ─────────────────────────────────────────────────────────────────
  card: {
    marginHorizontal: spacing.lg,
    marginTop: spacing.sm,
    backgroundColor: 'rgba(30,30,40,0.95)',
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radius.xxl,
    padding: spacing.xl,
  },
  cardTitle: {
    fontSize: font.xl3,
    fontWeight: font.bold,
    color: colors.textPrimary,
    marginBottom: spacing.xs,
    letterSpacing: 1,
  },
  cardTitleAccent: {
    color: colors.primary,
  },
  cardSub: {
    fontSize: font.sm,
    color: colors.textMuted,
    marginBottom: spacing.lg,
  },

  // ── Error banner ─────────────────────────────────────────────────────────
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

  // ── Fields ───────────────────────────────────────────────────────────────
  fieldGroup: {
    marginBottom: spacing.md,
  },
  fieldLabel: {
    fontSize: font.sm,
    color: colors.textSecondary,
    fontWeight: font.medium,
    marginBottom: spacing.xs,
  },
  input: {
    backgroundColor: colors.surfaceRaised,
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radius.lg,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm + 4,
    color: colors.textPrimary,
    fontSize: font.base,
  },
  inputError: {
    borderColor: colors.dangerBorder,
    backgroundColor: 'rgba(239,68,68,0.06)',
  },

  // ── Submit ────────────────────────────────────────────────────────────────
  submitBtn: {
    backgroundColor: colors.primary,
    borderRadius: radius.lg,
    paddingVertical: spacing.md,
    alignItems: 'center',
    marginTop: spacing.xs,
    // Soft shadow for the primary button (mirrors web box-shadow)
    shadowColor: colors.primary,
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.35,
    shadowRadius: 8,
    elevation: 6,
  },
  submitBtnDisabled: {
    opacity: 0.65,
  },
  submitBtnText: {
    fontSize: font.lg,
    fontWeight: font.bold,
    color: '#fff',
  },

  // ── Divider ───────────────────────────────────────────────────────────────
  divider: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    marginVertical: spacing.lg,
  },
  dividerLine: {
    flex: 1,
    height: 1,
    backgroundColor: colors.borderLight,
  },
  dividerText: {
    fontSize: font.sm,
    color: colors.textMuted,
  },

  // ── Social ────────────────────────────────────────────────────────────────
  socialRow: {
    flexDirection: 'row',
    gap: spacing.sm,
  },
  socialBtn: {
    flex: 1,
    backgroundColor: colors.surfaceRaised,
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radius.lg,
    paddingVertical: spacing.sm + 4,
    alignItems: 'center',
  },
  socialBtnText: {
    fontSize: font.sm,
    fontWeight: font.medium,
    color: colors.textPrimary,
  },

  // ── Card footer ───────────────────────────────────────────────────────────
  cardFooter: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    justifyContent: 'center',
    marginTop: spacing.lg,
    paddingTop: spacing.lg,
    borderTopWidth: 1,
    borderTopColor: colors.borderLight,
    gap: 2,
  },
  cardFooterText: {
    fontSize: font.sm,
    color: colors.textMuted,
  },
  cardFooterLink: {
    fontSize: font.sm,
    color: colors.primary,
    fontWeight: font.semibold,
  },
});