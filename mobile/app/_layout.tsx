import { Stack } from 'expo-router';

export default function RootLayout() {
  return (
    <Stack screenOptions={{ headerShown: false }}>
      {/* This exposes your public group (and its index.tsx landing page) as the default entry point */}
      <Stack.Screen name="(public)" options={{ headerShown: false }} />
      
      {/* Keeps your authenticated tabs hidden until routed to */}
      <Stack.Screen name="(tabs)" options={{ headerShown: false }} />
      
      {/* Regular screen routes */}
      <Stack.Screen name="login" options={{ headerShown: false }} />
      <Stack.Screen name="register" options={{ headerShown: false }} />
    </Stack>
  );
}