import { useUIPreferences } from '@/Composables/useUIPreferences';

/**
 * The admin redesign opt-in (`appearance.new_shell`). `app.blade.php` stamps `data-surface="admin"`
 * on every full page load; this also updates the live DOM so flipping it needs no reload.
 */
export function useNewShellToggle() {
  const preferences = useUIPreferences();

  const toggle = (): void => {
    const next = !preferences.newShell.value;
    preferences.setNewShell(next);

    if (typeof document === 'undefined') {
      return;
    }

    if (next) {
      document.documentElement.setAttribute('data-surface', 'admin');
    }
    else {
      document.documentElement.removeAttribute('data-surface');
    }
  };

  return { enabled: preferences.newShell, toggle };
}
