/**
 * components/ui/UserTabBar.tsx
 *
 * Bottom tab bar for the authenticated User zone (student / staff).
 * Rendered by app/(tabs)/_layout.tsx.
 *
 * Tabs mirror the web NavUser.vue links:
 *   🏠 Dashboard    → /(tabs)/
 *   🎫 Report       → /(tabs)/report      (create a new ticket)
 *   📋 My Reports   → /(tabs)/my-reports
 *   📚 Knowledge    → /(tabs)/knowledge-base
 *   🖥️ Lab Status  → /(tabs)/lab-status
 */

import React from 'react';
import {
  View,
  Text,
  TouchableOpacity,
  StyleSheet,
} from 'react-native';
import { BottomTabBarProps } from '@react-navigation/bottom-tabs';
import { router, usePathname } from 'expo-router';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { colors, font, spacing } from '@/constants/theme';

type TabDef = {
  key:   string;
  label: string;
  icon:  string;
  route: string;
};

const TABS: TabDef[] = [
  { key: 'index',          label: 'Dashboard', icon: '🏠', route: '/(tabs)/'              },
  { key: 'report',         label: 'Report',    icon: '🎫', route: '/(tabs)/report'        },
  { key: 'my-reports',     label: 'My Reports',icon: '📋', route: '/(tabs)/my-reports'   },
  { key: 'knowledge-base', label: 'KB',        icon: '📚', route: '/(tabs)/knowledge-base'},
  { key: 'lab-status',     label: 'Labs',      icon: '🖥️', route: '/(tabs)/lab-status'   },
];

export function UserTabBar(_props: BottomTabBarProps) {
  const insets  = useSafeAreaInsets();
  const pathname = usePathname();

  function isActive(tab: TabDef): boolean {
    if (tab.key === 'index') return pathname === '/' || pathname === '/(tabs)/';
    return pathname.includes(tab.key);
  }

  return (
    <View style={[s.bar, { paddingBottom: insets.bottom > 0 ? insets.bottom : spacing.sm }]}>
      {TABS.map((tab) => {
        const active = isActive(tab);
        return (
          <TouchableOpacity
            key={tab.key}
            style={s.tab}
            onPress={() => router.replace(tab.route as any)}
            activeOpacity={0.7}
          >
            <Text style={s.icon}>{tab.icon}</Text>
            <Text style={[s.label, active && s.labelActive]}>{tab.label}</Text>
            {active && <View style={s.activeDot} />}
          </TouchableOpacity>
        );
      })}
    </View>
  );
}

const s = StyleSheet.create({
  bar: {
    flexDirection: 'row',
    backgroundColor: 'rgba(20,20,20,0.97)',
    borderTopWidth: 1,
    borderTopColor: colors.border,
    paddingTop: spacing.xs + 2,
  },
  tab: {
    flex: 1,
    alignItems: 'center',
    gap: 3,
    paddingTop: spacing.xs,
    position: 'relative',
  },
  icon: {
    fontSize: 18,
    lineHeight: 22,
  },
  label: {
    fontSize: font.xs - 1,
    color: colors.textMuted,
    fontWeight: font.medium,
  },
  labelActive: {
    color: colors.primary,
    fontWeight: font.semibold,
  },
  activeDot: {
    position: 'absolute',
    top: 0,
    width: 3,
    height: 3,
    borderRadius: 2,
    backgroundColor: colors.primary,
  },
});