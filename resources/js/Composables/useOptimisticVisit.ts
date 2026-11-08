import type { Page, VisitOptions } from '@inertiajs/core';

function hasFlashError(page: Page): boolean {
  const flash = page.props.flash;
  return Boolean(flash && typeof flash === 'object' && 'error' in flash && flash.error);
}

export function withOptimisticRollback(rollback: () => void, options: VisitOptions = {}): VisitOptions {
  let rolledBack = false;
  let refused = false;
  const undo = () => {
    if (!rolledBack) {
      rolledBack = true;
      rollback();
    }
  };

  return {
    ...options,
    onBeforeUpdate: (page) => {
      // Toast watchers consume flash errors before onSuccess runs.
      refused = hasFlashError(page);
      return options.onBeforeUpdate?.(page);
    },
    onSuccess: (page, detail) => {
      if (rolledBack || refused || hasFlashError(page)) {
        undo();
        return;
      }
      return options.onSuccess?.(page, detail);
    },
    onError: (errors, detail) => {
      undo();
      return options.onError?.(errors, detail);
    },
    onHttpException: (response) => {
      undo();
      return options.onHttpException?.(response);
    },
    onNetworkError: (error) => {
      undo();
      return options.onNetworkError?.(error);
    },
    onCancel: () => {
      undo();
      return options.onCancel?.();
    },
  };
}
