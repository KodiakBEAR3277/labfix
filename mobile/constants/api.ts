/**
 * constants/api.ts
 *
 * Central place for the backend base URL so every screen imports from here
 * rather than hard-coding the URL inline.
 *
 * Development: point at your local machine's LAN IP so a physical device
 * or Android emulator can reach the Laravel dev server.
 * Production:  swap for the deployed domain before building a release APK.
 *
 * Usage:
 *   import { API_BASE } from '@/constants/api';
 *   fetch(`${API_BASE}/api/contact-info`)
 */

// Replace with your machine's local IP when testing on a physical device.
// 10.0.2.2 is the Android emulator alias for localhost.
// iOS simulator can use localhost directly.
export const API_BASE =
  process.env.EXPO_PUBLIC_API_URL ?? 'http://192.168.1.9:8000';

/** Convenience helper — returns full URL for an API path */
export const apiUrl = (path: string) =>
  `${API_BASE}/api/${path.replace(/^\//, '')}`;