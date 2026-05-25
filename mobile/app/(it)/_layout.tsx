/**
 * app/(it)/_layout.tsx  —  IT Zone Layout
 *
 * Route group for authenticated IT Support / Admin users.
 * Mirrors the role:it-support,admin middleware on the web's IT route group.
 *
 * Auth guard behaviour (mirrors CheckRole.php middleware):
 *   1. Load SecureStore credentials via loadAuth()
 *   2. If no token → redirect to /login
 *   3. If token exists but role is NOT 'it-support' or 'admin'
 *      → redirect to their correct home (/(tabs)/dashboard for students/staff)
 *   4. Only 'it-support' and 'admin' roles proceed to render the tab layout
 *
 * Note on admin accessing IT zone:
 *   Admin users can navigate to /(it)/ as a convenience — the web version
 *   lets admins view the IT queue and assignments. However, the admin's
 *   primary home is /(admin)/dashboard. The ITTabBar shows a subtle "(Admin)"
 *   label next to the user's name in the nav (mirrors NavIT.vue behaviour)
 *   when role === 'admin'.
 *
 * Tab structure (4 tabs, matches ITTabBar.tsx):
 *   📊 dashboard        → app/(it)/dashboard.tsx
 *   🎫 queue            → app/(it)/queue/index.tsx  (+ nested [id], [id]/edit)
 *   👤 assignments      → app/(it)/assignments/index.tsx (+ nested)
 *   📚 knowledge-base   → app/(it)/knowledge-base/index.tsx (+ nested)
 *
 * Expo Router registers ALL files under app/(it)/ automatically.
 * Screens that are NOT direct tab roots (e.g. queue/[id].tsx) are hidden
 * from the tab bar via tabBarButton: () => null.
 */

import { useEffect, useState } from 'react';
import { Tabs, router } from 'expo-router';
import { View, ActivityIndicator, StyleSheet } from 'react-native';
import { ITTabBar } from '@/components/ui/ITTabBar';
import { loadAuth, AuthUser } from '@/utils/auth';
import { colors } from '@/constants/theme';

// Roles that are allowed into this zone
const ALLOWED_ROLES: AuthUser['role'][] = ['it-support', 'admin'];

export default function ITLayout() {
  const [checked, setChecked] = useState(false);

  useEffect(() => {
    loadAuth().then((auth) => {
      if (!auth) {
        // No credentials at all → login
        router.replace('/login');
        return;
      }

      if (!ALLOWED_ROLES.includes(auth.user.role)) {
        // Wrong role (student / staff) → send to their zone
        router.replace('/(tabs)/dashboard' as any);
        return;
      }

      // Authorised — allow render
      setChecked(true);
    });
  }, []);

  // Block render until auth check resolves — prevents a flash of tab UI
  if (!checked) {
    return (
      <View style={s.splash}>
        <ActivityIndicator size="large" color={colors.primary} />
      </View>
    );
  }

  return (
    <Tabs
      tabBar={(props) => <ITTabBar {...props} />}
      screenOptions={{ headerShown: false }}
    >
      {/* ── Primary tab screens ─────────────────────────────────────── */}

      <Tabs.Screen
        name="dashboard"
        options={{ title: 'Dashboard' }}
      />

      {/*
        "queue" is a folder — Expo Router treats queue/index.tsx as the
        root of this tab. Nested routes (queue/[id].tsx etc.) are registered
        automatically but hidden from the tab bar below.
      */}
      <Tabs.Screen
        name="queue"
        options={{ title: 'Queue' }}
      />

      <Tabs.Screen
        name="assignments"
        options={{ title: 'Assigned' }}
      />

      <Tabs.Screen
        name="knowledge-base"
        options={{ title: 'KB Mgmt' }}
      />

      {/* ── Hidden nested screens ────────────────────────────────────── */}
      {/*
        These are folder-level groups that contain nested routes.
        We declare them here with tabBarButton: () => null so they
        don't appear as extra tabs while still being accessible via
        router.push() from within their parent tab screens.

        Expo Router will pick up:
          queue/[id].tsx          → ticket detail
          queue/[id]/edit.tsx     → ticket edit
          assignments/[id].tsx    → assignment detail
          assignments/[id]/edit.tsx → assignment update
          knowledge-base/create.tsx → new article
          knowledge-base/[id]/show.tsx → article preview
          knowledge-base/[id]/edit.tsx → article edit

        All of the above inherit this layout's tab bar but suppress the
        tab highlight for any tab other than their parent.
      */}
    </Tabs>
  );
}

// ─── Styles ───────────────────────────────────────────────────────────────────

const s = StyleSheet.create({
  splash: {
    flex: 1,
    backgroundColor: colors.bgPrimary,
    alignItems: 'center',
    justifyContent: 'center',
  },
});