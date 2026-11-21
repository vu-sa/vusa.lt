import { describe, test, expect, vi, beforeEach, afterEach } from 'vitest';
import { usePage } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';

import { createMockPage } from '@/tests/helpers/createMockPage';
import { apiRequest } from '@/Composables/apiRequest';

vi.mock('vue-sonner', () => ({
  toast: { success: vi.fn(), error: vi.fn(), info: vi.fn() },
}));

const mockFetch = vi.fn();
vi.stubGlobal('fetch', mockFetch);

function respond(body: unknown, status = 200) {
  return new Response(body === null ? null : JSON.stringify(body), {
    status,
    headers: { 'Content-Type': 'application/json' },
  });
}

function sentInit(): RequestInit & { headers: Record<string, string> } {
  return mockFetch.mock.calls[0]?.[1];
}

describe('apiRequest', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    vi.mocked(usePage).mockReturnValue(createMockPage({ csrf_token: 'prop-token' }));
  });

  afterEach(() => {
    document.cookie = 'XSRF-TOKEN=; expires=Thu, 01 Jan 1970 00:00:00 GMT';
  });

  test('sends JSON with the page CSRF token and unwraps the envelope', async () => {
    mockFetch.mockResolvedValueOnce(respond({ success: true, data: { id: 1 }, meta: { total: 3 } }));

    const result = await apiRequest<{ id: number }>('/api/v1/admin/x', { method: 'POST', body: { a: 1 } });

    expect(result).toMatchObject({ ok: true, status: 200, data: { id: 1 }, meta: { total: 3 } });
    expect(sentInit()).toMatchObject({ method: 'POST', credentials: 'same-origin', body: '{"a":1}' });
    expect(sentInit().headers).toMatchObject({
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'X-CSRF-TOKEN': 'prop-token',
    });
  });

  test('prefers the XSRF cookie over the page prop', async () => {
    document.cookie = 'XSRF-TOKEN=cookie%3Dtoken';
    mockFetch.mockResolvedValueOnce(respond({ success: true, data: null }));

    await apiRequest('/api/v1/admin/x', { method: 'DELETE' });

    expect(sentInit().headers['X-XSRF-TOKEN']).toBe('cookie=token');
    expect(sentInit().headers).not.toHaveProperty('X-CSRF-TOKEN');
  });

  test('passes bare JSON through as data, and keeps it whole with raw', async () => {
    mockFetch.mockResolvedValueOnce(respond([{ id: 1 }]));
    expect((await apiRequest('/mano/knowledge/link-options')).data).toEqual([{ id: 1 }]);

    mockFetch.mockResolvedValueOnce(respond({ success: true, config: { key: 'k' } }));
    expect((await apiRequest('/api/v1/admin/search/refresh-key', { raw: true })).data)
      .toEqual({ success: true, config: { key: 'k' } });
  });

  test('treats 204 as success without data', async () => {
    mockFetch.mockResolvedValueOnce(new Response(null, { status: 204 }));

    expect(await apiRequest('/api/v1/admin/preferences', { method: 'PUT', body: {} }))
      .toMatchObject({ ok: true, status: 204, data: null });
  });

  test('resolves error statuses with the body, errors and headers, without a toast by default', async () => {
    mockFetch.mockResolvedValueOnce(respond({ success: false, message: 'Conflict', data: { version: 4 } }, 409));
    const conflict = await apiRequest('/api/v1/admin/notes/1', { method: 'PUT', body: {} });
    expect(conflict).toMatchObject({ ok: false, status: 409, data: null, message: 'Conflict', body: { data: { version: 4 } } });

    mockFetch.mockResolvedValueOnce(new Response('{"message":"Slow down"}', { status: 429, headers: { 'Retry-After': '30' } }));
    const limited = await apiRequest('/api/v1/admin/search/config');
    expect(limited.headers?.get('retry-after')).toBe('30');

    mockFetch.mockResolvedValueOnce(respond({ success: false, message: 'Invalid', errors: { name: ['Required'] } }, 422));
    expect((await apiRequest('/api/v1/admin/x', { method: 'POST', body: {} })).errors).toEqual({ name: ['Required'] });

    expect(toast.error).not.toHaveBeenCalled();
  });

  test('toasts the server message when asked to', async () => {
    mockFetch.mockResolvedValueOnce(respond({ success: false, message: 'Forbidden' }, 403));

    await apiRequest('/api/v1/admin/x', { showErrorToast: true });

    expect(toast.error).toHaveBeenCalledWith('Forbidden');
  });

  test('sends FormData without a JSON content type', async () => {
    mockFetch.mockResolvedValueOnce(respond({ success: true, data: { url: '/a.png' } }));
    const body = new FormData();
    body.append('file', new Blob(['x']), 'a.png');

    await apiRequest('/api/v1/admin/files', { method: 'POST', body });

    expect(sentInit().body).toBe(body);
    expect(sentInit().headers).not.toHaveProperty('Content-Type');
  });

  test('passes keepalive and signal through, and rethrows aborts', async () => {
    const controller = new AbortController();
    mockFetch.mockResolvedValueOnce(respond({ success: true, data: null }));
    await apiRequest('/api/v1/admin/x', { method: 'PUT', body: {}, keepalive: true, signal: controller.signal });
    expect(sentInit()).toMatchObject({ keepalive: true, signal: controller.signal });

    controller.abort();
    mockFetch.mockRejectedValueOnce(new DOMException('Aborted', 'AbortError'));
    await expect(apiRequest('/api/v1/admin/x', { signal: controller.signal })).rejects.toThrow('Aborted');
  });

  test('reports network failures as status 0', async () => {
    mockFetch.mockRejectedValueOnce(new TypeError('Failed to fetch'));

    expect(await apiRequest('/api/v1/admin/x')).toMatchObject({ ok: false, status: 0, message: 'Failed to fetch' });
  });

  test('shows one session-expired toast with a reload action for concurrent 419s', async () => {
    mockFetch.mockResolvedValue(respond({ message: 'CSRF token mismatch.' }, 419));

    await Promise.all([apiRequest('/a', { method: 'POST', body: {}, showErrorToast: true }), apiRequest('/b', { method: 'POST', body: {} })]);

    const { calls } = vi.mocked(toast.error).mock;
    expect(calls).toHaveLength(2);
    expect(calls.every(([, options]) => options?.id === 'session-expired')).toBe(true);
    expect(calls[0]?.[1]?.action).toMatchObject({ label: 'common.reload_page' });
  });
});
