/**
 * components/ui/PublicTabBar.tsx
 *
 * Custom bottom tab bar for the public (pre-auth) zone.
 * Rendered by app/(public)/_layout.tsx.
 *
 * Tabs:
 *   🏠 Home     → navigates to (public)/index
 *   ✦ Features  → stays on (public)/index, fires 'scrollToFeatures' event
 *   ℹ About     → stays on (public)/index, fires 'scrollToAbout' event
 *   📞 Contact  → navigates to (public)/contact
 *
 * The scroll-section tabs emit events via a simple EventEmitter that
 * app/(public)/index.tsx subscribes to, so the tab bar can trigger
 * in-page scrolling without unmounting the landing screen.
 */

import React from 'react';
import {
  View,
  Text,
  TouchableOpacity,
  StyleSheet,
  Platform,
} from 'react-native';
import { BottomTabBarProps } from '@react-navigation/bottom-tabs';
import { router, usePathname } from 'expo-router';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { colors, font, spacing } from '@/constants/theme';
import { landingScrollEmitter } from '@/utils/landingScrollEmitter';

// ─── Tab definitions ──────────────────────────────────────────────────────────

type TabDef = {
  key: string;
  label: string;
  icon: string;
  /** If set, fires a scroll event instead of navigating */
  scrollTarget?: 'features' | 'about';
  /** Route to push (relative to the public group) */
  route?: string;
};

const TABS: TabDef[] = [
  {
    key: 'index',
    label: 'Home',
    icon: '🏠',
    route: '/',
  },
  {
    key: 'features',
    label: 'Features',
    icon: '✦',
    scrollTarget: 'features',
  },
  {
    key: 'about',
    label: 'About',
    icon: 'ℹ',
    scrollTarget: 'about',
  },
  {
    key: 'contact',
    label: 'Contact',
    icon: '📞',
    route: '/contact',
  },
];

// ─── Component ────────────────────────────────────────────────────────────────

export function PublicTabBar(_props: BottomTabBarProps) {
  const insets = useSafeAreaInsets();
  const pathname = usePathname();

  function handlePress(tab: TabDef) {
    if (tab.scrollTarget) {
      // Navigate to home first if we're on contact, then scroll
      if (pathname !== '/') {
        router.replace('/');
        // Small delay so the landing screen mounts before we fire the event
        setTimeout(() => {
          landingScrollEmitter.emit(tab.scrollTarget!);
        }, 150);
      } else {
        landingScrollEmitter.emit(tab.scrollTarget!);
      }
      return;
    }

    if (tab.route) {
      router.replace(tab.route as any);
    }
  }

  function isActive(tab: TabDef): boolean {
    if (tab.scrollTarget) return false; // section tabs never show as active
    if (tab.key === 'index') return pathname === '/';
    if (tab.key === 'contact') return pathname === '/contact';
    return false;
  }

  return (
    <View
      style={[
        s.bar,
        { paddingBottom: insets.bottom > 0 ? insets.bottom : spacing.sm },
      ]}
    >
      {TABS.map((tab) => {
        const active = isActive(tab);
        return (
          <TouchableOpacity
            key={tab.key}
            style={s.tab}
            onPress={() => handlePress(tab)}
            activeOpacity={0.7}
          >
            <Text style={[s.icon, active && s.iconActive]}>{tab.icon}</Text>
            <Text style={[s.label, active && s.labelActive]}>{tab.label}</Text>
            {active && <View style={s.activeDot} />}
          </TouchableOpacity>
        );
      })}
    </View>
  );
}

// ─── Styles ───────────────────────────────────────────────────────────────────

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
    color: colors.textMuted,
  },
  iconActive: {
    // Drop shadow glow effect for active tab icon
    ...(Platform.OS === 'ios'
      ? { shadowColor: colors.primary, shadowOffset: { width: 0, height: 0 }, shadowOpacity: 0.8, shadowRadius: 4 }
      : {}),
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