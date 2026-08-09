import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
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

import { useDuplicateDutyCheck } from '@/Composables/useDuplicateDutyCheck';

describe('useDuplicateDutyCheck', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    vi.useFakeTimers();
    dataRef.value = null;
    capturedUrl = null;
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  it('does not query for a name shorter than 3 characters', async () => {
    const name = ref('ab');
    useDuplicateDutyCheck(() => name.value, () => null);

    name.value = 'ab'; // no-op change to be explicit about intent; trigger below covers real change
    name.value = 'a';
    await vi.advanceTimersByTimeAsync(600);

    expect(executeMock).not.toHaveBeenCalled();
  });

  it('builds the request url from the name and institution once debounced', async () => {
    const name = ref('');
    const institutionId = ref<string | null>(null);
    useDuplicateDutyCheck(() => name.value, () => institutionId.value);

    name.value = 'Koordinatorius';
    institutionId.value = 'inst-1';
    await vi.advanceTimersByTimeAsync(600);

    expect(executeMock).toHaveBeenCalledTimes(1);
    expect(capturedUrl?.value).toContain('name=Koordinatorius');
    expect(capturedUrl?.value).toContain('institution_id=inst-1');
  });

  it('omits institution_id from the query when none is given', async () => {
    const name = ref('');
    useDuplicateDutyCheck(() => name.value, () => null);

    name.value = 'Pirmininkas';
    await vi.advanceTimersByTimeAsync(600);

    expect(capturedUrl?.value).toContain('name=Pirmininkas');
    expect(capturedUrl?.value).not.toContain('institution_id');
  });

  it('includes exclude_id so an edited duty does not match itself', async () => {
    const name = ref('');
    useDuplicateDutyCheck(() => name.value, () => 'inst-1', () => 'duty-9');

    name.value = 'Pirmininkas';
    await vi.advanceTimersByTimeAsync(600);

    expect(capturedUrl?.value).toContain('exclude_id=duty-9');
  });

  it('debounces rapid typing into a single request', async () => {
    const name = ref('');
    useDuplicateDutyCheck(() => name.value, () => null);

    for (const partial of ['K', 'Ko', 'Koo', 'Koor', 'Koord']) {
      name.value = partial;
      await vi.advanceTimersByTimeAsync(100);
    }
    await vi.advanceTimersByTimeAsync(500);

    expect(executeMock).toHaveBeenCalledTimes(1);
    expect(capturedUrl?.value).toContain('name=Koord');
  });

  it('clears the url (and therefore the last result) when the name is emptied', async () => {
    const name = ref('');
    const { matches } = useDuplicateDutyCheck(() => name.value, () => null);

    name.value = 'Koordinatorius';
    await vi.advanceTimersByTimeAsync(600);
    expect(capturedUrl?.value).not.toBe('');

    dataRef.value = { same_institution: [{ id: 'x' }], other_institution: [], other_institution_count: 0 };
    expect(matches.value.same_institution).toHaveLength(1);

    name.value = '';
    await vi.advanceTimersByTimeAsync(600);

    expect(capturedUrl?.value).toBe('');
    expect(matches.value).toEqual({ same_institution: [], other_institution: [], other_institution_count: 0 });
  });
});
