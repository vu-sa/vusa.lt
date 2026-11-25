import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import type { FacebookSDK, InstagramSDK } from '../embedUrl';
import { loadFacebookSdk, loadInstagramSdk } from '../socialEmbedSdk';

function cleanupSdkState(): void {
  delete window.FB;
  delete window.fbAsyncInit;
  delete window.instgrm;
  document.getElementById('facebook-jssdk')?.remove();
  document.getElementById('instagram-embed-js')?.remove();
}

describe('socialEmbedSdk', () => {
  beforeEach(() => {
    cleanupSdkState();
  });

  afterEach(() => {
    vi.useRealTimers();
    cleanupSdkState();
  });

  it('loads the Facebook SDK single-flight across concurrent callers', async () => {
    const first = loadFacebookSdk();
    const second = loadFacebookSdk();

    expect(first).toBe(second);
    expect(document.querySelectorAll('#facebook-jssdk')).toHaveLength(1);

    const sdk: FacebookSDK = { init: vi.fn(), XFBML: { parse: vi.fn() } };
    window.FB = sdk;
    window.fbAsyncInit?.();

    const [resultA, resultB] = await Promise.all([first, second]);
    expect(resultA).toBe(sdk);
    expect(resultB).toBe(sdk);
    expect(sdk.init).toHaveBeenCalledWith({ xfbml: true, version: 'v21.0' });
  });

  it('removes a failed Facebook script so a retry can inject a fresh one', async () => {
    const failed = loadFacebookSdk();
    const script = document.getElementById('facebook-jssdk') as HTMLScriptElement;
    script.onerror?.(new Event('error'));

    await expect(failed).rejects.toThrow('Failed to load Facebook SDK');
    expect(document.getElementById('facebook-jssdk')).toBeNull();

    const retry = loadFacebookSdk();
    expect(document.querySelectorAll('#facebook-jssdk')).toHaveLength(1);

    const sdk: FacebookSDK = { init: vi.fn(), XFBML: { parse: vi.fn() } };
    window.FB = sdk;
    window.fbAsyncInit?.();

    await expect(retry).resolves.toBe(sdk);
    expect(sdk.init).toHaveBeenCalledTimes(1);
  });

  it('removes a timed-out Facebook script so the retry makes a fresh request', async () => {
    vi.useFakeTimers();
    const timedOut = loadFacebookSdk();
    const assertion = expect(timedOut).rejects.toThrow('Facebook SDK load timeout');
    await vi.advanceTimersByTimeAsync(10000);
    await assertion;

    expect(document.getElementById('facebook-jssdk')).toBeNull();
    loadFacebookSdk().catch(() => {});
    expect(document.querySelectorAll('#facebook-jssdk')).toHaveLength(1);
  });

  it('removes an Instagram script that loads without exposing the SDK', async () => {
    vi.useFakeTimers();
    const failed = loadInstagramSdk();
    const assertion = expect(failed).rejects.toThrow('Instagram embed init timeout');
    (document.getElementById('instagram-embed-js') as HTMLScriptElement).onload?.(new Event('load'));
    await vi.advanceTimersByTimeAsync(5000);
    await assertion;

    expect(document.getElementById('instagram-embed-js')).toBeNull();
  });

  it('bounds a stalled Instagram download', async () => {
    vi.useFakeTimers();
    const stalled = loadInstagramSdk();
    const assertion = expect(stalled).rejects.toThrow('Instagram embed load timeout');
    await vi.advanceTimersByTimeAsync(10000);
    await assertion;

    expect(document.getElementById('instagram-embed-js')).toBeNull();
  });

  it('loads the Instagram SDK single-flight across concurrent callers', async () => {
    const first = loadInstagramSdk();
    const second = loadInstagramSdk();

    expect(first).toBe(second);
    expect(document.querySelectorAll('#instagram-embed-js')).toHaveLength(1);

    const sdk: InstagramSDK = { Embeds: { process: vi.fn() } };
    const script = document.getElementById('instagram-embed-js') as HTMLScriptElement;
    window.instgrm = sdk;
    script.onload?.(new Event('load'));

    const [resultA, resultB] = await Promise.all([first, second]);
    expect(resultA).toBe(sdk);
    expect(resultB).toBe(sdk);
  });

  it('returns an already-loaded SDK immediately without injecting scripts', async () => {
    const fbSdk: FacebookSDK = { init: vi.fn(), XFBML: { parse: vi.fn() } };
    const igSdk: InstagramSDK = { Embeds: { process: vi.fn() } };
    window.FB = fbSdk;
    window.instgrm = igSdk;

    await expect(loadFacebookSdk()).resolves.toBe(fbSdk);
    await expect(loadInstagramSdk()).resolves.toBe(igSdk);
    expect(document.getElementById('facebook-jssdk')).toBeNull();
    expect(document.getElementById('instagram-embed-js')).toBeNull();
  });
});
