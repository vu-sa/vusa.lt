import type { HttpResponse } from '@inertiajs/core';
import { describe, expect, it, vi } from 'vitest';

import { withOptimisticRollback } from '../useOptimisticVisit';

import { createMockPage } from '@/tests/helpers/createMockPage';

describe('withOptimisticRollback', () => {
  it.each(['validation', 'flash', 'http', 'network', 'cancel'] as const)('rolls back on %s failure', (failure) => {
    const rollback = vi.fn();
    const success = vi.fn();
    const options = withOptimisticRollback(rollback, { onSuccess: success });

    if (failure === 'validation') options.onError?.({ name: 'Invalid' });
    if (failure === 'flash') options.onSuccess?.(createMockPage({ flash: { error: 'Forbidden' } }));
    if (failure === 'http') options.onHttpException?.({ status: 500 } as HttpResponse);
    if (failure === 'network') options.onNetworkError?.(new Error('Offline'));
    if (failure === 'cancel') options.onCancel?.();

    expect(rollback).toHaveBeenCalledOnce();
    expect(success).not.toHaveBeenCalled();
  });

  it('keeps a clean success and calls its success callback', () => {
    const rollback = vi.fn();
    const success = vi.fn();
    const page = createMockPage();

    withOptimisticRollback(rollback, { onSuccess: success }).onSuccess?.(page);

    expect(rollback).not.toHaveBeenCalled();
    expect(success).toHaveBeenCalledWith(page, undefined);
  });

  it('remembers a flash refusal after a toast watcher consumes the message', () => {
    const rollback = vi.fn();
    const success = vi.fn();
    const page = createMockPage({ flash: { error: 'Forbidden' } });
    const options = withOptimisticRollback(rollback, { onSuccess: success });

    options.onBeforeUpdate?.(page);
    page.props.flash.error = null;
    options.onSuccess?.(page);

    expect(rollback).toHaveBeenCalledOnce();
    expect(success).not.toHaveBeenCalled();
  });

  it('rolls back once and preserves exception callback return values', () => {
    const rollback = vi.fn();
    const error = new Error('Offline');
    const onNetworkError = vi.fn(() => false as const);
    const options = withOptimisticRollback(rollback, { onNetworkError });

    expect(options.onNetworkError?.(error)).toBe(false);
    options.onCancel?.();

    expect(rollback).toHaveBeenCalledOnce();
    expect(onNetworkError).toHaveBeenCalledWith(error);
  });
});
