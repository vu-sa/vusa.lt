import { beforeEach, describe, expect, it, vi } from 'vitest';

import { useShareLink } from '../useShareLink';

const success = vi.fn();
const error = vi.fn();

vi.mock('@/Composables/useToasts', () => ({
  useToasts: () => ({ success, error, info: vi.fn(), showToast: vi.fn(), initializeToasts: vi.fn() }),
}));

vi.mock('laravel-vue-i18n', () => ({ trans: (key: string) => key }));

/** jsdom ships neither `navigator.share` nor `navigator.clipboard`, so each test installs its own. */
function stubNavigator(props: Partial<Navigator>): void {
  for (const [key, value] of Object.entries(props)) {
    Object.defineProperty(navigator, key, { value, configurable: true, writable: true });
  }
}

function removeFromNavigator(...keys: string[]): void {
  for (const key of keys) {
    Object.defineProperty(navigator, key, { value: undefined, configurable: true, writable: true });
  }
}

const PAYLOAD = { title: 'Naujiena' };

describe('useShareLink', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    removeFromNavigator('share', 'clipboard');
  });

  it('uses the native share sheet when the browser has one', async () => {
    const share = vi.fn().mockResolvedValue(undefined);
    const writeText = vi.fn();
    stubNavigator({ share, clipboard: { writeText } as unknown as Clipboard });

    await useShareLink().share(PAYLOAD);

    expect(share).toHaveBeenCalledWith(expect.objectContaining({ title: 'Naujiena', url: window.location.href }));
    expect(writeText).not.toHaveBeenCalled();
    expect(success).not.toHaveBeenCalled();
  });

  /**
   * The bug this composable was extracted to fix. `navigator.share()` rejects with `AbortError`
   * when the user closes the sheet; the old inline version caught everything and fell through to
   * the clipboard, so cancelling silently did the thing that was cancelled.
   */
  it('does nothing when the user dismisses the share sheet', async () => {
    const share = vi.fn().mockRejectedValue(Object.assign(new Error('cancelled'), { name: 'AbortError' }));
    const writeText = vi.fn();
    stubNavigator({ share, clipboard: { writeText } as unknown as Clipboard });

    await useShareLink().share(PAYLOAD);

    expect(writeText).not.toHaveBeenCalled();
    expect(success).not.toHaveBeenCalled();
    expect(error).not.toHaveBeenCalled();
  });

  it('falls back to the clipboard when the share sheet genuinely fails', async () => {
    const share = vi.fn().mockRejectedValue(Object.assign(new Error('nope'), { name: 'DataError' }));
    const writeText = vi.fn().mockResolvedValue(undefined);
    stubNavigator({ share, clipboard: { writeText } as unknown as Clipboard });

    await useShareLink().share(PAYLOAD);

    expect(writeText).toHaveBeenCalledWith(window.location.href);
    expect(success).toHaveBeenCalledWith('common.link_copied');
  });

  it('copies the link and says so when there is no share sheet', async () => {
    const writeText = vi.fn().mockResolvedValue(undefined);
    stubNavigator({ clipboard: { writeText } as unknown as Clipboard });

    await useShareLink().share({ title: 'Naujiena', url: 'https://vusa.lt/lt/naujiena/x' });

    expect(writeText).toHaveBeenCalledWith('https://vusa.lt/lt/naujiena/x');
    expect(success).toHaveBeenCalledWith('common.link_copied');
  });

  /** `navigator.clipboard` is undefined on an insecure origin — the old code threw a TypeError. */
  it('reports an error instead of throwing when the clipboard is unavailable', async () => {
    await expect(useShareLink().share(PAYLOAD)).resolves.toBeUndefined();

    expect(error).toHaveBeenCalledWith('common.link_copy_failed');
  });

  it('reports an error when the clipboard write is rejected', async () => {
    const writeText = vi.fn().mockRejectedValue(new Error('denied'));
    stubNavigator({ clipboard: { writeText } as unknown as Clipboard });

    await useShareLink().share(PAYLOAD);

    expect(error).toHaveBeenCalledWith('common.link_copy_failed');
    expect(success).not.toHaveBeenCalled();
  });
});
