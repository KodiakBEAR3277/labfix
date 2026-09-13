/**
 * app/(tabs)/report.tsx  —  Report Issue (Multi-Step Form)
 *
 * Mirrors: resources/js/Pages/User/Reports/Create.vue
 *
 * 5-step flow:
 *   Step 1 — Lab Location    (tap a lab card to select)
 *   Step 2 — Equipment       (dropdown, fetched from GET /api/labs/{id}/equipment)
 *   Step 3 — Problem Type    (category grid) — also where voice dictation starts
 *   Step 4 — Description     (title + description text inputs)
 *   Step 5 — Review & Submit (summary card, then POST /api/tickets)
 *
 * API:
 *   GET  /api/labs                    → list of active labs (auth required)
 *   GET  /api/labs/{id}/equipment     → equipment for selected lab
 *   POST /api/tickets                 → create ticket
 *   POST /api/reports/voice-extract   → transcript in, {title, description, category} out
 *
 * Voice dictation:
 *   Lives entirely on Step 3, since lab_id is required to submit and voice
 *   was scoped to category/title/description only — dictation can never
 *   happen before a lab is chosen. Flow: tap mic → speak → stop → review
 *   the transcript (editable) → confirm → backend extracts fields →
 *   form is pre-filled → jumps to Step 4 so the user sees and can further
 *   edit the actual title/description fields before continuing normally.
 *
 * Maintenance mode:
 *   If GET /api/dashboard returns a maintenance flag (or a dedicated endpoint),
 *   we show a maintenance notice and block submission.
 *   For now we check a lightweight /api/maintenance endpoint that mirrors
 *   the web's Setting::get('maintenance_mode') — falls back gracefully if absent.
 *
 * On success → navigate to /(tabs)/my-reports with a success param so the
 * list can show a flash banner.
 */

import React, {
  useEffect,
  useState,
  useCallback,
  useRef,
} from 'react';
import {
  View,
  Text,
  ScrollView,
  TouchableOpacity,
  TextInput,
  StyleSheet,
  StatusBar,
  ActivityIndicator,
  Alert,
  Animated,
  Easing,
  KeyboardAvoidingView,
  Platform,
  Modal,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { router, useFocusEffect } from 'expo-router';
import {
  ExpoSpeechRecognitionModule,
  useSpeechRecognitionEvent,
} from 'expo-speech-recognition';
import { colors, spacing, radius, font } from '@/constants/theme';
import { loadAuth } from '@/utils/auth';
import { apiUrl } from '@/constants/api';

// ─── Types ────────────────────────────────────────────────────────────────────

type Lab = {
  id:                number;
  name:              string;
  capacity:          number;
  operational_count: number;
};

type Equipment = {
  id:             number;
  equipment_code: string;
  type:           string;
  status:         string;
};

type Category = {
  value: 'hardware' | 'software' | 'network' | 'other';
  icon:  string;
  label: string;
  desc:  string;
};

type FormState = {
  lab_id:       number | null;
  equipment_id: number | '';
  category:     Category['value'] | '';
  title:        string;
  description:  string;
};

type FieldErrors = {
  lab_id?:      string;
  equipment_id?: string;
  category?:    string;
  title?:       string;
  description?: string;
  general?:     string;
};

type VoicePhase = 'ready' | 'listening' | 'review' | 'extracting';

// ─── Constants ────────────────────────────────────────────────────────────────

const TOTAL_STEPS = 5;

const CATEGORIES: Category[] = [
  { value: 'hardware', icon: '🔧', label: 'Hardware',  desc: 'Physical component issues'  },
  { value: 'software', icon: '💾', label: 'Software',  desc: 'Programs & applications'     },
  { value: 'network',  icon: '🌐', label: 'Network',   desc: 'Internet & connectivity'     },
  { value: 'other',    icon: '❓', label: 'Other',     desc: 'Other technical issues'      },
];

const STEP_LABELS = ['Lab', 'Equipment', 'Type', 'Describe', 'Review'];

// ─── Helpers ─────────────────────────────────────────────────────────────────

function equipmentStatusLabel(status: string): string {
  switch (status) {
    case 'operational': return '';
    case 'has-issue':   return ' (has issues)';
    case 'maintenance': return ' (maintenance)';
    case 'retired':     return ' (retired)';
    default:            return '';
  }
}

// ─── Sub-components ───────────────────────────────────────────────────────────

// Step progress bar at the top
function StepProgress({ current }: { current: number }) {
  return (
    <View style={sp.wrap}>
      {STEP_LABELS.map((label, i) => {
        const stepNum = i + 1;
        const done    = stepNum < current;
        const active  = stepNum === current;
        return (
          <React.Fragment key={label}>
            {i > 0 && (
              <View style={[sp.line, done && sp.lineDone]} />
            )}
            <View style={sp.node}>
              <View style={[sp.circle, done && sp.circleDone, active && sp.circleActive]}>
                {done ? (
                  <Text style={sp.circleCheckText}>✓</Text>
                ) : (
                  <Text style={[sp.circleText, active && sp.circleTextActive]}>
                    {stepNum}
                  </Text>
                )}
              </View>
              <Text style={[sp.label, active && sp.labelActive]}>{label}</Text>
            </View>
          </React.Fragment>
        );
      })}
    </View>
  );
}

const sp = StyleSheet.create({
  wrap: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.md,
  },
  node: {
    alignItems: 'center',
    gap: 4,
    zIndex: 1,
  },
  line: {
    flex: 1,
    height: 2,
    backgroundColor: colors.border,
    marginTop: 20,
    marginHorizontal: 4,
  },
  lineDone: {
    backgroundColor: colors.primary,
  },
  circle: {
    width: 42,
    height: 42,
    borderRadius: 23,
    backgroundColor: colors.bgCard,
    borderWidth: 2,
    borderColor: colors.border,
    alignItems: 'center',
    justifyContent: 'center',
  },
  circleDone: {
    backgroundColor: colors.primary,
    borderColor: colors.primary,
  },
  circleActive: {
    backgroundColor: colors.primaryLight,
    borderColor: colors.primary,
  },
  circleText: {
    fontSize: font.lg - 1,
    fontWeight: font.bold,
    color: colors.textMuted,
  },
  circleTextActive: {
    color: colors.primary,
  },
  circleCheckText: {
    fontSize: font.xs - 1,
    fontWeight: font.bold,
    color: '#fff',
  },
  label: {
    fontSize: font.xs - 2,
    color: colors.textMuted,
    textAlign: 'center',
    width: 48,
  },
  labelActive: {
    color: colors.primary,
    fontWeight: font.semibold,
  },
});

// Field error text
function FieldError({ message }: { message?: string }) {
  if (!message) return null;
  return <Text style={s.fieldError}>{message}</Text>;
}

