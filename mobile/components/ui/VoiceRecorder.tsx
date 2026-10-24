/**
 * components/ui/VoiceRecorder.tsx
 *
 * Records a short voice note and hands the finished file to the parent.
 *
 * Controls: record · pause · resume · stop · discard.
 *
 * expo-audio notes:
 *   - Pause/resume is native: `recorder.pause()` then `recorder.record()` to
 *     resume. No custom native code is involved — only this state machine.
 *   - `record({ forDuration: n })` makes the OS auto-stop at n seconds, and
 *     time spent paused does NOT count toward that limit. Resuming without
 *     options keeps the same limit.
 *   - The status listener fires with `isFinished` for both a manual stop and
 *     an auto-stop, so the note is emitted from one place either way.
 *   - `useAudioRecorderState` polls the live duration for the timer.
 */

import React, { useEffect, useRef, useState } from 'react';
import {
  View,
  Text,
  TouchableOpacity,
  StyleSheet,
} from 'react-native';
import {
  useAudioRecorder,
  useAudioRecorderState,
  AudioModule,
  RecordingPresets,
  setAudioModeAsync,
} from 'expo-audio';
import { colors, spacing, radius, font } from '@/constants/theme';

type Phase = 'idle' | 'recording' | 'paused';

export type Recording = { uri: string; duration: number };

type Props = {
  onAdd: (recording: Recording) => void;
  /** Hard cap in seconds. Mirrors the server's tolerance. */
  maxSeconds?: number;
};

function formatTime(totalSeconds: number): string {
  const m = Math.floor(totalSeconds / 60);
  const s = Math.floor(totalSeconds % 60);
  return `${m}:${String(s).padStart(2, '0')}`;
}

export default function VoiceRecorder({ onAdd, maxSeconds = 60 }: Props) {
  const [phase, setPhase] = useState<Phase>('idle');
  const [error, setError] = useState<string | null>(null);

  // Kept in refs so the status listener (created once) never reads stale values.
  const onAddRef = useRef(onAdd);
  onAddRef.current = onAdd;
  const recorderRef = useRef<ReturnType<typeof useAudioRecorder> | null>(null);
  const emittedRef = useRef(false);
  const durationRef = useRef(1);

  const recorder = useAudioRecorder(RecordingPresets.HIGH_QUALITY, (status) => {
    if (status.isFinished && !emittedRef.current) {
      emittedRef.current = true;
      const uri = status.url ?? recorderRef.current?.uri ?? null;
      if (uri) onAddRef.current({ uri, duration: durationRef.current });
      setPhase('idle');
    }
  });
  recorderRef.current = recorder;

  const state = useAudioRecorderState(recorder, 100);
  const elapsedMs = state.durationMillis ?? 0;
  durationRef.current = Math.max(1, Math.round(elapsedMs / 1000));

  const seconds = Math.min(maxSeconds, Math.floor(elapsedMs / 1000));
  const remaining = Math.max(0, maxSeconds - seconds);
  const progress = maxSeconds > 0 ? Math.min(1, seconds / maxSeconds) : 0;

  // Release the microphone if the user navigates away mid-recording.
  useEffect(() => {
    return () => {
      Promise.resolve(recorderRef.current?.stop?.()).catch(() => {});
    };
  }, []);

  async function handleStart() {
    setError(null);
    try {
      const permission = await AudioModule.requestRecordingPermissionsAsync();
      if (!permission.granted) {
        setError('Microphone access is needed to record a voice note.');
        return;
      }

      await setAudioModeAsync({ playsInSilentMode: true, allowsRecording: true });

      emittedRef.current = false;
      await recorder.prepareToRecordAsync();
      recorder.record({ forDuration: maxSeconds });
      setPhase('recording');
    } catch {
      setError('Could not start recording. Please try again.');
      setPhase('idle');
    }
  }

  function handlePause() {
    recorder.pause();
    setPhase('paused');
  }

  function handleResume() {
    recorder.record();
    setPhase('recording');
  }

  // Stop → the status listener emits the note and resets the phase.
  async function handleStop() {
    try {
      await recorder.stop();
    } catch {
      /* the listener still resets the phase */
    }
  }

  async function handleDiscard() {
    emittedRef.current = true; // suppress the emit — we're throwing this one away
    try {
      await recorder.stop();
    } catch {
      /* ignore */
    }
    setPhase('idle');
  }

  // ─── Idle ─────────────────────────────────────────────────────────────────
  if (phase === 'idle') {
    return (
      <View style={s.wrap}>
        <TouchableOpacity style={s.micBtn} onPress={handleStart} activeOpacity={0.85}>
          <Text style={s.micGlyph}>🎙️</Text>
        </TouchableOpacity>
        <Text style={s.hint}>
          Tap to record a voice note
          <Text style={s.hintMuted}> (max {formatTime(maxSeconds)})</Text>
        </Text>
        {error && <Text style={s.error}>{error}</Text>}
      </View>
    );
  }

  // ─── Recording / paused ───────────────────────────────────────────────────
  return (
    <View style={s.wrap}>
      <View style={s.liveRow}>
        <View style={[s.dot, phase === 'paused' && s.dotPaused]} />
        <Text style={s.liveLabel}>
          {phase === 'recording' ? 'Recording…' : 'Paused'}
        </Text>
        <Text style={s.timer}>{formatTime(seconds)}</Text>
      </View>

      {/* Elapsed / cap bar */}
      <View style={s.progressTrack}>
        <View style={[s.progressFill, { width: `${progress * 100}%` }]} />
      </View>

      <Text style={s.remaining}>
        {remaining > 0
          ? `${formatTime(remaining)} remaining`
          : 'Time limit reached — finishing up…'}
      </Text>

      <View style={s.actions}>
        {phase === 'recording' ? (
          <TouchableOpacity style={s.secondaryBtn} onPress={handlePause} activeOpacity={0.8}>
            <Text style={s.secondaryText}>❚❚ Pause</Text>
          </TouchableOpacity>
        ) : (
          <TouchableOpacity style={s.secondaryBtn} onPress={handleResume} activeOpacity={0.8}>
            <Text style={s.secondaryText}>▶ Resume</Text>
          </TouchableOpacity>
        )}

        <TouchableOpacity style={s.primaryBtn} onPress={handleStop} activeOpacity={0.85}>
          <Text style={s.primaryText}>■ Stop &amp; Attach</Text>
        </TouchableOpacity>
      </View>

      <TouchableOpacity style={s.discardBtn} onPress={handleDiscard} activeOpacity={0.7}>
        <Text style={s.discardText}>Discard</Text>
      </TouchableOpacity>
    </View>
  );
}

