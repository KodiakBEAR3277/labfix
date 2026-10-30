/**
 * components/ui/Icon.tsx
 *
 * Thin wrapper over Ionicons (@expo/vector-icons) so screens reference a single
 * semantic name instead of glyph strings. Keeps parity with the web registry in
 * backend/resources/js/icons/index.js — the keys here match the web icon names,
 * so `name="ticket"` means the same thing on both platforms.
 *
 * Filled style: use Ionicon base names (e.g. "home"), not "-outline".
 *
 *   <Icon name="ticket" size={18} color={colors.primary} />
 */

import { Ionicons } from '@expo/vector-icons';
import type { StyleProp, TextStyle } from 'react-native';
import { colors } from '@/constants/theme';

const MAP = {
  // Navigation / structure
  home: 'home',
  dashboard: 'grid',
  grid: 'grid',

  // Domain objects
  ticket: 'ticket',
  clipboard: 'clipboard',
  book: 'book',
  article: 'document-text',
  desktop: 'desktop',
  cpu: 'hardware-chip',
  globe: 'globe',
  mouse: 'game-controller',
  school: 'school',
  wrench: 'construct',

  // Files / media
  file: 'document',
  image: 'image',
  paperclip: 'attach',
  upload: 'cloud-upload',
  play: 'play',
  pause: 'pause',
  'volume-mute': 'volume-mute',

  // UI controls
  search: 'search',
  filter: 'filter',
  settings: 'settings',
  lock: 'lock-closed',
  check: 'checkmark',
  close: 'close',
  'chevron-left': 'chevron-back',
  'chevron-right': 'chevron-forward',
  'chevron-down': 'chevron-down',
  'arrow-left': 'arrow-back',
  'arrow-right': 'arrow-forward',
  'external-link': 'open-outline',
  refresh: 'refresh',
  edit: 'create',
  trash: 'trash',
  restore: 'arrow-undo',
  pin: 'bookmark',
  eye: 'eye',
  calendar: 'calendar',
  bolt: 'flash',
  sparkles: 'sparkles',
  help: 'help-circle',

  // Extended set
  circle: 'ellipse',
  'check-circle': 'checkmark-circle',
  'close-circle': 'close-circle',
  fire: 'flame',
  camera: 'camera',
  laptop: 'laptop',
  printer: 'print',
  videocam: 'videocam',
  tag: 'pricetag',
  hourglass: 'hourglass',
  mic: 'mic',
  'mic-off': 'mic-off',

  // Feedback / status
  alert: 'warning',
  info: 'information-circle',
  lightbulb: 'bulb',

  // People / contact
  user: 'person',
  users: 'people',
  mail: 'mail',
  phone: 'call',
  'map-pin': 'location',
  clock: 'time',
  bell: 'notifications',
  chart: 'bar-chart',
  rocket: 'rocket',

  // Brand marks (monochrome on this platform)
  google: 'logo-google',
  facebook: 'logo-facebook',
  twitter: 'logo-twitter',
  linkedin: 'logo-linkedin',
  instagram: 'logo-instagram',
} as const;

export type IconName = keyof typeof MAP;

type Props = {
  name: IconName;
  size?: number;
  color?: string;
  style?: StyleProp<TextStyle>;
};

export default function Icon({ name, size = 20, color = colors.textPrimary, style }: Props) {
  return <Ionicons name={MAP[name]} size={size} color={color} style={style} />;
}
