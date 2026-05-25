/**
 * app/(tabs)/_layout.tsx
 *
 * Layout for the authenticated User zone (student / staff).
 * Uses a custom UserTabBar for the bottom navigation.
 *
 * Route resolution after login:
 *   dashboardRouteForRole('student' | 'staff') → '/(tabs)/dashboard'
 *   This layout hosts that group, with 'index' being the dashboard.
 *
 * Guard: if SecureStore has no token on mount, redirect back to /login.
 * This prevents direct navigation to these screens without auth.
 */

import { useEffect, useState } from 'react';
import { Tabs } from 'expo-router';
import { router } from 'expo-router';
import { UserTabBar } from '@/components/ui/UserTabBar';
import { loadAuth } from '@/utils/auth';

export default function TabsLayout() {
  const [checked, setChecked] = useState(false);

  useEffect(() => {
    loadAuth().then((auth) => {
      if (!auth) {
        router.replace('/login');
      } else {
        setChecked(true);
      }
    });
  }, []);

  // Don't render tabs until auth check completes — prevents flash of content
  if (!checked) return null;

  return (
    <Tabs
      tabBar={(props) => <UserTabBar {...props} />}
      screenOptions={{ headerShown: false }}
    >
      {/* Dashboard — the root screen of this group */}
      <Tabs.Screen name="dashboard"          options={{ title: 'Dashboard'     }} />
      <Tabs.Screen name="report"         options={{ title: 'Report Issue'  }} />
      <Tabs.Screen name="my-reports"     options={{ title: 'My Reports'    }} />
      <Tabs.Screen name="knowledge-base" options={{ title: 'Knowledge Base'}} />
      <Tabs.Screen name="lab-status"     options={{ title: 'Lab Status'    }} />
    </Tabs>
  );
}