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

import { useDuplicateUserCheck } from '@/Composables/useDuplicateUserCheck';

describe('useDuplicateUserCheck', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    vi.useFakeTimers();
    dataRef.value = null;
    capturedUrl = null;
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  it('does not query when name has fewer than 2 parts and email is under 3 chars', async () => {
    const name = ref('');
    const email = ref('');
    useDuplicateUserCheck(() => name.value, () => email.value);

    name.value = 'Single';
    email.value = 'a@';
    await vi.advanceTimersByTimeAsync(600);

    expect(executeMock).not.toHaveBeenCalled();
    expect(capturedUrl?.value).toBe('');
  });

  it('queries when email has at least 3 chars even if name is incomplete', async () => {
    const name = ref('');
    const email = ref('');
    useDuplicateUserCheck(() => name.value, () => email.value);

    name.value = 'Jonas';
    email.value = 'jonas@example.com';
    await vi.advanceTimersByTimeAsync(600);

    expect(executeMock).toHaveBeenCalledTimes(1);
    expect(capturedUrl?.value).toContain('email=jonas%40example.com');
  });

  it('queries when full name has at least 2 parts with 3+ chars each', async () => {
    const name = ref('');
    const email = ref('');
    useDuplicateUserCheck(() => name.value, () => email.value);

    name.value = 'Vardenis Pavardenis';
    await vi.advanceTimersByTimeAsync(600);

    expect(executeMock).toHaveBeenCalledTimes(1);
    expect(capturedUrl?.value).toContain('name=Vardenis+Pavardenis');
  });

  it('clears url when input is reset', async () => {
    const name = ref('');
    const email = ref('');
    const { matches } = useDuplicateUserCheck(() => name.value, () => email.value);

    name.value = 'Vardenis Pavardenis';
    await vi.advanceTimersByTimeAsync(600);
    expect(capturedUrl?.value).not.toBe('');

    dataRef.value = [{ id: 1, name: 'Vardenis Pavardenis' }];
    expect(matches.value).toHaveLength(1);

    name.value = '';
    await vi.advanceTimersByTimeAsync(600);
    expect(capturedUrl?.value).toBe('');
    expect(matches.value).toEqual([]);
  });
});
