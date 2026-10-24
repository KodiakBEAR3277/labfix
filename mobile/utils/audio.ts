/**
 * utils/audio.ts
 *
 * Optional access to expo-audio.
 *
 * expo-audio ships a native module (`ExpoAudio`). A JS bundle running in a
 * binary that wasn't built with it — for example a dev client built before the
 * dependency was added, or an older Expo Go — throws the moment expo-audio is
 * imported.
 *
 * That matters more here than it looks: expo-router eagerly evaluates every
 * route file at startup, so an unguarded `import { useAudioPlayer } from
 * 'expo-audio'` in a component used by a route takes down the whole route
 * tree — the screen reports "missing the required default export" and vanishes
 * from the navigator, even though the file is perfectly fine.
 *
 * Requiring the module through here (instead of a static import) keeps that
 * failure contained to the voice UI. Rebuild the dev client
 * (`npx expo run:android`, or a fresh EAS dev build) to enable recording and
 * playback.
 */

type ExpoAudio = typeof import('expo-audio');

let mod: ExpoAudio | null = null;

try {
  // Metro resolves this statically; the try/catch covers binaries that were
  // built without the ExpoAudio native module.
  // eslint-disable-next-line @typescript-eslint/no-require-imports
  mod = require('expo-audio') as ExpoAudio;
} catch {
  mod = null;
}

/** True when the running binary actually contains the ExpoAudio native module. */
export const isAudioAvailable = mod !== null;

/** The expo-audio module, or null when the native module isn't present. */
export function getAudio(): ExpoAudio | null {
  return mod;
}
