/**
 * utils/auth.ts
 *
 * Thin wrapper around expo-secure-store for persisting the Sanctum API
 * token and the authenticated user object between app sessions.
 *
 * The web frontend uses session-based auth (cookies), but the mobile app
 * uses the Sanctum token API defined in routes/api.php and
 * App/Http/Controllers/Api/AuthController.php.
 *
 * Keys stored:
 *   labfix_token  — plain-text API token string
 *   labfix_user   — JSON-serialised user object
 *
 * Usage:
 *   import { saveAuth, loadAuth, clearAuth } from '@/utils/auth';
 */

import * as SecureStore from 'expo-secure-store';

const TOKEN_KEY = 'labfix_token';
const USER_KEY  = 'labfix_user';

export type AuthUser = {
  id:    number;
  name:  string;
  email: string;
  role:  'student' | 'staff' | 'it-support' | 'admin';
};

/** Persist token + user after a successful login */
export async function saveAuth(token: string, user: AuthUser): Promise<void> {
  await SecureStore.setItemAsync(TOKEN_KEY, token);
  await SecureStore.setItemAsync(USER_KEY, JSON.stringify(user));
}

/** Load persisted auth — returns null if nothing is stored */
export async function loadAuth(): Promise<{ token: string; user: AuthUser } | null> {
  const token = await SecureStore.getItemAsync(TOKEN_KEY);
  const raw   = await SecureStore.getItemAsync(USER_KEY);

  if (!token || !raw) return null;

  try {
    const user = JSON.parse(raw) as AuthUser;
    return { token, user };
  } catch {
    return null;
  }
}

/** Remove stored credentials on logout */
export async function clearAuth(): Promise<void> {
  await SecureStore.deleteItemAsync(TOKEN_KEY);
  await SecureStore.deleteItemAsync(USER_KEY);
}

/**
 * Map a user's role to their home route in the authenticated tab group.
 * Adjust the route strings once the (tabs) group screens are created.
 */
export function dashboardRouteForRole(role: AuthUser['role']): string {
  switch (role) {
    case 'admin':      return '/(admin)/dashboard';
    case 'it-support': return '/(it)/dashboard';
    default:           return '/(tabs)/dashboard';
  }
}