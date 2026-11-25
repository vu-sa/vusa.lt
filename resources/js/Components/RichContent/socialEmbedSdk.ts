import type { FacebookSDK, InstagramSDK } from './embedUrl';

const FACEBOOK_SCRIPT_ID = 'facebook-jssdk';
const FACEBOOK_SCRIPT_SRC = 'https://connect.facebook.net/en_US/sdk.js#xfbml=1&version=v21.0';
const INSTAGRAM_SCRIPT_ID = 'instagram-embed-js';
const INSTAGRAM_SCRIPT_SRC = 'https://www.instagram.com/embed.js';
const SDK_TIMEOUT_MS = 10000;
const INSTAGRAM_INIT_TIMEOUT_MS = 5000;

let facebookSdkPromise: Promise<FacebookSDK> | null = null;
let instagramSdkPromise: Promise<InstagramSDK> | null = null;

function pollForGlobal<T>(
  read: () => T | undefined,
  intervalMs: number,
  timeoutMs: number,
  timeoutMessage: string,
): Promise<T> {
  const existing = read();
  if (existing) {
    return Promise.resolve(existing);
  }

  return new Promise<T>((resolve, reject) => {
    const intervalId = setInterval(() => {
      const value = read();
      if (value) {
        clearInterval(intervalId);
        clearTimeout(timeoutId);
        resolve(value);
      }
    }, intervalMs);

    const timeoutId = setTimeout(() => {
      clearInterval(intervalId);
      const value = read();
      if (value) {
        resolve(value);
      }
      else {
        reject(new Error(timeoutMessage));
      }
    }, timeoutMs);
  });
}

/** Appends a script and settles once: any failure or timeout removes the tag so a later call retries. */
function injectScript<T>(
  id: string,
  src: string,
  attrs: Partial<HTMLScriptElement>,
  timeoutMessage: string,
  start: (script: HTMLScriptElement, resolve: (value: T) => void, reject: (error: Error) => void) => void,
): Promise<T> {
  return new Promise<T>((resolve, reject) => {
    const script = document.createElement('script');
    let settled = false;
    const timeoutId = setTimeout(() => fail(new Error(timeoutMessage)), SDK_TIMEOUT_MS);

    function settle(): boolean {
      if (settled) {
        return false;
      }
      settled = true;
      clearTimeout(timeoutId);
      return true;
    }

    function fail(error: Error): void {
      if (settle()) {
        script.remove();
        reject(error);
      }
    }

    Object.assign(script, attrs, { id, src, async: true });
    start(script, (value) => {
      if (settle()) {
        resolve(value);
      }
    }, fail);
    document.body.appendChild(script);
  });
}

/**
 * Single-flight loader for the Facebook XFBML SDK: concurrent embeds share one script tag
 * and promise, and a failed load removes the failed tag so a later retry can succeed.
 */
export function loadFacebookSdk(): Promise<FacebookSDK> {
  if (window.FB) {
    return Promise.resolve(window.FB);
  }
  if (facebookSdkPromise) {
    return facebookSdkPromise;
  }

  const promise = document.getElementById(FACEBOOK_SCRIPT_ID)
    ? pollForGlobal(() => window.FB, 100, SDK_TIMEOUT_MS, 'Facebook SDK load timeout')
    : injectScript<FacebookSDK>(
        FACEBOOK_SCRIPT_ID,
        FACEBOOK_SCRIPT_SRC,
        { defer: true, crossOrigin: 'anonymous' },
        'Facebook SDK load timeout',
        (script, resolve, reject) => {
          // Assigned, not chained: a failed attempt's hook must not run FB.init() a second time.
          window.fbAsyncInit = () => {
            const fb = window.FB;
            if (fb) {
              fb.init({ xfbml: true, version: 'v21.0' });
              resolve(fb);
            }
            else {
              reject(new Error('Facebook SDK failed to initialize'));
            }
          };
          script.onerror = () => reject(new Error('Failed to load Facebook SDK'));
        },
      );

  const tracked = promise.finally(() => {
    if (facebookSdkPromise === tracked) {
      facebookSdkPromise = null;
    }
  });
  facebookSdkPromise = tracked;

  return tracked;
}

/**
 * Single-flight loader for Instagram's embed script: concurrent embeds share one script tag
 * and promise, and a failed load removes the failed tag so a later retry can succeed.
 */
export function loadInstagramSdk(): Promise<InstagramSDK> {
  if (window.instgrm) {
    return Promise.resolve(window.instgrm);
  }
  if (instagramSdkPromise) {
    return instagramSdkPromise;
  }

  const promise = document.getElementById(INSTAGRAM_SCRIPT_ID)
    ? pollForGlobal(() => window.instgrm, 100, SDK_TIMEOUT_MS, 'Instagram embed load timeout')
    : injectScript<InstagramSDK>(
        INSTAGRAM_SCRIPT_ID,
        INSTAGRAM_SCRIPT_SRC,
        {},
        'Instagram embed load timeout',
        (script, resolve, reject) => {
          script.onload = () => {
            pollForGlobal(() => window.instgrm, 50, INSTAGRAM_INIT_TIMEOUT_MS, 'Instagram embed init timeout')
              .then(resolve, reject);
          };
          script.onerror = () => reject(new Error('Failed to load Instagram embed'));
        },
      );

  const tracked = promise.finally(() => {
    if (instagramSdkPromise === tracked) {
      instagramSdkPromise = null;
    }
  });
  instagramSdkPromise = tracked;

  return tracked;
}

declare global {
  interface Window {
    FB?: FacebookSDK;
    fbAsyncInit?: () => void;
    instgrm?: InstagramSDK;
  }
}
