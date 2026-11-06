/**
 * components/scan/EquipmentScanner.tsx
 *
 * Full-screen camera modal that resolves a scanned equipment QR code.
 *
 * The payload is untrusted. We only check its `LABFIX-EQ:` prefix locally to
 * give instant feedback; the server is authoritative and resolves the token
 * against the caller's institution (POST /api/equipment/resolve).
 *
 * The payload is deliberately NOT a URL, so nothing here ever hands it to the
 * OS or a browser — see AGENTS.md ("never auto-open scanned URLs").
 */

import React, { useEffect, useRef, useState } from 'react';
import {
  View,
  Text,
  TouchableOpacity,
  StyleSheet,
  StatusBar,
  ActivityIndicator,
  Modal,
  Linking,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { CameraView, useCameraPermissions, type BarcodeScanningResult } from 'expo-camera';
import { colors, spacing, radius, font } from '@/constants/theme';
import { apiUrl } from '@/constants/api';
import { loadAuth } from '@/utils/auth';
import Icon from '@/components/ui/Icon';

export const QR_PREFIX = 'LABFIX-EQ:';

export type ScannedEquipment = {
  id:             number;
  equipment_code: string;
  type:           string;
  status:         string;
  [key: string]: unknown;
};

export type ScannedLab = {
  id:   number;
  name: string;
  [key: string]: unknown;
};

export type ScanResult = {
  equipment: ScannedEquipment;
  lab:       ScannedLab;
};

type Props = {
  visible:    boolean;
  onClose:    () => void;
  onResolved: (result: ScanResult) => void;
};

export default function EquipmentScanner({ visible, onClose, onResolved }: Props) {
  const [permission, requestPermission] = useCameraPermissions();
  const [busy,    setBusy]    = useState(false);
  const [error,   setError]   = useState<string | null>(null);

  // Locks the handler so one physical code isn't submitted repeatedly while
  // the request is in flight (the camera fires continuously).
  const lockRef = useRef(false);

  useEffect(() => {
    if (!visible) return;
    lockRef.current = false;
    setBusy(false);
    setError(null);
  }, [visible]);

  async function handleScan(result: BarcodeScanningResult) {
    if (lockRef.current || busy) return;

    const data = (result.data ?? '').trim();

    if (!data.startsWith(QR_PREFIX)) {
      setError("That isn't a LabFix equipment code.");
      return;
    }

    lockRef.current = true;
    setBusy(true);
    setError(null);

    try {
      const auth = await loadAuth();
      if (!auth) {
        setError('Your session expired. Please sign in again.');
        return;
      }

      const res = await fetch(apiUrl('equipment/resolve'), {
        method:  'POST',
        headers: {
          'Accept':        'application/json',
          'Content-Type':  'application/json',
          'Authorization': `Bearer ${auth.token}`,
        },
        body: JSON.stringify({ qr: data }),
      });

      const payload = await res.json().catch(() => null);

      if (res.ok && payload?.equipment && payload?.lab) {
        onResolved({ equipment: payload.equipment, lab: payload.lab });
        return;
      }

      setError(
        payload?.message ?? 'That equipment could not be found for your institution.',
      );
    } catch {
      setError('Could not reach the server. Check your connection and try again.');
    } finally {
      setBusy(false);
      // Brief cooldown so the same code isn't instantly re-submitted.
      setTimeout(() => { lockRef.current = false; }, 1200);
    }
  }

  function renderBody() {
    if (!permission) {
      return (
        <View style={s.centered}>
          <ActivityIndicator color={colors.primary} />
          <Text style={s.hintMuted}>Preparing camera…</Text>
        </View>
      );
    }

    if (!permission.granted) {
      return (
        <View style={s.centered}>
          <View style={s.permissionIcon}>
            <Icon name="camera" size={30} color={colors.primary} />
          </View>
          <Text style={s.permissionTitle}>Camera access needed</Text>
          <Text style={s.hintMuted}>
            LabFix uses the camera only to scan equipment QR codes.
          </Text>

          {permission.canAskAgain ? (
            <TouchableOpacity style={s.primaryBtn} onPress={requestPermission} activeOpacity={0.85}>
              <Text style={s.primaryBtnText}>Allow Camera</Text>
            </TouchableOpacity>
          ) : (
            <TouchableOpacity style={s.primaryBtn} onPress={() => Linking.openSettings()} activeOpacity={0.85}>
              <Text style={s.primaryBtnText}>Open Settings</Text>
            </TouchableOpacity>
          )}
        </View>
      );
    }

    return (
      <View style={s.cameraWrap}>
        <CameraView
          style={StyleSheet.absoluteFill}
          facing="back"
          barcodeScannerSettings={{ barcodeTypes: ['qr'] }}
          onBarcodeScanned={busy ? undefined : handleScan}
        />

        <View style={s.overlay} pointerEvents="none">
          <View style={s.frame} />
          <Text style={s.overlayHint}>Point the camera at the equipment QR code</Text>
        </View>

        {busy && (
          <View style={s.busyOverlay}>
            <ActivityIndicator size="large" color="#ffffff" />
            <Text style={s.busyText}>Looking up equipment…</Text>
          </View>
        )}

        {error && (
          <View style={s.errorBanner}>
            <Icon name="alert" size={16} color="#fff" />
            <Text style={s.errorText}>{error}</Text>
          </View>
        )}
      </View>
    );
  }

  return (
    <Modal
      visible={visible}
      animationType="slide"
      onRequestClose={onClose}
      statusBarTranslucent
    >
      <SafeAreaView style={s.root} edges={['top', 'bottom']}>
        <StatusBar barStyle="light-content" backgroundColor="#000" />

        <View style={s.nav}>
          <Text style={s.navTitle}>Scan Equipment</Text>
          <TouchableOpacity onPress={onClose} hitSlop={12} activeOpacity={0.7}>
            <View style={s.closeBtn}>
              <Icon name="close" size={20} color="#fff" />
            </View>
          </TouchableOpacity>
        </View>

        {renderBody()}
      </SafeAreaView>
    </Modal>
  );
}

// ─── Styles ───────────────────────────────────────────────────────────────────

const s = StyleSheet.create({
  root: {
    flex: 1,
    backgroundColor: '#000000',
  },
  nav: {
    flexDirection:     'row',
    alignItems:        'center',
    justifyContent:    'space-between',
    paddingHorizontal: spacing.lg,
    paddingVertical:   spacing.sm + 4,
  },
  navTitle: {
    fontSize:   font.md,
    fontWeight: font.bold,
    color:      '#ffffff',
  },
  closeBtn: {
    width:           34,
    height:          34,
    borderRadius:    radius.full,
    backgroundColor: 'rgba(255,255,255,0.14)',
    alignItems:      'center',
    justifyContent:  'center',
  },

  centered: {
    flex:              1,
    alignItems:        'center',
    justifyContent:    'center',
    paddingHorizontal: spacing.xl,
    gap:               spacing.md,
  },
  permissionIcon: {
    width:           72,
    height:          72,
    borderRadius:    radius.full,
    backgroundColor: 'rgba(45,212,191,0.16)',
    alignItems:      'center',
    justifyContent:  'center',
  },
  permissionTitle: {
    fontSize:   font.lg,
    fontWeight: font.bold,
    color:      '#ffffff',
  },
  hintMuted: {
    fontSize:  font.sm,
    color:     '#9ca3af',
    textAlign: 'center',
  },
  primaryBtn: {
    marginTop:         spacing.sm,
    backgroundColor:   colors.primary,
    paddingHorizontal: spacing.xl,
    paddingVertical:   spacing.sm + 4,
    borderRadius:      radius.full,
  },
  primaryBtnText: {
    fontSize:   font.sm,
    fontWeight: font.bold,
    color:      '#042f2e',
  },

  cameraWrap: {
    flex: 1,
  },
  overlay: {
    ...StyleSheet.absoluteFillObject,
    alignItems:     'center',
    justifyContent: 'center',
    gap:            spacing.lg,
  },
  frame: {
    width:        250,
    height:       250,
    borderRadius: radius.xl,
    borderWidth:  3,
    borderColor:  colors.primary,
    backgroundColor: 'rgba(255,255,255,0.04)',
  },
  overlayHint: {
    fontSize:   font.sm,
    color:      '#ffffff',
    textAlign:  'center',
    paddingHorizontal: spacing.xl,
  },
  busyOverlay: {
    ...StyleSheet.absoluteFillObject,
    backgroundColor: 'rgba(0,0,0,0.55)',
    alignItems:      'center',
    justifyContent:  'center',
    gap:             spacing.md,
  },
  busyText: {
    fontSize:   font.sm,
    fontWeight: font.semibold,
    color:      '#ffffff',
  },
  errorBanner: {
    position:          'absolute',
    left:              spacing.lg,
    right:             spacing.lg,
    bottom:            spacing.xl,
    flexDirection:     'row',
    alignItems:        'center',
    gap:               spacing.sm,
    backgroundColor:   'rgba(239,68,68,0.94)',
    borderRadius:      radius.lg,
    paddingVertical:   spacing.sm + 2,
    paddingHorizontal: spacing.md,
  },
  errorText: {
    flex:       1,
    fontSize:   font.sm,
    color:      '#ffffff',
    lineHeight: 19,
  },
});
