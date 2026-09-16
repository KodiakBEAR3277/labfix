/**
 * app/(public)/_layout.tsx  —  Public Zone Tab Layout
 *
 * Bottom tab bar shown on all pre-authentication screens.
 * Tabs: Home · Features · About · Contact
 *
 * "Features" and "About" are sections inside the Landing screen, not
 * separate routes — tapping them scrolls to that section via a shared
 * event emitter, keeping the URL at "/" (matching the web behaviour where
 * #features and #about are hash anchors on the same page).
 *
 * Only "Contact" is a separate route (/contact).
 *
 * Note: "Features" and "About" are NOT registered as Tabs.Screen entries
 * here on purpose — PublicTabBar keeps its own independent tab list and
 * never navigates to them as real routes, it only fires a scroll event.
 * Registering them as screens with no backing file was causing Expo
 * Router's "No route named X exists" warnings on every load.
 *
 * Architecture note:
 *   We use a custom tab bar component (PublicTabBar) instead of the default
 *   Expo Router Tabs so we can fire scroll events for the in-page sections
 *   rather than navigating away.
 */

import { Tabs } from 'expo-router';
import { Platform } from 'react-native';
import { PublicTabBar } from '@/components/ui/PublicTabBar';

export default function PublicLayout() {
  return (
    <Tabs
      tabBar={(props) => <PublicTabBar {...props} />}
      screenOptions={{
        headerShown: false,
      }}
    >
      {/* Home tab — renders app/(public)/index.tsx */}
      <Tabs.Screen
        name="index"
        options={{ title: 'Home' }}
      />

      {/* Contact tab — renders app/(public)/contact.tsx */}
      <Tabs.Screen
        name="contact"
        options={{ title: 'Contact' }}
      />
    </Tabs>
  );
}