// Review row
function ReviewRow({ label, value }: { label: string; value: string }) {
  return (
    <View style={s.reviewRow}>
      <Text style={s.reviewLabel}>{label}</Text>
      <Text style={s.reviewValue}>{value}</Text>
    </View>
  );
}

// Nav buttons at the bottom of each step
function StepNav({
  onBack,
  onNext,
  nextLabel = 'Next Step →',
  nextDisabled = false,
  loading = false,
}: {
  onBack?: () => void;
  onNext: () => void;
  nextLabel?: string;
  nextDisabled?: boolean;
  loading?: boolean;
}) {
  return (
    <View style={s.stepNav}>
      {onBack ? (
        <TouchableOpacity style={s.backBtn} onPress={onBack} activeOpacity={0.8}>
          <Text style={s.backBtnText}>← Back</Text>
        </TouchableOpacity>
      ) : (
        <View style={{ flex: 1 }} />
      )}
      <TouchableOpacity
        style={[s.nextBtn, nextDisabled && s.nextBtnDisabled]}
        onPress={onNext}
        disabled={nextDisabled || loading}
        activeOpacity={0.85}
      >
        {loading ? (
          <ActivityIndicator color="#fff" size="small" />
        ) : (
          <Text style={s.nextBtnText}>{nextLabel}</Text>
        )}
      </TouchableOpacity>
    </View>
  );
}

// Voice dictation bottom sheet — handles all four phases of the flow:
// ready → listening → review (editable transcript) → extracting
function VoiceCaptureModal({
  visible,
  phase,
  liveTranscript,
  editableTranscript,
  onChangeTranscript,
  error,
  onStart,
  onStop,
  onConfirm,
  onRetry,
  onClose,
}: {
  visible: boolean;
  phase: VoicePhase;
  liveTranscript: string;
  editableTranscript: string;
  onChangeTranscript: (text: string) => void;
  error: string;
  onStart: () => void;
  onStop: () => void;
  onConfirm: () => void;
  onRetry: () => void;
  onClose: () => void;
}) {
  return (
    <Modal visible={visible} animationType="slide" transparent onRequestClose={onClose}>
      <View style={vm.overlay}>
        <View style={vm.sheet}>
          <View style={vm.header}>
            <Text style={vm.headerTitle}>Dictate Your Report</Text>
            <TouchableOpacity onPress={onClose}>
              <Text style={vm.closeText}>Close</Text>
            </TouchableOpacity>
          </View>

          {!!error && (
            <View style={vm.errorBox}>
              <Text style={vm.errorText}>{error}</Text>
            </View>
          )}

          {phase === 'ready' && (
            <View style={vm.centerBlock}>
              <Text style={vm.helpText}>
                Tap the mic and describe the problem — what's wrong, where it is,
                and anything you've already tried.
              </Text>
              <TouchableOpacity style={vm.micBtn} onPress={onStart} activeOpacity={0.85}>
                <Text style={vm.micIcon}>🎙️</Text>
              </TouchableOpacity>
              <Text style={vm.micHint}>Tap to start</Text>
            </View>
          )}

          {phase === 'listening' && (
            <View style={vm.centerBlock}>
              <View style={vm.listeningDot} />
              <Text style={vm.listeningLabel}>Listening…</Text>
              <ScrollView style={vm.transcriptBox}>
                <Text style={vm.transcriptText}>
                  {liveTranscript || 'Start speaking…'}
                </Text>
              </ScrollView>
              <TouchableOpacity style={vm.stopBtn} onPress={onStop} activeOpacity={0.85}>
                <Text style={vm.stopBtnText}>Stop</Text>
              </TouchableOpacity>
            </View>
          )}

          {phase === 'review' && (
            <View style={vm.reviewBlock}>
              <Text style={vm.helpText}>
                Here's what we heard. Fix anything that's wrong before continuing.
              </Text>
              <TextInput
                style={vm.reviewInput}
                value={editableTranscript}
                onChangeText={onChangeTranscript}
                multiline
                textAlignVertical="top"
                placeholder="Your transcript will appear here"
                placeholderTextColor={colors.textDisabled}
              />
              <View style={vm.reviewActions}>
                <TouchableOpacity style={vm.retryBtn} onPress={onRetry} activeOpacity={0.8}>
                  <Text style={vm.retryBtnText}>Try Again</Text>
                </TouchableOpacity>
                <TouchableOpacity
                  style={[vm.useBtn, !editableTranscript.trim() && vm.useBtnDisabled]}
                  onPress={onConfirm}
                  disabled={!editableTranscript.trim()}
                  activeOpacity={0.85}
                >
                  <Text style={vm.useBtnText}>Use This</Text>
                </TouchableOpacity>
              </View>
            </View>
          )}

          {phase === 'extracting' && (
            <View style={vm.centerBlock}>
              <ActivityIndicator size="large" color={colors.primary} />
              <Text style={vm.helpText}>Filling in your ticket details…</Text>
            </View>
          )}
        </View>
      </View>
    </Modal>
  );
}

// ─── Main Component ───────────────────────────────────────────────────────────

