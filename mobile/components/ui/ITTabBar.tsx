/**
 * components/ui/ITTabBar.tsx
 *
 * Bottom tab bar for the authenticated IT zone (it-support / admin).
 * Rendered by app/(it)/_layout.tsx.
 *
 * 4 tabs — mirrors the web NavIT.vue links:
 *   📊 Dashboard  → /(it)/dashboard
 *   🎫 Queue      → /(it)/queue         (all open tickets, self-assign)
 *   👤 Assigned   → /(it)/assignments   (my assignments)
 *   📚 KB Mgmt    → /(it)/knowledge-base
 *
 * Visual distinction from UserTabBar:
 *   - Avatar accent colour is amber (matches the IT HTML mockup) vs teal for users
 *   - Active dot uses the same primary teal but the overall bar is slightly darker
 *     to hint at the elevated role context
 *
 * Active detection:
 *   - Dashboard: exact match on /(it)/dashboard
 *   - Queue:     any pathname that starts with /it/queue (covers /it/queue/[id]/edit etc.)
 *   - Assigned:  any pathname that starts with /it/assignments
 *   - KB Mgmt:   any pathname that starts with /it/knowledge-base
 *   Each tab un-highlights itself when a child screen within another tab is open,
 *   so only one tab is ever active at a time.
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
import { colors, font, spacing, radius } from '@/constants/theme';

// ─── Tab definitions ──────────────────────────────────────────────────────────

type TabDef = {
  /** Unique key — also used for active-detection prefix matching */
  key:    string;
  label:  string;
  icon:   string;
  /** Full Expo Router route string */
  route:  string;
  /** If true, use exact pathname match instead of startsWith */
  exact?: boolean;
};

const TABS: TabDef[] = [
  {
    key:   'dashboard',
    label: 'Dashboard',
    icon:  '📊',
    route: '/(it)/dashboard',
    exact: true,
  },
  {
    key:   'queue',
    label: 'Queue',
    icon:  '🎫',
    route: '/(it)/queue',
  },
  {
    key:   'assignments',
    label: 'Assigned',
    icon:  '👤',
    route: '/(it)/assignments',
  },
  {
    key:   'knowledge-base',
    label: 'KB Mgmt',
    icon:  '📚',
    route: '/(it)/knowledge-base',
  },
];

// ─── Component ────────────────────────────────────────────────────────────────

export function ITTabBar(_props: BottomTabBarProps) {
  const insets   = useSafeAreaInsets();
  const pathname = usePathname();

  function isActive(tab: TabDef): boolean {
    if (tab.exact) {
      // Exact match — normalise trailing slash differences
      return (
        pathname === tab.route ||
        pathname === tab.route.replace('/(it)/', '/it/')
      );
    }
    // Prefix match — covers nested screens like /it/queue/42/edit
    const prefix = tab.route.replace('/(it)/', '/it/');
    return pathname.startsWith(prefix) || pathname.startsWith(tab.route);
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
            onPress={() => router.replace(tab.route as any)}
            activeOpacity={0.7}
          >
            {/* Active top indicator dot */}
            {active && <View style={s.activeDot} />}

            <Text
              style={[
                s.icon,
                // Subtle glow on iOS for active icon
                active && Platform.OS === 'ios' && s.iconActiveIOS,
              ]}
            >
              {tab.icon}
            </Text>

            <Text style={[s.label, active && s.labelActive]}>
              {tab.label}
            </Text>
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
    // Slightly darker than user bar to differentiate the IT context
    backgroundColor: 'rgba(16,16,20,0.98)',
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

  // Top accent dot (sits above the icon)
  activeDot: {
    position: 'absolute',
    top: 0,
    width: 28,
    height: 3,
    borderRadius: radius.full,
    backgroundColor: colors.primary,
  },

  icon: {
    fontSize: 19,
    lineHeight: 23,
  },

  // iOS shadow glow for active tab icon
  iconActiveIOS: {
    shadowColor:   colors.primary,
    shadowOffset:  { width: 0, height: 0 },
    shadowOpacity: 0.75,
    shadowRadius:  5,
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
});