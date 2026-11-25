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
    : new Promise<FacebookSDK>((resolve, reject) => {
        let settled = false;
        const finish = (cb: () => void) => {
          if (settled) {
            return;
          }
          settled = true;
          clearTimeout(timeoutId);
          cb();
        };

        const timeoutId = setTimeout(() => {
          finish(() => reject(new Error('Facebook SDK load timeout')));
        }, SDK_TIMEOUT_MS);

        const previousInit = window.fbAsyncInit;
        window.fbAsyncInit = () => {
          previousInit?.();
          const fb = window.FB;
          if (fb) {
            fb.init({
              xfbml: true,
              version: 'v21.0',
            });
            finish(() => resolve(fb));
          }
          else {
            finish(() => reject(new Error('Facebook SDK failed to initialize')));
          }
        };

        const script = document.createElement('script');
        script.id = FACEBOOK_SCRIPT_ID;
        script.src = FACEBOOK_SCRIPT_SRC;
        script.async = true;
        script.defer = true;
        script.crossOrigin = 'anonymous';
        script.onerror = () => {
          script.remove();
          finish(() => reject(new Error('Failed to load Facebook SDK')));
        };
        document.body.appendChild(script);
      });

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
    : new Promise<InstagramSDK>((resolve, reject) => {
        const script = document.createElement('script');
        script.id = INSTAGRAM_SCRIPT_ID;
        script.src = INSTAGRAM_SCRIPT_SRC;
        script.async = true;
        script.onload = () => {
          pollForGlobal(() => window.instgrm, 50, INSTAGRAM_INIT_TIMEOUT_MS, 'Instagram embed init timeout')
            .then(resolve, reject);
        };
        script.onerror = () => {
          script.remove();
          reject(new Error('Failed to load Instagram embed'));
        };
        document.body.appendChild(script);
      });

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