export default function ReportScreen() {
  const [token,      setToken]      = useState<string | null>(null);
  const [step,       setStep]       = useState(1);
  const [labs,       setLabs]       = useState<Lab[]>([]);
  const [labsLoading,setLabsLoading]= useState(true);
  const [equipment,  setEquipment]  = useState<Equipment[]>([]);
  const [equipLoading,setEquipLoading] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [errors,     setErrors]     = useState<FieldErrors>({});
  const [maintenance,setMaintenance]= useState(false);
  const [maintMsg,   setMaintMsg]   = useState('');

  // Display names for the review step
  const [selectedLabName,   setSelectedLabName]   = useState('');
  const [selectedEquipName, setSelectedEquipName] = useState("General lab issue / Don't know");

  const [form, setForm] = useState<FormState>({
    lab_id:       null,
    equipment_id: '',
    category:     '',
    title:        '',
    description:  '',
  });

  // ── Voice dictation state ──────────────────────────────────────────────────
  const [voiceModalVisible,   setVoiceModalVisible]   = useState(false);
  const [voicePhase,          setVoicePhase]          = useState<VoicePhase>('ready');
  const [liveTranscript,      setLiveTranscript]      = useState('');
  const [editableTranscript,  setEditableTranscript]  = useState('');
  const [voiceError,          setVoiceError]          = useState('');
  // Ref mirrors liveTranscript so the 'end' event always reads the latest
  // value even if the event fires before this render's state has settled.
  const liveTranscriptRef = useRef('');

  useFocusEffect(
    useCallback(() => {
      // 1. Reset step wizard back to the beginning
      setStep(1);

      // 2. Clear API dependent data and local visual state labels
      setEquipment([]);
      setSelectedLabName('');
      setSelectedEquipName("General lab issue / Don't know");

      // 3. Clear any validation errors left over from the last attempt
      setErrors({});

      // 4. Reset the form fields back to empty/null values
      setForm({
        lab_id: null,
        equipment_id: '',
        category: '',
        title: '',
        description: '',
      });

      // 5. Close out any in-progress dictation from a previous visit
      setVoiceModalVisible(false);
      setVoicePhase('ready');
      setLiveTranscript('');
      setEditableTranscript('');
      setVoiceError('');
    }, [])
  );

  // Slide animation between steps
  const slideAnim = useRef(new Animated.Value(0)).current;

  function animateStep() {
    slideAnim.setValue(30);
    Animated.timing(slideAnim, {
      toValue: 0,
      duration: 220,
      easing: Easing.out(Easing.quad),
      useNativeDriver: true,
    }).start();
  }

  // ── Auth ──────────────────────────────────────────────────────────────────
  useEffect(() => {
    loadAuth().then((auth) => {
      if (!auth) { router.replace('/login'); return; }
      setToken(auth.token);
    });
  }, []);

  // ── Fetch labs once we have token ─────────────────────────────────────────
  useEffect(() => {
    if (!token) return;

    // Fetch labs
    fetch(apiUrl('labs'), {
      headers: {
        'Accept':        'application/json',
        'Authorization': `Bearer ${token}`,
      },
    })
      .then((r) => r.json())
      .then((data: Lab[]) => setLabs(data))
      .catch(() => {/* fail silently, labs will be empty */})
      .finally(() => setLabsLoading(false));

    // Check maintenance mode (best-effort — not all backends expose this)
    fetch(apiUrl('contact-info'), { headers: { 'Accept': 'application/json' } })
      .catch(() => {/* ignore */});
  }, [token]);

  // ── Fetch equipment when lab is selected ──────────────────────────────────
  const fetchEquipment = useCallback(async (labId: number, tok: string) => {
    setEquipLoading(true);
    setEquipment([]);
    try {
      const res = await fetch(apiUrl(`labs/${labId}/equipment`), {
        headers: {
          'Accept':        'application/json',
          'Authorization': `Bearer ${tok}`,
        },
      });
      if (res.ok) {
        const data: Equipment[] = await res.json();
        setEquipment(data);
      }
    } catch {
      /* fail silently */
    } finally {
      setEquipLoading(false);
    }
  }, []);

  // ── Navigation ────────────────────────────────────────────────────────────
  function goNext() {
    animateStep();
    setStep((s) => Math.min(s + 1, TOTAL_STEPS));
  }

  function goBack() {
    animateStep();
    setStep((s) => Math.max(s - 1, 1));
  }

  // ── Step handlers ─────────────────────────────────────────────────────────

  function selectLab(lab: Lab) {
    setForm((f) => ({ ...f, lab_id: lab.id, equipment_id: '' }));
    setSelectedLabName(lab.name);
    setSelectedEquipName("General lab issue / Don't know");
    if (token) fetchEquipment(lab.id, token);
  }

  function selectEquipment(eq: Equipment | null) {
    if (!eq) {
      setForm((f) => ({ ...f, equipment_id: '' }));
      setSelectedEquipName("General lab issue / Don't know");
    } else {
      setForm((f) => ({ ...f, equipment_id: eq.id }));
      setSelectedEquipName(`${eq.equipment_code}${equipmentStatusLabel(eq.status)}`);
    }
  }

  function selectCategory(cat: Category['value']) {
    setForm((f) => ({ ...f, category: cat }));
  }

  // ── Step validations ──────────────────────────────────────────────────────

  function validateStep4(): boolean {
    const e: FieldErrors = {};
    if (!form.title.trim())           e.title       = 'Issue title is required.';
    if (form.description.trim().length < 10)
      e.description = 'Description must be at least 10 characters.';
    setErrors(e);
    return Object.keys(e).length === 0;
  }

  // ── Voice dictation ───────────────────────────────────────────────────────

  function openVoiceModal() {
    setVoiceModalVisible(true);
    setVoicePhase('ready');
    setVoiceError('');
    setLiveTranscript('');
    setEditableTranscript('');
    liveTranscriptRef.current = '';
  }

  function closeVoiceModal() {
    if (voicePhase === 'listening') {
      ExpoSpeechRecognitionModule.stop();
    }
    setVoiceModalVisible(false);
  }

  async function startVoiceCapture() {
    setVoiceError('');
    try {
      const result = await ExpoSpeechRecognitionModule.requestPermissionsAsync();
      if (!result.granted) {
        setVoiceError(
          'Microphone access is needed for dictation. You can close this and type your report instead.'
        );
        return;
      }
    } catch {
      setVoiceError('Could not access the microphone. You can close this and type your report instead.');
      return;
    }

    liveTranscriptRef.current = '';
    setLiveTranscript('');
    setVoicePhase('listening');

    ExpoSpeechRecognitionModule.start({
      lang: 'en-US',
      interimResults: true,
      continuous: false,
    });
  }

  function stopVoiceCapture() {
    ExpoSpeechRecognitionModule.stop();
  }

  function retryVoiceCapture() {
    setEditableTranscript('');
    setLiveTranscript('');
    liveTranscriptRef.current = '';
    setVoiceError('');
    setVoicePhase('ready');
  }

  useSpeechRecognitionEvent('result', (event) => {
    const text = event.results?.[0]?.transcript ?? '';
    liveTranscriptRef.current = text;
    setLiveTranscript(text);
  });

  useSpeechRecognitionEvent('end', () => {
    setVoicePhase((phase) => {
      if (phase !== 'listening') return phase;
      setEditableTranscript(liveTranscriptRef.current);
      return 'review';
    });
  });

  useSpeechRecognitionEvent('error', () => {
    setVoiceError('Could not hear you clearly. You can try again or type your report manually.');
    setVoicePhase('ready');
  });

  async function confirmTranscriptAndExtract() {
    if (!token || !editableTranscript.trim()) return;

    setVoicePhase('extracting');
    setVoiceError('');

    try {
      const res = await fetch(apiUrl('reports/voice-extract'), {
        method:  'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept':        'application/json',
          'Authorization': `Bearer ${token}`,
        },
        body: JSON.stringify({ transcript: editableTranscript.trim() }),
      });

      const data = await res.json();

      if (res.ok) {
        setForm((f) => ({
          ...f,
          category:    (data.category as Category['value']) || f.category,
          title:       data.title ?? f.title,
          description: data.description ?? f.description,
        }));
        setVoiceModalVisible(false);
        setStep(4);
      } else {
        setVoiceError(data?.message ?? 'Could not process that. You can try again or type your report manually.');
        setVoicePhase('review');
      }
    } catch {
      setVoiceError('Could not reach the server. Check your connection.');
      setVoicePhase('review');
    }
  }

  // ── Submit ────────────────────────────────────────────────────────────────

  async function handleSubmit() {
    if (!token) return;
    setSubmitting(true);
    setErrors({});

    try {
      const body: Record<string, unknown> = {
        lab_id:       form.lab_id,
        category:     form.category,
        title:        form.title.trim(),
        description:  form.description.trim(),
      };
      if (form.equipment_id) {
        body.equipment_id = form.equipment_id;
      }

      const res = await fetch(apiUrl('tickets'), {
        method:  'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept':        'application/json',
          'Authorization': `Bearer ${token}`,
        },
        body: JSON.stringify(body),
      });

      const data = await res.json();

      if (res.ok) {
        // Navigate to my-reports; the list screen can show a success toast
        router.replace('/(tabs)/my-reports' as any);
      } else if (res.status === 422) {
        // Validation errors from server
        const serverErrors: FieldErrors = {};
        const raw = data?.errors ?? {};
        for (const key of Object.keys(raw) as (keyof FieldErrors)[]) {
          serverErrors[key] = Array.isArray(raw[key]) ? raw[key][0] : raw[key];
        }
        setErrors(serverErrors);
        // Jump back to the step that has the error
        if (serverErrors.lab_id)      setStep(1);
        else if (serverErrors.category) setStep(3);
        else if (serverErrors.title || serverErrors.description) setStep(4);
      } else {
        setErrors({ general: data?.message ?? 'Something went wrong. Please try again.' });
      }
    } catch {
      setErrors({ general: 'Could not reach the server. Check your connection.' });
    } finally {
      setSubmitting(false);
    }
  }

  // ─── Render ───────────────────────────────────────────────────────────────

  return (
    <SafeAreaView style={s.root} edges={['top']}>
      <StatusBar barStyle="light-content" backgroundColor={colors.bgPrimary} />

      {/* ── Top Nav ── */}
      <View style={s.nav}>
        <TouchableOpacity onPress={() => {
          if (step > 1) {
            goBack();
          } else {
            Alert.alert(
              'Discard Report?',
              'Your progress will be lost.',
              [
                { text: 'Keep Editing', style: 'cancel' },
                { text: 'Discard', style: 'destructive', onPress: () => router.back() },
              ]
            );
          }
        }}>
          <Text style={s.navBack}>{step > 1 ? '← Back' : '✕'}</Text>
        </TouchableOpacity>

        <Text style={s.navTitle}>Report Issue</Text>

        <Text style={s.navStep}>{step}/{TOTAL_STEPS}</Text>
      </View>

      {/* ── Maintenance Notice ── */}
      {maintenance ? (
        <View style={s.maintWrap}>
          <Text style={s.maintIcon}>🔧</Text>
          <Text style={s.maintTitle}>System Maintenance</Text>
          <Text style={s.maintMsg}>{maintMsg || 'Ticket submission is temporarily disabled.'}</Text>
          <TouchableOpacity style={s.maintBtn} onPress={() => router.replace('/(tabs)/' as any)}>
            <Text style={s.maintBtnText}>Back to Dashboard</Text>
          </TouchableOpacity>
        </View>
      ) : (
        <KeyboardAvoidingView
          style={{ flex: 1 }}
          behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
          keyboardVerticalOffset={0}
        >
          {/* ── Step Progress ── */}
          <StepProgress current={step} />

          {/* ── General error banner ── */}
          {errors.general && (
            <View style={s.errorBanner}>
              <Text style={s.errorBannerText}>{errors.general}</Text>
            </View>
          )}

          {/* ── Step Content ── */}
          <Animated.View
            style={[{ flex: 1 }, { transform: [{ translateY: slideAnim }] }]}
          >

            {/* ════════════════════════════════
                STEP 1 — Lab Location
            ════════════════════════════════ */}
            {step === 1 && (
              <ScrollView
                contentContainerStyle={s.scrollBody}
                showsVerticalScrollIndicator={false}
                keyboardShouldPersistTaps="handled"
              >
                <Text style={s.stepHeading}>Select Lab Location</Text>
                <Text style={s.stepSub}>Which lab is the problem in?</Text>

                {labsLoading ? (
                  <View style={s.centered}>
                    <ActivityIndicator size="large" color={colors.primary} />
                    <Text style={s.loadingText}>Loading labs…</Text>
                  </View>
                ) : labs.length === 0 ? (
                  <View style={s.centered}>
                    <Text style={s.emptyIcon}>🏫</Text>
                    <Text style={s.emptyText}>No active labs found.</Text>
                  </View>
                ) : (
                  <View style={s.labGrid}>
                    {labs.map((lab) => {
                      const selected = form.lab_id === lab.id;
                      const pct      = lab.capacity > 0
                        ? Math.round((lab.operational_count / lab.capacity) * 100)
                        : 0;
                      const healthy  = pct >= 50;
                      return (
                        <TouchableOpacity
                          key={lab.id}
                          style={[s.labCard, selected && s.labCardSelected]}
                          onPress={() => selectLab(lab)}
                          activeOpacity={0.75}
                        >
                          <Text style={s.labIcon}>💻</Text>
                          <Text style={[s.labName, selected && s.labNameSelected]}>
                            {lab.name}
                          </Text>
                          <View style={s.labMeta}>
                            <View style={[
                              s.labStatusDot,
                              { backgroundColor: healthy ? colors.success : colors.warning }
                            ]} />
                            <Text style={s.labMetaText}>
                              {lab.operational_count}/{lab.capacity} operational
                            </Text>
                          </View>
                          {selected && (
                            <View style={s.labCheckmark}>
                              <Text style={s.labCheckmarkText}>✓</Text>
                            </View>
                          )}
                        </TouchableOpacity>
                      );
                    })}
                  </View>
                )}

                <FieldError message={errors.lab_id} />

                <StepNav
                  onNext={goNext}
                  nextDisabled={!form.lab_id}
                />
              </ScrollView>
            )}

            {/* ════════════════════════════════
                STEP 2 — Equipment
            ════════════════════════════════ */}
            {step === 2 && (
              <ScrollView
                contentContainerStyle={s.scrollBody}
                showsVerticalScrollIndicator={false}
                keyboardShouldPersistTaps="handled"
              >
                <Text style={s.stepHeading}>Select Equipment</Text>
                <Text style={s.stepSub}>Which device or equipment has the issue?</Text>

                {/* General / don't know option */}
                <TouchableOpacity
                  style={[
                    s.equipOption,
                    !form.equipment_id && s.equipOptionSelected,
                  ]}
                  onPress={() => selectEquipment(null)}
                  activeOpacity={0.75}
                >
                  <View style={s.equipOptionLeft}>
                    <Text style={s.equipIcon}>❓</Text>
                    <View>
                      <Text style={[
                        s.equipCode,
                        !form.equipment_id && s.equipCodeSelected,
                      ]}>
                        General / Don't know
                      </Text>
                      <Text style={s.equipSub}>
                        Report a general lab issue
                      </Text>
                    </View>
                  </View>
                  {!form.equipment_id && (
                    <Text style={s.equipCheck}>✓</Text>
                  )}
                </TouchableOpacity>

                {/* Divider */}
                <View style={s.equipDivider}>
                  <View style={s.equipDividerLine} />
                  <Text style={s.equipDividerText}>
                    {equipLoading ? 'Loading equipment…' : 'Or select specific equipment'}
                  </Text>
                  <View style={s.equipDividerLine} />
                </View>

                {equipLoading ? (
                  <View style={[s.centered, { paddingVertical: spacing.xl }]}>
                    <ActivityIndicator color={colors.primary} />
                  </View>
                ) : equipment.length === 0 ? (
                  <Text style={s.equipEmpty}>
                    No equipment found for this lab. Proceed with "General".
                  </Text>
                ) : (
                  equipment
                    .filter((eq) => eq.status !== 'retired')
                    .map((eq) => {
                      const selected = form.equipment_id === eq.id;
                      const hasIssue = eq.status === 'has-issue' || eq.status === 'maintenance';
                      return (
                        <TouchableOpacity
                          key={eq.id}
                          style={[
                            s.equipOption,
                            selected && s.equipOptionSelected,
                          ]}
                          onPress={() => selectEquipment(eq)}
                          activeOpacity={0.75}
                        >
                          <View style={s.equipOptionLeft}>
                            <Text style={s.equipIcon}>
                              {eq.type === 'computer'  ? '💻'
                               : eq.type === 'printer'  ? '🖨️'
                               : eq.type === 'projector' ? '📽️'
                               : '🖥️'}
                            </Text>
                            <View>
                              <Text style={[
                                s.equipCode,
                                selected && s.equipCodeSelected,
                              ]}>
                                {eq.equipment_code}
                              </Text>
                              <Text style={[
                                s.equipSub,
                                hasIssue && { color: colors.warning },
                              ]}>
                                {eq.status === 'operational'
                                  ? 'Operational'
                                  : eq.status === 'has-issue'
                                  ? 'Currently has issues'
                                  : 'Under maintenance'}
                              </Text>
                            </View>
                          </View>
                          {selected && (
                            <Text style={s.equipCheck}>✓</Text>
                          )}
                        </TouchableOpacity>
                      );
                    })
                )}

                <StepNav onBack={goBack} onNext={goNext} />
              </ScrollView>
            )}

            {/* ════════════════════════════════
                STEP 3 — Problem Type (Category)
            ════════════════════════════════ */}
            {step === 3 && (
              <ScrollView
                contentContainerStyle={s.scrollBody}
                showsVerticalScrollIndicator={false}
                keyboardShouldPersistTaps="handled"
              >
                <Text style={s.stepHeading}>What type of problem?</Text>
                <Text style={s.stepSub}>Select the category that best describes your issue</Text>

                {/* Voice dictation entry point — only place it can live,
                    since lab_id (steps 1–2) is already chosen by this point */}
                <TouchableOpacity
                  style={s.voiceEntryBtn}
                  onPress={openVoiceModal}
                  activeOpacity={0.8}
                >
                  <Text style={s.voiceEntryIcon}>🎙️</Text>
                  <View style={{ flex: 1 }}>
                    <Text style={s.voiceEntryTitle}>Dictate instead</Text>
                    <Text style={s.voiceEntrySub}>
                      Speak your issue and we'll fill in the category, title, and description
                    </Text>
                  </View>
                  <Text style={s.voiceEntryArrow}>→</Text>
                </TouchableOpacity>

                <View style={s.catGrid}>
                  {CATEGORIES.map((cat) => {
                    const selected = form.category === cat.value;
                    return (
                      <TouchableOpacity
                        key={cat.value}
                        style={[s.catCard, selected && s.catCardSelected]}
                        onPress={() => selectCategory(cat.value)}
                        activeOpacity={0.75}
                      >
                        <Text style={s.catIcon}>{cat.icon}</Text>
                        <Text style={[s.catLabel, selected && s.catLabelSelected]}>
                          {cat.label}
                        </Text>
                        <Text style={s.catDesc}>{cat.desc}</Text>
                        {selected && (
                          <View style={s.catCheck}>
                            <Text style={s.catCheckText}>✓</Text>
                          </View>
                        )}
                      </TouchableOpacity>
                    );
                  })}
                </View>

                {/* Summary of selections so far */}
                <View style={s.selectionSummary}>
                  <Text style={s.summaryTitle}>Selected so far</Text>
                  <View style={s.summaryPills}>
                    <View style={s.summaryPill}>
                      <Text style={s.summaryPillText}>🏫 {selectedLabName}</Text>
                    </View>
                    <View style={s.summaryPill}>
                      <Text style={s.summaryPillText}>🖥️ {selectedEquipName}</Text>
                    </View>
                  </View>
                </View>

                <StepNav
                  onBack={goBack}
                  onNext={goNext}
                  nextDisabled={!form.category}
                />
              </ScrollView>
            )}

            {/* ════════════════════════════════
                STEP 4 — Description
            ════════════════════════════════ */}
            {step === 4 && (
              <ScrollView
                contentContainerStyle={s.scrollBody}
                showsVerticalScrollIndicator={false}
                keyboardShouldPersistTaps="handled"
              >
                <Text style={s.stepHeading}>Describe the Problem</Text>
                <Text style={s.stepSub}>
                  The more detail you provide, the faster IT can help
                </Text>

                {/* Title field */}
                <View style={s.fieldGroup}>
                  <Text style={s.fieldLabel}>Issue Title *</Text>
                  <TextInput
                    style={[s.input, !!errors.title && s.inputError]}
                    value={form.title}
                    onChangeText={(v) => {
                      setForm((f) => ({ ...f, title: v }));
                      if (errors.title) setErrors((e) => ({ ...e, title: undefined }));
                    }}
                    placeholder="Brief description of the problem"
                    placeholderTextColor={colors.textDisabled}
                    returnKeyType="next"
                    maxLength={255}
                    editable={!submitting}
                  />
                  <FieldError message={errors.title} />
                  <Text style={s.charCount}>{form.title.length}/255</Text>
                </View>

                {/* Description field */}
                <View style={s.fieldGroup}>
                  <Text style={s.fieldLabel}>Detailed Description *</Text>
                  <TextInput
                    style={[s.input, s.textArea, !!errors.description && s.inputError]}
                    value={form.description}
                    onChangeText={(v) => {
                      setForm((f) => ({ ...f, description: v }));
                      if (errors.description) setErrors((e) => ({ ...e, description: undefined }));
                    }}
                    placeholder="Please describe the issue in detail. What happened? When did it start? What have you already tried?"
                    placeholderTextColor={colors.textDisabled}
                    multiline
                    textAlignVertical="top"
                    returnKeyType="default"
                    editable={!submitting}
                  />
                  <FieldError message={errors.description} />
                  <Text style={s.charCount}>
                    {form.description.length} chars
                    {form.description.length < 10 && form.description.length > 0
                      ? ` (${10 - form.description.length} more needed)`
                      : ''}
                  </Text>
                </View>

                {/* Tips card */}
                <View style={s.tipsCard}>
                  <Text style={s.tipsTitle}>💡 Helpful tips</Text>
                  <Text style={s.tipItem}>• Mention any error messages you saw</Text>
                  <Text style={s.tipItem}>• Describe when the issue started</Text>
                  <Text style={s.tipItem}>• Note if other workstations are affected</Text>
                </View>

                <StepNav
                  onBack={goBack}
                  onNext={() => {
                    if (validateStep4()) goNext();
                  }}
                  nextLabel="Review & Submit →"
                  nextDisabled={!form.title.trim() || form.description.trim().length < 10}
                />
              </ScrollView>
            )}

            {/* ════════════════════════════════
                STEP 5 — Review & Submit
            ════════════════════════════════ */}
            {step === 5 && (
              <ScrollView
                contentContainerStyle={s.scrollBody}
                showsVerticalScrollIndicator={false}
              >
                <Text style={s.stepHeading}>Review Your Report</Text>
                <Text style={s.stepSub}>
                  Check everything looks correct before submitting
                </Text>

                {/* Review card */}
                <View style={s.reviewCard}>
                  <ReviewRow label="Lab Location"     value={selectedLabName || '—'}        />
                  <ReviewRow label="Equipment"         value={selectedEquipName}              />
                  <ReviewRow
                    label="Problem Category"
                    value={
                      CATEGORIES.find((c) => c.value === form.category)?.label ?? '—'
                    }
                  />
                  <ReviewRow label="Issue Title"       value={form.title || '—'}             />
                  <View style={s.reviewRowDesc}>
                    <Text style={s.reviewLabel}>Description</Text>
                    <Text style={s.reviewDescText}>
                      {form.description || '—'}
                    </Text>
                  </View>
                </View>

                {/* What happens next */}
                <View style={s.nextStepsCard}>
                  <Text style={s.nextStepsTitle}>What happens next?</Text>
                  <View style={s.nextStep}>
                    <View style={s.nextStepDot} />
                    <Text style={s.nextStepText}>
                      Your ticket will be assigned a unique ID
                    </Text>
                  </View>
                  <View style={s.nextStep}>
                    <View style={s.nextStepDot} />
                    <Text style={s.nextStepText}>
                      IT support will review and assign a technician
                    </Text>
                  </View>
                </View>

                {/* Server error shown here if submit fails */}
                {errors.general && (
                  <View style={s.errorBanner}>
                    <Text style={s.errorBannerText}>{errors.general}</Text>
                  </View>
                )}

                <StepNav
                  onBack={goBack}
                  onNext={handleSubmit}
                  nextLabel="Submit Report"
                  loading={submitting}
                />

                <View style={{ height: spacing.xl }} />
              </ScrollView>
            )}

          </Animated.View>
        </KeyboardAvoidingView>
      )}

      <VoiceCaptureModal
        visible={voiceModalVisible}
        phase={voicePhase}
        liveTranscript={liveTranscript}
        editableTranscript={editableTranscript}
        onChangeTranscript={setEditableTranscript}
        error={voiceError}
        onStart={startVoiceCapture}
        onStop={stopVoiceCapture}
        onConfirm={confirmTranscriptAndExtract}
        onRetry={retryVoiceCapture}
        onClose={closeVoiceModal}
      />
    </SafeAreaView>
  );
}

