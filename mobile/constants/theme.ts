/**
 * constants/theme.ts
 *
 * Design tokens that mirror the web CSS variables defined in
 * public/css/base/variables.css so the mobile app stays visually consistent
 * with the web frontend without needing to duplicate raw hex strings everywhere.
 */

export const colors = {
  // Brand
  primary:      '#2dd4bf',
  primaryDark:  '#14b8a6',
  primaryLight: 'rgba(45,212,191,0.10)',

  // Backgrounds
  bgPrimary:    '#1a1a1a',
  bgSecondary:  '#2d2d2d',
  bgCard:       '#2a2a2a',
  bgCardAlt:    '#1e1e1e',

  // Text
  textPrimary:   '#ffffff',
  textSecondary: '#d1d5db',
  textMuted:     '#9ca3af',
  textDisabled:  '#6b7280',

  // Status
  success:       '#10b981',
  successLight:  '#34d399',
  successBg:     'rgba(16,185,129,0.18)',
  successBorder: 'rgba(16,185,129,0.3)',

  warning:       '#f59e0b',
  warningLight:  '#fbbf24',
  warningBg:     'rgba(245,158,11,0.18)',
  warningBorder: 'rgba(245,158,11,0.3)',

  danger:        '#ef4444',
  dangerLight:   '#dc2626',
  dangerBg:      'rgba(239,68,68,0.18)',
  dangerBorder:  'rgba(239,68,68,0.3)',

  info:          '#3b82f6',
  infoLight:     '#60a5fa',
  infoBg:        'rgba(59,130,246,0.18)',
  infoBorder:    'rgba(59,130,246,0.3)',

  // Borders / surfaces
  border:        'rgba(45,212,191,0.22)',
  borderLight:   'rgba(45,212,191,0.10)',
  borderStrong:  'rgba(45,212,191,0.42)',
  surfaceRaised: 'rgba(255,255,255,0.05)',
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