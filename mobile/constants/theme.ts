/**
 * constants/theme.ts
 *
 * Design tokens — migrated to match the web app's light theme. Mirrors
 * the same before/after decisions made in public/css/base/variables.css:
 * textPrimary goes dark instead of white, surfaceRaised/Hover go from a
 * light tint to a dark tint (since it's now sitting on a light base
 * instead of a dark one), primaryDark gets slightly deeper for
 * text-on-white contrast. Status colors (success/warning/danger/info)
 * are left as-is — they're already saturated enough to read on both
 * light and dark backgrounds.
 *
 * NOT touched here on purpose: login.tsx and register.tsx intentionally
 * hardcode their own dark background/card colors (the background-photo
 * look), matching the web auth pages staying dark. Those two screens
 * need their own pass to hardcode their text colors too, the same way
 * the web auth pages did, otherwise text that references colors.textPrimary
 * will go dark-on-dark once this file changes. Flagged, not fixed here.
 *
 * Exact background hex values are a best-effort match to the web
 * migration, not a byte-for-byte copy of variables.css (I don't have
 * that file's literal current values in front of me) — worth a visual
 * check once this is running rather than trusting it blind.
 */

export const colors = {
  // Brand
  primary:      '#2dd4bf',
  primaryDark:  '#0d9488',
  primaryLight: 'rgba(45,212,191,0.10)',

  // Backgrounds
  bgPrimary:    '#f9fafb',
  bgSecondary:  '#f3f4f6',
  bgCard:       '#ffffff',
  bgCardAlt:    '#f9fafb',

  // Text
  textPrimary:   '#111827',
  textSecondary: '#374151',
  textMuted:     '#6b7280',
  textDisabled:  '#9ca3af',

  // Status
  success:       '#10b981',
  successLight:  '#059669',
  successBg:     'rgba(16,185,129,0.14)',
  successBorder: 'rgba(16,185,129,0.3)',

  warning:       '#f59e0b',
  warningLight:  '#b45309',
  warningBg:     'rgba(245,158,11,0.14)',
  warningBorder: 'rgba(245,158,11,0.3)',

  danger:        '#ef4444',
  dangerLight:   '#dc2626',
  dangerBg:      'rgba(239,68,68,0.12)',
  dangerBorder:  'rgba(239,68,68,0.3)',

  info:          '#3b82f6',
  infoLight:     '#2563eb',
  infoBg:        'rgba(59,130,246,0.12)',
  infoBorder:    'rgba(59,130,246,0.3)',

  // Borders / surfaces
  border:        'rgba(13,148,136,0.22)',
  borderLight:   'rgba(13,148,136,0.14)',
  borderStrong:  'rgba(13,148,136,0.42)',
  surfaceRaised: 'rgba(0,0,0,0.03)',
  overlay:       'rgba(0,0,0,0.8)',
} as const;

export const spacing = {
  xs:  4,
  sm:  8,
  md:  16,
  lg:  24,
  xl:  32,
  xxl: 48,
} as const;

export const radius = {
  sm:   6,
  md:   8,
  lg:   10,
  xl:   12,
  xxl:  15,
  full: 999,
} as const;

export const font = {
  // sizes (sp units — React Native scales these for accessibility)
  xs:   11,
  sm:   13,
  base: 14,
  md:   16,
  lg:   17,
  xl:   19,
  xxl:  22,
  xl3:  26,
  xl4:  30,

  // weights (RN only accepts numeric weights or specific string literals)
  normal:   '400' as const,
  medium:   '500' as const,
  semibold: '600' as const,
  bold:     '700' as const,
} as const;