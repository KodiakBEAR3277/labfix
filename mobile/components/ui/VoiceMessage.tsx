/**
 * components/ui/VoiceMessage.tsx
 *
 * Messenger-style voice note player with a YouTube-like draggable scrubber.
 *
 * Used for two things:
 *   1. Previewing a recording the user just made (pass `uri` — a local file).
 *   2. Playing a voice note stored on the server (pass `path` — the relative
 *      path the API returns; it's resolved through storageUrl()).
 *
 * Only one VoiceMessage plays at a time: a module-level reference holds the
 * active player's pause function, and starting a new one pauses the old one.
 *
 * Split into an outer component (which renders a fallback when the ExpoAudio
 * native module isn't in the running binary) and an inner one that uses the
 * expo-audio hooks. See utils/audio.ts for why the import is guarded.
 */

import React, { useEffect, useMemo, useRef, useState } from 'react';
import {
  View,
  Text,
  TouchableOpacity,
  PanResponder,
  StyleSheet,
  ActivityIndicator,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { colors, spacing, radius, font } from '@/constants/theme';
import { storageUrl } from '@/constants/api';
import { getAudio, isAudioAvailable } from '@/utils/audio';
import Icon from './Icon';

// ─── One-at-a-time playback ───────────────────────────────────────────────────
// Holds the pause() of whichever VoiceMessage is currently playing.
let activePause: (() => void) | null = null;

// ─── Helpers ─────────────────────────────────────────────────────────────────

function formatTime(seconds: number): string {
  if (!isFinite(seconds) || seconds < 0) seconds = 0;
  const m = Math.floor(seconds / 60);
  const s = Math.floor(seconds % 60);
  return `${m}:${String(s).padStart(2, '0')}`;
}

// Deterministic decorative waveform — a fixed pattern so every note looks
// consistent and we avoid decoding audio just to draw it.
const WAVE_BARS = 24;
function barHeight(i: number): number {
  const pattern = [8, 14, 20, 11, 24, 16, 9, 19, 26, 13, 10, 22, 15, 18, 12, 25, 9, 17, 21, 14, 11, 23, 16, 12];
  return pattern[i % pattern.length];
}

// ─── Component ────────────────────────────────────────────────────────────────

type Props = {
  /** Local file URI, for previewing a just-recorded note. */
  uri?: string;
  /** Server relative path (e.g. "voice-notes/abc.m4a"), for stored notes. */
  path?: string;
  /** Stored duration in seconds — shown until real metadata loads. */
  duration?: number | null;
  /** When provided, renders a remove (✕) button — used by the staged list. */
  onRemove?: () => void;
};

export default function VoiceMessage(props: Props) {
  // Without the native module, render an inert row instead of throwing at
  // import time (which would take down the whole route tree).
  if (!isAudioAvailable) {
    return (
      <View style={s.row}>
        <View style={[s.playBtn, s.playBtnDisabled]}>
          <Ionicons name="volume-mute" size={18} color="#fff" />
        </View>
        <View style={s.middle}>
          <Text style={s.unavailable}>
            Voice playback needs an app build that includes expo-audio.
          </Text>
        </View>
        {props.onRemove && (
          <TouchableOpacity style={s.removeBtn} onPress={props.onRemove} activeOpacity={0.7}>
            <Icon name="close" size={12} color={colors.danger} />
          </TouchableOpacity>
        )}
      </View>
    );
  }

  return <VoiceMessagePlayer {...props} />;
}

function VoiceMessagePlayer({ uri, path, duration, onRemove }: Props) {
  const { useAudioPlayer, useAudioPlayerStatus } = getAudio()!;

  const source = uri ?? (path ? storageUrl(path) : null);

  const player = useAudioPlayer(source ? { uri: source } : null, { updateInterval: 200 });
  const status = useAudioPlayerStatus(player);

  // ── Track width for scrubbing ─────────────────────────────────────────────
  const [trackWidth, setTrackWidth] = useState(0);
  const widthRef = useRef(0);
  const totalRef = useRef(0);

  // Prefer the real decoded duration; fall back to the stored value.
  const decoded = status?.duration ?? 0;
  const total = isFinite(decoded) && decoded > 0 ? decoded : (duration ?? 0);
  const current = Math.min(status?.currentTime ?? 0, total || 0);
  const progress = total > 0 ? current / total : 0;
  const playing = !!status?.playing;

  widthRef.current = trackWidth;
  totalRef.current = total;

  // ── Pause this player when another one starts ─────────────────────────────
  const pauseSelf = useRef(() => player.pause()).current;

  useEffect(() => {
    // If this player loses the "active" slot, make sure it's actually paused.
    return () => {
      if (activePause === pauseSelf) activePause = null;
    };
  }, [pauseSelf]);

  // Reset to the start when it finishes, and stop blocking other players.
  useEffect(() => {
    if (status?.didJustFinish) {
      player.seekTo(0);
      if (activePause === pauseSelf) activePause = null;
    }
  }, [status?.didJustFinish]); // eslint-disable-line react-hooks/exhaustive-deps

  function togglePlay() {
    if (playing) {
      player.pause();
      if (activePause === pauseSelf) activePause = null;
    } else {
      if (activePause && activePause !== pauseSelf) activePause();
      activePause = pauseSelf;
      player.play();
    }
  }

  // ── Draggable scrubber ────────────────────────────────────────────────────
  function seekFromX(x: number) {
    const w = widthRef.current;
    const t = totalRef.current;
    if (w <= 0 || t <= 0) return;
    const ratio = Math.max(0, Math.min(1, x / w));
    player.seekTo(ratio * t);
  }

  const pan = useMemo(
    () =>
      PanResponder.create({
        onStartShouldSetPanResponder: () => true,
        onMoveShouldSetPanResponder: () => true,
        onPanResponderGrant: (e) => seekFromX(e.nativeEvent.locationX),
        onPanResponderMove: (e) => seekFromX(e.nativeEvent.locationX),
      }),
    // seekFromX only reads refs, so the responder never goes stale.
    // eslint-disable-next-line react-hooks/exhaustive-deps
    []
  );

  // ─── Render ───────────────────────────────────────────────────────────────
  return (
    <View style={s.row}>
      {/* Play / pause */}
      <TouchableOpacity
        style={s.playBtn}
        onPress={togglePlay}
        activeOpacity={0.85}
        disabled={!source}
      >
        {status?.isBuffering && !playing ? (
          <ActivityIndicator size="small" color="#fff" />
        ) : (
          <Ionicons name={playing ? 'pause' : 'play'} size={18} color="#fff" />
        )}
      </TouchableOpacity>

      {/* Waveform + scrub bar */}
      <View style={s.middle}>
        <View
          style={s.waveWrap}
          onLayout={(e) => setTrackWidth(e.nativeEvent.layout.width)}
          {...pan.panHandlers}
        >
          {Array.from({ length: WAVE_BARS }).map((_, i) => {
            const filled = i / WAVE_BARS < progress;
            return (
              <View
                key={i}
                style={[
                  s.bar,
                  { height: barHeight(i) },
                  filled ? s.barFilled : s.barEmpty,
                ]}
              />
            );
          })}

          {/* Draggable dot */}
          <View
            pointerEvents="none"
            style={[
              s.thumb,
              { left: Math.max(0, Math.min(trackWidth - 12, trackWidth * progress - 6)) },
            ]}
          />
        </View>

        <Text style={s.time}>
          {formatTime(current)} / {formatTime(total)}
        </Text>
      </View>

      {/* Optional remove */}
      {onRemove && (
        <TouchableOpacity style={s.removeBtn} onPress={onRemove} activeOpacity={0.7}>
          <Icon name="close" size={12} color={colors.danger} />
        </TouchableOpacity>
      )}
    </View>
  );
}

// ─── Styles ───────────────────────────────────────────────────────────────────

const s = StyleSheet.create({
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    backgroundColor: 'rgba(45,212,191,0.08)',
    borderWidth: 1,
    borderColor: 'rgba(45,212,191,0.2)',
    borderRadius: radius.xl,
    paddingVertical: spacing.sm,
    paddingHorizontal: spacing.sm + 2,
  },

  playBtn: {
    width: 38,
    height: 38,
    borderRadius: radius.full,
    backgroundColor: colors.primary,
    alignItems: 'center',
    justifyContent: 'center',
    flexShrink: 0,
  },
  playBtnDisabled: { backgroundColor: colors.textDisabled },

  middle: { flex: 1, gap: 4 },

  waveWrap: {
    flexDirection: 'row',
    alignItems: 'center',
    height: 30,
    gap: 2,
  },
  bar: {
    flex: 1,
    borderRadius: 2,
  },
  barFilled: { backgroundColor: colors.primary },
  barEmpty:  { backgroundColor: 'rgba(13,148,136,0.25)' },

  thumb: {
    position: 'absolute',
    width: 12,
    height: 12,
    borderRadius: radius.full,
    backgroundColor: '#fff',
    borderWidth: 2,
    borderColor: colors.primary,
    top: 9,
  },

  time: {
    fontSize: font.xs - 1,
    color: colors.textMuted,
  },

  unavailable: {
    fontSize: font.xs,
    color: colors.textMuted,
    lineHeight: 17,
  },

  removeBtn: {
    width: 26,
    height: 26,
    borderRadius: radius.full,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: 'rgba(239,68,68,0.1)',
    flexShrink: 0,
  },
  removeGlyph: {
    fontSize: font.xs,
    fontWeight: font.bold,
    color: colors.danger,
  },
});
