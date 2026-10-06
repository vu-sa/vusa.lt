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

import { usePermalinkPreview } from '@/Composables/usePermalinkPreview';

describe('usePermalinkPreview', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    vi.useFakeTimers();
    dataRef.value = null;
    capturedUrl = null;
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  it('does not query when title is shorter than 2 characters', async () => {
    const title = ref('');
    const lang = ref('lt');
    usePermalinkPreview('news', () => title.value, () => lang.value);

    title.value = 'a';
    await vi.advanceTimersByTimeAsync(600);
    expect(executeMock).not.toHaveBeenCalled();
    expect(capturedUrl?.value).toBe('');
  });

  it('queries news preview route for news type', async () => {
    const title = ref('');
    const lang = ref('lt');
    usePermalinkPreview('news', () => title.value, () => lang.value);

    title.value = 'Svarbi naujiena';
    await vi.advanceTimersByTimeAsync(600);
    expect(executeMock).toHaveBeenCalledTimes(1);
    expect(capturedUrl?.value).toContain('api.v1.admin.news.permalinkPreview');
    expect(capturedUrl?.value).toContain('title=Svarbi+naujiena');
    expect(capturedUrl?.value).toContain('lang=lt');
  });

  it('queries page preview route for page type', async () => {
    const title = ref('');
    const lang = ref('en');
    usePermalinkPreview('page', () => title.value, () => lang.value);

    title.value = 'Apie mus';
    await vi.advanceTimersByTimeAsync(600);
    expect(executeMock).toHaveBeenCalledTimes(1);
    expect(capturedUrl?.value).toContain('api.v1.admin.pages.permalinkPreview');
    expect(capturedUrl?.value).toContain('title=Apie+mus');
    expect(capturedUrl?.value).toContain('lang=en');
  });

  it('clears preview when title is cleared', async () => {
    const title = ref('');
    const lang = ref('lt');
    const { preview } = usePermalinkPreview('page', () => title.value, () => lang.value);

    title.value = 'Apie mus';
    await vi.advanceTimersByTimeAsync(600);
    dataRef.value = { permalink: 'apie-mus', url: 'https://vusa.lt/apie-mus' };
    expect(preview.value).toEqual({ permalink: 'apie-mus', url: 'https://vusa.lt/apie-mus' });

    title.value = '';
    await vi.advanceTimersByTimeAsync(600);
    expect(capturedUrl?.value).toBe('');
    expect(preview.value).toBeNull();
  });
});
