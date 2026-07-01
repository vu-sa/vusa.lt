import { ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';

/**
 * Shape of the `access_change_warning` flash emitted by the backend
 * self-lockout guard (see App\Http\Controllers\AdminController::guardSelfLockout).
 */
export interface AccessChangeWarning {
  lostRoles: string[];
  severity: 'none' | 'warning';
}

type SubmitFn = (acknowledge: boolean) => void;

/**
 * Guards a mutating admin submit against the acting user locking themselves out.
 *
 * Route a form submit (or router.delete) through {@link guardedSubmit}. If the
 * backend determines the change would remove the current user's own critical
 * access, it responds with an `access_change_warning` flash instead of
 * persisting; this composable surfaces it as a confirmation dialog. On confirm,
 * the original submit is replayed with `acknowledge_access_change = true`.
 *
 * Multiple guards may be mounted simultaneously (e.g. a page and a nested form);
 * a `pending` flag ensures only the guard that issued the request reacts to the
 * resulting warning.
 */
export function useAccessChangeGuard() {
  const report = ref<AccessChangeWarning | null>(null);
  const open = ref(false);

  let lastSubmit: SubmitFn | null = null;
  let pending = false;

  watch(
    () => (usePage().props.flash as { access_change_warning?: AccessChangeWarning | null })?.access_change_warning,
    (warning) => {
      if (!warning || !pending) {
        return;
      }

      pending = false;
      report.value = warning;
      open.value = true;

      // Consume the flash so it can't re-trigger on a later navigation.
      (usePage().props.flash as { access_change_warning?: AccessChangeWarning | null }).access_change_warning = null;
    },
    { deep: true },
  );

  /** Run a submit, watching for a self-lockout warning in the response. */
  const guardedSubmit = (submit: SubmitFn) => {
    lastSubmit = submit;
    pending = true;
    submit(false);
  };

  /** Proceed despite the warning: replay the submit with acknowledgement. */
  const confirm = () => {
    open.value = false;
    lastSubmit?.(true);
  };

  /** Dismiss the warning without persisting. */
  const cancel = () => {
    open.value = false;
    report.value = null;
  };

  return { report, open, guardedSubmit, confirm, cancel };
}
