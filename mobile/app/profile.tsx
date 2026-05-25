/**
 * app/profile.tsx  —  Profile Screen (shared across all roles)
 *
 * Accessible from:
 *   - /(tabs)/dashboard  → avatar onPress → router.push('/(tabs)/profile')
 *   - /(it)/dashboard    → avatar onPress → router.push('/(it)/profile')
 *
 * Since both zones push here, this file should be placed at:
 *   app/profile.tsx   (top-level, outside both tab groups)
 * and both dashboards updated to: router.push('/profile')
 *
 * API:
 *   GET  /api/profile               → load user data
 *   PUT  /api/profile               → update name/email/phone/ID
 *   PUT  /api/profile/password      → change password
 *   PUT  /api/profile/preferences   → toggle email notifications
 *   POST /api/logout                → delete token → clearAuth() → redirect /
 *
 * Sections:
 *   1. Avatar + name + role badge + quick stats strip
 *   2. Profile Information form
 *   3. Change Password form
 *   4. Notification Preferences toggle
 *   5. Sign Out (danger zone)
 */

import React, { useEffect, useState, useCallback } from 'react';
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
  Switch,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { router } from 'expo-router';
import { colors, spacing, radius, font } from '@/constants/theme';
import { loadAuth, clearAuth, AuthUser } from '@/utils/auth';
import { apiUrl } from '@/constants/api';

// ─── Types ────────────────────────────────────────────────────────────────────

type ProfileUser = {
  id:                   number;
  first_name:           string;
  last_name:            string;
  full_name:            string;
  initials:             string;
  email:                string;
  role:                 AuthUser['role'];
  phone:                string | null;
  student_staff_id:     string | null;
  email_notifications:  boolean;
  is_active:            boolean;
  can_submit_tickets:   boolean;
  created_at:           string;
};

type ProfileErrors = {
  first_name?: string;
  last_name?:  string;
  email?:      string;
  phone?:      string;
};

type PasswordErrors = {
  current_password?: string;
  password?:         string;
};

// ─── Helpers ─────────────────────────────────────────────────────────────────

function roleLabel(role: AuthUser['role']): string {
  const map: Record<AuthUser['role'], string> = {
    student:       'Student',
    staff:         'Staff',
    'it-support':  'IT Support',
    admin:         'Administrator',
  };
  return map[role] ?? role;
}

function roleColor(role: AuthUser['role']): string {
  const map: Record<AuthUser['role'], string> = {
    student:      colors.info,
    staff:        '#a855f7',
    'it-support': colors.warning,
    admin:        colors.danger,
  };
  return map[role] ?? colors.primary;
}

function formatDate(iso: string): string {
  return new Date(iso).toLocaleDateString('en-US', {
    month: 'long', day: 'numeric', year: 'numeric',
  });
}

// ─── Field Error ─────────────────────────────────────────────────────────────

function FieldError({ message }: { message?: string }) {
  if (!message) return null;
  return <Text style={fe.text}>{message}</Text>;
}
const fe = StyleSheet.create({
  text: { color: '#f87171', fontSize: font.xs, marginTop: spacing.xs - 2 },
});

// ─── Section Header ───────────────────────────────────────────────────────────

function SectionTitle({ title }: { title: string }) {
  return <Text style={st.text}>{title}</Text>;
}
const st = StyleSheet.create({
  text: {
    fontSize: font.base,
    fontWeight: font.bold,
    color: colors.primary,
    marginBottom: spacing.md,
  },
});

// ─── Component ────────────────────────────────────────────────────────────────

