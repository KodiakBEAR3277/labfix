/**
 * components/ui/AttachmentList.tsx
 *
 * Renders a ticket's attachments (images / PDFs, uploaded from the web form)
 * as a tappable grid. Tapping opens the file in the device's browser or viewer —
 * the app has no in-app preview.
 *
 * The API returns relative storage paths ("attachments/abc.png"), so each is
 * resolved through storageUrl() before being opened.
 */

import React from 'react';
import {
  View,
  Text,
  TouchableOpacity,
  Linking,
  StyleSheet,
} from 'react-native';
import { colors, spacing, radius, font } from '@/constants/theme';
import { storageUrl } from '@/constants/api';

type Props = {
  attachments?: string[] | null;
};

export default function AttachmentList({ attachments }: Props) {
  if (!attachments || attachments.length === 0) return null;

  return (
    <View style={s.grid}>
      {attachments.map((att) => {
        const name  = att.split('/').pop() ?? att;
        const isPdf = /\.pdf$/i.test(att);

        return (
          <TouchableOpacity
            key={att}
            style={s.item}
            onPress={() => Linking.openURL(storageUrl(att))}
            activeOpacity={0.75}
          >
            <Text style={s.icon}>{isPdf ? '📄' : '🖼️'}</Text>
            <Text style={s.name} numberOfLines={2}>{name}</Text>
          </TouchableOpacity>
        );
      })}
    </View>
  );
}

const s = StyleSheet.create({
  grid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
  },
  item: {
    width: '30%',
    backgroundColor: 'rgba(0,0,0,0.03)',
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radius.lg,
    padding: spacing.sm,
    alignItems: 'center',
    gap: spacing.xs,
  },
  icon: { fontSize: 22 },
  name: {
    fontSize: font.xs - 1,
    color: colors.textMuted,
    textAlign: 'center',
  },
});
