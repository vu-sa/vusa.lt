import { http, router, HttpCancelledError } from '@inertiajs/core';
import type { HttpRequestConfig, HttpResponse, Page } from '@inertiajs/core';
import { flushPromises } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import { withOptimisticRollback } from '../useOptimisticVisit';

import { createMockPage } from '@/tests/helpers/createMockPage';

const initialPage = (): Page => ({
  ...createMockPage(),
  component: 'Test', url: window.location.href, version: null,
  flash: {}, rememberedState: {},
  clearHistory: false, encryptHistory: false,
});

const pending: { config: HttpRequestConfig; resolve: (response: HttpResponse) => void; reject: (error: Error) => void }[] = [];
const originalClient = http.getClient();

beforeEach(async () => {
  pending.length = 0;
  vi.spyOn(window, 'scrollTo').mockImplementation(() => {});
  router.init({ initialPage: initialPage(), resolveComponent: async () => ({}), swapComponent: async () => {} });
  http.setClient({
    request: config => new Promise((resolve, reject) => {
      pending.push({ config, resolve, reject });
      config.signal?.addEventListener('abort', () => reject(new HttpCancelledError()), { once: true });
    }),
  });
  await flushPromises();
});

afterEach(async () => {
  router.cancelAll();
  await flushPromises();
  http.setClient(originalClient);
  vi.restoreAllMocks();
});

function respond(index: number, props: Record<string, unknown> = {}) {
  pending[index]!.resolve({
    status: 200, headers: { 'x-inertia': 'true' },
    data: JSON.stringify({ ...initialPage(), props: { errors: {}, flash: {}, ...props } }),
  });
}

describe('optimistic visit request lifecycle', () => {
  it.each([false, true])('allows independent writes to finish (reverse: %s)', async (reverse) => {
    const undoA = vi.fn();
    const undoB = vi.fn();
    const successA = vi.fn();
    const successB = vi.fn();
    router.post('/write-a', {}, withOptimisticRollback(undoA, { onSuccess: successA }));
    router.post('/write-b', {}, withOptimisticRollback(undoB, { onSuccess: successB }));
    await flushPromises();
    expect(pending).toHaveLength(2);
    expect(pending.every(request => !request.config.signal?.aborted)).toBe(true);
    respond(reverse ? 1 : 0);
    await flushPromises();
    respond(reverse ? 0 : 1);
    await flushPromises();
    expect(successA).toHaveBeenCalledOnce();
    expect(successB).toHaveBeenCalledOnce();
    expect(undoA).not.toHaveBeenCalled();
    expect(undoB).not.toHaveBeenCalled();
  });

  it.each(['flash', 'validation', 'http', 'network', 'cancel'] as const)('reverts on %s', async (failure) => {
    const undo = vi.fn();
    const success = vi.fn();
    const finish = vi.fn();
    const reconcile = vi.fn();
    router.post('/write', {}, withOptimisticRollback(undo, {
      onSuccess: success, onFinish: finish,
      onBeforeUpdate: page => { page.props.flash.error = null; },
      onHttpException: () => false,
      onNetworkError: () => false,
    }, reconcile));
    await flushPromises();
    if (failure === 'flash') respond(0, { flash: { error: 'Forbidden' } });
    if (failure === 'validation') respond(0, { errors: { name: 'Invalid' } });
    if (failure === 'http') pending[0]!.resolve({ status: 500, data: 'Error', headers: {} });
    if (failure === 'network') pending[0]!.reject(new Error('Offline'));
    if (failure === 'cancel') router.cancelAll();
    await flushPromises();
    expect(undo).toHaveBeenCalledOnce();
    expect(success).not.toHaveBeenCalled();
    expect(finish).toHaveBeenCalledOnce();
    expect(reconcile).toHaveBeenCalledTimes(failure === 'cancel' ? 1 : 0);
  });
});
