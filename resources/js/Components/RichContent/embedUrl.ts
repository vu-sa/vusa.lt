/**
 * Shared URL resolution for the Spotify/Mixcloud/Facebook/Instagram embeds —
 * one place to fix host, lookalike-domain, and embed URL bugs instead of four.
 */

export type SocialPlatform = 'facebook' | 'instagram';

export interface FacebookSDK {
  init: (options: { xfbml: boolean; version: string }) => void;
  XFBML?: {
    parse: (element?: HTMLElement | null) => void;
  };
}

export interface InstagramSDK {
  Embeds?: {
    process: () => void;
  };
}

/**
 * Validates and identifies the platform of a social media URL (Facebook or Instagram).
 * Rejects lookalike domains, userinfo credentials, non-HTTP(S) protocols, and URLs without posts/paths.
 */
export function detectSocialPlatform(rawUrl: string | null | undefined): SocialPlatform | null {
  if (!rawUrl || typeof rawUrl !== 'string') return null;

  try {
    const trimmed = rawUrl.trim();
    if (!trimmed) return null;

    const url = new URL(trimmed);
    if (url.protocol !== 'http:' && url.protocol !== 'https:') {
      return null;
    }

    if (url.username || url.password) {
      return null;
    }

    const host = url.hostname.toLowerCase();

    // Facebook hosts: facebook.com, *.facebook.com, fb.watch, *.fb.watch
    const isFbHost = host === 'facebook.com' || host.endsWith('.facebook.com') || host === 'fb.watch' || host.endsWith('.fb.watch');
    if (isFbHost) {
      if (url.pathname.length > 1 || url.search.length > 1) {
        return 'facebook';
      }
      return null;
    }

    // Instagram hosts: instagram.com, *.instagram.com, instagr.am, *.instagr.am
    const isIgHost = host === 'instagram.com' || host.endsWith('.instagram.com') || host === 'instagr.am' || host.endsWith('.instagr.am');
    if (isIgHost) {
      if (/^\/(?:p|reel|reels|tv)\/[\w-]+/i.test(url.pathname)) {
        return 'instagram';
      }
      return null;
    }

    return null;
  }
  catch {
    return null;
  }
}

/** Cleans an Instagram post URL for embedding: strips query params and hash, ensures trailing slash. */
export function toInstagramEmbedUrl(rawUrl: string | null | undefined): string {
  if (!rawUrl) return '';
  try {
    const parsed = new URL(rawUrl.trim());
    const cleanPath = parsed.pathname.replace(/\/+$/, '');
    return `${parsed.origin}${cleanPath}/`;
  }
  catch {
    const cleanUrl = (rawUrl.split('?')[0] || '').split('#')[0] || '';
    return cleanUrl.endsWith('/') ? cleanUrl : `${cleanUrl}/`;
  }
}

export function isMixcloudUrl(url: string): boolean {
  try {
    const host = new URL(url, window.location.origin).hostname.toLowerCase();
    return host === 'mixcloud.com' || host === 'www.mixcloud.com' || host === 'player-widget.mixcloud.com';
  }
  catch {
    return false;
  }
}

/** A raw Mixcloud page/track URL, or an existing widget URL, to the `player-widget` iframe src. */
export function toMixcloudEmbedUrl(url: string, dark: boolean): string {
  const light = dark ? '0' : '1';

  try {
    const parsed = new URL(url);

    if (parsed.hostname === 'player-widget.mixcloud.com') {
      parsed.searchParams.set('light', light);
      return parsed.toString();
    }

    return `https://player-widget.mixcloud.com/widget/iframe/?hide_cover=1&light=${light}&feed=${encodeURIComponent(parsed.pathname)}`;
  }
  catch {
    return url;
  }
}

/** Appends Spotify's `theme` param (dark/light iframe chrome); passes any other host through untouched. */
export function toSpotifyEmbedUrl(url: string, dark: boolean): string {
  const isSpotify = /^https?:\/\/(open\.)?spotify\.com\//.test(url);
  if (!isSpotify) {
    return url;
  }

  const themeParam = dark ? '0' : '1';

  try {
    const parsed = new URL(url, window.location.origin);
    parsed.searchParams.set('theme', themeParam);
    return parsed.toString();
  }
  catch {
    return url.includes('?') ? `${url}&theme=${themeParam}` : `${url}?theme=${themeParam}`;
  }
}
