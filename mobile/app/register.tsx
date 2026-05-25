/**
 * app/register.tsx  —  Registration Screen
 *
 * Mirrors: resources/js/Pages/Auth/Register.vue
 *
 * Fields (same as web):
 *   first_name, last_name, email, role (select), password,
 *   password_confirmation, terms (checkbox)
 *
 * API:
 *   POST /api/register
 *   Body:    { first_name, last_name, email, role, password,
 *              password_confirmation, terms }
 *   Success: { token, user: { id, name, email, role } }
 *   Failure: 422 { errors: { field: string[] } }
 *
 *   NOTE: The /api/register endpoint does not yet exist in routes/api.php
 *   (only /api/login is defined). You will need to add a register route
 *   mirroring the web AuthController::register() logic. The UI is fully
 *   built here and will work once that endpoint is added.
 *
 * On success:
 *   Token + user saved to SecureStore, then redirect to role dashboard.
 *
 * On failure:
 *   Inline field errors shown below each input, matching the web version's
 *   form.errors.field_name pattern.
 *   Password fields are cleared on any error (mirrors Register.vue onError).
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

type Role = 'student' | 'staff' | 'it-support';

type ApiRegisterResponse = {
  token: string;
  user:  AuthUser;
};

type FieldErrors = {
  first_name?:            string;
  last_name?:             string;
  email?:                 string;
  role?:                  string;
  password?:              string;
  password_confirmation?: string;
  terms?:                 string;
};

const ROLES: { label: string; value: Role }[] = [
  { label: 'Student',    value: 'student'    },
  { label: 'Staff',      value: 'staff'      },
  { label: 'IT Support', value: 'it-support' },
];

// ─── Small sub-components ─────────────────────────────────────────────────────

function FieldError({ message }: { message?: string }) {
  if (!message) return null;
  return <Text style={fe.text}>{message}</Text>;
}
const fe = StyleSheet.create({
  text: {
    color: '#f87171',
    fontSize: font.xs,
    marginTop: spacing.xs - 2,
  },
});

// ─── Component ────────────────────────────────────────────────────────────────

export default function RegisterScreen() {
  // ── Form state ──────────────────────────────────────────────────────────
  const [firstName,    setFirstName]    = useState('');
  const [lastName,     setLastName]     = useState('');
  const [email,        setEmail]        = useState('');
  const [role,         setRole]         = useState<Role | ''>('');
  const [password,     setPassword]     = useState('');
  const [passwordConf, setPasswordConf] = useState('');
  const [terms,        setTerms]        = useState(false);
  const [errors,       setErrors]       = useState<FieldErrors>({});
  const [loading,      setLoading]      = useState(false);

  // ── Refs for keyboard tab-through ────────────────────────────────────────
  const lastNameRef    = useRef<TextInput>(null);
  const emailRef       = useRef<TextInput>(null);
  const passwordRef    = useRef<TextInput>(null);
  const passwordConRef = useRef<TextInput>(null);

  function clearErrors() { setErrors({}); }

  function clearPasswords() {
    setPassword('');
    setPasswordConf('');
  }

  // ── Client-side validation (mirrors Laravel rules) ───────────────────────
  function validate(): FieldErrors {
    const e: FieldErrors = {};
    if (!firstName.trim()) e.first_name = 'First name is required.';
    if (!lastName.trim())  e.last_name  = 'Last name is required.';
    if (!email.trim())     e.email      = 'Email is required.';
    if (!role)             e.role       = 'Please select your role.';
    if (!password)         e.password   = 'Password is required.';
    else if (password.length < 8)
      e.password = 'Password must be at least 8 characters.';
    if (password !== passwordConf)
      e.password = 'Passwords do not match.';
    if (!terms)
      e.terms = 'You must accept the terms and conditions.';
    return e;
  }

  // ── Submit ───────────────────────────────────────────────────────────────
  async function handleSubmit() {
    clearErrors();

    const clientErrors = validate();
    if (Object.keys(clientErrors).length > 0) {
      setErrors(clientErrors);
      return;
    }

    setLoading(true);

    try {
      const res = await fetch(apiUrl('register'), {
        method:  'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept':        'application/json',
        },
        body: JSON.stringify({
          first_name:            firstName.trim(),
          last_name:             lastName.trim(),
          email:                 email.trim(),
          role,
          password,
          password_confirmation: passwordConf,
          terms,
        }),
      });

      const data = await res.json();

      if (res.ok) {
        // ── Success ─────────────────────────────────────────────────────
        const { token, user } = data as ApiRegisterResponse;
        await saveAuth(token, user);
        router.replace(dashboardRouteForRole(user.role) as any);

      } else {
        // ── Server validation errors (422) ───────────────────────────────
        // Laravel returns { errors: { field: ['message'] } }
        const serverErrors: FieldErrors = {};
        const raw = data?.errors ?? {};
        for (const key of Object.keys(raw) as (keyof FieldErrors)[]) {
          serverErrors[key] = Array.isArray(raw[key]) ? raw[key][0] : raw[key];
        }
        // Fall back to generic message if no field errors returned
        if (Object.keys(serverErrors).length === 0) {
          serverErrors.email = data?.message ?? 'Registration failed. Please try again.';
        }
        setErrors(serverErrors);
        clearPasswords();
      }

    } catch {
      setErrors({ email: 'Could not reach the server. Check your connection.' });
      clearPasswords();
    } finally {
      setLoading(false);
    }
  }

  // ── Role selector ────────────────────────────────────────────────────────
  // Simple inline pill selector — replaces a native <select> which looks
  // inconsistent across platforms. Maps to the same values as the web dropdown.
  function RoleSelector() {
    return (
      <View style={s.roleRow}>
        {ROLES.map((r) => (
          <TouchableOpacity
            key={r.value}
            style={[s.rolePill, role === r.value && s.rolePillActive]}
            onPress={() => { setRole(r.value); clearErrors(); }}
            activeOpacity={0.7}
          >
            <Text
              style={[s.rolePillText, role === r.value && s.rolePillTextActive]}
            >
              {r.label}
            </Text>
          </TouchableOpacity>
        ))}
      </View>
    );
  }

  // ── Terms checkbox ───────────────────────────────────────────────────────
  function TermsRow() {
    return (
      <TouchableOpacity
        style={s.termsRow}
        onPress={() => { setTerms((v) => !v); clearErrors(); }}
        activeOpacity={0.7}
      >
        <View style={[s.checkbox, terms && s.checkboxChecked]}>
          {terms && <Text style={s.checkmark}>✓</Text>}
        </View>
        <Text style={s.termsText}>
          I have read the{' '}
          <Text style={s.termsLink}>Terms and Conditions</Text>
        </Text>
      </TouchableOpacity>
    );
  }

  // ── Render ───────────────────────────────────────────────────────────────
  return (
    <SafeAreaView style={s.root} edges={['top']}>
      <StatusBar barStyle="light-content" backgroundColor={colors.bgPrimary} />

      {/* ── Top Nav ── */}
      <View style={s.nav}>
        <Link href="/" asChild>
          <TouchableOpacity>
            <Text style={s.navLogo}>LabFix</Text>
          </TouchableOpacity>
        </Link>
        <View style={s.navRight}>
          <Text style={s.navHint}>Already have an account?</Text>
          <Link href="/login" asChild>
            <TouchableOpacity style={s.navBtn}>
              <Text style={s.navBtnText}>Sign In</Text>
            </TouchableOpacity>
          </Link>
        </View>
      </View>

      <KeyboardAvoidingView
        style={s.kav}
        behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
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
              Sign <Text style={s.brandSubAccent}>up</Text> to get started
            </Text>
          </View>

          {/* ── Auth card ── */}
          <View style={s.card}>

            <Text style={s.cardTitle}>
              CREATE{'\n'}<Text style={s.cardTitleAccent}>ACCOUNT</Text>
            </Text>
            <Text style={s.cardSub}>Create your account to get started</Text>

            {/* ── Name row ── */}
            <View style={s.nameRow}>
              <View style={s.nameField}>
                <Text style={s.fieldLabel}>First Name</Text>
                <TextInput
                  style={[s.input, !!errors.first_name && s.inputError]}
                  value={firstName}
                  onChangeText={(v) => { setFirstName(v); clearErrors(); }}
                  placeholder="e.g. Juan"
                  placeholderTextColor={colors.textDisabled}
                  autoCapitalize="words"
                  returnKeyType="next"
                  onSubmitEditing={() => lastNameRef.current?.focus()}
                  editable={!loading}
                />
                <FieldError message={errors.first_name} />
              </View>
              <View style={s.nameField}>
                <Text style={s.fieldLabel}>Last Name</Text>
                <TextInput
                  ref={lastNameRef}
                  style={[s.input, !!errors.last_name && s.inputError]}
                  value={lastName}
                  onChangeText={(v) => { setLastName(v); clearErrors(); }}
                  placeholder="e.g. Dela Cruz"
                  placeholderTextColor={colors.textDisabled}
                  autoCapitalize="words"
                  returnKeyType="next"
                  onSubmitEditing={() => emailRef.current?.focus()}
                  editable={!loading}
                />
                <FieldError message={errors.last_name} />
              </View>
            </View>

            {/* ── Email ── */}
            <View style={s.fieldGroup}>
              <Text style={s.fieldLabel}>Email Address</Text>
              <TextInput
                ref={emailRef}
                style={[s.input, !!errors.email && s.inputError]}
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
              <FieldError message={errors.email} />
            </View>

            {/* ── Role selector ── */}
            <View style={s.fieldGroup}>
              <Text style={s.fieldLabel}>Role</Text>
              <RoleSelector />
              <FieldError message={errors.role} />
            </View>

            {/* ── Password ── */}
            <View style={s.fieldGroup}>
              <Text style={s.fieldLabel}>Password</Text>
              <TextInput
                ref={passwordRef}
                style={[s.input, !!errors.password && s.inputError]}
                value={password}
                onChangeText={(v) => { setPassword(v); clearErrors(); }}
                placeholder="Minimum 8 characters"
                placeholderTextColor={colors.textDisabled}
                secureTextEntry
                returnKeyType="next"
                onSubmitEditing={() => passwordConRef.current?.focus()}
                editable={!loading}
              />
              <FieldError message={errors.password} />
            </View>

            {/* ── Confirm password ── */}
            <View style={s.fieldGroup}>
              <Text style={s.fieldLabel}>Confirm Password</Text>
              <TextInput
                ref={passwordConRef}
                style={[s.input, !!errors.password_confirmation && s.inputError]}
                value={passwordConf}
                onChangeText={(v) => { setPasswordConf(v); clearErrors(); }}
                placeholder="Re-enter your password"
                placeholderTextColor={colors.textDisabled}
                secureTextEntry
                returnKeyType="done"
                onSubmitEditing={handleSubmit}
                editable={!loading}
              />
              <FieldError message={errors.password_confirmation} />
            </View>

            {/* ── Terms ── */}
            <TermsRow />
            <FieldError message={errors.terms} />

            {/* ── Submit ── */}
            <TouchableOpacity
              style={[s.submitBtn, loading && s.submitBtnDisabled]}
              onPress={handleSubmit}
              activeOpacity={0.85}
              disabled={loading}
            >
              {loading ? (
                <ActivityIndicator color="#fff" />
              ) : (
                <Text style={s.submitBtnText}>Create Account</Text>
              )}
            </TouchableOpacity>

            {/* ── Divider ── */}
            <View style={s.divider}>
              <View style={s.dividerLine} />
              <Text style={s.dividerText}>or continue with</Text>
              <View style={s.dividerLine} />
            </View>

            {/* ── Social buttons ── */}
            <View style={s.socialRow}>
              <TouchableOpacity style={s.socialBtn} activeOpacity={0.7}>
                <Text style={s.socialBtnText}>G  Google</Text>
              </TouchableOpacity>
              <TouchableOpacity style={s.socialBtn} activeOpacity={0.7}>
                <Text style={s.socialBtnText}>f  Facebook</Text>
              </TouchableOpacity>
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
    paddingHorizontal: spacing.sm + 4,
    paddingVertical: spacing.xs + 1,
    borderRadius: radius.full,
  },
  navBtnText: {
    fontSize: font.xs,
    fontWeight: font.bold,
    color: '#fff',
  },

  // ── Layout ───────────────────────────────────────────────────────────────
  kav: { flex: 1 },
  scrollContent: {
    flexGrow: 1,
    paddingBottom: spacing.xxl,
    backgroundColor: 'rgba(20,20,30,0.6)',
  },

  // ── Brand ────────────────────────────────────────────────────────────────
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
  brandIconText: { fontSize: 26 },
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
    lineHeight: 34,
  },
  cardTitleAccent: { color: colors.primary },
  cardSub: {
    fontSize: font.sm,
    color: colors.textMuted,
    marginBottom: spacing.lg,
  },

  // ── Fields ───────────────────────────────────────────────────────────────
  nameRow: {
    flexDirection: 'row',
    gap: spacing.sm,
    marginBottom: spacing.md,
  },
  nameField: { flex: 1 },
  fieldGroup: { marginBottom: spacing.md },
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

  // ── Role selector ────────────────────────────────────────────────────────
  roleRow: {
    flexDirection: 'row',
    gap: spacing.sm,
    flexWrap: 'wrap',
  },
  rolePill: {
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radius.full,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.xs + 2,
    backgroundColor: colors.surfaceRaised,
  },
  rolePillActive: {
    borderColor: colors.primary,
    backgroundColor: colors.primaryLight,
  },
  rolePillText: {
    fontSize: font.sm,
    color: colors.textMuted,
    fontWeight: font.medium,
  },
  rolePillTextActive: {
    color: colors.primary,
    fontWeight: font.semibold,
  },

  // ── Terms ────────────────────────────────────────────────────────────────
  termsRow: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: spacing.sm,
    marginBottom: spacing.sm,
    marginTop: spacing.xs,
  },
  checkbox: {
    width: 20,
    height: 20,
    borderWidth: 1.5,
    borderColor: colors.border,
    borderRadius: radius.sm - 2,
    backgroundColor: colors.surfaceRaised,
    alignItems: 'center',
    justifyContent: 'center',
    marginTop: 1,
    flexShrink: 0,
  },
  checkboxChecked: {
    backgroundColor: colors.primary,
    borderColor: colors.primary,
  },
  checkmark: {
    color: '#fff',
    fontSize: 12,
    fontWeight: font.bold,
    lineHeight: 14,
  },
  termsText: {
    flex: 1,
    fontSize: font.sm,
    color: 'rgba(255,255,255,0.7)',
    lineHeight: 20,
  },
  termsLink: {
    color: colors.primary,
    fontWeight: font.semibold,
  },

  // ── Submit ────────────────────────────────────────────────────────────────
  submitBtn: {
    backgroundColor: colors.primary,
    borderRadius: radius.lg,
    paddingVertical: spacing.md,
    alignItems: 'center',
    marginTop: spacing.sm,
    shadowColor: colors.primary,
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.35,
    shadowRadius: 8,
    elevation: 6,
  },
  submitBtnDisabled: { opacity: 0.65 },
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
});