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

const SPOTIFY_HOSTS = ['spotify.com', 'open.spotify.com'];
const MIXCLOUD_HOSTS = ['mixcloud.com', 'www.mixcloud.com', 'player-widget.mixcloud.com'];
const FACEBOOK_HOSTS = ['facebook.com', 'www.facebook.com', 'm.facebook.com', 'web.facebook.com', 'fb.watch', 'www.fb.watch'];
const INSTAGRAM_HOSTS = ['instagram.com', 'www.instagram.com', 'm.instagram.com', 'instagr.am', 'www.instagr.am'];

function providerUrl(rawUrl: string | null | undefined, hosts: string[]): URL | null {
  if (typeof rawUrl !== 'string' || /[\\\x00-\x1f\x7f]/.test(rawUrl)) return null;
  try {
    const url = new URL(rawUrl.trim());
    return url.protocol === 'https:' && !url.username && !url.password && !url.port && hosts.includes(url.hostname)
      ? url
      : null;
  }
  catch {
    return null;
  }
}

export function detectSocialPlatform(rawUrl: string | null | undefined): SocialPlatform | null {
  const facebook = providerUrl(rawUrl, FACEBOOK_HOSTS);
  if (facebook && (facebook.pathname.length > 1 || facebook.search.length > 1)) return 'facebook';

  const instagram = providerUrl(rawUrl, INSTAGRAM_HOSTS);
  return instagram && /^\/(?:p|reel|reels|tv)\/[\w-]+/i.test(instagram.pathname) ? 'instagram' : null;
}

export function toInstagramEmbedUrl(rawUrl: string | null | undefined): string {
  if (detectSocialPlatform(rawUrl) !== 'instagram') return '';
  const parsed = providerUrl(rawUrl, INSTAGRAM_HOSTS)!;
  return `${parsed.origin}${parsed.pathname.replace(/\/+$/, '')}/`;
}

export function isMixcloudUrl(url: string): boolean {
  return providerUrl(url, MIXCLOUD_HOSTS) !== null;
}

export function toMixcloudEmbedUrl(url: string, dark: boolean): string {
  const parsed = providerUrl(url, MIXCLOUD_HOSTS);
  if (!parsed) return '';

  const light = dark ? '0' : '1';
  if (parsed.hostname === 'player-widget.mixcloud.com') {
    parsed.searchParams.set('light', light);
    return parsed.toString();
  }

  return `https://player-widget.mixcloud.com/widget/iframe/?hide_cover=1&light=${light}&feed=${encodeURIComponent(parsed.pathname)}`;
}

export function toSpotifyEmbedUrl(url: string, dark: boolean): string {
  const parsed = providerUrl(url, SPOTIFY_HOSTS);
  if (!parsed) return '';
  parsed.searchParams.set('theme', dark ? '0' : '1');
  return parsed.toString();
}
