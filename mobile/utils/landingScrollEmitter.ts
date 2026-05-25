/**
 * utils/landingScrollEmitter.ts
 *
 * A tiny typed event emitter used to signal the Landing screen to scroll
 * to a specific section when the Features or About tab is tapped.
 *
 * This avoids prop drilling and keeps the tab bar and the landing screen
 * decoupled — the tab bar emits, the landing screen listens.
 *
 * Usage:
 *   // In PublicTabBar (emit):
 *   landingScrollEmitter.emit('features');
 *
 *   // In Landing screen (listen):
 *   useEffect(() => {
 *     const sub = landingScrollEmitter.on('features', () => scrollToFeatures());
 *     return () => sub.remove();
 *   }, []);
 */

type ScrollTarget = 'features' | 'about';
type Listener = () => void;

class LandingScrollEmitter {
  private listeners: Map<ScrollTarget, Set<Listener>> = new Map();

  on(target: ScrollTarget, listener: Listener): { remove: () => void } {
    if (!this.listeners.has(target)) {
      this.listeners.set(target, new Set());
    }
    this.listeners.get(target)!.add(listener);

    return {
      remove: () => {
        this.listeners.get(target)?.delete(listener);
      },
    };
  }

  emit(target: ScrollTarget) {
    this.listeners.get(target)?.forEach((fn) => fn());
  }
}

// Singleton — same instance shared across the app
export const landingScrollEmitter = new LandingScrollEmitter();