// ─── Styles ───────────────────────────────────────────────────────────────────

const s = StyleSheet.create({
  root: {
    flex: 1,
    backgroundColor: colors.bgPrimary,
  },

  // ── Nav ─────────────────────────────────────────────────────────────────
  nav: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.sm + 4,
    borderBottomWidth: 1,
    borderBottomColor: colors.border,
  },
  navBack: {
    fontSize: font.sm,
    fontWeight: font.semibold,
    color: colors.primary,
    width: 60,
  },
  navTitle: {
    fontSize: font.base,
    fontWeight: font.bold,
    color: colors.textPrimary,
  },
  navStep: {
    fontSize: font.sm,
    color: colors.textMuted,
    width: 60,
    textAlign: 'right',
  },

  // ── Maintenance ───────────────────────────────────────────────────────────
  maintWrap: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    padding: spacing.xl,
    gap: spacing.md,
  },
  maintIcon: { fontSize: 48 },
  maintTitle: {
    fontSize: font.xl,
    fontWeight: font.bold,
    color: colors.textPrimary,
  },
  maintMsg: {
    fontSize: font.base,
    color: colors.textMuted,
    textAlign: 'center',
    lineHeight: 22,
  },
  maintBtn: {
    marginTop: spacing.sm,
    backgroundColor: colors.primary,
    paddingHorizontal: spacing.xl,
    paddingVertical: spacing.sm + 4,
    borderRadius: radius.full,
  },
  maintBtnText: {
    fontSize: font.base,
    fontWeight: font.bold,
    color: '#fff',
  },

  // ── Error banner ──────────────────────────────────────────────────────────
  errorBanner: {
    marginHorizontal: spacing.lg,
    marginBottom: spacing.sm,
    backgroundColor: colors.dangerBg,
    borderWidth: 1,
    borderColor: colors.dangerBorder,
    borderRadius: radius.md,
    padding: spacing.sm + 2,
  },
  errorBannerText: {
    color: '#fca5a5',
    fontSize: font.sm,
    lineHeight: 19,
  },

  // ── Scroll body ───────────────────────────────────────────────────────────
  scrollBody: {
    paddingHorizontal: spacing.lg,
    paddingBottom: spacing.xxl,
  },

  // ── Step headings ─────────────────────────────────────────────────────────
  stepHeading: {
    fontSize: font.xl,
    fontWeight: font.bold,
    color: colors.textPrimary,
    marginBottom: spacing.xs,
    marginTop: spacing.xs,
  },
  stepSub: {
    fontSize: font.sm,
    color: colors.textMuted,
    marginBottom: spacing.lg,
    lineHeight: 19,
  },

  // ── Centered state ────────────────────────────────────────────────────────
  centered: {
    alignItems: 'center',
    paddingVertical: spacing.xl,
    gap: spacing.sm,
  },
  loadingText: {
    fontSize: font.sm,
    color: colors.textMuted,
  },
  emptyIcon: { fontSize: 32, opacity: 0.4 },
  emptyText: { fontSize: font.sm, color: colors.textMuted },

  // ── Lab grid ──────────────────────────────────────────────────────────────
  labGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
    marginBottom: spacing.md,
  },
  labCard: {
    width: '48%',
    backgroundColor: colors.bgCard,
    borderWidth: 1.5,
    borderColor: colors.border,
    borderRadius: radius.xl,
    padding: spacing.md,
    alignItems: 'center',
    gap: spacing.xs,
    position: 'relative',
  },
  labCardSelected: {
    borderColor: colors.primary,
    backgroundColor: colors.primaryLight,
  },
  labIcon: {
    fontSize: 26,
    lineHeight: 32,
  },
  labName: {
    fontSize: font.sm,
    fontWeight: font.semibold,
    color: colors.textPrimary,
    textAlign: 'center',
  },
  labNameSelected: {
    color: colors.primary,
  },
  labMeta: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 5,
  },
  labStatusDot: {
    width: 6,
    height: 6,
    borderRadius: 3,
  },
  labMetaText: {
    fontSize: font.xs - 1,
    color: colors.textMuted,
  },
  labCheckmark: {
    position: 'absolute',
    top: spacing.xs,
    right: spacing.xs,
    width: 18,
    height: 18,
    borderRadius: 9,
    backgroundColor: colors.primary,
    alignItems: 'center',
    justifyContent: 'center',
  },
  labCheckmarkText: {
    fontSize: font.xs - 2,
    color: '#fff',
    fontWeight: font.bold,
  },

  // ── Equipment list ────────────────────────────────────────────────────────
  equipOption: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    backgroundColor: colors.bgCard,
    borderWidth: 1.5,
    borderColor: colors.border,
    borderRadius: radius.lg,
    padding: spacing.md,
    marginBottom: spacing.sm,
  },
  equipOptionSelected: {
    borderColor: colors.primary,
    backgroundColor: colors.primaryLight,
  },
  equipOptionLeft: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    flex: 1,
  },
  equipIcon: {
    fontSize: 22,
    width: 28,
    textAlign: 'center',
  },
  equipCode: {
    fontSize: font.sm,
    fontWeight: font.semibold,
    color: colors.textPrimary,
    marginBottom: 2,
  },
  equipCodeSelected: {
    color: colors.primary,
  },
  equipSub: {
    fontSize: font.xs,
    color: colors.textMuted,
  },
  equipCheck: {
    fontSize: font.base,
    color: colors.primary,
    fontWeight: font.bold,
  },
  equipDivider: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    marginVertical: spacing.md,
  },
  equipDividerLine: {
    flex: 1,
    height: 1,
    backgroundColor: colors.borderLight,
  },
  equipDividerText: {
    fontSize: font.xs,
    color: colors.textMuted,
    flexShrink: 1,
    textAlign: 'center',
  },
  equipEmpty: {
    fontSize: font.sm,
    color: colors.textMuted,
    textAlign: 'center',
    paddingVertical: spacing.md,
  },

  // ── Voice dictation entry (Step 3) ────────────────────────────────────────
  voiceEntryBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    backgroundColor: colors.primaryLight,
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radius.lg,
    padding: spacing.md,
    marginBottom: spacing.lg,
  },
  voiceEntryIcon: {
    fontSize: 24,
  },
  voiceEntryTitle: {
    fontSize: font.sm,
    fontWeight: font.bold,
    color: colors.primary,
  },
  voiceEntrySub: {
    fontSize: font.xs,
    color: colors.textMuted,
    marginTop: 1,
  },
  voiceEntryArrow: {
    fontSize: font.lg,
    color: colors.primary,
  },

  // ── Category grid ─────────────────────────────────────────────────────────
  catGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
    marginBottom: spacing.lg,
  },
  catCard: {
    width: '48%',
    backgroundColor: colors.bgCard,
    borderWidth: 1.5,
    borderColor: colors.border,
    borderRadius: radius.xl,
    padding: spacing.md,
    alignItems: 'center',
    gap: spacing.xs,
    position: 'relative',
    minHeight: 100,
    justifyContent: 'center',
  },
  catCardSelected: {
    borderColor: colors.primary,
    backgroundColor: colors.primaryLight,
  },
  catIcon: { fontSize: 26 },
  catLabel: {
    fontSize: font.sm,
    fontWeight: font.semibold,
    color: colors.textPrimary,
    textAlign: 'center',
  },
  catLabelSelected: {
    color: colors.primary,
  },
  catDesc: {
    fontSize: font.xs - 1,
    color: colors.textMuted,
    textAlign: 'center',
    lineHeight: 15,
  },
  catCheck: {
    position: 'absolute',
    top: spacing.xs,
    right: spacing.xs,
    width: 18,
    height: 18,
    borderRadius: 9,
    backgroundColor: colors.primary,
    alignItems: 'center',
    justifyContent: 'center',
  },
  catCheckText: {
    fontSize: font.xs - 2,
    color: '#fff',
    fontWeight: font.bold,
  },

  // ── Selection summary ─────────────────────────────────────────────────────
  selectionSummary: {
    backgroundColor: 'rgba(45,212,191,0.05)',
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radius.lg,
    padding: spacing.md,
    marginBottom: spacing.md,
  },
  summaryTitle: {
    fontSize: font.xs,
    fontWeight: font.semibold,
    color: colors.textMuted,
    textTransform: 'uppercase',
    letterSpacing: 0.5,
    marginBottom: spacing.sm,
  },
  summaryPills: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.xs,
  },
  summaryPill: {
    backgroundColor: colors.primaryLight,
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radius.full,
    paddingHorizontal: spacing.sm + 2,
    paddingVertical: spacing.xs,
  },
  summaryPillText: {
    fontSize: font.xs,
    color: colors.primary,
    fontWeight: font.semibold,
  },

  // ── Description form fields ───────────────────────────────────────────────
  fieldGroup: {
    marginBottom: spacing.md,
  },
  fieldLabel: {
    fontSize: font.sm,
    fontWeight: font.semibold,
    color: colors.textSecondary,
    marginBottom: spacing.xs,
  },
  input: {
    backgroundColor: colors.bgCard,
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radius.lg,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm + 4,
    color: colors.textPrimary,
    fontSize: font.base,
  },
  textArea: {
    minHeight: 130,
    paddingTop: spacing.md,
    lineHeight: 22,
  },
  inputError: {
    borderColor: colors.dangerBorder,
    backgroundColor: 'rgba(239,68,68,0.05)',
  },
  fieldError: {
    color: '#f87171',
    fontSize: font.xs,
    marginTop: spacing.xs - 2,
  },
  charCount: {
    fontSize: font.xs - 1,
    color: colors.textMuted,
    marginTop: spacing.xs - 2,
    textAlign: 'right',
  },
  tipsCard: {
    backgroundColor: 'rgba(59,130,246,0.07)',
    borderWidth: 1,
    borderColor: 'rgba(59,130,246,0.2)',
    borderRadius: radius.lg,
    padding: spacing.md,
    marginBottom: spacing.md,
    gap: spacing.xs,
  },
  tipsTitle: {
    fontSize: font.sm,
    fontWeight: font.semibold,
    color: '#60a5fa',
    marginBottom: spacing.xs - 2,
  },
  tipItem: {
    fontSize: font.xs,
    color: '#93c5fd',
    lineHeight: 18,
  },

  // ── Review card ───────────────────────────────────────────────────────────
  reviewCard: {
    backgroundColor: colors.bgCard,
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radius.xl,
    padding: spacing.lg,
    marginBottom: spacing.md,
  },
  reviewRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'flex-start',
    paddingVertical: spacing.sm,
    borderBottomWidth: 1,
    borderBottomColor: 'rgba(45,212,191,0.07)',
    gap: spacing.sm,
  },
  reviewRowDesc: {
    paddingVertical: spacing.sm,
    gap: spacing.xs,
  },
  reviewLabel: {
    fontSize: font.sm,
    color: colors.textMuted,
    flex: 1,
  },
  reviewValue: {
    fontSize: font.sm,
    fontWeight: font.medium,
    color: colors.textPrimary,
    flex: 2,
    textAlign: 'right',
  },
  reviewDescText: {
    fontSize: font.sm,
    color: colors.textSecondary,
    lineHeight: 20,
  },

  // ── Next steps info card ──────────────────────────────────────────────────
  nextStepsCard: {
    backgroundColor: 'rgba(45,212,191,0.06)',
    borderWidth: 1,
    borderColor: 'rgba(45,212,191,0.2)',
    borderRadius: radius.lg,
    padding: spacing.md,
    marginBottom: spacing.md,
    gap: spacing.xs + 2,
  },
  nextStepsTitle: {
    fontSize: font.sm,
    fontWeight: font.bold,
    color: colors.primary,
    marginBottom: spacing.xs,
  },
  nextStep: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: spacing.sm,
  },
  nextStepDot: {
    width: 6,
    height: 6,
    borderRadius: 3,
    backgroundColor: colors.primary,
    marginTop: 6,
    flexShrink: 0,
  },
  nextStepText: {
    fontSize: font.sm,
    color: colors.textSecondary,
    lineHeight: 20,
    flex: 1,
  },

  // ── Step nav buttons ──────────────────────────────────────────────────────
  stepNav: {
    flexDirection: 'row',
    gap: spacing.sm,
    marginTop: spacing.lg,
    marginBottom: spacing.sm,
  },
  backBtn: {
    flex: 1,
    backgroundColor: colors.bgCard,
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radius.lg,
    paddingVertical: spacing.sm + 4,
    alignItems: 'center',
  },
  backBtnText: {
    fontSize: font.base,
    fontWeight: font.semibold,
    color: colors.textSecondary,
  },
  nextBtn: {
    flex: 2,
    backgroundColor: colors.primary,
    borderRadius: radius.lg,
    paddingVertical: spacing.sm + 4,
    alignItems: 'center',
    shadowColor: colors.primary,
    shadowOffset: { width: 0, height: 3 },
    shadowOpacity: 0.3,
    shadowRadius: 6,
    elevation: 4,
  },
  nextBtnDisabled: {
    opacity: 0.45,
  },
  nextBtnText: {
    fontSize: font.base,
    fontWeight: font.bold,
    color: '#fff',
  },
});

