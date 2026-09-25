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
 * Tab structure (matches ITTabBar.tsx, which keeps its own independent
 * route list — dashboard, queue, assignments, knowledge-base):
 *   📊 dashboard        → app/(it)/dashboard.tsx
 *   🎫 queue            → app/(it)/queue/index.tsx  (+ nested [id], [id]/edit)
 *   👤 assignments      → app/(it)/assignments/index.tsx (+ nested)
 *   📚 knowledge-base   → app/(it)/knowledge-base/index.tsx (+ nested)
 *
 * Expo Router registers ALL files under app/(it)/ automatically.
 * Only "dashboard" is declared as a Tabs.Screen below — it's a flat file,
 * so its route name genuinely is "dashboard". "queue", "assignments", and
 * "knowledge-base" are folders, and Expo Router names their nested routes
 * by full path (queue/index, queue/[id], etc.), never by the bare folder
 * name, so declaring <Tabs.Screen name="queue"> here never matched any
 * route Expo Router actually registers. That produced "No route named
 * queue exists" warnings and broken navigation. ITTabBar doesn't need
 * these declared here at all — it keeps its own independent route list
 * and navigates via router.push(), same fix as the public zone's
 * features/about issue earlier.
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
      <Tabs.Screen
        name="dashboard"
        options={{ title: 'Dashboard' }}
      />

      {/*
        "queue", "assignments", and "knowledge-base" are intentionally NOT
        declared as Tabs.Screen entries here — see the file header comment
        for why. ITTabBar renders and navigates to them independently.
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