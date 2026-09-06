/**
 * Shared URL resolution for the Spotify/Mixcloud embeds (`RCSpotifyEmbed`, `RCMixcloudEmbed`,
 * `RCSpotifyPromoDisplay`) — one place to fix a host/theme-param bug instead of three.
 */

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