export default function ProfileScreen() {
  const [token,   setToken]   = useState<string | null>(null);
  const [user,    setUser]    = useState<ProfileUser | null>(null);
  const [loading, setLoading] = useState(true);
  const [error,   setError]   = useState<string | null>(null);

  // ── Profile form state ────────────────────────────────────────────────────
  const [firstName,  setFirstName]  = useState('');
  const [lastName,   setLastName]   = useState('');
  const [email,      setEmail]      = useState('');
  const [phone,      setPhone]      = useState('');
  const [staffId,    setStaffId]    = useState('');
  const [profErrors, setProfErrors] = useState<ProfileErrors>({});
  const [profSaving, setProfSaving] = useState(false);
  const [profFlash,  setProfFlash]  = useState('');

  // ── Password form state ───────────────────────────────────────────────────
  const [currentPw,  setCurrentPw]  = useState('');
  const [newPw,      setNewPw]      = useState('');
  const [confirmPw,  setConfirmPw]  = useState('');
  const [pwErrors,   setPwErrors]   = useState<PasswordErrors>({});
  const [pwSaving,   setPwSaving]   = useState(false);
  const [pwFlash,    setPwFlash]    = useState('');

  // ── Preferences state ─────────────────────────────────────────────────────
  const [emailNotif,   setEmailNotif]   = useState(true);
  const [prefSaving,   setPrefSaving]   = useState(false);
  const [prefFlash,    setPrefFlash]    = useState('');

  // ── Logout ────────────────────────────────────────────────────────────────
  const [loggingOut, setLoggingOut] = useState(false);

  // ── Auth + fetch ──────────────────────────────────────────────────────────
  useEffect(() => {
    loadAuth().then((auth) => {
      if (!auth) { router.replace('/login'); return; }
      setToken(auth.token);
    });
  }, []);

  const fetchProfile = useCallback(async (tok: string) => {
    try {
      const res = await fetch(apiUrl('profile'), {
        headers: {
          'Accept':        'application/json',
          'Authorization': `Bearer ${tok}`,
        },
      });
      if (res.status === 401) { router.replace('/login'); return; }
      if (!res.ok) throw new Error(`Server error ${res.status}`);
      const data: { user: ProfileUser } = await res.json();
      const u = data.user;
      setUser(u);
      // Populate form fields
      setFirstName(u.first_name ?? '');
      setLastName(u.last_name ?? '');
      setEmail(u.email ?? '');
      setPhone(u.phone ?? '');
      setStaffId(u.student_staff_id ?? '');
      setEmailNotif(u.email_notifications ?? true);
      setError(null);
    } catch (e: any) {
      setError(e.message ?? 'Could not load profile.');
    }
  }, []);

  useEffect(() => {
    if (!token) return;
    setLoading(true);
    fetchProfile(token).finally(() => setLoading(false));
  }, [token, fetchProfile]);

  // ── Update Profile ────────────────────────────────────────────────────────
  async function handleUpdateProfile() {
    const errs: ProfileErrors = {};
    if (!firstName.trim()) errs.first_name = 'First name is required.';
    if (!lastName.trim())  errs.last_name  = 'Last name is required.';
    if (!email.trim())     errs.email      = 'Email is required.';
    if (Object.keys(errs).length > 0) { setProfErrors(errs); return; }

    if (!token) return;
    setProfSaving(true);
    setProfErrors({});
    setProfFlash('');

    try {
      const res = await fetch(apiUrl('profile'), {
        method: 'PUT',
        headers: {
          'Content-Type':  'application/json',
          'Accept':        'application/json',
          'Authorization': `Bearer ${token}`,
        },
        body: JSON.stringify({
          first_name:       firstName.trim(),
          last_name:        lastName.trim(),
          email:            email.trim(),
          phone:            phone.trim() || null,
          student_staff_id: staffId.trim() || null,
        }),
      });

      const data = await res.json();

      if (res.ok) {
        // Update stored user name/initials
        setUser(prev => prev ? {
          ...prev,
          first_name: data.user.first_name,
          last_name:  data.user.last_name,
          full_name:  data.user.full_name,
          initials:   data.user.initials,
          email:      data.user.email,
          phone:      data.user.phone,
          student_staff_id: data.user.student_staff_id,
        } : prev);
        setProfFlash('Profile updated successfully!');
        setTimeout(() => setProfFlash(''), 3000);
      } else {
        const raw = data?.errors ?? {};
        const serverErrs: ProfileErrors = {};
        for (const key of Object.keys(raw) as (keyof ProfileErrors)[]) {
          serverErrs[key] = Array.isArray(raw[key]) ? raw[key][0] : raw[key];
        }
        if (Object.keys(serverErrs).length === 0) {
          Alert.alert('Error', data?.message ?? 'Failed to update profile.');
        }
        setProfErrors(serverErrs);
      }
    } catch {
      Alert.alert('Error', 'Could not reach the server.');
    } finally {
      setProfSaving(false);
    }
  }

  // ── Update Password ───────────────────────────────────────────────────────
  async function handleUpdatePassword() {
    const errs: PasswordErrors = {};
    if (!currentPw) errs.current_password = 'Current password is required.';
    if (!newPw)     errs.password          = 'New password is required.';
    else if (newPw.length < 8)
      errs.password = 'Password must be at least 8 characters.';
    if (newPw !== confirmPw)
      errs.password = 'Passwords do not match.';
    if (Object.keys(errs).length > 0) { setPwErrors(errs); return; }

    if (!token) return;
    setPwSaving(true);
    setPwErrors({});
    setPwFlash('');

    try {
      const res = await fetch(apiUrl('profile/password'), {
        method: 'PUT',
        headers: {
          'Content-Type':  'application/json',
          'Accept':        'application/json',
          'Authorization': `Bearer ${token}`,
        },
        body: JSON.stringify({
          current_password:      currentPw,
          password:              newPw,
          password_confirmation: confirmPw,
        }),
      });

      const data = await res.json();

      if (res.ok) {
        setCurrentPw(''); setNewPw(''); setConfirmPw('');
        setPwFlash('Password updated successfully!');
        setTimeout(() => setPwFlash(''), 3000);
      } else {
        const raw = data?.errors ?? {};
        const serverErrs: PasswordErrors = {};
        for (const key of Object.keys(raw) as (keyof PasswordErrors)[]) {
          serverErrs[key] = Array.isArray(raw[key]) ? raw[key][0] : raw[key];
        }
        if (Object.keys(serverErrs).length === 0) {
          Alert.alert('Error', data?.message ?? 'Failed to update password.');
        }
        setPwErrors(serverErrs);
      }
    } catch {
      Alert.alert('Error', 'Could not reach the server.');
    } finally {
      setPwSaving(false);
    }
  }

  // ── Update Preferences ────────────────────────────────────────────────────
  async function handleUpdatePreferences(value: boolean) {
    setEmailNotif(value);
    if (!token) return;
    setPrefSaving(true);
    setPrefFlash('');

    try {
      const res = await fetch(apiUrl('profile/preferences'), {
        method: 'PUT',
        headers: {
          'Content-Type':  'application/json',
          'Accept':        'application/json',
          'Authorization': `Bearer ${token}`,
        },
        body: JSON.stringify({ email_notifications: value }),
      });

      if (res.ok) {
        setPrefFlash('Preferences saved!');
        setTimeout(() => setPrefFlash(''), 2500);
      } else {
        // Revert on failure
        setEmailNotif(!value);
        Alert.alert('Error', 'Failed to update preferences.');
      }
    } catch {
      setEmailNotif(!value);
      Alert.alert('Error', 'Could not reach the server.');
    } finally {
      setPrefSaving(false);
    }
  }

  // ── Logout ────────────────────────────────────────────────────────────────
  function confirmLogout() {
    Alert.alert(
      'Sign Out',
      'Are you sure you want to sign out of LabFix?',
      [
        { text: 'Cancel', style: 'cancel' },
        { text: 'Sign Out', style: 'destructive', onPress: handleLogout },
      ]
    );
  }

  async function handleLogout() {
    if (!token) return;
    setLoggingOut(true);
    try {
      // Tell the server to revoke the token
      await fetch(apiUrl('logout'), {
        method: 'POST',
        headers: {
          'Accept':        'application/json',
          'Authorization': `Bearer ${token}`,
        },
      });
    } catch {
      // Even if the server call fails, clear local auth
    } finally {
      await clearAuth();
      router.replace('/');
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

  if (error || !user) {
    return (
      <SafeAreaView style={s.root}>
        <StatusBar barStyle="light-content" backgroundColor={colors.bgPrimary} />
        <View style={s.centered}>
          <Text style={s.errorText}>{error ?? 'Could not load profile.'}</Text>
          <TouchableOpacity style={s.retryBtn} onPress={() => token && fetchProfile(token)}>
            <Text style={s.retryText}>Try Again</Text>
          </TouchableOpacity>
        </View>
      </SafeAreaView>
    );
  }

  const rc = roleColor(user.role);

  // ─── Main render ──────────────────────────────────────────────────────────

  return (
    <SafeAreaView style={s.root} edges={['top']}>
      <StatusBar barStyle="light-content" backgroundColor={colors.bgPrimary} />

      {/* ── Top Nav ── */}
      <View style={s.nav}>
        <TouchableOpacity onPress={() => router.back()} activeOpacity={0.7}>
          <Text style={s.navBack}>← Back</Text>
        </TouchableOpacity>
        <Text style={s.navTitle}>My Profile</Text>
        <View style={{ width: 60 }} />
      </View>

      <ScrollView
        showsVerticalScrollIndicator={false}
        contentContainerStyle={s.scroll}
        keyboardShouldPersistTaps="handled"
      >

        {/* ── Avatar Block ── */}
        <View style={s.avatarBlock}>
          <View style={s.avatarCircle}>
            <Text style={s.avatarText}>{user.initials}</Text>
          </View>
          <Text style={s.userName}>{user.full_name}</Text>
          <Text style={s.userEmail}>{user.email}</Text>
          <View style={[s.roleBadge, { backgroundColor: rc + '22', borderColor: rc + '55' }]}>
            <Text style={[s.roleBadgeText, { color: rc }]}>{roleLabel(user.role)}</Text>
          </View>
        </View>

        {/* ── Quick Info Strip ── */}
        <View style={s.infoStrip}>
          <View style={s.stripItem}>
            <Text style={s.stripLabel}>Account Status</Text>
            <View style={[
              s.stripBadge,
              user.is_active
                ? { backgroundColor: colors.successBg, borderColor: colors.successBorder }
                : { backgroundColor: colors.dangerBg,  borderColor: colors.dangerBorder  },
            ]}>
              <Text style={[
                s.stripBadgeText,
                { color: user.is_active ? colors.successLight : '#f87171' },
              ]}>
                {user.is_active ? 'Active' : 'Inactive'}
              </Text>
            </View>
          </View>
          <View style={s.stripDivider} />
          <View style={s.stripItem}>
            <Text style={s.stripLabel}>Ticket Submission</Text>
            <Text style={[
              s.stripValue,
              { color: user.can_submit_tickets ? colors.successLight : '#f87171' },
            ]}>
              {user.can_submit_tickets ? '✅ Enabled' : '❌ Disabled'}
            </Text>
          </View>
          <View style={s.stripDivider} />
          <View style={s.stripItem}>
            <Text style={s.stripLabel}>Member Since</Text>
            <Text style={s.stripValue}>{formatDate(user.created_at)}</Text>
          </View>
        </View>

        {/* ── Profile Information ── */}
        <View style={s.card}>
          <SectionTitle title="Profile Information" />

          {/* Flash */}
          {!!profFlash && <View style={s.flash}><Text style={s.flashText}>✓ {profFlash}</Text></View>}

          {/* Name row */}
          <View style={s.fieldRow}>
            <View style={[s.fieldGroup, { flex: 1 }]}>
              <Text style={s.label}>First Name</Text>
              <TextInput
                style={[s.input, !!profErrors.first_name && s.inputError]}
                value={firstName}
                onChangeText={(v) => { setFirstName(v); setProfErrors(e => ({ ...e, first_name: undefined })); }}
                placeholder="First name"
                placeholderTextColor={colors.textDisabled}
                editable={!profSaving}
              />
              <FieldError message={profErrors.first_name} />
            </View>
            <View style={[s.fieldGroup, { flex: 1 }]}>
              <Text style={s.label}>Last Name</Text>
              <TextInput
                style={[s.input, !!profErrors.last_name && s.inputError]}
                value={lastName}
                onChangeText={(v) => { setLastName(v); setProfErrors(e => ({ ...e, last_name: undefined })); }}
                placeholder="Last name"
                placeholderTextColor={colors.textDisabled}
                editable={!profSaving}
              />
              <FieldError message={profErrors.last_name} />
            </View>
          </View>

          {/* Email */}
          <View style={s.fieldGroup}>
            <Text style={s.label}>Email Address</Text>
            <TextInput
              style={[s.input, !!profErrors.email && s.inputError]}
              value={email}
              onChangeText={(v) => { setEmail(v); setProfErrors(e => ({ ...e, email: undefined })); }}
              placeholder="your@email.com"
              placeholderTextColor={colors.textDisabled}
              keyboardType="email-address"
              autoCapitalize="none"
              editable={!profSaving}
            />
            <FieldError message={profErrors.email} />
          </View>

          {/* Phone + Student ID */}
          <View style={s.fieldRow}>
            <View style={[s.fieldGroup, { flex: 1 }]}>
              <Text style={s.label}>Phone <Text style={s.optional}>(optional)</Text></Text>
              <TextInput
                style={s.input}
                value={phone}
                onChangeText={setPhone}
                placeholder="+63 9xx..."
                placeholderTextColor={colors.textDisabled}
                keyboardType="phone-pad"
                editable={!profSaving}
              />
            </View>
            <View style={[s.fieldGroup, { flex: 1 }]}>
              <Text style={s.label}>Student/Staff ID <Text style={s.optional}>(optional)</Text></Text>
              <TextInput
                style={s.input}
                value={staffId}
                onChangeText={setStaffId}
                placeholder="e.g. 2021-001"
                placeholderTextColor={colors.textDisabled}
                editable={!profSaving}
              />
            </View>
          </View>

          <TouchableOpacity
            style={[s.saveBtn, profSaving && s.saveBtnDisabled]}
            onPress={handleUpdateProfile}
            disabled={profSaving}
            activeOpacity={0.85}
          >
            {profSaving
              ? <ActivityIndicator color="#fff" />
              : <Text style={s.saveBtnText}>Update Profile</Text>}
          </TouchableOpacity>
        </View>

        {/* ── Change Password ── */}
        <View style={s.card}>
          <SectionTitle title="Change Password" />

          {!!pwFlash && <View style={s.flash}><Text style={s.flashText}>✓ {pwFlash}</Text></View>}

          <View style={s.fieldGroup}>
            <Text style={s.label}>Current Password</Text>
            <TextInput
              style={[s.input, !!pwErrors.current_password && s.inputError]}
              value={currentPw}
              onChangeText={(v) => { setCurrentPw(v); setPwErrors(e => ({ ...e, current_password: undefined })); }}
              placeholder="Current password"
              placeholderTextColor={colors.textDisabled}
              secureTextEntry
              editable={!pwSaving}
            />
            <FieldError message={pwErrors.current_password} />
          </View>

          <View style={s.fieldGroup}>
            <Text style={s.label}>New Password</Text>
            <TextInput
              style={[s.input, !!pwErrors.password && s.inputError]}
              value={newPw}
              onChangeText={(v) => { setNewPw(v); setPwErrors(e => ({ ...e, password: undefined })); }}
              placeholder="Minimum 8 characters"
              placeholderTextColor={colors.textDisabled}
              secureTextEntry
              editable={!pwSaving}
            />
            <FieldError message={pwErrors.password} />
          </View>

          <View style={s.fieldGroup}>
            <Text style={s.label}>Confirm New Password</Text>
            <TextInput
              style={s.input}
              value={confirmPw}
              onChangeText={setConfirmPw}
              placeholder="Re-enter new password"
              placeholderTextColor={colors.textDisabled}
              secureTextEntry
              editable={!pwSaving}
            />
          </View>

          <TouchableOpacity
            style={[s.saveBtn, pwSaving && s.saveBtnDisabled]}
            onPress={handleUpdatePassword}
            disabled={pwSaving}
            activeOpacity={0.85}
          >
            {pwSaving
              ? <ActivityIndicator color="#fff" />
              : <Text style={s.saveBtnText}>Update Password</Text>}
          </TouchableOpacity>
        </View>

        {/* ── Notification Preferences ── */}
        <View style={s.card}>
          <SectionTitle title="Notification Preferences" />

          {!!prefFlash && <View style={s.flash}><Text style={s.flashText}>✓ {prefFlash}</Text></View>}

          <View style={s.toggleRow}>
            <View style={s.toggleInfo}>
              <Text style={s.toggleLabel}>Email Notifications</Text>
              <Text style={s.toggleDesc}>
                Receive email updates when your ticket status changes
              </Text>
            </View>
            <Switch
              value={emailNotif}
              onValueChange={handleUpdatePreferences}
              disabled={prefSaving}
              trackColor={{ false: 'rgba(255,255,255,0.12)', true: colors.primary }}
              thumbColor="#fff"
            />
          </View>
        </View>

        {/* ── Sign Out ── */}
        <View style={s.dangerCard}>
          <Text style={s.dangerTitle}>Sign Out</Text>
          <Text style={s.dangerDesc}>
            Sign out of your LabFix account on this device.
          </Text>
          <TouchableOpacity
            style={[s.signOutBtn, loggingOut && s.saveBtnDisabled]}
            onPress={confirmLogout}
            disabled={loggingOut}
            activeOpacity={0.85}
          >
            {loggingOut
              ? <ActivityIndicator size="small" color="#f87171" />
              : <Text style={s.signOutBtnText}>Sign Out</Text>}
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
  navBack:  { fontSize: font.sm, fontWeight: font.semibold, color: colors.primary, width: 60 },
  navTitle: { fontSize: font.base, fontWeight: font.bold, color: colors.textPrimary },

  // Avatar block
  avatarBlock: {
    alignItems: 'center',
    paddingTop: spacing.xl,
    paddingBottom: spacing.lg,
    gap: spacing.xs + 2,
  },
  avatarCircle: {
    width: 80,
    height: 80,
    borderRadius: radius.full,
    backgroundColor: colors.primary,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: spacing.sm,
    shadowColor: colors.primary,
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.4,
    shadowRadius: 10,
    elevation: 6,
  },
  avatarText: { fontSize: font.xl3, fontWeight: font.bold, color: '#fff' },
  userName:   { fontSize: font.xl, fontWeight: font.bold, color: colors.textPrimary },
  userEmail:  { fontSize: font.sm, color: colors.textMuted },
  roleBadge: {
    borderWidth: 1,
    borderRadius: radius.full,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.xs,
    marginTop: spacing.xs,
  },
  roleBadgeText: { fontSize: font.sm, fontWeight: font.semibold },

  // Quick info strip
  infoStrip: {
    flexDirection: 'row',
    marginHorizontal: spacing.lg,
    marginBottom: spacing.md,
    backgroundColor: colors.bgCard,
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radius.xl,
    padding: spacing.md,
  },
  stripItem:    { flex: 1, alignItems: 'center', gap: spacing.xs },
  stripDivider: { width: 1, backgroundColor: colors.borderLight, marginHorizontal: spacing.xs },
  stripLabel:   { fontSize: font.xs - 1, color: colors.textMuted, textAlign: 'center' },
  stripValue:   { fontSize: font.xs, fontWeight: font.medium, color: colors.textPrimary, textAlign: 'center' },
  stripBadge: {
    borderWidth: 1,
    borderRadius: radius.full,
    paddingHorizontal: spacing.sm,
    paddingVertical: 2,
  },
  stripBadgeText: { fontSize: font.xs - 1, fontWeight: font.bold },

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

  // Flash message
  flash: {
    backgroundColor: colors.successBg,
    borderWidth: 1,
    borderColor: colors.successBorder,
    borderRadius: radius.md,
    padding: spacing.sm + 2,
    marginBottom: spacing.md,
  },
  flashText: { color: colors.successLight, fontSize: font.sm, fontWeight: font.medium },

  // Fields
  fieldRow:   { flexDirection: 'row', gap: spacing.sm },
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
    fontSize: font.sm,
  },
  inputError: {
    borderColor: colors.dangerBorder,
    backgroundColor: 'rgba(239,68,68,0.06)',
  },

  // Save button
  saveBtn: {
    backgroundColor: colors.primary,
    borderRadius: radius.lg,
    paddingVertical: spacing.md,
    alignItems: 'center',
    marginTop: spacing.xs,
  },
  saveBtnDisabled: { opacity: 0.65 },
  saveBtnText:     { fontSize: font.base, fontWeight: font.bold, color: '#fff' },

  // Toggle row
  toggleRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    gap: spacing.md,
  },
  toggleInfo:  { flex: 1 },
  toggleLabel: { fontSize: font.base, fontWeight: font.medium, color: colors.textPrimary },
  toggleDesc:  { fontSize: font.xs, color: colors.textMuted, marginTop: 3, lineHeight: 17 },

  // Danger zone
  dangerCard: {
    marginHorizontal: spacing.lg,
    marginBottom: spacing.md,
    backgroundColor: 'rgba(239,68,68,0.05)',
    borderWidth: 1,
    borderColor: 'rgba(239,68,68,0.25)',
    borderRadius: radius.xl,
    padding: spacing.lg,
    gap: spacing.sm,
  },
  dangerTitle: { fontSize: font.base, fontWeight: font.bold, color: colors.danger },
  dangerDesc:  { fontSize: font.sm, color: colors.textMuted, lineHeight: 19 },
  signOutBtn: {
    backgroundColor: colors.dangerBg,
    borderWidth: 1,
    borderColor: colors.dangerBorder,
    borderRadius: radius.lg,
    paddingVertical: spacing.md,
    alignItems: 'center',
  },
  signOutBtnText: { fontSize: font.base, fontWeight: font.semibold, color: '#f87171' },

  // Error state
  errorText: { fontSize: font.base, color: colors.textSecondary, textAlign: 'center' },
  retryBtn: {
    backgroundColor: colors.primary,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.sm + 2,
    borderRadius: radius.full,
  },
  retryText: { fontSize: font.base, fontWeight: font.bold, color: '#fff' },
});