// ─── Voice modal styles ─────────────────────────────────────────────────────

const vm = StyleSheet.create({
  overlay: {
    flex: 1,
    backgroundColor: 'rgba(0,0,0,0.6)',
    justifyContent: 'flex-end',
  },
  sheet: {
    backgroundColor: colors.bgPrimary,
    borderTopLeftRadius: radius.xxl,
    borderTopRightRadius: radius.xxl,
    padding: spacing.lg,
    minHeight: 380,
    borderWidth: 1,
    borderColor: colors.border,
    borderBottomWidth: 0,
  },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: spacing.lg,
  },
  headerTitle: {
    fontSize: font.lg,
    fontWeight: font.bold,
    color: colors.textPrimary,
  },
  closeText: {
    fontSize: font.sm,
    color: colors.textMuted,
    fontWeight: font.medium,
  },
  errorBox: {
    backgroundColor: colors.dangerBg,
    borderWidth: 1,
    borderColor: colors.dangerBorder,
    borderRadius: radius.md,
    padding: spacing.sm + 2,
    marginBottom: spacing.md,
  },
  errorText: {
    color: '#fca5a5',
    fontSize: font.sm,
    lineHeight: 19,
  },
  centerBlock: {
    alignItems: 'center',
    justifyContent: 'center',
    gap: spacing.md,
    paddingVertical: spacing.xl,
  },
  helpText: {
    fontSize: font.sm,
    color: colors.textMuted,
    textAlign: 'center',
    lineHeight: 20,
    paddingHorizontal: spacing.md,
  },
  micBtn: {
    width: 84,
    height: 84,
    borderRadius: 42,
    backgroundColor: colors.primary,
    alignItems: 'center',
    justifyContent: 'center',
    shadowColor: colors.primary,
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.4,
    shadowRadius: 10,
    elevation: 6,
  },
  micIcon: {
    fontSize: 36,
  },
  micHint: {
    fontSize: font.sm,
    color: colors.textMuted,
  },
  listeningDot: {
    width: 12,
    height: 12,
    borderRadius: 6,
    backgroundColor: colors.danger,
  },
  listeningLabel: {
    fontSize: font.base,
    fontWeight: font.semibold,
    color: colors.textPrimary,
  },
  transcriptBox: {
    maxHeight: 140,
    width: '100%',
    backgroundColor: colors.bgCard,
    borderRadius: radius.lg,
    borderWidth: 1,
    borderColor: colors.border,
    padding: spacing.md,
  },
  transcriptText: {
    fontSize: font.base,
    color: colors.textPrimary,
    lineHeight: 22,
  },
  stopBtn: {
    backgroundColor: colors.dangerBg,
    borderWidth: 1,
    borderColor: colors.dangerBorder,
    borderRadius: radius.full,
    paddingHorizontal: spacing.xl,
    paddingVertical: spacing.sm + 4,
  },
  stopBtnText: {
    fontSize: font.base,
    fontWeight: font.bold,
    color: colors.danger,
  },
  reviewBlock: {
    gap: spacing.md,
  },
  reviewInput: {
    backgroundColor: colors.bgCard,
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radius.lg,
    padding: spacing.md,
    color: colors.textPrimary,
    fontSize: font.base,
    minHeight: 140,
    lineHeight: 22,
  },
  reviewActions: {
    flexDirection: 'row',
    gap: spacing.sm,
  },
  retryBtn: {
    flex: 1,
    backgroundColor: colors.bgCard,
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radius.lg,
    paddingVertical: spacing.sm + 4,
    alignItems: 'center',
  },
  retryBtnText: {
    fontSize: font.base,
    fontWeight: font.semibold,
    color: colors.textSecondary,
  },
  useBtn: {
    flex: 2,
    backgroundColor: colors.primary,
    borderRadius: radius.lg,
    paddingVertical: spacing.sm + 4,
    alignItems: 'center',
  },
  useBtnDisabled: {
    opacity: 0.45,
  },
  useBtnText: {
    fontSize: font.base,
    fontWeight: font.bold,
    color: '#fff',
  },
});