// ─── Styles ───────────────────────────────────────────────────────────────────

const s = StyleSheet.create({
  wrap: {
    alignItems: 'center',
    gap: spacing.sm,
    backgroundColor: 'rgba(45,212,191,0.05)',
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radius.lg,
    paddingVertical: spacing.md,
    paddingHorizontal: spacing.md,
  },

  // Idle
  micBtn: {
    width: 62,
    height: 62,
    borderRadius: radius.full,
    backgroundColor: colors.primary,
    alignItems: 'center',
    justifyContent: 'center',
    shadowColor: colors.primary,
    shadowOffset: { width: 0, height: 3 },
    shadowOpacity: 0.3,
    shadowRadius: 6,
    elevation: 4,
  },
  micGlyph: { fontSize: 26 },
  hint: {
    fontSize: font.sm,
    color: colors.textSecondary,
    textAlign: 'center',
  },
  hintMuted: { color: colors.textMuted },
  error: {
    fontSize: font.xs,
    color: colors.danger,
    textAlign: 'center',
  },

  // Live recording
  liveRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
  },
  dot: {
    width: 10,
    height: 10,
    borderRadius: radius.full,
    backgroundColor: colors.danger,
  },
  dotPaused: { backgroundColor: colors.textMuted },
  liveLabel: {
    fontSize: font.sm,
    fontWeight: font.semibold,
    color: colors.textPrimary,
    flex: 1,
  },
  timer: {
    fontSize: font.base,
    fontWeight: font.bold,
    color: colors.primary,
    fontVariant: ['tabular-nums'],
  },

  progressTrack: {
    alignSelf: 'stretch',
    height: 4,
    borderRadius: 2,
    backgroundColor: 'rgba(13,148,136,0.18)',
    overflow: 'hidden',
  },
  progressFill: {
    height: 4,
    borderRadius: 2,
    backgroundColor: colors.primary,
  },
  remaining: {
    fontSize: font.xs,
    color: colors.textMuted,
  },

  actions: {
    flexDirection: 'row',
    gap: spacing.sm,
    alignSelf: 'stretch',
    marginTop: spacing.xs,
  },
  secondaryBtn: {
    flex: 1,
    borderWidth: 1.5,
    borderColor: colors.primary,
    borderRadius: radius.lg,
    paddingVertical: spacing.sm + 2,
    alignItems: 'center',
  },
  secondaryText: {
    fontSize: font.sm,
    fontWeight: font.semibold,
    color: colors.primary,
  },
  primaryBtn: {
    flex: 1.4,
    backgroundColor: colors.primary,
    borderRadius: radius.lg,
    paddingVertical: spacing.sm + 2,
    alignItems: 'center',
  },
  primaryText: {
    fontSize: font.sm,
    fontWeight: font.bold,
    color: '#fff',
  },

  discardBtn: {
    paddingVertical: spacing.xs,
    paddingHorizontal: spacing.md,
  },
  discardText: {
    fontSize: font.xs,
    fontWeight: font.semibold,
    color: colors.danger,
  },
});
