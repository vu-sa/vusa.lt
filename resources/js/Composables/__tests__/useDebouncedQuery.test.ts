import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { ref } from 'vue';

const executeMock = vi.fn();
const dataRef = ref<unknown>(null);
const isFetchingRef = ref(false);
let capturedUrl: ReturnType<typeof ref<string>> | null = null;

vi.mock('@/Composables/useApi', () => ({
  useApi: vi.fn((url: ReturnType<typeof ref<string>>) => {
    capturedUrl = url;
    return { data: dataRef, isFetching: isFetchingRef, execute: executeMock };
  }),
}));

import { useDebouncedQuery } from '@/Composables/useDebouncedQuery';

describe('useDebouncedQuery', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    vi.useFakeTimers();
    dataRef.value = null;
    isFetchingRef.value = false;
    capturedUrl = null;
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  it('does not trigger fetch if url returns empty string or null', async () => {
    const query = ref('');
    useDebouncedQuery({
      url: () => (query.value.length >= 3 ? `/api/test?q=${query.value}` : null),
      watchSources: [query],
      debounceMs: 300,
      initialValue: [] as string[],
    });

    query.value = 'ab';
    await vi.advanceTimersByTimeAsync(400);

    expect(executeMock).not.toHaveBeenCalled();
    expect(capturedUrl?.value).toBe('');
  });

  it('triggers execute with generated url after debounce delay', async () => {
    const query = ref('');
    useDebouncedQuery({
      url: () => (query.value.length >= 3 ? `/api/test?q=${query.value}` : null),
      watchSources: [query],
      debounceMs: 300,
      initialValue: [] as string[],
    });

    query.value = 'hello';
    await vi.advanceTimersByTimeAsync(100);
    expect(executeMock).not.toHaveBeenCalled();

    await vi.advanceTimersByTimeAsync(250);
    expect(executeMock).toHaveBeenCalledTimes(1);
    expect(capturedUrl?.value).toBe('/api/test?q=hello');
  });

  it('debounces rapid changes into a single request', async () => {
    const query = ref('');
    useDebouncedQuery({
      url: () => (query.value.length >= 3 ? `/api/test?q=${query.value}` : null),
      watchSources: [query],
      debounceMs: 300,
      initialValue: [] as string[],
    });

    query.value = 'hel';
    await vi.advanceTimersByTimeAsync(100);
    query.value = 'hell';
    await vi.advanceTimersByTimeAsync(100);
    query.value = 'hello';
    await vi.advanceTimersByTimeAsync(350);

    expect(executeMock).toHaveBeenCalledTimes(1);
    expect(capturedUrl?.value).toBe('/api/test?q=hello');
  });

  it('clears url and falls back to initialValue when url becomes invalid', async () => {
    const query = ref('');
    const { data } = useDebouncedQuery({
      url: () => (query.value.length >= 3 ? `/api/test?q=${query.value}` : null),
      watchSources: [query],
      debounceMs: 300,
      initialValue: [] as string[],
    });

    query.value = 'valid';
    await vi.advanceTimersByTimeAsync(350);
    expect(capturedUrl?.value).toBe('/api/test?q=valid');

    dataRef.value = ['result1', 'result2'];
    expect(data.value).toEqual(['result1', 'result2']);

    query.value = '';
    await vi.advanceTimersByTimeAsync(350);
    expect(capturedUrl?.value).toBe('');
    expect(data.value).toEqual([]);
  });

  it('allows manual trigger via check()', async () => {
    const query = ref('manual');
    const { check } = useDebouncedQuery({
      url: () => (query.value.length >= 3 ? `/api/test?q=${query.value}` : null),
      debounceMs: 200,
      initialValue: null as string | null,
    });

    check();
    await vi.advanceTimersByTimeAsync(250);

    expect(executeMock).toHaveBeenCalledTimes(1);
    expect(capturedUrl?.value).toBe('/api/test?q=manual');
  });
});
